<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CurrencyScrapingService;
use App\Repositories\CurrencyRepository;
use App\Repositories\CurrencyEquivalenceRepository;

class UpdateCurrencyEquivalences extends Command
{
    protected $signature = 'currency:update-equivalences 
                            {--year= : Año específico a actualizar (ej: 2025)}
                            {--from-year= : Año inicial para actualizar un rango}
                            {--to-year= : Año final para actualizar un rango}
                            {--current-only : Actualizar solo el año actual (2025)}
                            {--all : Actualizar todos los datos disponibles}';

    protected $description = 'Actualiza las equivalencias de monedas desde aduana.cl';

    private CurrencyScrapingService $scrapingService;
    private CurrencyRepository $currencyRepository;
    private CurrencyEquivalenceRepository $equivalenceRepository;

    public function __construct(
        CurrencyScrapingService $scrapingService,
        CurrencyRepository $currencyRepository,
        CurrencyEquivalenceRepository $equivalenceRepository
    ) {
        parent::__construct();
        $this->scrapingService = $scrapingService;
        $this->currencyRepository = $currencyRepository;
        $this->equivalenceRepository = $equivalenceRepository;
    }

    /**
     * Ejecuta el comando de actualización
     */
    public function handle()
    {
        $this->info('🔄 Iniciando actualización de equivalencias...');
        $this->newLine();

        if ($this->currencyRepository->findById(1) === null && \App\Models\Currency::count() == 0) {
            $this->error('❌ No hay monedas registradas. Ejecuta primero: php artisan db:seed --class=CurrencySeeder');
            return Command::FAILURE;
        }

        $currentOnly = $this->option('current-only');
        $all = $this->option('all');
        $year = $this->option('year');
        $fromYear = $this->option('from-year');
        $toYear = $this->option('to-year');

        if ($currentOnly) {
            $this->updateCurrentYear();
        } elseif ($year) {
            $this->updateSpecificYear((int)$year);
        } elseif ($fromYear && $toYear) {
            $this->updateYearRange((int)$fromYear, (int)$toYear);
        } elseif ($all) {
            $this->updateAll();
        } else {
            $this->info('📅 Actualizando solo el año actual (2025)...');
            $this->info('💡 Tip: Usa --help para ver todas las opciones disponibles');
            $this->newLine();
            $this->updateCurrentYear();
        }

        $this->newLine();
        $this->info('✅ Actualización completada!');
        
        $totalEquivalences = \App\Models\CurrencyEquivalence::count();
        $totalCurrencies = \App\Models\Currency::count();
        $this->info("📊 Total de equivalencias: {$totalEquivalences}");
        $this->info("📊 Total de monedas: {$totalCurrencies}");

        return Command::SUCCESS;
    }

    /**
     * Actualiza solo el año actual (2025)
     */
    private function updateCurrentYear(): void
    {
        $this->info('📅 Actualizando solo el año actual (2025)...');
        $url = 'https://www.aduana.cl/indicadores-equivalencias/aduana/2019-04-22/145635.html';
        
        $rowsProcessed = $this->scrapingService->scrapeCurrentYear($url, function ($year, $month, $count) {
            $this->info("  ✅ Procesadas {$count} equivalencias para {$year}-{$month}");
        });

        if ($rowsProcessed > 0) {
            $this->info("  ✅ Total procesadas: {$rowsProcessed} equivalencias para 2025");
        } else {
            $this->warn("  ⚠ No se encontraron datos para 2025");
        }
    }

    /**
     * Actualiza un año específico
     */
    private function updateSpecificYear(int $year): void
    {
        $this->info("📅 Actualizando año específico: {$year}...");
        
        if ($year == 2025) {
            $this->updateCurrentYear();
        } else {
            $url = 'https://www.aduana.cl/historico-equivalencias/aduana/2007-02-28/002433.html';
            
            $rowsProcessed = $this->scrapingService->scrapeHistoricalYear($url, $year, function ($y, $month, $count) {
                $this->info("  ✅ Procesadas equivalencias para {$y}-{$month}");
            });

            if ($rowsProcessed > 0) {
                $this->info("  ✅ Procesadas equivalencias para {$year}");
            } else {
                $this->warn("  ⚠ No se encontraron datos para {$year}");
            }
        }
    }

    /**
     * Actualiza un rango de años
     */
    private function updateYearRange(int $fromYear, int $toYear): void
    {
        if ($fromYear > $toYear) {
            $this->error('El año inicial debe ser menor o igual al año final.');
            return;
        }

        $this->info("📅 Actualizando rango de años: {$fromYear} - {$toYear}...");
        $url = 'https://www.aduana.cl/historico-equivalencias/aduana/2007-02-28/002433.html';

        for ($year = $fromYear; $year <= $toYear; $year++) {
            if ($year == 2025) {
                $this->updateCurrentYear();
            } else {
                $this->scrapingService->scrapeHistoricalYear($url, $year, function ($y, $month, $count) {
                    $this->info("  ✅ Procesadas equivalencias para {$y}-{$month}");
                });
            }
        }
    }

    /**
     * Actualiza todos los datos disponibles
     */
    private function updateAll(): void
    {
        $this->info('📅 Actualizando todos los datos disponibles...');
        
        $currentUrl = 'https://www.aduana.cl/indicadores-equivalencias/aduana/2019-04-22/145635.html';
        $historicalUrl = 'https://www.aduana.cl/historico-equivalencias/aduana/2007-02-28/002433.html';

        $this->scrapingService->scrapeCurrentYear($currentUrl, function ($year, $month, $count) {
            $this->info("  ✅ Procesadas equivalencias para {$year}-{$month}");
        });

        $this->scrapingService->scrapeHistorical($historicalUrl, function ($year, $month, $count) {
            $this->info("  ✅ Procesadas equivalencias para {$year}-{$month}");
        });
    }
}
