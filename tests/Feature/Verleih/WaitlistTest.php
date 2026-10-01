<?php

namespace Tests\Feature\Verleih;

use App\Enums\LendingScope;
use App\Enums\LoanStatus;
use App\Models\Club;
use App\Models\Item;
use App\Models\LoanRequest;
use App\Models\User;
use App\Models\WaitlistEntry;
use App\Notifications\WaitlistAvailable;
use App\Notifications\WaitlistVerify;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class WaitlistTest extends TestCase
{
    use RefreshDatabase;

    private function bookedItem(array $attrs = []): array
    {
        $club = Club::factory()->create();
        $owner = User::factory()->clubAdmin($club)->create();
        $item = Item::factory()->create(['club_id' => $club->id, 'quantity' => 1, 'lending_scope' => LendingScope::Both] + $attrs);
        $loan = LoanRequest::factory()->status(LoanStatus::Approved)->create([
            'item_id' => $item->id,
            'start_date' => today()->addDays(5)->toDateString(),
            'end_date' => today()->addDays(8)->toDateString(),
        ]);

        return [$item, $owner, $loan];
    }

    private function payload(array $o = []): array
    {
        return array_merge([
            'requester_name' => 'Wanda Warte',
            'requester_email' => 'wanda@example.com',
            'quantity' => 1,
            'start_date' => today()->addDays(6)->toDateString(),
            'end_date' => today()->addDays(7)->toDateString(),
        ], $o);
    }

    public function test_guest_joins_with_email_verification(): void
    {
        Notification::fake();
        [$item] = $this->bookedItem();

        $this->post("/katalog/{$item->id}/warteliste", $this->payload())->assertRedirect();

        $entry = WaitlistEntry::firstOrFail();
        $this->assertSame('unverified', $entry->status);
        Notification::assertSentOnDemand(WaitlistVerify::class);

        $this->get("/warteliste/{$entry->token}/bestaetigen")->assertRedirect();
        $this->assertSame('waiting', $entry->fresh()->status);
    }

    public function test_cannot_join_when_the_item_is_free(): void
    {
        [$item] = $this->bookedItem();

        $this->post("/katalog/{$item->id}/warteliste", $this->payload([
            'start_date' => today()->addDays(20)->toDateString(), 'end_date' => today()->addDays(21)->toDateString(),
        ]))->assertSessionHasErrors('start_date');
        $this->assertSame(0, WaitlistEntry::count());
    }

    public function test_scope_own_item_and_duplicates_are_rejected(): void
    {
        Notification::fake();
        [$item, $owner] = $this->bookedItem(['lending_scope' => LendingScope::Private]);

        $this->post("/katalog/{$item->id}/warteliste", $this->payload())->assertSessionHasNoErrors();
        $this->post("/katalog/{$item->id}/warteliste", $this->payload())->assertSessionHasErrors('start_date');
        $this->assertSame(1, WaitlistEntry::count());

        $clubOnly = Item::factory()->create(['lending_scope' => LendingScope::Clubs, 'quantity' => 1]);
        $this->post("/katalog/{$clubOnly->id}/warteliste", $this->payload())->assertSessionHasErrors('quantity');

        // eigener Gegenstand (Vereinsmitglied)
        $this->actingAs($owner)->post("/katalog/{$item->id}/warteliste", $this->payload())->assertSessionHasErrors('quantity');
    }

    public function test_club_member_joins_directly(): void
    {
        [$item] = $this->bookedItem();
        $user = User::factory()->clubAdmin()->create();

        $this->actingAs($user)->post("/katalog/{$item->id}/warteliste", $this->payload(['requester_name' => null, 'requester_email' => null]));

        $entry = WaitlistEntry::firstOrFail();
        $this->assertSame('waiting', $entry->status);
        $this->assertSame($user->club_id, $entry->requester_club_id);
    }

    public function test_decline_or_return_notifies_first_in_line_only_once_per_slot(): void
    {
        Notification::fake();
        [$item, $owner, $loan] = $this->bookedItem();
        $first = WaitlistEntry::create($this->entry($item, 'erste@example.com', 'waiting'));
        $second = WaitlistEntry::create($this->entry($item, 'zweite@example.com', 'waiting'));
        $unverified = WaitlistEntry::create($this->entry($item, 'unbestaetigt@example.com', 'unverified'));

        $this->actingAs($owner)->patch("/verwaltung/eingang/{$loan->id}", ['status' => 'declined'])->assertSessionHasNoErrors();

        $this->assertSame('notified', $first->fresh()->status);
        $this->assertSame('waiting', $second->fresh()->status, 'Bestand ist nur 1× frei – der Zweite muss warten');
        $this->assertSame('unverified', $unverified->fresh()->status);
        Notification::assertSentOnDemandTimes(WaitlistAvailable::class, 1);
    }

    public function test_more_stock_serves_several_entries(): void
    {
        Notification::fake();
        [$item, $owner] = $this->bookedItem();
        $a = WaitlistEntry::create($this->entry($item, 'a@example.com', 'waiting'));
        $b = WaitlistEntry::create($this->entry($item, 'b@example.com', 'waiting'));

        $this->actingAs($owner)->put("/verwaltung/gegenstaende/{$item->id}", [
            'name' => $item->name, 'quantity' => 3, 'lending_scope' => 'both', 'active' => true,
        ])->assertSessionHasNoErrors();

        $this->assertSame('notified', $a->fresh()->status);
        $this->assertSame('notified', $b->fresh()->status);
        Notification::assertSentOnDemandTimes(WaitlistAvailable::class, 2);
    }

    public function test_guest_cancelling_an_approved_loan_frees_the_waitlist(): void
    {
        Notification::fake();
        [$item, , $loan] = $this->bookedItem();
        $entry = WaitlistEntry::create($this->entry($item, 'w@example.com', 'waiting'));

        $this->post("/anfragen/{$loan->token}/stornieren");

        $this->assertSame('notified', $entry->fresh()->status);
    }

    public function test_cancel_and_expiry(): void
    {
        [$item] = $this->bookedItem();
        $entry = WaitlistEntry::create($this->entry($item, 'w@example.com', 'waiting'));
        $this->post("/warteliste/{$entry->token}/abmelden");
        $this->assertSame('cancelled', $entry->fresh()->status);

        $old = WaitlistEntry::create(['start_date' => today()->subDays(5), 'end_date' => today()->subDays(2)] + $this->entry($item, 'alt@example.com', 'waiting'));
        $event = collect(app(\Illuminate\Console\Scheduling\Schedule::class)->events())->first(fn ($e) => $e->description === 'cleanup-waitlist');
        $this->assertNotNull($event);
        $event->run(app());
        $this->assertSame('expired', $old->fresh()->status);
    }

    private function entry(Item $item, string $email, string $status): array
    {
        return [
            'item_id' => $item->id, 'requester_type' => 'private', 'requester_name' => 'Test', 'requester_email' => $email,
            'quantity' => 1, 'start_date' => today()->addDays(6)->toDateString(), 'end_date' => today()->addDays(7)->toDateString(),
            'status' => $status,
        ];
    }
}
