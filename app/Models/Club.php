<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Club extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'description', 'email', 'phone', 'street', 'zip', 'city', 'website', 'notification_settings'];

    protected static function booted(): void
    {
        static::creating(function (Club $club) {
            if (! $club->slug) {
                $base = Str::slug($club->name) ?: 'verein';
                $slug = $base;
                $i = 2;
                while (static::where('slug', $slug)->exists()) {
                    $slug = $base.'-'.$i++;
                }
                $club->slug = $slug;
            }
        });
    }

    public const NOTIFICATION_DEFAULTS = [
        'requests' => true,    // neue Ausleihanfragen
        'extensions' => true,  // Verlängerungsanfragen
        'overdue' => true,     // Info bei überfälliger Rückgabe
        'recipients' => 'both', // both | club_email | admins
        'extra_email' => null,
    ];

    protected function casts(): array
    {
        return ['notification_settings' => 'array'];
    }

    /** Einstellungen inkl. Standardwerten (alles an, Vereinsadresse + Admin-Konten). */
    public function notificationSettings(): array
    {
        return array_replace(self::NOTIFICATION_DEFAULTS, array_intersect_key($this->notification_settings ?? [], self::NOTIFICATION_DEFAULTS));
    }

    /**
     * Empfänger-Adressen für Vereins-Benachrichtigungen laut Einstellungen (ohne Doppelungen).
     *
     * @return list<string>
     */
    public function notificationRecipients(): array
    {
        $cfg = $this->notificationSettings();
        $adminMails = $this->users()->where('role', 'club_admin')->pluck('email')->all();

        $mails = match ($cfg['recipients']) {
            'club_email' => [$this->email],
            'admins' => $adminMails ?: [$this->email], // ohne Admin-Konto nicht ins Leere laufen
            default => [$this->email, ...$adminMails],
        };

        if ($cfg['extra_email']) {
            $mails[] = $cfg['extra_email'];
        }

        return array_values(array_unique(array_map('strtolower', $mails)));
    }

    /**
     * Benachrichtigt den Verein zu einem Thema (requests | extensions | overdue), sofern in den
     * Einstellungen aktiviert. @return int Anzahl Empfänger
     */
    public function notifyContacts(\Illuminate\Notifications\Notification $notification, string $topic = 'requests'): int
    {
        if (! ($this->notificationSettings()[$topic] ?? true)) {
            return 0;
        }

        return $this->sendTo($notification);
    }

    /** Sendet ohne Themenfilter an alle konfigurierten Empfänger (z. B. Test-Mail). */
    public function sendTo(\Illuminate\Notifications\Notification $notification): int
    {
        $mails = $this->notificationRecipients();
        foreach ($mails as $mail) {
            \Illuminate\Support\Facades\Notification::route('mail', $mail)->notify($notification);
        }

        return count($mails);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }
}
