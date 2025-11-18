<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Goutte\Client;
use App\Models\Currency;
use App\Models\CurrencyEquivalence;

class UpdateCurrencyEquivalences extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'currency:update-equivalences 
                            {--year= : Año específico a actualizar (ej: 2025)}
                            {--from-year= : Año inicial para actualizar un rango}
                            {--to-year= : Año final para actualizar un rango}
                            {--current-only : Actualizar solo el año actual (2025)}
                            {--all : Actualizar todos los datos disponibles}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Actualiza las equivalencias de monedas desde aduana.cl';

    private $client;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->client = new Client();
        
        $this->info('🔄 Iniciando actualización de equivalencias...');
        $this->newLine();

        // Verificar que CurrencySeeder haya sido ejecutado primero
        if (Currency::count() == 0) {
            $this->error('❌ No hay monedas registradas. Ejecuta primero: php artisan db:seed --class=CurrencySeeder');
            return Command::FAILURE;
        }

        $currentOnly = $this->option('current-only');
        $all = $this->option('all');
        $year = $this->option('year');
        $fromYear = $this->option('from-year');
        $toYear = $this->option('to-year');

        // Determinar qué actualizar
        if ($currentOnly) {
            $this->info('📅 Actualizando solo el año actual (2025)...');
            $this->updateCurrentYear();
        } elseif ($year) {
            $this->info("📅 Actualizando año específico: {$year}...");
            $this->updateSpecificYear((int)$year);
        } elseif ($fromYear && $toYear) {
            $this->info("📅 Actualizando rango de años: {$fromYear} - {$toYear}...");
            $this->updateYearRange((int)$fromYear, (int)$toYear);
        } elseif ($all) {
            $this->info('📅 Actualizando todos los datos disponibles...');
            $this->updateAll();
        } else {
            // Por defecto, actualizar solo el año actual
            $this->info('📅 Actualizando solo el año actual (2025)...');
            $this->info('💡 Tip: Usa --help para ver todas las opciones disponibles');
            $this->newLine();
            $this->updateCurrentYear();
        }

        $this->newLine();
        $this->info('✅ Actualización completada!');
        
        // Mostrar estadísticas
        $totalEquivalences = CurrencyEquivalence::count();
        $totalCurrencies = Currency::count();
        $this->info("📊 Total de equivalencias: {$totalEquivalences}");
        $this->info("📊 Total de monedas: {$totalCurrencies}");

        return Command::SUCCESS;
    }

    /**
     * Actualiza solo el año actual (2025)
     */
    private function updateCurrentYear()
    {
        $url = 'https://www.aduana.cl/indicadores-equivalencias/aduana/2019-04-22/145635.html';
        $this->scrapeCurrentYear($url);
    }

    /**
     * Actualiza un año específico
     */
    private function updateSpecificYear($year)
    {
        if ($year == 2025) {
            $this->updateCurrentYear();
        } else {
            $url = 'https://www.aduana.cl/historico-equivalencias/aduana/2007-02-28/002433.html';
            $this->scrapeHistoricalYear($url, $year);
        }
    }

    /**
     * Actualiza un rango de años
     */
    private function updateYearRange($fromYear, $toYear)
    {
        if ($fromYear > $toYear) {
            $this->error('El año inicial debe ser menor o igual al año final.');
            return;
        }

        $url = 'https://www.aduana.cl/historico-equivalencias/aduana/2007-02-28/002433.html';
        
        for ($year = $fromYear; $year <= $toYear; $year++) {
            if ($year == 2025) {
                $this->updateCurrentYear();
            } else {
                $this->scrapeHistoricalYear($url, $year);
            }
        }
    }

    /**
     * Actualiza todos los datos
     */
    private function updateAll()
    {
        // Actualizar año actual
        $this->updateCurrentYear();
        
        // Actualizar histórico
        $url = 'https://www.aduana.cl/historico-equivalencias/aduana/2007-02-28/002433.html';
        $this->scrapeHistorical($url);
    }

    /**
     * Scrapea la página del año actual (2025)
     */
    private function scrapeCurrentYear($url)
    {
        $this->info("  🔍 Scrapeando página actual: {$url}");
        
        try {
            $crawler = $this->client->request('GET', $url);
            $rowsProcessed = 0;
            
            // Buscar la tabla que contiene los meses del año 2025
            $crawler->filter('table tr')->each(function ($row) use (&$rowsProcessed) {
                $cells = $row->filter('td');
                
                $cells->each(function ($cell) use ($row, &$rowsProcessed) {
                    $cellText = trim($cell->text());
                    if (preg_match('/^(\d{4})$/', $cellText, $yearMatches)) {
                        $year = (int)$yearMatches[1];
                        
                        if ($year == 2025) {
                            $row->filter('a')->each(function ($link) use ($year, &$rowsProcessed) {
                                $href = $link->attr('href');
                                $text = trim($link->text());
                                
                                $month = $this->extractMonthFromText($text);
                                if ($month && $href) {
                                    $fullUrl = $this->buildFullUrl($href);
                                    $result = $this->scrapeEquivalencePage($fullUrl, $year, $month);
                                    if ($result) {
                                        $rowsProcessed += $result;
                                    }
                                }
                            });
                        }
                    }
                });
            });
            
            // También buscar directamente enlaces con nombres de meses
            $crawler->filter('a')->each(function ($node) use (&$rowsProcessed) {
                $href = $node->attr('href');
                $text = trim($node->text());
                
                if ($href && strpos($href, 'equivalencias') !== false) {
                    $month = $this->extractMonthFromText($text);
                    if ($month) {
                        if (preg_match('/equivalencias-(enero|febrero|marzo|abril|mayo|junio|julio|agosto|septiembre|octubre|noviembre|diciembre)-(\d{4})/i', $href, $matches)) {
                            $year = (int)$matches[2];
                            if ($year == 2025) {
                                $fullUrl = $this->buildFullUrl($href);
                                $result = $this->scrapeEquivalencePage($fullUrl, $year, $month);
                                if ($result) {
                                    $rowsProcessed += $result;
                                }
                            }
                        }
                    }
                }
            });
            
            if ($rowsProcessed > 0) {
                $this->info("  ✅ Procesadas {$rowsProcessed} equivalencias para 2025");
            } else {
                $this->warn("  ⚠ No se encontraron datos para 2025");
            }
        } catch (\Exception $e) {
            $this->error("  ❌ Error al scrapear página actual: " . $e->getMessage());
        }
    }

    /**
     * Scrapea un año específico del histórico
     */
    private function scrapeHistoricalYear($url, $targetYear)
    {
        $this->info("  🔍 Scrapeando año {$targetYear}...");
        
        try {
            $crawler = $this->client->request('GET', $url);
            $rowsProcessed = 0;
            
            // Buscar en la tabla todas las filas que contengan el año objetivo
            $crawler->filter('table tr')->each(function ($row) use ($targetYear, &$rowsProcessed) {
                $cells = $row->filter('td');
                
                if ($cells->count() > 0) {
                    $firstCell = trim($cells->eq(0)->text());
                    
                    if (preg_match('/^(\d{4})$/', $firstCell, $yearMatches)) {
                        $year = (int)$yearMatches[1];
                        
                        if ($year == $targetYear) {
                            // Buscar enlaces de meses en las demás celdas de esta fila
                            for ($i = 1; $i < $cells->count(); $i++) {
                                $cell = $cells->eq($i);
                                $cell->filter('a')->each(function ($link) use ($year, &$rowsProcessed) {
                                    $href = $link->attr('href');
                                    $text = trim($link->text());
                                    
                                    $month = $this->extractMonthFromText($text);
                                    if ($month && $href) {
                                        $fullUrl = $this->buildFullUrl($href);
                                        $result = $this->scrapeEquivalencePage($fullUrl, $year, $month);
                                        if ($result) {
                                            $rowsProcessed += $result;
                                        }
                                    } elseif ($href && strpos($href, 'equivalencias') !== false) {
                                        if (preg_match('/equivalencias-(enero|febrero|marzo|abril|mayo|junio|julio|agosto|septiembre|octubre|noviembre|diciembre)-(\d{4})/i', $href, $matches)) {
                                            $month = $this->monthNameToNumber($matches[1]);
                                            $yearFromUrl = (int)$matches[2];
                                            if ($yearFromUrl == $year) {
                                                $fullUrl = $this->buildFullUrl($href);
                                                $result = $this->scrapeEquivalencePage($fullUrl, $yearFromUrl, $month);
                                                if ($result) {
                                                    $rowsProcessed += $result;
                                                }
                                            }
                                        }
                                    }
                                });
                            }
                        }
                    }
                }
            });
            
            if ($rowsProcessed > 0) {
                $this->info("  ✅ Procesadas equivalencias para {$targetYear}");
            } else {
                $this->warn("  ⚠ No se encontraron datos para {$targetYear}");
            }
        } catch (\Exception $e) {
            $this->error("  ❌ Error al scrapear año {$targetYear}: " . $e->getMessage());
        }
    }

    /**
     * Scrapea la página histórica completa
     */
    private function scrapeHistorical($url)
    {
        $this->info("  🔍 Scrapeando página histórica: {$url}");
        
        try {
            $crawler = $this->client->request('GET', $url);
            $totalRowsProcessed = 0;
            
            // La página histórica tiene una tabla con años y meses
            $crawler->filter('table tr')->each(function ($row) use (&$totalRowsProcessed) {
                $cells = $row->filter('td');
                
                if ($cells->count() > 0) {
                    $firstCell = trim($cells->eq(0)->text());
                    
                    if (preg_match('/^(\d{4})$/', $firstCell, $yearMatches)) {
                        $year = (int)$yearMatches[1];
                        
                        if ($year >= 2004 && $year <= 2024) {
                            // Buscar enlaces de meses en las demás celdas de esta fila
                            for ($i = 1; $i < $cells->count(); $i++) {
                                $cell = $cells->eq($i);
                                $cell->filter('a')->each(function ($link) use ($year, &$totalRowsProcessed) {
                                    $href = $link->attr('href');
                                    $text = trim($link->text());
                                    
                                    $month = $this->extractMonthFromText($text);
                                    if ($month && $href) {
                                        $fullUrl = $this->buildFullUrl($href);
                                        $result = $this->scrapeEquivalencePage($fullUrl, $year, $month);
                                        if ($result) {
                                            $totalRowsProcessed += $result;
                                        }
                                    } elseif ($href && strpos($href, 'equivalencias') !== false) {
                                        if (preg_match('/equivalencias-(enero|febrero|marzo|abril|mayo|junio|julio|agosto|septiembre|octubre|noviembre|diciembre)-(\d{4})/i', $href, $matches)) {
                                            $month = $this->monthNameToNumber($matches[1]);
                                            $yearFromUrl = (int)$matches[2];
                                            $fullUrl = $this->buildFullUrl($href);
                                            $result = $this->scrapeEquivalencePage($fullUrl, $yearFromUrl, $month);
                                            if ($result) {
                                                $totalRowsProcessed += $result;
                                            }
                                        }
                                    }
                                });
                            }
                        }
                    }
                }
            });
            
            $this->info("  ✅ Procesadas equivalencias del histórico");
        } catch (\Exception $e) {
            $this->error("  ❌ Error al scrapear página histórica: " . $e->getMessage());
        }
    }

    /**
     * Scrapea una página de equivalencias específica
     */
    private function scrapeEquivalencePage($url, $year, $month)
    {
        try {
            $crawler = $this->client->request('GET', $url);
            $rowsProcessed = 0;
            
            // Buscar tablas con equivalencias
            $crawler->filter('table tr')->each(function ($row) use ($year, $month, &$rowsProcessed) {
                $cells = $row->filter('td');
                if ($cells->count() >= 2) {
                    $result = $this->processTableRow($cells, $year, $month);
                    if ($result) {
                        $rowsProcessed++;
                    }
                }
            });
            
            return $rowsProcessed;
        } catch (\Exception $e) {
            $this->warn("  ⚠ Error al scrapear equivalencias para {$year}-{$month}: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Procesa una fila de tabla (reutilizado del seeder)
     */
    private function processTableRow($cells, $year, $month)
    {
        try {
            if ($cells->count() < 2) {
                return false;
            }
            
            $country = '';
            $currency = '';
            $equivalenceText = '';
            
            if ($cells->count() >= 4) {
                $country = trim($cells->eq(1)->text());
                $currency = trim($cells->eq(2)->text());
                $equivalenceCell = $cells->eq(3);
                $equivalenceText = trim($equivalenceCell->text());
                
                if (empty($equivalenceText)) {
                    $h3 = $equivalenceCell->filter('h3');
                    if ($h3->count() > 0) {
                        $equivalenceText = trim($h3->text());
                    }
                }
            } elseif ($cells->count() >= 3) {
                $country = trim($cells->eq(0)->text());
                $currency = trim($cells->eq(1)->text());
                $equivalenceCell = $cells->eq(2);
                $equivalenceText = trim($equivalenceCell->text());
                
                if (empty($equivalenceText)) {
                    $h3 = $equivalenceCell->filter('h3');
                    if ($h3->count() > 0) {
                        $equivalenceText = trim($h3->text());
                    }
                }
            } elseif ($cells->count() >= 2) {
                $col1 = trim($cells->eq(0)->text());
                $col2Cell = $cells->eq(1);
                $col2 = trim($col2Cell->text());
                
                if (preg_match('/^[\d.,]+$/', $col2)) {
                    $currency = $col1;
                    $equivalenceText = $col2;
                } elseif (preg_match('/^[\d.,]+$/', $col1)) {
                    $currency = $col2;
                    $equivalenceText = $col1;
                } else {
                    $currency = $col1;
                    $equivalenceText = $col2;
                    if (empty($equivalenceText)) {
                        $h3 = $col2Cell->filter('h3');
                        if ($h3->count() > 0) {
                            $equivalenceText = trim($h3->text());
                        }
                    }
                }
            }
            
            // Saltar filas de encabezado o vacías
            $allText = strtolower($country . ' ' . $currency . ' ' . $equivalenceText);
            if (stripos($allText, 'país') !== false || stripos($allText, 'country') !== false || 
                stripos($allText, 'moneda') !== false || stripos($allText, 'currency') !== false ||
                stripos($allText, 'equivalencia') !== false || stripos($allText, 'equivalence') !== false ||
                stripos($allText, 'total') !== false || empty($currency)) {
                return false;
            }
            
            // Limpiar y convertir equivalencia
            $equivalence = $this->parseEquivalence($equivalenceText);
            
            // Validar que la equivalencia sea razonable
            if (empty($currency) || $equivalence <= 0 || $equivalence >= 100000) {
                return false;
            }
            
            // Si no hay país, extraerlo del nombre de la moneda
            if (empty($country) || strlen($country) < 3) {
                $country = $this->extractCountryFromCurrency($currency);
            }
            
            // Buscar o crear currency
            $currencyModel = Currency::firstOrCreate(
                [
                    'country' => $country,
                    'currency' => $currency
                ],
                ['equivalence' => $equivalence]
            );
            
            // Crear o actualizar equivalencia
            CurrencyEquivalence::updateOrCreate(
                [
                    'currency_id' => $currencyModel->id,
                    'year' => $year,
                    'month' => $month
                ],
                ['equivalence' => $equivalence]
            );
            
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Convierte texto de equivalencia a número (reutilizado del seeder)
     */
    private function parseEquivalence($text)
    {
        $originalText = trim($text);
        $text = preg_replace('/[^\d.,]/', '', $originalText);
        
        $hasComma = strpos($text, ',') !== false;
        $hasDot = strpos($text, '.') !== false;
        
        if ($hasComma && $hasDot) {
            $text = str_replace('.', '', $text);
            $text = str_replace(',', '.', $text);
        } elseif ($hasComma && !$hasDot) {
            $text = str_replace(',', '.', $text);
        } elseif (!$hasComma && $hasDot) {
            $parts = explode('.', $text);
            if (count($parts) == 2) {
                $decimalPart = $parts[1];
                $integerPart = $parts[0];
                
                if (strlen($decimalPart) > 2) {
                    // Mantener como decimal
                } elseif (strlen($integerPart) > 3 && strlen($decimalPart) <= 2) {
                    $text = str_replace('.', '', $text);
                }
            } else {
                $text = str_replace('.', '', $text);
            }
        }
        
        $result = (float)$text;
        
        if ($result == 0 && preg_match('/[1-9]/', $originalText)) {
            $cleanText = preg_replace('/[^\d.,]/', '', $originalText);
            if (strpos($cleanText, ',') !== false) {
                $cleanText = str_replace('.', '', $cleanText);
                $cleanText = str_replace(',', '.', $cleanText);
                $result = (float)$cleanText;
            }
        }
        
        return $result;
    }

    /**
     * Extrae país del nombre de la moneda
     */
    private function extractCountryFromCurrency($currency)
    {
        $mapping = [
            'Dólar EE.UU.' => 'Estados Unidos',
            'Dólar' => 'Estados Unidos',
            'Libra Esterlina' => 'Gran Bretaña',
            'Franco Suizo' => 'Suiza',
            'Yen Japonés' => 'Japón',
            'Yen' => 'Japón',
            'Dólar Canadiense' => 'Canadá',
            'Dólar Australiano' => 'Australia',
            'Yuan' => 'China',
            'Euro' => 'Unión Europea',
            'Peso Mexicano' => 'México',
            'Real Brasileño' => 'Brasil',
        ];
        
        foreach ($mapping as $key => $country) {
            if (stripos($currency, $key) !== false) {
                return $country;
            }
        }
        
        return $currency;
    }

    /**
     * Convierte nombre de mes a número
     */
    private function monthNameToNumber($monthName)
    {
        $months = [
            'enero' => 1, 'febrero' => 2, 'marzo' => 3, 'abril' => 4,
            'mayo' => 5, 'junio' => 6, 'julio' => 7, 'agosto' => 8,
            'septiembre' => 9, 'octubre' => 10, 'noviembre' => 11, 'diciembre' => 12
        ];
        
        $monthName = strtolower(trim($monthName));
        return $months[$monthName] ?? null;
    }

    /**
     * Extrae mes de texto
     */
    private function extractMonthFromText($text)
    {
        $months = [
            'enero' => 1, 'febrero' => 2, 'marzo' => 3, 'abril' => 4,
            'mayo' => 5, 'junio' => 6, 'julio' => 7, 'agosto' => 8,
            'septiembre' => 9, 'octubre' => 10, 'noviembre' => 11, 'diciembre' => 12
        ];
        
        $text = strtolower(trim($text));
        foreach ($months as $monthName => $monthNum) {
            if (stripos($text, $monthName) !== false) {
                return $monthNum;
            }
        }
        
        return null;
    }

    /**
     * Construye URL completa
     */
    private function buildFullUrl($href)
    {
        if (strpos($href, 'http') === 0) {
            return $href;
        }
        
        if (strpos($href, '/') === 0) {
            return 'https://www.aduana.cl' . $href;
        }
        
        return 'https://www.aduana.cl/' . $href;
    }
}
