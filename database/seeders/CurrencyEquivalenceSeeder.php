<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Goutte\Client;
use App\Models\Currency;
use App\Models\CurrencyEquivalence;

class CurrencyEquivalenceSeeder extends Seeder
{
    private $client;

    public function __construct()
    {
        $this->client = new Client();
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Iniciando scraping de equivalencias...');

        // URL para el año 2025 (página actual)
        $currentUrl = 'https://www.aduana.cl/indicadores-equivalencias/aduana/2019-04-22/145635.html';
        $this->scrapeCurrentYear($currentUrl);

        // URL para histórico (desde 2024 hacia atrás)
        $historicalUrl = 'https://www.aduana.cl/historico-equivalencias/aduana/2007-02-28/002433.html';
        $this->scrapeHistorical($historicalUrl);

        $this->command->info('Scraping completado!');
    }

    /**
     * Scrapea la página del año actual (2025)
     */
    private function scrapeCurrentYear($url)
    {
        $this->command->info("Scrapeando página actual: {$url}");
        
        try {
            $crawler = $this->client->request('GET', $url);
            
            // Buscar la tabla que contiene los meses del año 2025
            // La tabla tiene una fila con el año 2025 y luego otra fila con los meses
            $crawler->filter('table tr')->each(function ($row) {
                $cells = $row->filter('td');
                
                // Buscar la celda que contiene "2025"
                $cells->each(function ($cell) use ($row) {
                    $cellText = trim($cell->text());
                    if (preg_match('/^(\d{4})$/', $cellText, $yearMatches)) {
                        $year = (int)$yearMatches[1];
                        
                        // Si encontramos el año 2025, buscar en la misma fila o siguiente fila los meses
                        if ($year == 2025) {
                            // Buscar enlaces de meses en esta fila y la siguiente
                            $row->filter('a')->each(function ($link) use ($year) {
                                $href = $link->attr('href');
                                $text = trim($link->text());
                                
                                // Extraer mes del texto del enlace
                                $month = $this->extractMonthFromText($text);
                                if ($month && $href) {
                                    $fullUrl = $this->buildFullUrl($href);
                                    $this->scrapeEquivalencePage($fullUrl, $year, $month);
                                }
                            });
                        }
                    }
                });
            });
            
            // También buscar directamente enlaces con nombres de meses
            $crawler->filter('a')->each(function ($node) {
                $href = $node->attr('href');
                $text = trim($node->text());
                
                // Buscar enlaces que contengan "equivalencias" y un mes
                if ($href && strpos($href, 'equivalencias') !== false) {
                    // Extraer mes del texto
                    $month = $this->extractMonthFromText($text);
                    if ($month) {
                        // Intentar extraer año del href
                        if (preg_match('/equivalencias-(enero|febrero|marzo|abril|mayo|junio|julio|agosto|septiembre|octubre|noviembre|diciembre)-(\d{4})/i', $href, $matches)) {
                            $year = (int)$matches[2];
                            $fullUrl = $this->buildFullUrl($href);
                            $this->scrapeEquivalencePage($fullUrl, $year, $month);
                        } elseif (preg_match('/(\d{4})[-\/](\d{2})/', $href, $matches)) {
                            $year = (int)$matches[1];
                            $monthFromUrl = (int)$matches[2];
                            $fullUrl = $this->buildFullUrl($href);
                            $this->scrapeEquivalencePage($fullUrl, $year, $monthFromUrl);
                        }
                    }
                }
            });
        } catch (\Exception $e) {
            $this->command->error("Error al scrapear página actual: " . $e->getMessage());
        }
    }

    /**
     * Scrapea la página histórica
     */
    private function scrapeHistorical($url)
    {
        $this->command->info("Scrapeando página histórica: {$url}");
        
        try {
            $crawler = $this->client->request('GET', $url);
            
            // La página histórica tiene una tabla con años y meses
            // Buscar en la tabla todas las filas que contengan años
            $crawler->filter('table tr')->each(function ($row) {
                $cells = $row->filter('td');
                
                // La primera celda de cada fila contiene el año
                if ($cells->count() > 0) {
                    $firstCell = trim($cells->eq(0)->text());
                    
                    // Verificar si la primera celda es un año (2004-2024)
                    if (preg_match('/^(\d{4})$/', $firstCell, $yearMatches)) {
                        $year = (int)$yearMatches[1];
                        
                        if ($year >= 2004 && $year <= 2024) {
                            // Buscar enlaces de meses en las demás celdas de esta fila
                            for ($i = 1; $i < $cells->count(); $i++) {
                                $cell = $cells->eq($i);
                                $cell->filter('a')->each(function ($link) use ($year) {
                                    $href = $link->attr('href');
                                    $text = trim($link->text());
                                    
                                    // Extraer mes del texto
                                    $month = $this->extractMonthFromText($text);
                                    if ($month && $href) {
                                        $fullUrl = $this->buildFullUrl($href);
                                        $this->scrapeEquivalencePage($fullUrl, $year, $month);
                                    } elseif ($href && strpos($href, 'equivalencias') !== false) {
                                        // Intentar extraer mes y año del href
                                        if (preg_match('/equivalencias-(enero|febrero|marzo|abril|mayo|junio|julio|agosto|septiembre|octubre|noviembre|diciembre)-(\d{4})/i', $href, $matches)) {
                                            $month = $this->monthNameToNumber($matches[1]);
                                            $yearFromUrl = (int)$matches[2];
                                            $fullUrl = $this->buildFullUrl($href);
                                            $this->scrapeEquivalencePage($fullUrl, $yearFromUrl, $month);
                                        }
                                    }
                                });
                            }
                        }
                    }
                }
            });
            
            // También buscar directamente enlaces con años (fallback)
            $crawler->filter('a')->each(function ($node) {
                $href = $node->attr('href');
                $text = trim($node->text());
                
                // Buscar enlaces que contengan años (2004-2024)
                if ($href && preg_match('/^(\d{4})$/', $text, $matches)) {
                    $year = (int)$matches[1];
                    if ($year >= 2004 && $year <= 2024) {
                        $fullUrl = $this->buildFullUrl($href);
                        $this->scrapeYearPage($fullUrl, $year);
                    }
                }
            });
        } catch (\Exception $e) {
            $this->command->error("Error al scrapear página histórica: " . $e->getMessage());
        }
    }

    /**
     * Scrapea una página de un año específico para obtener los meses
     */
    private function scrapeYearPage($url, $year)
    {
        $this->command->info("Scrapeando año {$year}: {$url}");
        
        try {
            $crawler = $this->client->request('GET', $url);
            
            // Buscar enlaces a meses en la tabla
            $crawler->filter('table tr')->each(function ($row) use ($year) {
                $row->filter('a')->each(function ($link) use ($year) {
                    $href = $link->attr('href');
                    $text = trim($link->text());
                    
                    // Buscar nombres de meses en español
                    $month = $this->extractMonthFromText($text);
                    if ($month && $href) {
                        $fullUrl = $this->buildFullUrl($href);
                        $this->scrapeEquivalencePage($fullUrl, $year, $month);
                    }
                });
            });
            
            // También buscar directamente enlaces con meses
            $crawler->filter('a')->each(function ($node) use ($year) {
                $href = $node->attr('href');
                $text = trim($node->text());
                
                // Buscar nombres de meses en español
                $month = $this->extractMonthFromText($text);
                if ($month && $href && strpos($href, 'equivalencias') !== false) {
                    $fullUrl = $this->buildFullUrl($href);
                    $this->scrapeEquivalencePage($fullUrl, $year, $month);
                } elseif ($href && strpos($href, 'equivalencias') !== false) {
                    // Intentar extraer mes y año del href
                    if (preg_match('/equivalencias-(enero|febrero|marzo|abril|mayo|junio|julio|agosto|septiembre|octubre|noviembre|diciembre)-(\d{4})/i', $href, $matches)) {
                        $month = $this->monthNameToNumber($matches[1]);
                        $yearFromUrl = (int)$matches[2];
                        $fullUrl = $this->buildFullUrl($href);
                        $this->scrapeEquivalencePage($fullUrl, $yearFromUrl, $month);
                    } elseif (preg_match('/(\d{4})[-\/](\d{2})/', $href, $matches)) {
                        $yearFromUrl = (int)$matches[1];
                        $monthFromUrl = (int)$matches[2];
                        $fullUrl = $this->buildFullUrl($href);
                        $this->scrapeEquivalencePage($fullUrl, $yearFromUrl, $monthFromUrl);
                    }
                }
            });
        } catch (\Exception $e) {
            $this->command->error("Error al scrapear año {$year}: " . $e->getMessage());
        }
    }

    /**
     * Scrapea una página de equivalencias específica
     */
    private function scrapeEquivalencePage($url, $year, $month)
    {
        $this->command->info("Scrapeando equivalencias para {$year}-{$month}: {$url}");
        
        try {
            $crawler = $this->client->request('GET', $url);
            $rowsProcessed = 0;
            
            // Buscar tablas con equivalencias - múltiples selectores
            // Selector 1: tabla estándar (solo td, no th para evitar encabezados)
            $crawler->filter('table tr')->each(function ($row) use ($year, $month, &$rowsProcessed) {
                $cells = $row->filter('td');
                if ($cells->count() >= 2) {
                    $result = $this->processTableRow($cells, $year, $month);
                    if ($result) {
                        $rowsProcessed++;
                    }
                }
            });
            
            // Selector 2: tabla con tbody (más específico)
            if ($rowsProcessed == 0) {
                $crawler->filter('table tbody tr')->each(function ($row) use ($year, $month, &$rowsProcessed) {
                    $cells = $row->filter('td');
                    if ($cells->count() >= 2) {
                        $this->processTableRow($cells, $year, $month);
                        $rowsProcessed++;
                    }
                });
            }
            
            // Selector 3: buscar cualquier tabla en la página
            if ($rowsProcessed == 0) {
                $crawler->filter('table')->each(function ($table) use ($year, $month, &$rowsProcessed) {
                    $table->filter('tr')->each(function ($row) use ($year, $month, &$rowsProcessed) {
                        $cells = $row->filter('td');
                        if ($cells->count() >= 2) {
                            $this->processTableRow($cells, $year, $month);
                            $rowsProcessed++;
                        }
                    });
                });
            }
            
            // Selector 4: buscar en divs que contengan tablas
            if ($rowsProcessed == 0) {
                $crawler->filter('div table tr')->each(function ($row) use ($year, $month, &$rowsProcessed) {
                    $cells = $row->filter('td');
                    if ($cells->count() >= 2) {
                        $this->processTableRow($cells, $year, $month);
                        $rowsProcessed++;
                    }
                });
            }
            
            // Selector 5: buscar en cualquier elemento con estructura de tabla
            if ($rowsProcessed == 0) {
                $crawler->filter('[class*="table"], [class*="tabla"]')->each(function ($tableNode) use ($year, $month, &$rowsProcessed) {
                    $tableNode->filter('tr, .row')->each(function ($row) use ($year, $month, &$rowsProcessed) {
                        $cells = $row->filter('td, .cell, div');
                        if ($cells->count() >= 2) {
                            $this->processTableRow($cells, $year, $month);
                            $rowsProcessed++;
                        }
                    });
                });
            }
            
            // Selector 6: extraer de texto plano si hay datos estructurados
            if ($rowsProcessed == 0) {
                $pageText = $crawler->text();
                // Buscar patrones de equivalencias en el texto
                if (preg_match_all('/([A-Za-zÁÉÍÓÚáéíóúñÑ\s]+)\s+([A-Za-zÁÉÍÓÚáéíóúñÑ\s]+)\s+([\d.,]+)/', $pageText, $matches, PREG_SET_ORDER)) {
                    foreach ($matches as $match) {
                        $country = trim($match[1]);
                        $currency = trim($match[2]);
                        $equivalence = $this->parseEquivalence($match[3]);
                        
                        if ($equivalence > 0 && !empty($currency)) {
                            $currencyModel = Currency::firstOrCreate(
                                ['country' => $country ?: $this->extractCountryFromCurrency($currency), 'currency' => $currency],
                                ['equivalence' => $equivalence]
                            );
                            
                            CurrencyEquivalence::updateOrCreate(
                                ['currency_id' => $currencyModel->id, 'year' => $year, 'month' => $month],
                                ['equivalence' => $equivalence]
                            );
                            $rowsProcessed++;
                        }
                    }
                }
            }
            
            if ($rowsProcessed == 0) {
                $this->command->warn("  ⚠ No se encontraron datos para {$year}-{$month}");
            } else {
                $this->command->info("  ✓ Procesadas {$rowsProcessed} filas para {$year}-{$month}");
            }
            
        } catch (\Exception $e) {
            $this->command->error("Error al scrapear equivalencias para {$year}-{$month}: " . $e->getMessage());
        }
    }

    /**
     * Procesa una fila de tabla
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
            
            // Estructura real de la tabla de aduana.cl:
            // Columna 0: Número (1, 2, 3...)
            // Columna 1: PAIS
            // Columna 2: MONEDA
            // Columna 3: Equivalencia (puede estar en <h3> dentro del td)
            // Columna 4: Vacía
            
            if ($cells->count() >= 4) {
                // Estructura completa: Número | País | Moneda | Equivalencia | Vacía
                $country = trim($cells->eq(1)->text());
                $currency = trim($cells->eq(2)->text());
                // La equivalencia puede estar en un <h3> dentro del td, intentar extraerla
                $equivalenceCell = $cells->eq(3);
                $equivalenceText = trim($equivalenceCell->text());
                // Si está vacío, buscar dentro de h3
                if (empty($equivalenceText)) {
                    $h3 = $equivalenceCell->filter('h3');
                    if ($h3->count() > 0) {
                        $equivalenceText = trim($h3->text());
                    }
                }
            } elseif ($cells->count() >= 3) {
                // Estructura alternativa: País | Moneda | Equivalencia
                $country = trim($cells->eq(0)->text());
                $currency = trim($cells->eq(1)->text());
                $equivalenceCell = $cells->eq(2);
                $equivalenceText = trim($equivalenceCell->text());
                // Si está vacío, buscar dentro de h3
                if (empty($equivalenceText)) {
                    $h3 = $equivalenceCell->filter('h3');
                    if ($h3->count() > 0) {
                        $equivalenceText = trim($h3->text());
                    }
                }
            }
            // Estructura 2: Moneda | Equivalencia (2 columnas)
            elseif ($cells->count() >= 2) {
                // Intentar detectar qué columna es qué
                $col1 = trim($cells->eq(0)->text());
                $col2Cell = $cells->eq(1);
                $col2 = trim($col2Cell->text());
                
                // Si la segunda columna parece un número, es la equivalencia
                if (preg_match('/^[\d.,]+$/', $col2)) {
                    $currency = $col1;
                    $equivalenceText = $col2;
                } elseif (preg_match('/^[\d.,]+$/', $col1)) {
                    $currency = $col2;
                    $equivalenceText = $col1;
                } else {
                    // Asumir que es Moneda | Equivalencia
                    $currency = $col1;
                    $equivalenceText = $col2;
                    // Buscar en h3 si está vacío
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
            
            // Validar que la equivalencia sea razonable (entre 0.0001 y 100000)
            // Algunas monedas como el Yen o el Won tienen valores muy altos
            if (empty($currency) || $equivalence <= 0 || $equivalence >= 100000) {
                return false; // Saltar si no hay datos válidos
            }
            
            // Debug: mostrar información de la primera fila de cada página
            static $firstRowShown = [];
            $key = "{$year}-{$month}";
            if (!isset($firstRowShown[$key])) {
                $this->command->line("  [DEBUG] Primera fila: currency='{$currency}', equivalence={$equivalence}, country='{$country}', cells={$cells->count()}");
                $firstRowShown[$key] = true;
            }
            
            // Validar nuevamente antes de guardar (redundante pero seguro)
            if ($equivalence > 0 && $equivalence < 100000 && !empty($currency) && !empty($equivalenceText)) {
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
                    ['equivalence' => $equivalence] // Valor por defecto
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
                
                $this->command->info("  ✓ {$country} - {$currency}: {$equivalence} ({$year}-{$month})");
                return true;
            }
        } catch (\Exception $e) {
            // Mostrar errores para debugging
            $this->command->warn("  Error procesando fila: " . $e->getMessage());
        }
        return false;
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
        
        // Si no se encuentra, usar el nombre de la moneda como país
        return $currency;
    }

    /**
     * Extrae equivalencia de texto libre
     */
    private function extractEquivalenceFromText($text, $year, $month)
    {
        // Patrón para encontrar país, moneda y equivalencia en texto
        if (preg_match('/([A-Za-zÁÉÍÓÚáéíóúñÑ\s]+)\s+([A-Za-zÁÉÍÓÚáéíóúñÑ\s]+)\s+([\d.,]+)/', $text, $matches)) {
            $country = trim($matches[1]);
            $currency = trim($matches[2]);
            $equivalence = $this->parseEquivalence($matches[3]);
            
            if ($equivalence > 0) {
                $currencyModel = Currency::firstOrCreate(
                    [
                        'country' => $country,
                        'currency' => $currency
                    ],
                    ['equivalence' => $equivalence]
                );
                
                CurrencyEquivalence::updateOrCreate(
                    [
                        'currency_id' => $currencyModel->id,
                        'year' => $year,
                        'month' => $month
                    ],
                    ['equivalence' => $equivalence]
                );
            }
        }
    }

    /**
     * Convierte texto de equivalencia a número
     */
    private function parseEquivalence($text)
    {
        // Remover espacios y caracteres especiales
        $text = trim($text);
        // Reemplazar coma por punto si es necesario
        $text = str_replace(',', '.', $text);
        // Remover caracteres no numéricos excepto punto
        $text = preg_replace('/[^\d.]/', '', $text);
        
        return (float)$text;
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
            'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio',
            'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'
        ];
        
        $text = strtolower($text);
        foreach ($months as $index => $month) {
            if (stripos($text, $month) !== false) {
                return $index + 1;
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
