<?php

namespace App\Services;

use App\Repositories\CurrencyRepository;
use App\Repositories\CurrencyEquivalenceRepository;

class CurrencyConversionService
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
     * Normaliza el monto aceptando comas como separador decimal
     */
    public function normalizeAmount(string $amount): float
    {
        $amount = trim($amount);
        
        if (strpos($amount, ',') !== false && strpos($amount, '.') !== false) {
            $amount = str_replace('.', '', $amount);
            $amount = str_replace(',', '.', $amount);
        } else {
            $amount = str_replace(',', '.', $amount);
        }
        
        return (float) $amount;
    }

    /**
     * Valida que existan datos para el año y mes especificados
     */
    public function validateDateAvailability(int $year, int $month): array
    {
        $errors = [];

        if (!$this->equivalenceRepository->yearExists($year)) {
            $errors['year'] = 'El año seleccionado no tiene datos disponibles.';
        }

        if (!$this->equivalenceRepository->monthExists($year, $month)) {
            $errors['month'] = 'El mes seleccionado no tiene datos disponibles para ese año.';
        }

        return $errors;
    }

    /**
     * Obtiene el valor de equivalencia para una moneda, año y mes
     */
    public function getEquivalenceValue(int $currencyId, int $year, int $month): float
    {
        $equivalence = $this->equivalenceRepository->findByCurrencyAndDate($currencyId, $year, $month);

        if ($equivalence) {
            return (float) $equivalence->equivalence;
        }

        $currency = \App\Models\Currency::find($currencyId);
        
        if ($currency && $currency->equivalence && $currency->equivalence > 0) {
            return (float) $currency->equivalence;
        }
        
        return 1.0;
    }

    /**
     * Convierte un monto de una moneda extranjera a dólares
     * La equivalencia representa cuántas unidades de la moneda extranjera equivalen a 1 USD
     * Por lo tanto, para convertir a USD se debe dividir el monto por la equivalencia
     */
    public function convertToDollars(float $amount, float $equivalenceValue): float
    {
        if ($equivalenceValue == 0) {
            return 0;
        }
        return $amount / $equivalenceValue;
    }

    /**
     * Obtiene el nombre del mes en español
     */
    public function getMonthName(int $month): string
    {
        $monthNames = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
        ];

        return $monthNames[$month] ?? '';
    }
}

