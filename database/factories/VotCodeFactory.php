<?php

namespace Database\Factories;

use App\Models\VotCode;
use Illuminate\Database\Eloquent\Factories\Factory;

class VotCodeFactory extends Factory
{
    protected $model = VotCode::class;

    public function definition(): array
    {
        return [
            'code'        => strtoupper(fake()->unique()->bothify('??###')),
            'description' => fake()->words(4, true),
            'is_active'   => true,
            'sort_order'  => fake()->numberBetween(1, 100),
        ];
    }
}
