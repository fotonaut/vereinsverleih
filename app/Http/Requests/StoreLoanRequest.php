<?php

namespace App\Http\Requests;

use App\Support\LoanSeries;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLoanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $guest = $this->user() === null;

        return [
            'requester_name' => [$guest ? 'required' : 'nullable', 'string', 'max:150'],
            'requester_email' => [$guest ? 'required' : 'nullable', 'email', 'max:190'],
            'requester_phone' => ['nullable', 'string', 'max:50'],
            'quantity' => ['required', 'integer', 'min:1', 'max:10000'],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'message' => ['nullable', 'string', 'max:2000'],
            'repeat' => ['nullable', Rule::in(array_keys(LoanSeries::INTERVALS))],
            'repeat_count' => ['nullable', 'required_with:repeat', 'integer', 'min:2', 'max:'.LoanSeries::MAX_OCCURRENCES],
            // Honeypot: Bots füllen das Feld aus
            'website' => ['prohibited'],
        ];
    }
}
