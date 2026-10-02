<?php

namespace App\Models;

use App\Enums\LoanStatus;
use App\Enums\ReturnCondition;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class LoanRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_id', 'requester_user_id', 'requester_club_id', 'requester_type', 'requester_name',
        'requester_email', 'requester_phone', 'quantity', 'start_date', 'end_date', 'message',
        'status', 'decision_note', 'reminded_at', 'overdue_notified_at', 'overdue_count', 'series_id',
        'returned_at', 'return_condition', 'return_note', 'deposit_returned',
    ];

    protected function casts(): array
    {
        return [
            'status' => LoanStatus::class,
            'start_date' => 'date:Y-m-d',
            'end_date' => 'date:Y-m-d',
            'reminded_at' => 'datetime',
            'overdue_notified_at' => 'datetime',
            'returned_at' => 'datetime',
            'return_condition' => ReturnCondition::class,
            'deposit_returned' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (LoanRequest $r) {
            $r->token ??= Str::random(48);
        });
    }

    /** Ausgeliehen und Enddatum liegt in der Vergangenheit. */
    public function isOverdue(): bool
    {
        return $this->status === LoanStatus::PickedUp && $this->end_date->lt(today());
    }

    /** Alle Termine der Serie (inkl. dieses), sonst nur dieser. */
    public function seriesLoans(): \Illuminate\Support\Collection
    {
        if (! $this->series_id) {
            return collect([$this]);
        }

        return static::where('series_id', $this->series_id)->orderBy('start_date')->orderBy('id')->get();
    }

    public function returnPhotos(): HasMany
    {
        return $this->hasMany(LoanReturnPhoto::class);
    }

    public function extensions(): HasMany
    {
        return $this->hasMany(LoanExtension::class);
    }

    public function pendingExtension(): HasOne
    {
        return $this->hasOne(LoanExtension::class)->where('status', 'pending')->latestOfMany();
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function requesterClub(): BelongsTo
    {
        return $this->belongsTo(Club::class, 'requester_club_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_user_id');
    }
}
