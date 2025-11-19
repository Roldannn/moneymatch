<?php

namespace App\Services;

use Goutte\Client;
use App\Repositories\CurrencyRepository;
use App\Repositories\CurrencyEquivalenceRepository;

class CurrencyScrapingService
{
    private Client $client;
    private CurrencyRepository $currencyRepository;
    private CurrencyEquivalenceRepository $equivalenceRepository;

    public function __construct(
        CurrencyRepository $currencyRepository,
        CurrencyEquivalenceRepository $equivalenceRepository
    ) {
        $this->client = new Client();
        $this->currencyRepository = $currencyRepository;
        $this->equivalenceRepository = $equivalenceRepository;
    }

    /**
     * Scrapea la página del año actual (2025)
     */
    public function scrapeCurrentYear(string $url, callable $progressCallback = null): int
    {
        $crawler = $this->client->request('GET', $url);
        $rowsProcessed = 0;

        $crawler->filter('table tr')->each(function ($row) use (&$rowsProcessed, $progressCallback) {
            $cells = $row->filter('td');
            
            $cells->each(function ($cell) use ($row, &$rowsProcessed, $progressCallback) {
                $cellText = trim($cell->text());
                if (preg_match('/^(\d{4})$/', $cellText, $yearMatches)) {
                    $year = (int)$yearMatches[1];
                    
                    if ($year == 2025) {
                        $row->filter('a')->each(function ($link) use ($year, &$rowsProcessed, $progressCallback) {
                            $href = $link->attr('href');
                            $text = trim($link->text());
                            
                            $month = $this->extractMonthFromText($text);
                            if ($month && $href) {
                                $fullUrl = $this->buildFullUrl($href);
                                $result = $this->scrapeEquivalencePage($fullUrl, $year, $month);
                                if ($result) {
                                    $rowsProcessed += $result;
                                    if ($progressCallback) {
                                        $progressCallback($year, $month, $result);
                                    }
                                }
                            }
                        });
                    }
                }
            });
        });

        $crawler->filter('a')->each(function ($node) use (&$rowsProcessed, $progressCallback) {
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
                                if ($progressCallback) {
                                    $progressCallback($year, $month, $result);
                                }
                            }
                        }
                    }
                }
            }
        });

        return $rowsProcessed;
    }

    /**
     * Scrapea un año específico del histórico
     */
    public function scrapeHistoricalYear(string $url, int $targetYear, callable $progressCallback = null): int
    {
        $crawler = $this->client->request('GET', $url);
        $rowsProcessed = 0;

        $crawler->filter('table tr')->each(function ($row) use ($targetYear, &$rowsProcessed, $progressCallback) {
            $cells = $row->filter('td');
            
            if ($cells->count() > 0) {
                $firstCell = trim($cells->eq(0)->text());
                
                if (preg_match('/^(\d{4})$/', $firstCell, $yearMatches)) {
                    $year = (int)$yearMatches[1];
                    
                    if ($year == $targetYear) {
                        for ($i = 1; $i < $cells->count(); $i++) {
                            $cell = $cells->eq($i);
                            $cell->filter('a')->each(function ($link) use ($year, &$rowsProcessed, $progressCallback) {
                                $href = $link->attr('href');
                                $text = trim($link->text());
                                
                                $month = $this->extractMonthFromText($text);
                                if ($month && $href) {
                                    $fullUrl = $this->buildFullUrl($href);
                                    $result = $this->scrapeEquivalencePage($fullUrl, $year, $month);
                                    if ($result) {
                                        $rowsProcessed += $result;
                                        if ($progressCallback) {
                                            $progressCallback($year, $month, $result);
                                        }
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
                                                if ($progressCallback) {
                                                    $progressCallback($yearFromUrl, $month, $result);
                                                }
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

        return $rowsProcessed;
    }

    /**
     * Scrapea la página histórica completa
     */
    public function scrapeHistorical(string $url, callable $progressCallback = null): int
    {
        $crawler = $this->client->request('GET', $url);
        $totalRowsProcessed = 0;

        $crawler->filter('table tr')->each(function ($row) use (&$totalRowsProcessed, $progressCallback) {
            $cells = $row->filter('td');
            
            if ($cells->count() > 0) {
                $firstCell = trim($cells->eq(0)->text());
                
                if (preg_match('/^(\d{4})$/', $firstCell, $yearMatches)) {
                    $year = (int)$yearMatches[1];
                    
                    if ($year >= 2004 && $year <= 2024) {
                        for ($i = 1; $i < $cells->count(); $i++) {
                            $cell = $cells->eq($i);
                            $cell->filter('a')->each(function ($link) use ($year, &$totalRowsProcessed, $progressCallback) {
                                $href = $link->attr('href');
                                $text = trim($link->text());
                                
                                $month = $this->extractMonthFromText($text);
                                if ($month && $href) {
                                    $fullUrl = $this->buildFullUrl($href);
                                    $result = $this->scrapeEquivalencePage($fullUrl, $year, $month);
                                    if ($result) {
                                        $totalRowsProcessed += $result;
                                        if ($progressCallback) {
                                            $progressCallback($year, $month, $result);
                                        }
                                    }
                                } elseif ($href && strpos($href, 'equivalencias') !== false) {
                                    if (preg_match('/equivalencias-(enero|febrero|marzo|abril|mayo|junio|julio|agosto|septiembre|octubre|noviembre|diciembre)-(\d{4})/i', $href, $matches)) {
                                        $month = $this->monthNameToNumber($matches[1]);
                                        $yearFromUrl = (int)$matches[2];
                                        $fullUrl = $this->buildFullUrl($href);
                                        $result = $this->scrapeEquivalencePage($fullUrl, $yearFromUrl, $month);
                                        if ($result) {
                                            $totalRowsProcessed += $result;
                                            if ($progressCallback) {
                                                $progressCallback($yearFromUrl, $month, $result);
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

        return $totalRowsProcessed;
    }

    /**
     * Scrapea una página de equivalencias específica
     */
    private function scrapeEquivalencePage(string $url, int $year, int $month): int
    {
        try {
            $crawler = $this->client->request('GET', $url);
            $rowsProcessed = 0;

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
            return 0;
        }
    }

    /**
     * Procesa una fila de tabla extrayendo país, moneda y equivalencia
     */
    private function processTableRow($cells, int $year, int $month): bool
    {
        try {
            if ($cells->count() < 2) {
                return false;
            }

            $data = $this->extractRowData($cells);
            
            if (!$this->isValidRow($data)) {
                return false;
            }

            $equivalence = $this->parseEquivalence($data['equivalenceText']);

            if (!$this->isValidEquivalence($equivalence, $data['currency'])) {
                return false;
            }

            $this->saveEquivalence($data, $equivalence, $year, $month);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Extrae datos de país, moneda y equivalencia de las celdas
     */
    private function extractRowData($cells): array
    {
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

        return [
            'country' => $country,
            'currency' => $currency,
            'equivalenceText' => $equivalenceText
        ];
    }

    /**
     * Valida si una fila contiene datos válidos (no es encabezado)
     */
    private function isValidRow(array $data): bool
    {
        $allText = strtolower($data['country'] . ' ' . $data['currency'] . ' ' . $data['equivalenceText']);
        
        $invalidKeywords = ['país', 'country', 'moneda', 'currency', 'equivalencia', 'equivalence', 'total'];
        
        foreach ($invalidKeywords as $keyword) {
            if (stripos($allText, $keyword) !== false) {
                return false;
            }
        }

        return !empty($data['currency']);
    }

    /**
     * Valida que la equivalencia sea un valor razonable
     */
    private function isValidEquivalence(float $equivalence, string $currency): bool
    {
        return $equivalence > 0 && $equivalence < 100000 && !empty($currency);
    }

    /**
     * Guarda o actualiza la equivalencia en la base de datos
     */
    private function saveEquivalence(array $data, float $equivalence, int $year, int $month): void
    {
        if (empty($data['country']) || strlen($data['country']) < 3) {
            $data['country'] = $this->extractCountryFromCurrency($data['currency']);
        }

        $currencyModel = $this->currencyRepository->firstOrCreate(
            [
                'country' => $data['country'],
                'currency' => $data['currency']
            ],
            ['equivalence' => $equivalence]
        );

        $this->equivalenceRepository->updateOrCreate(
            [
                'currency_id' => $currencyModel->id,
                'year' => $year,
                'month' => $month
            ],
            ['equivalence' => $equivalence]
        );
    }

    /**
     * Convierte texto de equivalencia a número
     */
    private function parseEquivalence(string $text): float
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
    private function extractCountryFromCurrency(string $currency): string
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
    private function monthNameToNumber(string $monthName): ?int
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
    private function extractMonthFromText(string $text): ?int
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
    private function buildFullUrl(string $href): string
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

