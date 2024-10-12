<?php

namespace Database\Seeders\v1;

use App\Enums\AbilityEnum;
use App\Models\User;
use App\Helpers\Slugable;
use App\Models\Workspace;
use Illuminate\Database\Seeder;
use Silber\Bouncer\BouncerFacade;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    use Slugable;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::query()->create([
            "first_name" => "Admin",
            "last_name" => "Admin",
            "username" => "admin",
            "slug" => $this->getSlug("admin"),
            "email" => "admin@example.com",
            "password" => Hash::make("admin@example.com"),
        ]);
        $manager = User::query()->create([
            "first_name" => "Manager",
            "last_name" => "Manager",
            "username" => "manager",
            "slug" => $this->getSlug("manager"),
            "email" => "manager@example.com",
            "password" => Hash::make("manager@example.com"),
        ]);

        BouncerFacade::allow($admin)->everything();
        BouncerFacade::allow($manager)->to(
            [
                AbilityEnum::LIST->value,
                AbilityEnum::VIEW->value,
                AbilityEnum::CREATE->value,
                AbilityEnum::UPDATE->value,

                AbilityEnum::USER_WORKSPACE_LIST->value,
                AbilityEnum::USER_WORKSPACE_ADD->value,
                AbilityEnum::USER_WORKSPACE_REMOVE->value,
                AbilityEnum::USER_PROJECT_LIST->value,
                AbilityEnum::USER_PROJECT_ADD->value,
                AbilityEnum::USER_PROJECT_REMOVE->value,
                AbilityEnum::USER_ABILITY_VIEW->value,
                AbilityEnum::USER_ABILITY_MANAGE->value,
            ],
            User::class
        );
        BouncerFacade::allow($manager)->to(
            [
                AbilityEnum::LIST->value,
                AbilityEnum::VIEW->value,
                AbilityEnum::CREATE->value,
                AbilityEnum::UPDATE->value,

                AbilityEnum::WORKSPACE_MEMBER_LIST->value,
                AbilityEnum::WORKSPACE_MEMBER_ADD->value,
                AbilityEnum::WORKSPACE_MEMBER_REMOVE->value,
                AbilityEnum::WORKSPACE_MEMBER_ABILITY_MANAGE->value,
                AbilityEnum::WORKSPACE_PROJECT_LIST->value,
                AbilityEnum::WORKSPACE_PROJECT_ADD->value,
                AbilityEnum::WORKSPACE_PROJECT_REMOVE->value,
            ],
            Workspace::class
        );
    }
}
