<?php

namespace Tests\Feature\Verleih;

use App\Enums\LendingScope;
use App\Models\Club;
use App\Models\Item;
use App\Models\User;
use App\Notifications\ClubTestMail;
use App\Notifications\LoanRequestReceived;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Notifications\AnonymousNotifiable;
use Tests\TestCase;

class NotificationSettingsTest extends TestCase
{
    use RefreshDatabase;

    private function setup2(?array $settings = null): array
    {
        $club = Club::factory()->create(['email' => 'verein@example.com', 'notification_settings' => $settings]);
        $admin = User::factory()->clubAdmin($club)->create(['email' => 'admin@example.com']);
        $item = Item::factory()->create(['club_id' => $club->id, 'lending_scope' => LendingScope::Private, 'quantity' => 1]);

        return [$club, $admin, $item];
    }

    private function request(Item $item): void
    {
        $this->post("/katalog/{$item->id}/anfragen", [
            'requester_name' => 'Gast', 'requester_email' => 'gast@example.com', 'quantity' => 1,
            'start_date' => today()->addDays(3)->toDateString(), 'end_date' => today()->addDays(4)->toDateString(),
        ]);
        // Gast bestätigt
        $loan = \App\Models\LoanRequest::latest('id')->first();
        $this->get("/anfragen/{$loan->token}/bestaetigen");
    }

    private function recipients(): array
    {
        $out = [];
        Notification::assertSentOnDemand(LoanRequestReceived::class, function ($n, $channels, AnonymousNotifiable $notifiable) use (&$out) {
            $out[] = $notifiable->routes['mail'];

            return true;
        });
        sort($out);

        return $out;
    }

    public function test_defaults_send_to_club_email_and_admin_accounts(): void
    {
        Notification::fake();
        [, , $item] = $this->setup2();
        $this->request($item);

        $this->assertSame(['admin@example.com', 'verein@example.com'], $this->recipients());
    }

    public function test_recipient_modes_and_extra_address(): void
    {
        Notification::fake();
        [$club] = $this->setup2(['recipients' => 'club_email', 'extra_email' => 'Material@Example.com']);
        $this->assertSame(['verein@example.com', 'material@example.com'], $club->notificationRecipients());

        $club->update(['notification_settings' => ['recipients' => 'admins']]);
        $this->assertSame(['admin@example.com'], $club->fresh()->notificationRecipients());

        // ohne Admin-Konto: Fallback auf Vereinsadresse
        $club->users()->delete();
        $this->assertSame(['verein@example.com'], $club->fresh()->notificationRecipients());

        // Duplikate (Admin = Vereinsadresse) werden zusammengefasst
        $club->update(['email' => 'same@example.com', 'notification_settings' => ['recipients' => 'both']]);
        User::factory()->clubAdmin($club)->create(['email' => 'SAME@example.com']);
        $this->assertSame(['same@example.com'], $club->fresh()->notificationRecipients());
    }

    public function test_disabled_topic_sends_nothing(): void
    {
        Notification::fake();
        [, , $item] = $this->setup2(['requests' => false]);
        $this->request($item);

        Notification::assertSentOnDemandTimes(LoanRequestReceived::class, 0);
    }

    public function test_only_admins_can_view_and_change_settings(): void
    {
        $club = Club::factory()->create();
        $member = User::factory()->member($club)->create();
        $admin = User::factory()->clubAdmin($club)->create();

        $this->get('/verwaltung/benachrichtigungen')->assertRedirect('/login');
        $this->actingAs($member)->get('/verwaltung/benachrichtigungen')->assertForbidden();
        $this->actingAs($member)->put('/verwaltung/benachrichtigungen', ['recipients' => 'both'])->assertForbidden();
        $this->actingAs($member)->post('/verwaltung/benachrichtigungen/test')->assertForbidden();

        $this->actingAs($admin)->get('/verwaltung/benachrichtigungen')->assertOk();
    }

    public function test_saving_validates_and_persists(): void
    {
        $club = Club::factory()->create();
        $admin = User::factory()->clubAdmin($club)->create();

        $this->actingAs($admin)->put('/verwaltung/benachrichtigungen', ['recipients' => 'alle'])->assertSessionHasErrors('recipients');
        $this->actingAs($admin)->put('/verwaltung/benachrichtigungen', ['recipients' => 'both', 'extra_email' => 'kein-mail'])->assertSessionHasErrors('extra_email');

        $this->actingAs($admin)->put('/verwaltung/benachrichtigungen', [
            'requests' => true, 'extensions' => false, 'overdue' => false, 'recipients' => 'club_email', 'extra_email' => 'x@example.org',
        ])->assertSessionHasNoErrors();

        $s = $club->fresh()->notificationSettings();
        $this->assertTrue($s['requests']);
        $this->assertFalse($s['extensions']);
        $this->assertFalse($s['overdue']);
        $this->assertSame('club_email', $s['recipients']);
        $this->assertSame('x@example.org', $s['extra_email']);
    }

    public function test_test_mail_goes_to_configured_recipients_even_if_topics_are_off(): void
    {
        Notification::fake();
        $club = Club::factory()->create(['email' => 'verein@example.com', 'notification_settings' => ['requests' => false, 'recipients' => 'club_email', 'extra_email' => 'extra@example.com']]);
        $admin = User::factory()->clubAdmin($club)->create();

        $this->actingAs($admin)->post('/verwaltung/benachrichtigungen/test')->assertSessionHas('flash');

        Notification::assertSentOnDemandTimes(ClubTestMail::class, 2);
    }
}
