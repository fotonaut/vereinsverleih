<?php

namespace Tests\Feature\Verleih;

use App\Enums\LendingScope;
use App\Enums\LoanStatus;
use App\Models\Club;
use App\Models\Item;
use App\Models\LoanRequest;
use App\Models\User;
use App\Notifications\LoanRequestDecided;
use App\Notifications\LoanRequestReceived;
use App\Notifications\LoanRequestVerify;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class LoanFlowTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $o = []): array
    {
        return array_merge([
            'requester_name' => 'Erika Muster',
            'requester_email' => 'erika@example.com',
            'quantity' => 1,
            'start_date' => now()->addDays(3)->toDateString(),
            'end_date' => now()->addDays(5)->toDateString(),
        ], $o);
    }

    public function test_catalog_hides_internal_and_inactive_items(): void
    {
        Item::factory()->create(['name' => 'Sichtbar', 'lending_scope' => LendingScope::Both]);
        $internal = Item::factory()->create(['name' => 'Intern', 'lending_scope' => LendingScope::None]);
        Item::factory()->create(['name' => 'Inaktiv', 'active' => false]);

        $this->get('/katalog')->assertOk()
            ->assertSee('Sichtbar')->assertDontSee('Intern')->assertDontSee('Inaktiv');
        $this->get("/katalog/{$internal->id}")->assertNotFound();
    }

    public function test_guest_request_needs_email_verification_before_the_club_is_notified(): void
    {
        Notification::fake();
        $item = Item::factory()->create(['lending_scope' => LendingScope::Private]);

        $this->post("/katalog/{$item->id}/anfragen", $this->payload())->assertRedirect();

        $loan = LoanRequest::firstOrFail();
        $this->assertSame(LoanStatus::Unverified, $loan->status);
        Notification::assertSentOnDemand(LoanRequestVerify::class);
        Notification::assertCount(1); // nur die Bestätigungs-Mail, der Verein weiß noch nichts

        $this->get("/anfragen/{$loan->token}/bestaetigen")->assertRedirect();
        $this->assertSame(LoanStatus::Pending, $loan->fresh()->status);
        Notification::assertSentOnDemand(LoanRequestReceived::class);
    }

    public function test_guests_cannot_request_club_only_items(): void
    {
        $item = Item::factory()->create(['lending_scope' => LendingScope::Clubs]);

        $this->post("/katalog/{$item->id}/anfragen", $this->payload())->assertSessionHasErrors('quantity');
        $this->assertSame(0, LoanRequest::count());
    }

    public function test_club_members_cannot_request_private_only_or_own_items(): void
    {
        $mine = Club::factory()->create();
        $user = User::factory()->clubAdmin($mine)->create();
        $privateOnly = Item::factory()->create(['lending_scope' => LendingScope::Private]);
        $own = Item::factory()->create(['club_id' => $mine->id]);

        $this->actingAs($user)->post("/katalog/{$privateOnly->id}/anfragen", $this->payload())->assertSessionHasErrors('quantity');
        $this->actingAs($user)->post("/katalog/{$own->id}/anfragen", $this->payload())->assertSessionHasErrors('quantity');
        $this->assertSame(0, LoanRequest::count());
    }

    public function test_club_request_is_pending_immediately_and_stores_the_club(): void
    {
        Notification::fake();
        $club = Club::factory()->create();
        $user = User::factory()->clubAdmin($club)->create();
        $item = Item::factory()->create(['lending_scope' => LendingScope::Clubs]);

        $this->actingAs($user)->post("/katalog/{$item->id}/anfragen", $this->payload(['requester_name' => null, 'requester_email' => null]))
            ->assertRedirect();

        $loan = LoanRequest::firstOrFail();
        $this->assertSame(LoanStatus::Pending, $loan->status);
        $this->assertSame($club->id, $loan->requester_club_id);
        $this->assertSame('club', $loan->requester_type);
    }

    public function test_stock_is_respected_across_overlapping_approved_requests(): void
    {
        $item = Item::factory()->create(['quantity' => 2]);
        LoanRequest::factory()->status(LoanStatus::Approved)->create([
            'item_id' => $item->id, 'quantity' => 2,
            'start_date' => now()->addDays(4)->toDateString(), 'end_date' => now()->addDays(6)->toDateString(),
        ]);

        $this->assertSame(0, $item->availableQuantity(now()->addDays(5)->toDateString(), now()->addDays(8)->toDateString()));
        $this->assertSame(2, $item->availableQuantity(now()->addDays(7)->toDateString(), now()->addDays(8)->toDateString()));

        $this->post("/katalog/{$item->id}/anfragen", $this->payload([
            'start_date' => now()->addDays(5)->toDateString(), 'end_date' => now()->addDays(7)->toDateString(),
        ]))->assertSessionHasErrors('start_date');
    }

    public function test_only_the_lending_club_can_decide_and_transitions_are_enforced(): void
    {
        Notification::fake();
        $club = Club::factory()->create();
        $owner = User::factory()->clubAdmin($club)->create();
        $stranger = User::factory()->clubAdmin()->create();
        $item = Item::factory()->create(['club_id' => $club->id]);
        $loan = LoanRequest::factory()->create(['item_id' => $item->id]);

        $this->actingAs($stranger)->patch("/verwaltung/eingang/{$loan->id}", ['status' => 'approved'])->assertForbidden();

        $this->actingAs($owner)->patch("/verwaltung/eingang/{$loan->id}", ['status' => 'returned'])->assertSessionHasErrors('status');
        $this->actingAs($owner)->patch("/verwaltung/eingang/{$loan->id}", ['status' => 'approved'])->assertSessionHasNoErrors();
        $this->assertSame(LoanStatus::Approved, $loan->fresh()->status);
        Notification::assertSentOnDemand(LoanRequestDecided::class);

        $this->actingAs($owner)->patch("/verwaltung/eingang/{$loan->id}", ['status' => 'picked_up']);
        $this->actingAs($owner)->patch("/verwaltung/eingang/{$loan->id}", ['status' => 'returned']);
        $this->assertSame(LoanStatus::Returned, $loan->fresh()->status);
    }

    public function test_items_can_only_be_managed_by_their_own_club(): void
    {
        $owner = User::factory()->clubAdmin()->create();
        $other = User::factory()->clubAdmin()->create();
        $item = Item::factory()->create(['club_id' => $owner->club_id]);

        $this->actingAs($other)->get("/verwaltung/gegenstaende/{$item->id}/edit")->assertForbidden();
        $this->actingAs($other)->delete("/verwaltung/gegenstaende/{$item->id}")->assertForbidden();
        $this->actingAs($owner)->get("/verwaltung/gegenstaende/{$item->id}/edit")->assertOk();
    }

    public function test_creating_an_item_with_scope_and_deposit(): void
    {
        $user = User::factory()->clubAdmin()->create();

        $this->actingAs($user)->post('/verwaltung/gegenstaende', [
            'name' => 'Pavillon', 'quantity' => 3, 'lending_scope' => 'both', 'deposit_euro' => 12.5, 'active' => true,
        ])->assertRedirect('/verwaltung/gegenstaende');

        $item = Item::firstOrFail();
        $this->assertSame($user->club_id, $item->club_id);
        $this->assertSame(1250, $item->deposit_cents);
        $this->assertSame(LendingScope::Both, $item->lending_scope);
    }

    public function test_registration_creates_a_club_and_makes_the_user_its_admin(): void
    {
        $this->post('/register', [
            'name' => 'Max', 'email' => 'max@example.com', 'password' => 'password123!', 'password_confirmation' => 'password123!',
            'club_name' => 'TSV Test', 'club_city' => 'Teststadt',
        ])->assertRedirect();

        $user = User::where('email', 'max@example.com')->firstOrFail();
        $this->assertTrue($user->isClubAdmin());
        $this->assertSame('TSV Test', $user->club->name);
    }

    public function test_admins_can_invite_members_but_members_cannot(): void
    {
        Notification::fake();
        $club = Club::factory()->create();
        $admin = User::factory()->clubAdmin($club)->create();
        $member = User::factory()->member($club)->create();

        $this->actingAs($member)->post('/verwaltung/mitglieder', ['name' => 'X', 'email' => 'x@example.com', 'role' => 'member'])->assertForbidden();
        $this->actingAs($admin)->post('/verwaltung/mitglieder', ['name' => 'Neu', 'email' => 'neu@example.com', 'role' => 'member'])->assertRedirect();

        $this->assertSame($club->id, User::where('email', 'neu@example.com')->value('club_id'));
    }
}
