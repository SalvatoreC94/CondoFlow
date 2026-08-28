<?php

namespace Database\Seeders;

use App\Models\PlatformUser;
use Illuminate\Database\Seeder;

/**
 * Creates (or updates the password of) the platform operator account used
 * to log into /platform. Credentials are read from the environment so the
 * same seeder is safe to run in every environment without hardcoding a
 * password into source control.
 */
class PlatformUserSeeder extends Seeder
{
    public function run(): void
    {
        PlatformUser::updateOrCreate(
            ['email' => config('platform.operator.email')],
            [
                'name' => config('platform.operator.name'),
                'password' => config('platform.operator.password'),
            ],
        );
    }
}
