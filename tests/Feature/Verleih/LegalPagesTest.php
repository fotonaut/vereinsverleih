<?php

namespace Tests\Feature\Verleih;

use App\Enums\LoanStatus;
use App\Models\LoanRequest;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegalPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_imprint_and_privacy_show_operator_data_from_config(): void
    {
        config([
            'imprint.brand' => 'Testbetrieb',
            'imprint.name' => 'Erika Mustermann',
            'imprint.street' => 'Musterweg 1',
            'imprint.city' => '12345 Musterstadt',
            'imprint.email' => 'kontakt@example.org',
        ]);

        $this->get('/impressum')->assertOk()
            ->assertSee('Erika Mustermann')->assertSee('Musterweg 1')->assertSee('kontakt@example.org');
        $this->get('/datenschutz')->assertOk()
            ->assertSee('Erika Mustermann')->assertSee('kontakt@example.org');
    }

    public function test_stale_unverified_requests_are_pruned_but_others_are_kept(): void
    {
        $old = LoanRequest::factory()->status(LoanStatus::Unverified)->create(['created_at' => now()->subDays(8)]);
        $fresh = LoanRequest::factory()->status(LoanStatus::Unverified)->create(['created_at' => now()->subDays(2)]);
        $oldApproved = LoanRequest::factory()->status(LoanStatus::Approved)->create(['created_at' => now()->subDays(30)]);

        $event = collect(app(Schedule::class)->events())
            ->first(fn ($e) => $e->description === 'prune-unverified-requests');
        $this->assertNotNull($event, 'Prune-Job ist nicht eingeplant');
        $event->run(app());

        $this->assertModelMissing($old);
        $this->assertModelExists($fresh);
        $this->assertModelExists($oldApproved);
    }
}
