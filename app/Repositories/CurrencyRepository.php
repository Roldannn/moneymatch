<?php

namespace App\Repositories;

use App\Models\Currency;
use Illuminate\Support\Collection;

class CurrencyRepository
{
    /**
     * Obtiene todas las monedas que tienen equivalencias registradas
     */
    public function getCurrenciesWithEquivalences(): Collection
    {
        $currencyIds = \App\Models\CurrencyEquivalence::distinct()->pluck('currency_id');
        
        return Currency::whereIn('id', $currencyIds)
            ->orderBy('country')
            ->get();
    }

    /**
     * Busca una moneda por ID
     */
    public function findById(int $id): ?Currency
    {
        return Currency::find($id);
    }

    /**
     * Crea o busca una moneda existente
     */
    public function firstOrCreate(array $attributes, array $values = []): Currency
    {
        return Currency::firstOrCreate($attributes, $values);
    }
}

