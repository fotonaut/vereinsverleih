<?php

namespace App\Http\Requests;

use App\Enums\LendingScope;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->club_id !== null;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:5000'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:10000'],
            'condition' => ['nullable', 'string', 'max:100'],
            'location' => ['nullable', 'string', 'max:150'],
            'deposit_euro' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'lending_scope' => ['required', Rule::enum(LendingScope::class)],
            'active' => ['boolean'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ];
    }
}
