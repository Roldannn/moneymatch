<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\CurrencyDataService;
use App\Services\CurrencyConversionService;
use App\Services\CurrencyScrapingService;
use App\Models\Currency;

class CurrencyController extends Controller
{
    private CurrencyDataService $dataService;
    private CurrencyConversionService $conversionService;
    private CurrencyScrapingService $scrapingService;

    public function __construct(
        CurrencyDataService $dataService,
        CurrencyConversionService $conversionService,
        CurrencyScrapingService $scrapingService
    ) {
        $this->dataService = $dataService;
        $this->conversionService = $conversionService;
        $this->scrapingService = $scrapingService;
    }

    /**
     * Muestra el formulario de conversión con datos disponibles
     * Verifica automáticamente si hay nuevos datos del mes en curso y meses anteriores
     */
    public function index()
    {
        // Verificar y actualizar meses faltantes del año en curso
        // Solo se ejecuta si el mes actual no tiene datos
        $currentUrl = 'https://www.aduana.cl/indicadores-equivalencias/aduana/2019-04-22/145635.html';

        try {
            $this->scrapingService->updateMissingMonthsForCurrentYear($currentUrl);
        } catch (\Exception $e) {
            // Silenciar errores para no interrumpir la carga de la página
            Log::warning('Error al verificar actualizaciones automáticas: ' . $e->getMessage());
        }

        return view('currency.index', $this->dataService->getIndexData());
    }

    /**
     * Procesa la conversión de moneda
     */
    public function convert(Request $request)
    {
        if ($request->has('amount') && $request->input('amount') !== null) {
            $request->merge([
                'amount' => $this->conversionService->normalizeAmount($request->input('amount'))
            ]);
        }

        $request->validate([
            'country' => 'required|exists:currencies,id',
            'amount' => 'required|numeric|min:0',
            'year' => 'required|integer',
            'month' => 'required|integer|min:1|max:12',
        ]);

        $dateErrors = $this->conversionService->validateDateAvailability(
            $request->input('year'),
            $request->input('month')
        );

        if (!empty($dateErrors)) {
            return back()->withErrors($dateErrors)->withInput();
        }

        $currency = Currency::find($request->input('country'));

        if (!$currency) {
            return back()->withErrors(['country' => 'País no válido.'])->withInput();
        }

        $amount = $this->conversionService->normalizeAmount($request->input('amount'));
        $equivalenceValue = $this->conversionService->getEquivalenceValue(
            $currency->id,
            $request->input('year'),
            $request->input('month')
        );

        $converted = $this->conversionService->convertToDollars($amount, $equivalenceValue);

        return back()->with('result', [
            'currencyName' => $currency->currency,
            'rate' => $equivalenceValue,
            'converted' => round($converted, 10),
            'amount' => $amount,
            'year' => $request->input('year'),
            'month' => $this->conversionService->getMonthName($request->input('month'))
        ]);
    }
}
