<?php

namespace Database\Seeders;

use App\Helpers\Slugable;
use App\Models\User;
use App\Models\Workspace;
use App\Models\Project;
use Database\Seeders\v1\BouncerSeeder;
use Database\Seeders\v1\UserSeeder;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use Slugable;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            BouncerSeeder::class,
            UserSeeder::class,
        ]);

        User::factory(20)->create();
        Workspace::factory(10)->create();
        Project::factory(10)->create();
    }
}
