<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Services\CurrencyScrapingService;

class CurrencyEquivalenceSeeder extends Seeder
{
    private CurrencyScrapingService $scrapingService;

    public function __construct(CurrencyScrapingService $scrapingService)
    {
        $this->scrapingService = $scrapingService;
    }

    /**
     * Ejecuta el scraping de equivalencias desde aduana.cl
     */
    public function run(): void
    {
        $this->command->info('Iniciando scraping de equivalencias...');

        $currentUrl = 'https://www.aduana.cl/indicadores-equivalencias/aduana/2019-04-22/145635.html';
        $historicalUrl = 'https://www.aduana.cl/historico-equivalencias/aduana/2007-02-28/002433.html';

        $this->scrapingService->scrapeCurrentYear($currentUrl, function ($year, $month, $count) {
            $this->command->info("  ✓ Procesadas equivalencias para {$year}-{$month}");
        });

        $this->scrapingService->scrapeHistorical($historicalUrl, function ($year, $month, $count) {
            $this->command->info("  ✓ Procesadas equivalencias para {$year}-{$month}");
        });

        $this->command->info('Scraping completado!');
    }
}
