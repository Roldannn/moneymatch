<?php

namespace Tests\Unit\Repositories;

use Tests\TestCase;
use App\Repositories\CurrencyEquivalenceRepository;
use App\Models\Currency;
use App\Models\CurrencyEquivalence;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CurrencyEquivalenceRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private CurrencyEquivalenceRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new CurrencyEquivalenceRepository();
    }

    /**
     * Prueba obtener años disponibles
     */
    public function test_get_available_years(): void
    {
        $currency = Currency::factory()->create();
        
        CurrencyEquivalence::factory()->create(['currency_id' => $currency->id, 'year' => 2023]);
        CurrencyEquivalence::factory()->create(['currency_id' => $currency->id, 'year' => 2024]);
        CurrencyEquivalence::factory()->create(['currency_id' => $currency->id, 'year' => 2025]);

        $result = $this->repository->getAvailableYears();

        $this->assertCount(3, $result);
        $this->assertTrue($result->contains(2023));
        $this->assertTrue($result->contains(2024));
        $this->assertTrue($result->contains(2025));
    }

    /**
     * Prueba obtener meses disponibles
     */
    public function test_get_available_months(): void
    {
        $currency = Currency::factory()->create();
        
        CurrencyEquivalence::factory()->create(['currency_id' => $currency->id, 'month' => 1]);
        CurrencyEquivalence::factory()->create(['currency_id' => $currency->id, 'month' => 3]);
        CurrencyEquivalence::factory()->create(['currency_id' => $currency->id, 'month' => 5]);

        $result = $this->repository->getAvailableMonths();

        $this->assertGreaterThanOrEqual(3, $result->count());
        $this->assertTrue($result->contains(1));
        $this->assertTrue($result->contains(3));
        $this->assertTrue($result->contains(5));
    }

    /**
     * Prueba obtener meses agrupados por año
     */
    public function test_get_months_by_year(): void
    {
        $currency = Currency::factory()->create();
        
        CurrencyEquivalence::factory()->create(['currency_id' => $currency->id, 'year' => 2024, 'month' => 1]);
        CurrencyEquivalence::factory()->create(['currency_id' => $currency->id, 'year' => 2024, 'month' => 2]);
        CurrencyEquivalence::factory()->create(['currency_id' => $currency->id, 'year' => 2025, 'month' => 1]);

        $result = $this->repository->getMonthsByYear();

        $this->assertTrue($result->has(2024));
        $this->assertTrue($result->has(2025));
        $this->assertCount(2, $result[2024]);
        $this->assertCount(1, $result[2025]);
    }

    /**
     * Prueba verificar existencia de año
     */
    public function test_year_exists(): void
    {
        $currency = Currency::factory()->create();
        CurrencyEquivalence::factory()->create(['currency_id' => $currency->id, 'year' => 2024]);

        $this->assertTrue($this->repository->yearExists(2024));
        $this->assertFalse($this->repository->yearExists(2023));
    }

    /**
     * Prueba verificar existencia de mes para año específico
     */
    public function test_month_exists(): void
    {
        $currency = Currency::factory()->create();
        CurrencyEquivalence::factory()->create([
            'currency_id' => $currency->id,
            'year' => 2024,
            'month' => 3
        ]);

        $this->assertTrue($this->repository->monthExists(2024, 3));
        $this->assertFalse($this->repository->monthExists(2024, 4));
        $this->assertFalse($this->repository->monthExists(2023, 3));
    }

    /**
     * Prueba buscar equivalencia por moneda, año y mes
     */
    public function test_find_by_currency_and_date(): void
    {
        $currency = Currency::factory()->create();
        $equivalence = CurrencyEquivalence::factory()->create([
            'currency_id' => $currency->id,
            'year' => 2024,
            'month' => 6,
            'equivalence' => 1.5
        ]);

        $result = $this->repository->findByCurrencyAndDate($currency->id, 2024, 6);

        $this->assertNotNull($result);
        $this->assertEquals($equivalence->id, $result->id);
        $this->assertEquals(1.5, $result->equivalence);
    }

    /**
     * Prueba crear o actualizar equivalencia
     */
    public function test_update_or_create(): void
    {
        $currency = Currency::factory()->create();

        $result = $this->repository->updateOrCreate(
            ['currency_id' => $currency->id, 'year' => 2024, 'month' => 1],
            ['equivalence' => 1.25]
        );

        $this->assertNotNull($result->id);
        $this->assertEquals(1.25, $result->equivalence);

        $updated = $this->repository->updateOrCreate(
            ['currency_id' => $currency->id, 'year' => 2024, 'month' => 1],
            ['equivalence' => 1.30]
        );

        $this->assertEquals($result->id, $updated->id);
        $this->assertEquals(1.30, $updated->equivalence);
        $this->assertEquals(1, CurrencyEquivalence::count());
    }
}

