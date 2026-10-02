<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class LoanReturnPhoto extends Model
{
    public const DISK = 'local'; // privat: nur über geschützte Routen auslieferbar

    protected $fillable = ['loan_request_id', 'path'];

    protected static function booted(): void
    {
        // Datei mitlöschen, wenn der Datensatz (explizit) gelöscht wird
        static::deleting(fn (LoanReturnPhoto $p) => Storage::disk(self::DISK)->delete($p->path));
    }

    public function loanRequest(): BelongsTo
    {
        return $this->belongsTo(LoanRequest::class);
    }
}
