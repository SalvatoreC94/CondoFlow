<?php

use App\Models\Condominium;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

pest()->extend(TestCase::class)
    ->in('Unit');

/*
|--------------------------------------------------------------------------
| Shared scenario builders
|--------------------------------------------------------------------------
|
| Small helpers to build the minimal multi-tenant fixtures most tests need,
| so individual tests can stay focused on the behaviour they assert.
*/

function adminUser(): User
{
    return User::factory()->administrator()->create();
}

function condominiumFor(User $admin): Condominium
{
    return Condominium::factory()->create(['administrator_id' => $admin->id]);
}

function unitIn(Condominium $condominium): Unit
{
    return Unit::factory()->create(['condominium_id' => $condominium->id]);
}

function residentOf(Unit $unit): User
{
    $user = User::factory()->condomino()->create();
    $unit->users()->attach($user->id, ['relationship' => 'owner', 'is_primary' => true]);

    return $user;
}

function caretakerFor(Condominium $condominium): User
{
    $user = User::factory()->caretaker()->create();
    $condominium->caretakers()->attach($user->id);

    return $user;
}
