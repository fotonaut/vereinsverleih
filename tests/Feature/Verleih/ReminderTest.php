<?php

namespace Tests\Feature\Verleih;

use App\Enums\LoanStatus;
use App\Models\LoanRequest;
use App\Notifications\LoanReturnReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ReminderTest extends TestCase
{
    use RefreshDatabase;

    private function loan(LoanStatus $status, int $startOffset, int $endOffset, array $extra = []): LoanRequest
    {
        return LoanRequest::factory()->status($status)->create([
            'start_date' => today()->addDays($startOffset)->toDateString(),
            'end_date' => today()->addDays($endOffset)->toDateString(),
        ] + $extra);
    }

    public function test_reminds_active_loans_ending_tomorrow_exactly_once(): void
    {
        Notification::fake();
        $due = $this->loan(LoanStatus::PickedUp, -2, 1);
        $dueApproved = $this->loan(LoanStatus::Approved, -1, 1);
        $later = $this->loan(LoanStatus::PickedUp, -1, 5);
        $pending = $this->loan(LoanStatus::Pending, -1, 1);
        $returned = $this->loan(LoanStatus::Returned, -3, 1);
        $future = $this->loan(LoanStatus::Approved, 1, 1);

        $this->artisan('loans:send-reminders')->assertSuccessful();
        Notification::assertSentOnDemandTimes(LoanReturnReminder::class, 2);
        $this->assertNotNull($due->fresh()->reminded_at);
        $this->assertNotNull($dueApproved->fresh()->reminded_at);
        foreach ([$later, $pending, $returned, $future] as $skipped) {
            $this->assertNull($skipped->fresh()->reminded_at);
        }

        // zweiter Lauf am selben Tag: keine Doppel-Erinnerung
        $this->artisan('loans:send-reminders')->assertSuccessful();
        Notification::assertSentOnDemandTimes(LoanReturnReminder::class, 2);
    }

    public function test_reminder_mail_content(): void
    {
        $loan = $this->loan(LoanStatus::PickedUp, -1, 1);
        $mail = (new LoanReturnReminder($loan->load('item.club')))->toMail(new \stdClass);

        $this->assertStringContainsString('Erinnerung', $mail->subject);
        $this->assertStringContainsString('morgen', $mail->subject);
        $this->assertStringContainsString($loan->token, $mail->actionUrl);
    }
}
