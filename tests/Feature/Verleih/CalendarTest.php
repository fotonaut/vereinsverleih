<?php

namespace Tests\Feature\Verleih;

use App\Enums\LoanStatus;
use App\Models\Item;
use App\Models\LoanRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CalendarTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_page_exposes_only_blocking_reservations(): void
    {
        $item = Item::factory()->create(['quantity' => 2]);
        LoanRequest::factory()->status(LoanStatus::Approved)->create(['item_id' => $item->id, 'requester_name' => 'Geheim Genehmigt']);
        LoanRequest::factory()->status(LoanStatus::Pending)->create(['item_id' => $item->id]);
        LoanRequest::factory()->status(LoanStatus::Declined)->create(['item_id' => $item->id]);

        $this->get("/katalog/{$item->id}")->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('catalog/Show')
            ->has('reservations', 1)
            ->where('reservations.0.quantity', 1)
            ->missing('reservations.0.requester_name'));
    }

    public function test_owner_calendar_lists_names_and_pending_requests(): void
    {
        $owner = User::factory()->clubAdmin()->create();
        $item = Item::factory()->create(['club_id' => $owner->club_id, 'quantity' => 3]);
        $dates = fn (int $from) => ['start_date' => today()->addDays($from)->toDateString(), 'end_date' => today()->addDays($from + 1)->toDateString()];
        LoanRequest::factory()->status(LoanStatus::Approved)->create(['item_id' => $item->id, 'requester_name' => 'Anna'] + $dates(3));
        LoanRequest::factory()->status(LoanStatus::Pending)->create(['item_id' => $item->id, 'requester_name' => 'Berta'] + $dates(10));
        LoanRequest::factory()->status(LoanStatus::Returned)->create(['item_id' => $item->id, 'requester_name' => 'Carla'] + $dates(20));

        $this->actingAs($owner)->get("/verwaltung/gegenstaende/{$item->id}/kalender")->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('manage/items/Calendar')
                ->has('reservations', 2)
                ->where('reservations.1.pending', true)
                ->where('reservations.0.label', 'Anna')
                ->has('loans', 2));
    }

    public function test_owner_calendar_is_protected(): void
    {
        $item = Item::factory()->create();
        $this->get("/verwaltung/gegenstaende/{$item->id}/kalender")->assertRedirect('/login');
        $this->actingAs(User::factory()->clubAdmin()->create())
            ->get("/verwaltung/gegenstaende/{$item->id}/kalender")->assertForbidden();
    }
}
