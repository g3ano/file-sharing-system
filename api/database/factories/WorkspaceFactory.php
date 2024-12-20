<?php

namespace Database\Factories;

use App\Helpers\Slugable;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Workspace>
 */
class WorkspaceFactory extends Factory
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

        return [
            "user_id" => User::factory()->create(),
            "name" => $name,
            "size" => Workspace::$DEFAULT_SIZE,
            "description" => fake()->text(200),
        ];
    }
}
