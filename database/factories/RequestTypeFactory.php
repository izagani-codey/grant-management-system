<?php

namespace Database\Factories;

use App\Models\RequestType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class RequestTypeFactory extends Factory
{
    protected $model = RequestType::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);
        return [
            'name'               => ucwords($name),
            'slug'               => Str::slug($name),
            'description'        => fake()->sentence(),
            'requires_vot'       => true,
            'is_active'          => true,
            'field_schema'       => null,
            'required_documents' => [],
        ];
    }

    public function withoutVot(): static
    {
        return $this->state(['requires_vot' => false]);
    }
}
