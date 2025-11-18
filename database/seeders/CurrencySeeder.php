<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Currency;

class CurrencySeeder extends Seeder
{
    public function run()
    {
        // Monedas iniciales por defecto (se usarán si no se encuentran en el scraping)
        $defaultCurrencies = [
            ['country' => 'Estados Unidos', 'currency' => 'Dólar EE.UU.', 'equivalence' => 1.0000],
            ['country' => 'Gran Bretaña', 'currency' => 'Libra Esterlina', 'equivalence' => 1.3546],
            ['country' => 'Suiza', 'currency' => 'Franco Suizo', 'equivalence' => 1.0835],
            ['country' => 'Japón', 'currency' => 'Yen Japonés', 'equivalence' => 0.0091],
            ['country' => 'Canadá', 'currency' => 'Dólar Canadiense', 'equivalence' => 0.7854],
            ['country' => 'Australia', 'currency' => 'Dólar Australiano', 'equivalence' => 0.7273],
            ['country' => 'China', 'currency' => 'Yuan', 'equivalence' => 0.1567],
            ['country' => 'Unión Europea', 'currency' => 'Euro', 'equivalence' => 1.1284],
            ['country' => 'México', 'currency' => 'Peso Mexicano', 'equivalence' => 0.0485],
            ['country' => 'Brasil', 'currency' => 'Real Brasileño', 'equivalence' => 0.1852],
        ];

        // Registrar monedas por defecto si no existen
        foreach ($defaultCurrencies as $currencyData) {
            Currency::firstOrCreate(
                [
                    'country' => $currencyData['country'],
                    'currency' => $currencyData['currency']
                ],
                ['equivalence' => $currencyData['equivalence']]
            );
        }

        $this->command->info('Monedas por defecto registradas/actualizadas.');
    }
}
