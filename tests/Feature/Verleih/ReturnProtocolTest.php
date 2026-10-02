<?php

namespace Tests\Feature\Verleih;

use App\Enums\LoanStatus;
use App\Enums\ReturnCondition;
use App\Models\Club;
use App\Models\Item;
use App\Models\LoanRequest;
use App\Models\User;
use App\Notifications\LoanReturned;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ReturnProtocolTest extends TestCase
{
    use RefreshDatabase;

    private function pickedUp(array $itemAttrs = []): array
    {
        $club = Club::factory()->create();
        $owner = User::factory()->clubAdmin($club)->create();
        $item = Item::factory()->create(['club_id' => $club->id] + $itemAttrs);
        $loan = LoanRequest::factory()->status(LoanStatus::PickedUp)->create(['item_id' => $item->id, 'start_date' => today()->subDays(2), 'end_date' => today()->addDay()]);

        return [$owner, $item, $loan];
    }

    public function test_return_stores_protocol_and_notifies_the_borrower(): void
    {
        Notification::fake();
        [$owner, , $loan] = $this->pickedUp(['deposit_cents' => 5000]);

        $this->actingAs($owner)->patch("/verwaltung/eingang/{$loan->id}", [
            'status' => 'returned', 'return_condition' => 'damaged', 'return_note' => 'Riss an der Seite', 'deposit_returned' => false,
        ])->assertSessionHasNoErrors();

        $loan->refresh();
        $this->assertSame(LoanStatus::Returned, $loan->status);
        $this->assertSame(ReturnCondition::Damaged, $loan->return_condition);
        $this->assertSame('Riss an der Seite', $loan->return_note);
        $this->assertFalse($loan->deposit_returned);
        $this->assertNotNull($loan->returned_at);
        Notification::assertSentOnDemandTimes(LoanReturned::class, 1);
    }

    public function test_return_without_details_defaults_to_ok(): void
    {
        Notification::fake();
        [$owner, , $loan] = $this->pickedUp();

        $this->actingAs($owner)->patch("/verwaltung/eingang/{$loan->id}", ['status' => 'returned'])->assertSessionHasNoErrors();

        $this->assertSame(ReturnCondition::Ok, $loan->fresh()->return_condition);
    }

    public function test_invalid_condition_is_rejected_and_nothing_changes(): void
    {
        [$owner, , $loan] = $this->pickedUp();

        $this->actingAs($owner)->patch("/verwaltung/eingang/{$loan->id}", ['status' => 'returned', 'return_condition' => 'kaputt'])
            ->assertSessionHasErrors('return_condition');
        $this->assertSame(LoanStatus::PickedUp, $loan->fresh()->status);
    }

    public function test_mail_mentions_condition_note_and_deposit(): void
    {
        [, $item, $loan] = $this->pickedUp(['deposit_cents' => 2500]);
        $loan->update(['status' => 'returned', 'return_condition' => 'worn', 'return_note' => 'Leicht verschmutzt', 'deposit_returned' => true, 'returned_at' => now()]);

        $mail = (new LoanReturned($loan->load('item.club')))->toMail(new \stdClass);
        $text = implode("\n", $mail->introLines);

        $this->assertStringContainsString('Gebrauchsspuren', $text);
        $this->assertStringContainsString('Leicht verschmutzt', $text);
        $this->assertStringContainsString('Kaution (25,00 €) wurde zurückgegeben', $text);
    }

    public function test_status_page_shows_the_protocol_to_the_borrower(): void
    {
        [, , $loan] = $this->pickedUp();
        $loan->update(['status' => 'returned', 'return_condition' => 'incomplete', 'return_note' => 'Ein Hering fehlt', 'returned_at' => now()]);

        $this->get("/anfragen/{$loan->token}")->assertInertia(fn (Assert $page) => $page
            ->where('loan.return.condition', 'Unvollständig')->where('loan.return.note', 'Ein Hering fehlt'));
    }

    public function test_item_list_flags_items_whose_last_return_needs_attention(): void
    {
        [$owner, $item, $loan] = $this->pickedUp();
        $loan->update(['status' => 'returned', 'return_condition' => 'damaged', 'returned_at' => now()->subDay()]);
        $fine = Item::factory()->create(['club_id' => $owner->club_id, 'name' => 'Ganz']);

        // spätere, einwandfreie Rückgabe hebt die Markierung wieder auf
        $later = LoanRequest::factory()->status(LoanStatus::Returned)->create([
            'item_id' => $item->id, 'return_condition' => 'ok', 'returned_at' => now(),
        ]);

        $this->actingAs($owner)->get('/verwaltung/gegenstaende')->assertInertia(fn (Assert $page) => $page
            ->where('items.0.needs_attention', false));

        $later->update(['returned_at' => now()->subDays(5)]);
        $this->actingAs($owner)->get('/verwaltung/gegenstaende')->assertInertia(fn (Assert $page) => $page
            ->where('items', fn ($items) => collect($items)->firstWhere('id', $item->id)['needs_attention'] === true
                && collect($items)->firstWhere('id', $fine->id)['needs_attention'] === false));
    }

    public function test_calendar_page_lists_recent_returns_and_csv_has_the_new_columns(): void
    {
        [$owner, $item, $loan] = $this->pickedUp(['deposit_cents' => 1000]);
        $loan->update(['status' => 'returned', 'return_condition' => 'damaged', 'return_note' => 'Delle', 'deposit_returned' => true, 'returned_at' => now()]);

        $this->actingAs($owner)->get("/verwaltung/gegenstaende/{$item->id}/kalender")->assertInertia(fn (Assert $page) => $page
            ->has('returns', 1)->where('returns.0.attention', true)->where('returns.0.condition_label', 'Beschädigt'));

        $csv = $this->actingAs($owner)->get('/verwaltung/export/ausleihen.csv')->streamedContent();
        $this->assertStringContainsString('Zustand bei Rückgabe', $csv);
        $this->assertStringContainsString('Beschädigt;Delle;Ja', $csv);
    }
}
