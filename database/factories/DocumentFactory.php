<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\RequestType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Document>
 */
class DocumentFactory extends Factory
{
    protected $model = Document::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'request_id' => null,
            'request_type_id' => RequestType::factory(),
            'uploaded_by' => User::factory(),
            'uploader_role' => $this->faker->randomElement(['admin', 'staff2']),
            'file_path' => $this->faker->filePath(),
            'original_name' => $this->faker->word() . '.pdf',
            'document_type' => 'user_submission',
            'is_template' => false,
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'is_active' => true,
            'download_count' => 0,
        ];
    }

    /**
     * Create a template document.
     */
    public function template(): static
    {
        return $this->state(fn (array $attributes) => [
            'document_type' => 'template',
            'is_template' => true,
            'is_active' => true,
            'download_count' => 0,
        ]);
    }

    /**
     * Create a user submission document.
     */
    public function userSubmission(): static
    {
        return $this->state(fn (array $attributes) => [
            'document_type' => 'user_submission',
            'is_template' => false,
            'uploader_role' => 'user',
        ]);
    }

    /**
     * Create a staff attachment document.
     */
    public function staffAttachment(): static
    {
        return $this->state(fn (array $attributes) => [
            'document_type' => 'staff_attachment',
            'is_template' => false,
            'uploader_role' => 'staff2',
        ]);
    }

    /**
     * Create an inactive template.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
