<?php

namespace Tests\Feature\Verleih;

use App\Enums\LoanStatus;
use App\Models\Club;
use App\Models\Item;
use App\Models\LoanExtension;
use App\Models\LoanRequest;
use App\Models\User;
use App\Notifications\ExtensionDecided;
use App\Notifications\ExtensionRequested;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ExtensionTest extends TestCase
{
    use RefreshDatabase;

    private function loan(array $itemAttrs = [], LoanStatus $status = LoanStatus::PickedUp, int $endOffset = 2): LoanRequest
    {
        $item = Item::factory()->create(['quantity' => 1] + $itemAttrs);

        return LoanRequest::factory()->status($status)->create([
            'item_id' => $item->id,
            'start_date' => today()->subDays(1)->toDateString(),
            'end_date' => today()->addDays($endOffset)->toDateString(),
        ]);
    }

    public function test_borrower_can_request_an_extension_and_the_club_is_notified(): void
    {
        Notification::fake();
        $loan = $this->loan();
        $new = today()->addDays(7)->toDateString();

        $this->post("/anfragen/{$loan->token}/verlaengern", ['requested_end_date' => $new, 'message' => 'Fest verschoben'])
            ->assertSessionHasNoErrors();

        $ext = $loan->extensions()->firstOrFail();
        $this->assertSame('pending', $ext->status);
        $this->assertSame($loan->end_date->toDateString(), $ext->previous_end_date->toDateString());
        Notification::assertSentOnDemand(ExtensionRequested::class);
    }

    public function test_extension_must_be_later_and_only_one_may_be_open(): void
    {
        Notification::fake();
        $loan = $this->loan();

        $this->post("/anfragen/{$loan->token}/verlaengern", ['requested_end_date' => $loan->end_date->toDateString()])
            ->assertSessionHasErrors('requested_end_date');

        $ok = today()->addDays(6)->toDateString();
        $this->post("/anfragen/{$loan->token}/verlaengern", ['requested_end_date' => $ok]);
        $this->post("/anfragen/{$loan->token}/verlaengern", ['requested_end_date' => today()->addDays(9)->toDateString()])
            ->assertSessionHasErrors('requested_end_date');
        $this->assertSame(1, $loan->extensions()->count());
    }

    public function test_not_possible_for_pending_or_returned_loans(): void
    {
        foreach ([LoanStatus::Pending, LoanStatus::Returned, LoanStatus::Declined] as $status) {
            $loan = $this->loan([], $status);
            $this->post("/anfragen/{$loan->token}/verlaengern", ['requested_end_date' => today()->addDays(8)->toDateString()])
                ->assertSessionHasErrors('requested_end_date');
            $this->assertSame(0, $loan->extensions()->count());
        }
    }

    public function test_blocked_when_someone_else_has_the_item_in_the_extended_period(): void
    {
        $loan = $this->loan();
        LoanRequest::factory()->status(LoanStatus::Approved)->create([
            'item_id' => $loan->item_id,
            'start_date' => today()->addDays(4)->toDateString(),
            'end_date' => today()->addDays(6)->toDateString(),
        ]);

        $this->post("/anfragen/{$loan->token}/verlaengern", ['requested_end_date' => today()->addDays(5)->toDateString()])
            ->assertSessionHasErrors('requested_end_date');
    }

    public function test_club_approval_moves_end_date_and_resets_reminders(): void
    {
        Notification::fake();
        $club = Club::factory()->create();
        $owner = User::factory()->clubAdmin($club)->create();
        $loan = $this->loan(['club_id' => $club->id], endOffset: -1);
        $loan->update(['overdue_count' => 2, 'overdue_notified_at' => now(), 'reminded_at' => now()]);
        $new = today()->addDays(5)->toDateString();
        $ext = LoanExtension::create([
            'loan_request_id' => $loan->id, 'previous_end_date' => $loan->end_date, 'requested_end_date' => $new,
        ]);

        $this->actingAs($owner)
            ->patch("/verwaltung/eingang/{$loan->id}/verlaengerung/{$ext->id}", ['decision' => 'approved', 'decision_note' => 'Alles gut'])
            ->assertSessionHasNoErrors();

        $loan->refresh();
        $this->assertSame($new, $loan->end_date->toDateString());
        $this->assertSame(0, $loan->overdue_count);
        $this->assertNull($loan->reminded_at);
        $this->assertSame('approved', $ext->fresh()->status);
        Notification::assertSentOnDemand(ExtensionDecided::class);
    }

    public function test_decline_keeps_the_end_date(): void
    {
        Notification::fake();
        $club = Club::factory()->create();
        $owner = User::factory()->clubAdmin($club)->create();
        $loan = $this->loan(['club_id' => $club->id]);
        $old = $loan->end_date->toDateString();
        $ext = LoanExtension::create([
            'loan_request_id' => $loan->id, 'previous_end_date' => $loan->end_date, 'requested_end_date' => today()->addDays(9)->toDateString(),
        ]);

        $this->actingAs($owner)->patch("/verwaltung/eingang/{$loan->id}/verlaengerung/{$ext->id}", ['decision' => 'declined']);

        $this->assertSame($old, $loan->fresh()->end_date->toDateString());
        $this->assertSame('declined', $ext->fresh()->status);
    }

    public function test_strangers_cannot_decide_and_approval_rechecks_stock(): void
    {
        Notification::fake();
        $club = Club::factory()->create();
        $owner = User::factory()->clubAdmin($club)->create();
        $stranger = User::factory()->clubAdmin()->create();
        $loan = $this->loan(['club_id' => $club->id]);
        $ext = LoanExtension::create([
            'loan_request_id' => $loan->id, 'previous_end_date' => $loan->end_date, 'requested_end_date' => today()->addDays(6)->toDateString(),
        ]);
        $url = "/verwaltung/eingang/{$loan->id}/verlaengerung/{$ext->id}";

        $this->actingAs($stranger)->patch($url, ['decision' => 'approved'])->assertForbidden();

        // zwischenzeitlich vergeben
        LoanRequest::factory()->status(LoanStatus::Approved)->create([
            'item_id' => $loan->item_id,
            'start_date' => today()->addDays(4)->toDateString(),
            'end_date' => today()->addDays(5)->toDateString(),
        ]);
        $this->actingAs($owner)->patch($url, ['decision' => 'approved'])->assertSessionHasErrors('status');
        $this->assertSame('pending', $ext->fresh()->status);
    }
}
