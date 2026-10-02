<?php

namespace Tests\Feature\Verleih;

use App\Models\Club;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SharedPropsTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_id_and_name_of_the_club_are_shared(): void
    {
        $club = Club::factory()->create(['email' => 'geheim@example.com', 'phone' => '0123']);
        $user = User::factory()->clubAdmin($club)->create();

        $this->actingAs($user)->get('/dashboard')->assertInertia(fn (Assert $page) => $page
            ->where('auth.user.club.name', $club->name)
            ->has('auth.user.club', fn (Assert $c) => $c->has('id')->has('name')->etc())
            ->missing('auth.user.club.email')
            ->missing('auth.user.password'));
    }

    /** Regression: ein Teilmodell des Vereins ließ Vereinsdaten und Benachrichtigungen leer erscheinen. */
    public function test_controllers_get_the_full_club_model(): void
    {
        $club = Club::factory()->create(['email' => 'verein@example.com', 'phone' => '0123 456', 'city' => 'Teststadt']);
        $admin = User::factory()->clubAdmin($club)->create();

        $this->actingAs($admin)->get('/verwaltung/verein')->assertInertia(fn (Assert $page) => $page
            ->where('club.email', 'verein@example.com')->where('club.phone', '0123 456')->where('club.city', 'Teststadt'));

        $this->actingAs($admin)->get('/verwaltung/benachrichtigungen')->assertInertia(fn (Assert $page) => $page
            ->where('clubEmail', 'verein@example.com'));
    }

    public function test_test_mail_uses_the_real_club_address(): void
    {
        \Illuminate\Support\Facades\Notification::fake();
        $club = Club::factory()->create(['email' => 'verein@example.com', 'notification_settings' => ['recipients' => 'club_email']]);
        $admin = User::factory()->clubAdmin($club)->create();

        $this->actingAs($admin)->post('/verwaltung/benachrichtigungen/test');

        \Illuminate\Support\Facades\Notification::assertSentOnDemand(
            \App\Notifications\ClubTestMail::class,
            fn ($n, $ch, $notifiable) => $notifiable->routes['mail'] === 'verein@example.com',
        );
    }
}
