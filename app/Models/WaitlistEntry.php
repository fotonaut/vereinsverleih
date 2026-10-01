<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class WaitlistEntry extends Model
{
    protected $fillable = [
        'item_id', 'requester_user_id', 'requester_club_id', 'requester_type', 'requester_name',
        'requester_email', 'quantity', 'start_date', 'end_date', 'status', 'notified_at',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date:Y-m-d',
            'end_date' => 'date:Y-m-d',
            'notified_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (WaitlistEntry $e) {
            $e->token ??= Str::random(48);
        });
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'unverified' => 'E-Mail unbestätigt',
            'waiting' => 'Auf der Warteliste',
            'notified' => 'Gegenstand war frei – benachrichtigt',
            'cancelled' => 'Abgemeldet',
            'expired' => 'Abgelaufen',
            default => $this->status,
        };
    }
}
