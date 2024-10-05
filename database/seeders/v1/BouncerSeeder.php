<?php

namespace Database\Seeders\v1;

use App\Enums\AbilityEnum;
use Illuminate\Database\Seeder;
use Silber\Bouncer\BouncerFacade;

class BouncerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $abilityCases = AbilityEnum::cases();

        foreach ($abilityCases as $ability) {
            BouncerFacade::ability()->firstOrCreate([
                'name' => $ability->value,
                'title' => $ability->label(),
            ]);
        }
    }
}
