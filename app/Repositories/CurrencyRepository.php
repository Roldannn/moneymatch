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
}

