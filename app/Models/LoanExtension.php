<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanExtension extends Model
{
    protected $fillable = ['loan_request_id', 'previous_end_date', 'requested_end_date', 'message', 'status', 'decision_note'];

    protected function casts(): array
    {
        return [
            'previous_end_date' => 'date:Y-m-d',
            'requested_end_date' => 'date:Y-m-d',
        ];
    }

    public function loanRequest(): BelongsTo
    {
        return $this->belongsTo(LoanRequest::class);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'approved' => 'Genehmigt',
            'declined' => 'Abgelehnt',
            default => 'Angefragt',
        };
    }
}
