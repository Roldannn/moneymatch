<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\CurrencyDataService;
use App\Repositories\CurrencyRepository;
use App\Repositories\CurrencyEquivalenceRepository;
use App\Models\Currency;
use App\Models\CurrencyEquivalence;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CurrencyDataServiceTest extends TestCase
{
    use RefreshDatabase;

    private CurrencyDataService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CurrencyDataService(
            new CurrencyRepository(),
            new CurrencyEquivalenceRepository()
        );
    }

    /**
     * Prueba obtener datos para la vista principal
     */
    public function test_get_index_data(): void
    {
        $currency1 = Currency::factory()->create(['country' => 'Estados Unidos']);
        $currency2 = Currency::factory()->create(['country' => 'México']);

        CurrencyEquivalence::factory()->create([
            'currency_id' => $currency1->id,
            'year' => 2024,
            'month' => 1
        ]);
        CurrencyEquivalence::factory()->create([
            'currency_id' => $currency2->id,
            'year' => 2024,
            'month' => 2
        ]);

        $result = $this->service->getIndexData();

        $this->assertArrayHasKey('currencies', $result);
        $this->assertArrayHasKey('years', $result);
        $this->assertArrayHasKey('months', $result);
        $this->assertArrayHasKey('monthNames', $result);
        $this->assertArrayHasKey('monthsByYear', $result);

        $this->assertCount(2, $result['currencies']);
        $this->assertTrue($result['years']->contains(2024));
        $this->assertArrayHasKey(1, $result['monthNames']);
        $this->assertEquals('Enero', $result['monthNames'][1]);
    }
}

