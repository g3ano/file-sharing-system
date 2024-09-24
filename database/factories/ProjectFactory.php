<?php

namespace Database\Factories;

use App\Helpers\Slugable;
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
        $name = fake()->sentence(4);
        $slug = $this->getSlug($name);

        return [
            'workspace_id' => fake()->numberBetween(1, 10),
            'name' => $name,
            'slug' => $slug,
            'description' => fake()->text(100),
        ];
    }
}
