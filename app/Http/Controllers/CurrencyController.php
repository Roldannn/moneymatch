<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\CurrencyDataService;
use App\Services\CurrencyConversionService;
use App\Repositories\CurrencyRepository;

class CurrencyController extends Controller
{
    private CurrencyDataService $dataService;
    private CurrencyConversionService $conversionService;
    private CurrencyRepository $currencyRepository;

    public function __construct(
        CurrencyDataService $dataService,
        CurrencyConversionService $conversionService,
        CurrencyRepository $currencyRepository
    ) {
        $this->dataService = $dataService;
        $this->conversionService = $conversionService;
        $this->currencyRepository = $currencyRepository;
    }

    /**
     * Muestra el formulario de conversión con datos disponibles
     */
    public function index()
    {
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

        $currency = $this->currencyRepository->findById($request->input('country'));

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
            'converted' => round($converted, 2),
            'amount' => $amount,
            'year' => $request->input('year'),
            'month' => $this->conversionService->getMonthName($request->input('month'))
        ]);
    }
}
