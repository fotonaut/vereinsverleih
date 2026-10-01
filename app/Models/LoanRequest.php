<?php

namespace App\Models;

use App\Enums\LoanStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class LoanRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_id', 'requester_user_id', 'requester_club_id', 'requester_type', 'requester_name',
        'requester_email', 'requester_phone', 'quantity', 'start_date', 'end_date', 'message',
        'status', 'decision_note',
    ];

    protected function casts(): array
    {
        return [
            'status' => LoanStatus::class,
            'start_date' => 'date:Y-m-d',
            'end_date' => 'date:Y-m-d',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (LoanRequest $r) {
            $r->token ??= Str::random(48);
        });
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
