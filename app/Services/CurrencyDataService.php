<?php

namespace App\Services;

use App\Repositories\CurrencyRepository;
use App\Repositories\CurrencyEquivalenceRepository;

class CurrencyDataService
{
    private CurrencyRepository $currencyRepository;
    private CurrencyEquivalenceRepository $equivalenceRepository;

    public function __construct(
        CurrencyRepository $currencyRepository,
        CurrencyEquivalenceRepository $equivalenceRepository
    ) {
        $this->currencyRepository = $currencyRepository;
        $this->equivalenceRepository = $equivalenceRepository;
    }

    /**
     * Obtiene todos los datos necesarios para la vista principal
     */
    public function getIndexData(): array
    {
        return [
            'currencies' => $this->currencyRepository->getCurrenciesWithEquivalences(),
            'years' => $this->equivalenceRepository->getAvailableYears(),
            'months' => $this->equivalenceRepository->getAvailableMonths(),
            'monthNames' => $this->getMonthNames(),
            'monthsByYear' => $this->equivalenceRepository->getMonthsByYear()
        ];
    }

    /**
     * Obtiene el mapeo de números de mes a nombres en español
     */
    private function getMonthNames(): array
    {
        return [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
        ];
    }
}

