<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Currency;
use App\Models\CurrencyEquivalence;

class CurrencyController extends Controller
{
    public function index()
    {
        // Obtener solo monedas que tienen equivalencias registradas
        $currencyIds = CurrencyEquivalence::distinct()->pluck('currency_id');
        $currencies = Currency::whereIn('id', $currencyIds)
            ->orderBy('country')
            ->get();

        // Obtener años disponibles desde las equivalencias
        $years = CurrencyEquivalence::distinct()
            ->orderBy('year', 'desc')
            ->pluck('year');

        // Obtener meses disponibles desde las equivalencias
        $months = CurrencyEquivalence::distinct()
            ->orderBy('month')
            ->pluck('month')
            ->unique()
            ->sort();

        // Mapeo de números de mes a nombres
        $monthNames = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
        ];

        // Obtener meses disponibles por año para JavaScript
        $monthsByYear = CurrencyEquivalence::select('year', 'month')
            ->distinct()
            ->orderBy('year', 'desc')
            ->orderBy('month')
            ->get()
            ->groupBy('year')
            ->map(function ($group) {
                return $group->pluck('month')->sort()->values();
            });

        // Pasa los datos a la vista
        return view('currency.index', [
            'currencies' => $currencies,
            'years' => $years,
            'months' => $months,
            'monthNames' => $monthNames,
            'monthsByYear' => $monthsByYear
        ]);
    }

    public function convert(Request $request)
    {
        // Normalizar el monto antes de validar (aceptar comas como separador decimal)
        $request->merge([
            'amount' => str_replace(',', '.', $request->input('amount'))
        ]);
        
        $request->validate([
            'country' => 'required|exists:currencies,id',
            'amount' => 'required|numeric|min:0',
            'year' => 'required|integer',
            'month' => 'required|integer|min:1|max:12',
        ]);
        
        // Validar que el año y mes existan en las equivalencias
        $yearExists = CurrencyEquivalence::where('year', $request->input('year'))->exists();
        $monthExists = CurrencyEquivalence::where('year', $request->input('year'))
            ->where('month', $request->input('month'))
            ->exists();
        
        if (!$yearExists) {
            return back()->withErrors(['year' => 'El año seleccionado no tiene datos disponibles.'])->withInput();
        }
        
        if (!$monthExists) {
            return back()->withErrors(['month' => 'El mes seleccionado no tiene datos disponibles para ese año.'])->withInput();
        }

        $currency = Currency::where('id', $request->input('country'))->first();

        if (!$currency) {
            return back()->withErrors(['country' => 'País no válido.']);
        }

        // Buscar equivalencia para el año y mes especificados
        $equivalence = CurrencyEquivalence::where('currency_id', $currency->id)
            ->where('year', $request->input('year'))
            ->where('month', $request->input('month'))
            ->first();

        // Si no se encuentra equivalencia específica, usar la del modelo Currency como fallback
        if (!$equivalence) {
            $equivalenceValue = $currency->equivalence ?? 1.0;
        } else {
            $equivalenceValue = $equivalence->equivalence;
        }

        // Normalizar el monto (aceptar comas como separador decimal)
        $amount = str_replace(',', '.', $request->input('amount'));
        $amount = floatval($amount);
        
        // La equivalencia representa cuántas unidades de la moneda extranjera equivalen a 1 dólar
        // Por lo tanto, para convertir a dólares: monto * equivalencia
        $converted = $amount * $equivalenceValue;

        $monthNames = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
        ];

        return back()->with('result', [
            'currencyName' => $currency->currency,
            'rate' => $equivalenceValue,
            'converted' => round($converted, 2),
            'amount' => $amount,
            'year' => $request->input('year'),
            'month' => $monthNames[$request->input('month')]
        ]);
    }
}
