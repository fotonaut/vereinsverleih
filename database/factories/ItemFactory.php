<?php

namespace Database\Factories;

use App\Enums\LendingScope;
use App\Models\Club;
use App\Models\Item;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Item> */
class ItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'club_id' => Club::factory(),
            'name' => fake()->words(2, true),
            'description' => fake()->sentence(),
            'quantity' => fake()->numberBetween(1, 10),
            'condition' => 'gut',
            'location' => fake()->city(),
            'lending_scope' => LendingScope::Both,
            'active' => true,
        ];
    }

    public function scope(LendingScope $scope): static
    {
        return $this->state(['lending_scope' => $scope]);
    }
}
