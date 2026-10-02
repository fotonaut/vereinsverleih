<?php

namespace Tests\Feature\Verleih;

use App\Enums\LendingScope;
use App\Enums\LoanStatus;
use App\Models\Club;
use App\Models\Item;
use App\Models\LoanRequest;
use App\Models\User;
use App\Notifications\LoanRequestReceived;
use App\Notifications\LoanRequestVerify;
use App\Notifications\LoanSeriesDecided;
use App\Support\LoanSeries;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SeriesTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $o = []): array
    {
        return array_merge([
            'requester_name' => 'Serien Susi',
            'requester_email' => 'susi@example.com',
            'quantity' => 1,
            'start_date' => '2030-03-02',
            'end_date' => '2030-03-03',
            'repeat' => 'weekly',
            'repeat_count' => 4,
        ], $o);
    }

    public function test_occurrence_calculation(): void
    {
        $weekly = LoanSeries::occurrences('2030-03-02', '2030-03-03', 'weekly', 3);
        $this->assertSame(['2030-03-02', '2030-03-09', '2030-03-16'], array_column($weekly, 'start'));
        $this->assertSame(['2030-03-03', '2030-03-10', '2030-03-17'], array_column($weekly, 'end'));

        $biweekly = LoanSeries::occurrences('2030-03-02', '2030-03-02', 'biweekly', 3);
        $this->assertSame(['2030-03-02', '2030-03-16', '2030-03-30'], array_column($biweekly, 'start'));

        // Monatsende: 31.1. -> 28.2. (kein Überlauf in den März)
        $monthly = LoanSeries::occurrences('2030-01-31', '2030-01-31', 'monthly', 3);
        $this->assertSame(['2030-01-31', '2030-02-28', '2030-03-31'], array_column($monthly, 'start'));
    }

    public function test_guest_series_creates_all_occurrences_and_one_verification(): void
    {
        Notification::fake();
        $item = Item::factory()->create(['lending_scope' => LendingScope::Private, 'quantity' => 1]);

        $this->post("/katalog/{$item->id}/anfragen", $this->payload())->assertSessionHasNoErrors();

        $loans = LoanRequest::orderBy('start_date')->get();
        $this->assertCount(4, $loans);
        $this->assertCount(1, $loans->pluck('series_id')->unique());
        $this->assertSame(['2030-03-02', '2030-03-09', '2030-03-16', '2030-03-23'], $loans->map(fn ($l) => $l->start_date->toDateString())->all());
        $this->assertTrue($loans->every(fn ($l) => $l->status === LoanStatus::Unverified));
        Notification::assertSentOnDemandTimes(LoanRequestVerify::class, 1);

        // Bestätigung gilt für die ganze Serie, der Verein erfährt es einmal
        $this->get("/anfragen/{$loans->first()->token}/bestaetigen");
        $this->assertSame(4, LoanRequest::where('status', 'pending')->count());
        Notification::assertSentOnDemandTimes(LoanRequestReceived::class, 1);
    }

    public function test_all_or_nothing_when_one_date_is_taken(): void
    {
        $item = Item::factory()->create(['lending_scope' => LendingScope::Both, 'quantity' => 1]);
        LoanRequest::factory()->status(LoanStatus::Approved)->create([
            'item_id' => $item->id, 'start_date' => '2030-03-16', 'end_date' => '2030-03-17',
        ]);

        $this->post("/katalog/{$item->id}/anfragen", $this->payload())->assertSessionHasErrors('repeat');
        $this->assertStringContainsString('16.03.2030', session('errors')->first('repeat'));
        $this->assertStringNotContainsString('09.03.2030', session('errors')->first('repeat'));
        $this->assertSame(1, LoanRequest::count(), 'es darf kein Teil der Serie gebucht werden');
    }

    public function test_overlapping_interval_and_limits_are_rejected(): void
    {
        $item = Item::factory()->create(['lending_scope' => LendingScope::Both]);

        $this->post("/katalog/{$item->id}/anfragen", $this->payload(['start_date' => '2030-03-02', 'end_date' => '2030-03-12']))
            ->assertSessionHasErrors('repeat');
        $this->post("/katalog/{$item->id}/anfragen", $this->payload(['repeat_count' => 13]))->assertSessionHasErrors('repeat_count');
        $this->post("/katalog/{$item->id}/anfragen", $this->payload(['repeat_count' => 1]))->assertSessionHasErrors('repeat_count');
        $this->post("/katalog/{$item->id}/anfragen", $this->payload(['repeat' => 'daily']))->assertSessionHasErrors('repeat');
        $this->assertSame(0, LoanRequest::count());
    }

    public function test_club_decides_whole_series_with_one_mail(): void
    {
        Notification::fake();
        $club = Club::factory()->create();
        $owner = User::factory()->clubAdmin($club)->create();
        $item = Item::factory()->create(['club_id' => $club->id, 'lending_scope' => LendingScope::Both, 'quantity' => 1]);
        $this->post("/katalog/{$item->id}/anfragen", $this->payload());
        LoanRequest::query()->update(['status' => 'pending']);
        $seriesId = LoanRequest::value('series_id');

        $this->actingAs($owner)->patch("/verwaltung/eingang/serie/{$seriesId}", ['decision' => 'approved', 'decision_note' => 'Passt'])
            ->assertSessionHasNoErrors();

        $this->assertSame(4, LoanRequest::where('status', 'approved')->count());
        Notification::assertSentOnDemandTimes(LoanSeriesDecided::class, 1);
    }

    public function test_series_approval_is_blocked_if_stock_got_taken_meanwhile(): void
    {
        Notification::fake();
        $club = Club::factory()->create();
        $owner = User::factory()->clubAdmin($club)->create();
        $item = Item::factory()->create(['club_id' => $club->id, 'lending_scope' => LendingScope::Both, 'quantity' => 1]);
        $this->post("/katalog/{$item->id}/anfragen", $this->payload());
        LoanRequest::query()->update(['status' => 'pending']);
        $seriesId = LoanRequest::value('series_id');
        LoanRequest::factory()->status(LoanStatus::Approved)->create([
            'item_id' => $item->id, 'start_date' => '2030-03-09', 'end_date' => '2030-03-09',
        ]);

        $this->actingAs($owner)->patch("/verwaltung/eingang/serie/{$seriesId}", ['decision' => 'approved'])->assertSessionHasErrors('status');
        $this->assertSame(0, LoanRequest::where('series_id', $seriesId)->where('status', 'approved')->count());
    }

    public function test_strangers_cannot_decide_a_series(): void
    {
        $item = Item::factory()->create(['lending_scope' => LendingScope::Both]);
        $this->post("/katalog/{$item->id}/anfragen", $this->payload());
        $seriesId = LoanRequest::value('series_id');
        $stranger = User::factory()->clubAdmin()->create();

        $this->actingAs($stranger)->patch("/verwaltung/eingang/serie/{$seriesId}", ['decision' => 'declined'])->assertForbidden();
    }

    public function test_cancel_whole_series_keeps_picked_up_terms(): void
    {
        Notification::fake();
        $item = Item::factory()->create(['lending_scope' => LendingScope::Both]);
        $this->post("/katalog/{$item->id}/anfragen", $this->payload());
        $first = LoanRequest::orderBy('start_date')->first();
        $first->update(['status' => 'picked_up']);
        LoanRequest::where('id', '!=', $first->id)->update(['status' => 'approved']);

        $this->post("/anfragen/{$first->token}/serie-stornieren");

        $this->assertSame('picked_up', $first->fresh()->status->value);
        $this->assertSame(3, LoanRequest::where('status', 'cancelled')->count());
    }

    public function test_status_page_lists_the_series(): void
    {
        $item = Item::factory()->create(['lending_scope' => LendingScope::Both]);
        $this->post("/katalog/{$item->id}/anfragen", $this->payload());
        $loan = LoanRequest::orderBy('start_date')->first();

        $this->get("/anfragen/{$loan->token}")->assertOk()->assertInertia(fn ($page) => $page
            ->has('loan.series', 4)->where('loan.series.0.current', true)->where('loan.canCancelSeries', true));
    }
}
