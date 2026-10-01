<?php

namespace Tests\Feature\Verleih;

use App\Enums\LoanStatus;
use App\Models\Club;
use App\Models\Item;
use App\Models\LoanRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExportTest extends TestCase
{
    use RefreshDatabase;

    private function csv($response): array
    {
        $body = $response->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $body, 'BOM fehlt');

        return array_map(fn ($l) => str_getcsv($l, ';'), array_filter(explode("\n", substr($body, 3))));
    }

    public function test_requires_login_and_a_club(): void
    {
        $this->get('/verwaltung/export/ausleihen.csv')->assertRedirect('/login');
        $this->actingAs(User::factory()->create())->get('/verwaltung/export/ausleihen.csv')->assertForbidden();
    }

    public function test_exports_only_own_incoming_loans_with_excel_friendly_format(): void
    {
        $club = Club::factory()->create(['name' => 'Müller & Söhne e.V.']);
        $user = User::factory()->clubAdmin($club)->create();
        $mine = Item::factory()->create(['club_id' => $club->id, 'name' => 'Bierzelt Ä']);
        $foreign = Item::factory()->create();
        LoanRequest::factory()->status(LoanStatus::Approved)->create(['item_id' => $mine->id, 'requester_name' => 'Anna Beispiel']);
        LoanRequest::factory()->status(LoanStatus::Unverified)->create(['item_id' => $mine->id, 'requester_name' => 'Unbestätigt']);
        LoanRequest::factory()->create(['item_id' => $foreign->id, 'requester_name' => 'Fremd']);

        $response = $this->actingAs($user)->get('/verwaltung/export/ausleihen.csv');
        $response->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $rows = $this->csv($response);

        $this->assertSame('ID', $rows[0][0]);
        $this->assertCount(2, $rows, 'Kopfzeile + genau eine Zeile');
        $this->assertSame('Bierzelt Ä', $rows[1][1]);
        $this->assertSame('Anna Beispiel', $rows[1][3]);
        $this->assertSame('Genehmigt', $rows[1][10]);
    }

    public function test_outgoing_direction_and_filters(): void
    {
        $club = Club::factory()->create();
        $user = User::factory()->clubAdmin($club)->create();
        $item = Item::factory()->create();
        LoanRequest::factory()->status(LoanStatus::Returned)->create([
            'item_id' => $item->id, 'requester_club_id' => $club->id, 'requester_type' => 'club',
            'start_date' => '2026-03-01', 'end_date' => '2026-03-03',
        ]);
        LoanRequest::factory()->status(LoanStatus::Approved)->create([
            'item_id' => $item->id, 'requester_club_id' => $club->id, 'requester_type' => 'club',
            'start_date' => '2026-08-01', 'end_date' => '2026-08-03',
        ]);

        $all = $this->csv($this->actingAs($user)->get('/verwaltung/export/ausleihen.csv?direction=outgoing'));
        $this->assertCount(3, $all);

        $byStatus = $this->csv($this->actingAs($user)->get('/verwaltung/export/ausleihen.csv?direction=outgoing&status=returned'));
        $this->assertCount(2, $byStatus);

        $byDate = $this->csv($this->actingAs($user)->get('/verwaltung/export/ausleihen.csv?direction=outgoing&from=2026-07-01&to=2026-12-31'));
        $this->assertCount(2, $byDate);
        $this->assertSame('01.08.2026', $byDate[1][8]);
    }

    public function test_formula_injection_is_neutralised_and_input_validated(): void
    {
        $club = Club::factory()->create();
        $user = User::factory()->clubAdmin($club)->create();
        $item = Item::factory()->create(['club_id' => $club->id]);
        LoanRequest::factory()->create(['item_id' => $item->id, 'requester_name' => '=HYPERLINK("http://evil")', 'message' => '@SUM(1)']);

        $rows = $this->csv($this->actingAs($user)->get('/verwaltung/export/ausleihen.csv'));
        $this->assertSame('\'=HYPERLINK("http://evil")', $rows[1][3]);
        $this->assertSame("'@SUM(1)", $rows[1][11]);

        $this->actingAs($user)->get('/verwaltung/export/ausleihen.csv?status=bogus')->assertSessionHasErrors('status');
        $this->actingAs($user)->get('/verwaltung/export/ausleihen.csv?from=2026-05-01&to=2026-04-01')->assertSessionHasErrors('to');
    }
}
