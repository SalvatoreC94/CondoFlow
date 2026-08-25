<?php

namespace App\Services;

use App\Enums\SplitMethod;
use App\Models\Condominium;
use App\Models\Installment;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Splits an installment's total amount across a condominium's units and
 * persists it with its per-unit charges. Ripartizione per millesimi or in
 * equal parts, always distributed in integer cents (largest-remainder
 * method) so the per-unit charges always sum up exactly to the total —
 * never a euro cent lost or gained to floating-point rounding.
 */
class InstallmentSplitter
{
    public function create(Condominium $condominium, User $creator, array $data): Installment
    {
        $units = $condominium->units()->orderBy('id')->get();

        if ($units->isEmpty()) {
            throw ValidationException::withMessages([
                'condominium_id' => ['Il condominio non ha unità immobiliari a cui ripartire la rata.'],
            ]);
        }

        $splitMethod = SplitMethod::from($data['split_method']);
        $amounts = $this->split($units, (float) $data['total_amount'], $splitMethod);

        return DB::transaction(function () use ($condominium, $creator, $data, $splitMethod, $units, $amounts) {
            $installment = $condominium->installments()->create([
                'created_by' => $creator->id,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'total_amount' => $data['total_amount'],
                'split_method' => $splitMethod,
                'due_date' => $data['due_date'],
            ]);

            foreach ($units as $unit) {
                $installment->charges()->create([
                    'unit_id' => $unit->id,
                    'amount' => $amounts[$unit->id],
                ]);
            }

            return $installment;
        });
    }

    /**
     * @param  Collection<int, Unit>  $units
     * @return array<int, string> unit_id => amount (2 decimals), summing exactly to $totalAmount
     */
    private function split(Collection $units, float $totalAmount, SplitMethod $method): array
    {
        $totalCents = (int) round($totalAmount * 100);

        if ($method === SplitMethod::Millesimi) {
            if ($units->contains(fn (Unit $u) => $u->millesimi === null)) {
                throw ValidationException::withMessages([
                    'split_method' => ['Alcune unità non hanno i millesimi impostati. Imposta i millesimi per tutte le unità oppure scegli la ripartizione in parti uguali.'],
                ]);
            }

            $totalMillesimi = (float) $units->sum(fn (Unit $u) => (float) $u->millesimi);
            if ($totalMillesimi <= 0) {
                throw ValidationException::withMessages([
                    'split_method' => ['La somma dei millesimi delle unità deve essere maggiore di zero.'],
                ]);
            }

            $weights = $units->mapWithKeys(fn (Unit $u) => [$u->id => (float) $u->millesimi / $totalMillesimi]);
        } else {
            $weight = 1 / $units->count();
            $weights = $units->mapWithKeys(fn (Unit $u) => [$u->id => $weight]);
        }

        return $this->distributeCents($totalCents, $weights);
    }

    /**
     * @param  Collection<int, float>  $weights  unit_id => share of the total (sums to 1)
     * @return array<int, string>
     */
    private function distributeCents(int $totalCents, Collection $weights): array
    {
        $raw = $weights->map(fn (float $w) => $w * $totalCents);
        $floored = $raw->map(fn (float $v) => (int) floor($v));
        $remainder = $totalCents - $floored->sum();

        $byLargestFraction = $raw->map(fn (float $v) => $v - floor($v))
            ->sortDesc()
            ->keys();

        $cents = $floored->all();
        foreach ($byLargestFraction->take($remainder) as $unitId) {
            $cents[$unitId]++;
        }

        return collect($cents)->map(fn (int $c) => number_format($c / 100, 2, '.', ''))->all();
    }
}
