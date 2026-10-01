<?php

namespace App\Models;

use App\Enums\LendingScope;
use App\Enums\LoanStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Item extends Model
{
    use HasFactory;

    protected $fillable = [
        'club_id', 'category_id', 'name', 'description', 'quantity', 'condition',
        'location', 'deposit_cents', 'image_path', 'lending_scope', 'active',
    ];

    protected $appends = ['image_url'];

    protected function casts(): array
    {
        return [
            'lending_scope' => LendingScope::class,
            'active' => 'boolean',
            'quantity' => 'integer',
            'deposit_cents' => 'integer',
        ];
    }

    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function loanRequests(): HasMany
    {
        return $this->hasMany(LoanRequest::class);
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path ? Storage::disk('public')->url($this->image_path) : null;
    }

    /** Im öffentlichen Katalog sichtbar: aktiv und überhaupt verleihbar. */
    public function scopeCatalog(Builder $query): Builder
    {
        return $query->where('active', true)->where('lending_scope', '!=', LendingScope::None->value);
    }

    /** Anzahl Stück, die im Zeitraum (inklusive) noch frei sind. */
    public function availableQuantity(string $start, string $end, ?int $ignoreRequestId = null): int
    {
        $reserved = $this->loanRequests()
            ->whereIn('status', array_map(fn ($s) => $s->value, LoanStatus::blocking()))
            ->when($ignoreRequestId, fn ($q) => $q->where('id', '!=', $ignoreRequestId))
            ->whereDate('start_date', '<=', $end)
            ->whereDate('end_date', '>=', $start)
            ->sum('quantity');

        return max(0, $this->quantity - (int) $reserved);
    }

    /** Ob der Gegenstand für diesen Antragstellertyp verliehen werden darf. */
    public function lendableTo(string $type): bool
    {
        return $type === 'club' ? $this->lending_scope->allowsClubs() : $this->lending_scope->allowsPrivate();
    }
}
