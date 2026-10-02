<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Support\LoanSeries;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class WaitlistEntry extends Model
{
    protected $fillable = [
        'item_id', 'requester_user_id', 'requester_club_id', 'requester_type', 'requester_name',
        'requester_email', 'quantity', 'start_date', 'end_date', 'repeat', 'repeat_count', 'until_date', 'status', 'notified_at',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date:Y-m-d',
            'end_date' => 'date:Y-m-d',
            'until_date' => 'date:Y-m-d',
            'notified_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (WaitlistEntry $e) {
            $e->token ??= Str::random(48);
            $e->until_date ??= $e->repeat
                ? Arr::last($e->occurrences())['end']
                : $e->end_date;
        });
    }

    public function isSeries(): bool
    {
        return $this->repeat !== null;
    }

    /**
     * Gewünschte Zeiträume: bei einer Serie alle Termine, sonst nur der eine.
     *
     * @return list<array{start:string,end:string}>
     */
    public function occurrences(): array
    {
        $start = Carbon::parse($this->start_date)->toDateString();
        $end = Carbon::parse($this->end_date)->toDateString();

        return $this->repeat
            ? LoanSeries::occurrences($start, $end, $this->repeat, (int) $this->repeat_count)
            : [['start' => $start, 'end' => $end]];
    }

    /** Einzel-Wunsch: abgelaufen, wenn der Zeitraum vorbei ist; Serie: sobald der erste Termin begonnen hat. */
    public function scopeStale(Builder $query): Builder
    {
        return $query->where(fn ($q) => $q
            ->whereDate('until_date', '<', today())
            ->orWhere(fn ($s) => $s->whereNotNull('repeat')->whereDate('start_date', '<', today())));
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
