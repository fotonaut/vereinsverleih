<?php

namespace Database\Factories;

use App\Enums\LoanStatus;
use App\Models\Item;
use App\Models\LoanRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<LoanRequest> */
class LoanRequestFactory extends Factory
{
    public function definition(): array
    {
        $start = now()->addDays(fake()->numberBetween(2, 20));

        return [
            'item_id' => Item::factory(),
            'requester_type' => 'private',
            'requester_name' => fake()->name(),
            'requester_email' => fake()->safeEmail(),
            'quantity' => 1,
            'start_date' => $start->toDateString(),
            'end_date' => $start->copy()->addDays(2)->toDateString(),
            'status' => LoanStatus::Pending,
        ];
    }

    public function status(LoanStatus $status): static
    {
        return $this->state(['status' => $status]);
    }
}
