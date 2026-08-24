<?php

namespace Database\Factories;

use App\Enums\DocumentVisibility;
use App\Models\Condominium;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'condominium_id' => Condominium::factory(),
            'document_category_id' => DocumentCategory::factory(),
            'uploaded_by' => User::factory()->administrator(),
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->sentence(),
            'disk' => 'local',
            'path' => 'documents/'.fake()->uuid().'.pdf',
            'original_name' => fake()->word().'.pdf',
            'mime_type' => 'application/pdf',
            'size' => fake()->numberBetween(15_000, 3_000_000),
            'visibility' => DocumentVisibility::All,
            'published_at' => now(),
        ];
    }
}
