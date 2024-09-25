<?php

namespace Database\Seeders;

use App\Helpers\Slugable;
use App\Models\User;
use App\Models\Workspace;
use App\Enums\RoleEnum;
use App\Models\Project;
use App\Models\Role;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use Slugable;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        foreach (RoleEnum::cases() as $case) {
            Role::query()->create([
                'name' => $case->name,
                'slug' => $this->getSlug($case->name),
            ]);
        }

        $admin = User::query()->create([
            'first_name' => 'Admin',
            'last_name' => 'Admin',
            'username' => 'admin',
            'slug' => $this->getSlug('admin'),
            'email' => 'admin@example.com',
            'password' => Hash::make('admin@example.com'),
        ]);

        User::factory(20)->create();
        Workspace::factory(10)->create();
        Project::factory(10)->create();

        $admin->roles()->attach([
            RoleEnum::MANAGER->value => [
                'workspace_id' => 1,
            ],
            RoleEnum::EDITOR->value => [
                'workspace_id' => 2,
            ],
        ]);
        $admin->roles()->attach([
            RoleEnum::MANAGER->value => [
                'project_id' => 1,
            ],
            RoleEnum::EDITOR->value => [
                'project_id' => 2,
            ],
        ]);
        $admin->roles()->attach(RoleEnum::ADMIN->value);
    }
}
