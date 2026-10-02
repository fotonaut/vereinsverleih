<?php

namespace Tests\Feature\Verleih;

use App\Models\Club;
use App\Models\Item;
use App\Models\User;
use App\Models\WaitlistEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class WaitlistOverviewTest extends TestCase
{
    use RefreshDatabase;

    private function entry(Item $item, ?Club $club, string $status = 'waiting', array $extra = []): WaitlistEntry
    {
        return WaitlistEntry::create([
            'item_id' => $item->id, 'requester_club_id' => $club?->id, 'requester_type' => $club ? 'club' : 'private',
            'requester_name' => $club?->name ?? 'Privat Paula', 'requester_email' => 'x'.uniqid().'@example.com',
            'quantity' => 1, 'start_date' => today()->addDays(5), 'end_date' => today()->addDays(6), 'status' => $status,
        ] + $extra);
    }

    public function test_dashboard_widget_lists_own_open_entries_with_notified_first(): void
    {
        $club = Club::factory()->create();
        $user = User::factory()->clubAdmin($club)->create();
        $item = Item::factory()->create();

        $waiting = $this->entry($item, $club);
        $notified = $this->entry($item, $club, 'notified', ['notified_at' => now()->subDay()]);
        $oldNotified = $this->entry($item, $club, 'notified', ['notified_at' => now()->subDays(20)]);
        $cancelled = $this->entry($item, $club, 'cancelled');
        $foreign = $this->entry($item, Club::factory()->create());

        $this->actingAs($user)->get('/dashboard')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->has('waitlist', 2)
            ->where('waitlist.0.id', $notified->id)
            ->where('waitlist.1.id', $waiting->id)
            ->where('waitlistTotal', 2));
    }

    public function test_dashboard_counts_people_waiting_for_our_items(): void
    {
        $club = Club::factory()->create();
        $user = User::factory()->clubAdmin($club)->create();
        $mine = Item::factory()->create(['club_id' => $club->id]);
        $this->entry($mine, null);
        $this->entry($mine, null, 'notified');
        $this->entry(Item::factory()->create(), null);

        $this->actingAs($user)->get('/dashboard')->assertInertia(fn (Assert $page) => $page->where('stats.waiting_for_us', 1));
    }

    public function test_widget_is_limited_to_five_but_reports_the_total(): void
    {
        $club = Club::factory()->create();
        $user = User::factory()->clubAdmin($club)->create();
        $item = Item::factory()->create();
        foreach (range(1, 7) as $i) {
            $this->entry($item, $club);
        }

        $this->actingAs($user)->get('/dashboard')->assertInertia(fn (Assert $page) => $page
            ->has('waitlist', 5)->where('waitlistTotal', 7));
    }

    public function test_overview_page_separates_own_entries_from_waiters_on_our_items(): void
    {
        $club = Club::factory()->create();
        $user = User::factory()->clubAdmin($club)->create();
        $mine = Item::factory()->create(['club_id' => $club->id]);
        $other = Item::factory()->create();
        $this->entry($other, $club);
        $this->entry($mine, null);
        $this->entry($mine, null, 'cancelled');

        $this->actingAs($user)->get('/verwaltung/warteliste')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('manage/Waitlist')->has('own', 1)->has('forUs', 1)->where('forUs.0.requester', 'Privat Paula'));
    }

    public function test_overview_requires_login_and_club(): void
    {
        $this->get('/verwaltung/warteliste')->assertRedirect('/login');
        $this->actingAs(User::factory()->create())->get('/verwaltung/warteliste')->assertForbidden();
    }
}
