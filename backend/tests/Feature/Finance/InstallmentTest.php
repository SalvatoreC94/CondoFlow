<?php

use App\Models\Installment;
use App\Models\InstallmentCharge;
use App\Models\Unit;

it('splits an installment by millesimi so the unit charges sum exactly to the total', function () {
    $admin = adminUser();
    $condominium = condominiumFor($admin);
    $units = collect([300, 300, 400])->map(
        fn ($millesimi) => Unit::factory()->create(['condominium_id' => $condominium->id, 'millesimi' => $millesimi])
    );

    $response = $this->actingAs($admin, 'sanctum')->postJson("/api/condominiums/{$condominium->id}/installments", [
        'title' => 'Rata test',
        'total_amount' => 1000.01,
        'split_method' => 'millesimi',
        'due_date' => now()->addMonth()->toDateString(),
    ]);

    $response->assertCreated();
    $charges = collect($response->json('data.charges'));
    expect($charges)->toHaveCount(3);
    expect($charges->sum(fn ($c) => (float) $c['amount']))->toEqualWithDelta(1000.01, 0.001);

    $byUnit = $charges->keyBy('unit.id');
    $largestUnit = $units->sortByDesc('millesimi')->first();
    $smallestUnit = $units->sortBy('millesimi')->first();
    expect((float) $byUnit[$largestUnit->id]['amount'])->toBeGreaterThan((float) $byUnit[$smallestUnit->id]['amount']);
});

it('rejects a millesimi split when some units are missing millesimi', function () {
    $admin = adminUser();
    $condominium = condominiumFor($admin);
    Unit::factory()->create(['condominium_id' => $condominium->id, 'millesimi' => 500]);
    Unit::factory()->create(['condominium_id' => $condominium->id, 'millesimi' => null]);

    $this->actingAs($admin, 'sanctum')->postJson("/api/condominiums/{$condominium->id}/installments", [
        'title' => 'Rata test',
        'total_amount' => 1000,
        'split_method' => 'millesimi',
        'due_date' => now()->addMonth()->toDateString(),
    ])->assertStatus(422)->assertJsonValidationErrors('split_method');
});

it('splits an installment equally among all units regardless of millesimi', function () {
    $admin = adminUser();
    $condominium = condominiumFor($admin);
    Unit::factory()->count(3)->create(['condominium_id' => $condominium->id, 'millesimi' => null]);

    $response = $this->actingAs($admin, 'sanctum')->postJson("/api/condominiums/{$condominium->id}/installments", [
        'title' => 'Rata straordinaria',
        'total_amount' => 100.01,
        'split_method' => 'equal',
        'due_date' => now()->addMonth()->toDateString(),
    ]);

    $response->assertCreated();
    $amounts = collect($response->json('data.charges'))->map(fn ($c) => (float) $c['amount']);
    expect($amounts->sum())->toEqualWithDelta(100.01, 0.001);
    // At most a one-cent difference across the three shares.
    expect($amounts->max() - $amounts->min())->toBeLessThanOrEqual(0.0101);
});

it('lets an administrator mark a charge as paid', function () {
    $admin = adminUser();
    $condominium = condominiumFor($admin);
    $unit = unitIn($condominium);
    $installment = Installment::factory()->create(['condominium_id' => $condominium->id, 'created_by' => $admin->id]);
    $charge = InstallmentCharge::factory()->create([
        'installment_id' => $installment->id,
        'unit_id' => $unit->id,
        'paid' => false,
    ]);

    $response = $this->actingAs($admin, 'sanctum')->patchJson("/api/installment-charges/{$charge->id}", ['paid' => true]);

    $response->assertOk()->assertJsonPath('data.paid', true);
    expect($charge->fresh()->paid_at)->not->toBeNull();
});

it('prevents a caretaker and a condomino from managing the condominiums finances', function () {
    $admin = adminUser();
    $condominium = condominiumFor($admin);
    $unit = unitIn($condominium);
    $caretaker = caretakerFor($condominium);
    $resident = residentOf($unit);

    foreach ([$caretaker, $resident] as $user) {
        $this->actingAs($user, 'sanctum')
            ->postJson("/api/condominiums/{$condominium->id}/expenses", [
                'category' => 'Pulizie',
                'description' => 'Test',
                'amount' => 100,
                'expense_date' => now()->toDateString(),
            ])->assertForbidden();

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/condominiums/{$condominium->id}/installments", [
                'title' => 'Rata',
                'total_amount' => 100,
                'split_method' => 'equal',
                'due_date' => now()->addMonth()->toDateString(),
            ])->assertForbidden();
    }
});

it('lets a condomino see only their own units charges via /api/me/charges', function () {
    $admin = adminUser();
    $condominium = condominiumFor($admin);
    $unitA = unitIn($condominium);
    $unitB = unitIn($condominium);
    $residentA = residentOf($unitA);
    residentOf($unitB);

    $installment = Installment::factory()->create(['condominium_id' => $condominium->id, 'created_by' => $admin->id]);
    $chargeA = InstallmentCharge::factory()->create(['installment_id' => $installment->id, 'unit_id' => $unitA->id]);
    InstallmentCharge::factory()->create(['installment_id' => $installment->id, 'unit_id' => $unitB->id]);

    $response = $this->actingAs($residentA, 'sanctum')->getJson('/api/me/charges');

    $response->assertOk();
    $ids = collect($response->json('data'))->pluck('id');
    expect($ids)->toHaveCount(1);
    expect($ids)->toContain($chargeA->id);
});

it('prevents deleting an installment that already has paid charges', function () {
    $admin = adminUser();
    $condominium = condominiumFor($admin);
    $unit = unitIn($condominium);
    $installment = Installment::factory()->create(['condominium_id' => $condominium->id, 'created_by' => $admin->id]);
    InstallmentCharge::factory()->create([
        'installment_id' => $installment->id,
        'unit_id' => $unit->id,
        'paid' => true,
        'paid_at' => now(),
    ]);

    $this->actingAs($admin, 'sanctum')
        ->deleteJson("/api/installments/{$installment->id}")
        ->assertStatus(422);

    expect(Installment::find($installment->id))->not->toBeNull();
});
