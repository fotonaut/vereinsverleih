<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Club extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'description', 'email', 'phone', 'street', 'zip', 'city', 'website'];

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

    /** Benachrichtigt die Vereinsadresse und alle Admin-Konten (ohne Doppelung, wenn die Adresse gleich ist). */
    public function notifyContacts(\Illuminate\Notifications\Notification $notification): void
    {
        \Illuminate\Support\Facades\Notification::route('mail', $this->email)->notify($notification);

        foreach ($this->users()->where('role', 'club_admin')->where('email', '!=', $this->email)->get() as $admin) {
            $admin->notify($notification);
        }
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
