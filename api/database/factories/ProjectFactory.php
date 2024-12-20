<?php

namespace Database\Factories;

use App\Helpers\Slugable;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Project>
 */
class ProjectFactory extends Factory
{
    use Slugable;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            "workspace_id" => Workspace::factory()->create(),
            "user_id" => fake()->numberBetween(1, 10),
            "name" => fake()->sentence(2),
            "description" => fake()->text(100),
        ];
    }
}
