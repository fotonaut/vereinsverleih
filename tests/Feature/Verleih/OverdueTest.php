<?php

namespace Tests\Feature\Verleih;

use App\Enums\LoanStatus;
use App\Models\LoanRequest;
use App\Notifications\LoanOverdue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class OverdueTest extends TestCase
{
    use RefreshDatabase;

    private function loan(LoanStatus $status, int $endOffset, array $extra = []): LoanRequest
    {
        return LoanRequest::factory()->status($status)->create([
            'start_date' => today()->addDays($endOffset - 3)->toDateString(),
            'end_date' => today()->addDays($endOffset)->toDateString(),
        ] + $extra);
    }

    public function test_only_picked_up_loans_past_their_end_date_are_chased(): void
    {
        Notification::fake();
        $overdue = $this->loan(LoanStatus::PickedUp, -2);
        $skipped = [
            $this->loan(LoanStatus::PickedUp, 0),   // heute fällig, noch nicht überfällig
            $this->loan(LoanStatus::PickedUp, 3),
            $this->loan(LoanStatus::Returned, -5),
            $this->loan(LoanStatus::Approved, -2),  // nie abgeholt
        ];

        $this->artisan('loans:send-overdue')->assertSuccessful();

        // je eine Mail an Ausleihende und an den Verein
        Notification::assertSentOnDemandTimes(LoanOverdue::class, 2);
        $this->assertSame(1, $overdue->fresh()->overdue_count);
        foreach ($skipped as $s) {
            $this->assertSame(0, $s->fresh()->overdue_count);
        }
    }

    public function test_follow_ups_respect_interval_and_maximum(): void
    {
        Notification::fake();
        $loan = $this->loan(LoanStatus::PickedUp, -10);

        $this->artisan('loans:send-overdue');
        $this->artisan('loans:send-overdue'); // gleicher Tag: nichts
        $this->assertSame(1, $loan->fresh()->overdue_count);

        $this->travel(3)->days();
        $this->artisan('loans:send-overdue');
        $this->assertSame(2, $loan->fresh()->overdue_count);

        $this->travel(3)->days();
        $this->artisan('loans:send-overdue');
        $this->assertSame(3, $loan->fresh()->overdue_count);

        $this->travel(3)->days();
        $this->artisan('loans:send-overdue'); // Maximum erreicht
        $this->assertSame(3, $loan->fresh()->overdue_count);
        Notification::assertSentOnDemandTimes(LoanOverdue::class, 6);
    }

    public function test_is_overdue_only_when_picked_up_and_past_end_date(): void
    {
        $loan = $this->loan(LoanStatus::PickedUp, -1);
        $this->assertTrue($loan->isOverdue());
        $this->assertFalse($this->loan(LoanStatus::PickedUp, 1)->isOverdue());
    }

    public function test_mail_texts(): void
    {
        $loan = $this->loan(LoanStatus::PickedUp, -2)->load('item.club');

        $toRequester = (new LoanOverdue($loan))->toMail(new \stdClass);
        $toOwner = (new LoanOverdue($loan, forOwner: true))->toMail(new \stdClass);

        $this->assertStringContainsString('Rückgabe überfällig', $toRequester->subject);
        $this->assertStringContainsString('Überfällig', $toOwner->subject);
        $this->assertStringContainsString($loan->token, $toRequester->actionUrl);
    }
}
