<?php

namespace App\Repositories;

use App\Models\CurrencyEquivalence;
use Illuminate\Support\Collection;

class CurrencyEquivalenceRepository
{
    /**
     * Obtiene todos los años disponibles ordenados descendente
     */
    public function getAvailableYears(): Collection
    {
        return CurrencyEquivalence::distinct()
            ->orderBy('year', 'desc')
            ->pluck('year');
    }

    /**
     * Obtiene todos los meses disponibles ordenados
     */
    public function getAvailableMonths(): Collection
    {
        return CurrencyEquivalence::distinct()
            ->orderBy('month')
            ->pluck('month')
            ->unique()
            ->sort()
            ->values();
    }

    /**
     * Obtiene meses disponibles agrupados por año
     */
    public function getMonthsByYear(): Collection
    {
        return CurrencyEquivalence::select('year', 'month')
            ->distinct()
            ->orderBy('year', 'desc')
            ->orderBy('month')
            ->get()
            ->groupBy('year')
            ->map(function ($group) {
                return $group->pluck('month')->sort()->values();
            });
    }

    /**
     * Verifica si existe al menos una equivalencia para un año
     */
    public function yearExists(int $year): bool
    {
        return CurrencyEquivalence::where('year', $year)->exists();
    }

    /**
     * Verifica si existe una equivalencia para un año y mes específicos
     */
    public function monthExists(int $year, int $month): bool
    {
        return CurrencyEquivalence::where('year', $year)
            ->where('month', $month)
            ->exists();
    }

    /**
     * Busca una equivalencia por moneda, año y mes
     */
    public function findByCurrencyAndDate(int $currencyId, int $year, int $month): ?CurrencyEquivalence
    {
        return CurrencyEquivalence::where('currency_id', $currencyId)
            ->where('year', $year)
            ->where('month', $month)
            ->first();
    }
}

