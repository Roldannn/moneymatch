<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Currency; // Importa el modelo Currency

class CurrencyController extends Controller
{
    public function index()
    {
        // Obtiene todas las monedas de la base de datos
        $currencies = Currency::all()->keyBy('country'); // Agrupa por el código del país

        // Pasa los datos a la vista
        return view('currency.index', ['currencies' => $currencies]);
    }

    public function convert(Request $request)
    {
        $request->validate([
            'country' => 'required',
            'amount' => 'required|numeric|min:0',
        ]);

        $currency = Currency::where('id', $request->input('country'))->first();

        if (!$currency) {
            return back()->withErrors(['country' => 'País no válido.']);
        }

        $amount = $request->input('amount');
        $converted = $amount / $currency->equivalence;

        return back()->with('result', [
            'currencyName' => $currency->currency,
            'rate' => $currency->equivalence,
            'converted' => round($converted, 2),
            'amount' => $amount
        ]);
    }
}
