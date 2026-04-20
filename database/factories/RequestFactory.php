<?php

namespace Database\Factories;

use App\Enums\RequestStatus;
use App\Models\Request as GrantRequest;
use App\Models\RequestType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class RequestFactory extends Factory
{
    protected $model = GrantRequest::class;

    public function definition(): array
    {
        return [
            'user_id'         => User::factory()->state(['role' => 'admission']),
            'request_type_id' => RequestType::factory(),
            'ref_number'      => 'REQ-' . now()->format('Ymd') . '-' . str_pad(fake()->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'status_id'       => RequestStatus::SUBMITTED->value,
            'payload'         => ['description' => fake()->sentence()],
            'vot_items'       => [],
            'total_amount'    => 0,
            'submitted_at'    => now(),
        ];
    }

    public function submitted(): static
    {
        return $this->state(['status_id' => RequestStatus::SUBMITTED->value]);
    }

    public function staff1Reviewed(): static
    {
        return $this->state(['status_id' => RequestStatus::STAFF1_REVIEWED->value]);
    }

    public function staff2Approved(): static
    {
        return $this->state(['status_id' => RequestStatus::STAFF2_APPROVED->value]);
    }

    public function completed(): static
    {
        return $this->state(['status_id' => RequestStatus::COMPLETED->value]);
    }

    public function returned(): static
    {
        return $this->state([
            'status_id'     => RequestStatus::RETURNED->value,
            'return_reason' => 'Please fix the documents',
        ]);
    }

    public function declined(): static
    {
        return $this->state([
            'status_id'      => RequestStatus::DECLINED->value,
            'decline_reason' => 'Insufficient documentation',
        ]);
    }
}
