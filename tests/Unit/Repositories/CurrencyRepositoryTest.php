<?php

namespace Tests\Unit\Repositories;

use Tests\TestCase;
use App\Repositories\CurrencyRepository;
use App\Models\Currency;
use App\Models\CurrencyEquivalence;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CurrencyRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private CurrencyRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new CurrencyRepository();
    }

    /**
     * Prueba obtener monedas con equivalencias registradas
     */
    public function test_get_currencies_with_equivalences(): void
    {
        $currency1 = Currency::factory()->create(['country' => 'Estados Unidos', 'currency' => 'Dólar']);
        $currency2 = Currency::factory()->create(['country' => 'México', 'currency' => 'Peso']);
        $currency3 = Currency::factory()->create(['country' => 'Brasil', 'currency' => 'Real']);

        CurrencyEquivalence::factory()->create(['currency_id' => $currency1->id]);
        CurrencyEquivalence::factory()->create(['currency_id' => $currency2->id]);

        $result = $this->repository->getCurrenciesWithEquivalences();

        $this->assertCount(2, $result);
        $this->assertTrue($result->contains('id', $currency1->id));
        $this->assertTrue($result->contains('id', $currency2->id));
        $this->assertFalse($result->contains('id', $currency3->id));
    }

    /**
     * Prueba buscar moneda por ID
     */
    public function test_find_by_id(): void
    {
        $currency = Currency::factory()->create();

        $result = $this->repository->findById($currency->id);

        $this->assertNotNull($result);
        $this->assertEquals($currency->id, $result->id);
    }

    /**
     * Prueba buscar moneda por ID inexistente
     */
    public function test_find_by_id_not_found(): void
    {
        $result = $this->repository->findById(999);

        $this->assertNull($result);
    }

    /**
     * Prueba crear o buscar moneda existente
     */
    public function test_first_or_create_existing(): void
    {
        $currency = Currency::factory()->create([
            'country' => 'Estados Unidos',
            'currency' => 'Dólar'
        ]);

        $result = $this->repository->firstOrCreate(
            ['country' => 'Estados Unidos', 'currency' => 'Dólar'],
            ['equivalence' => 1.0]
        );

        $this->assertEquals($currency->id, $result->id);
        $this->assertEquals(1, Currency::count());
    }

    /**
     * Prueba crear nueva moneda cuando no existe
     */
    public function test_first_or_create_new(): void
    {
        $result = $this->repository->firstOrCreate(
            ['country' => 'Chile', 'currency' => 'Peso Chileno'],
            ['equivalence' => 0.001]
        );

        $this->assertNotNull($result->id);
        $this->assertEquals('Chile', $result->country);
        $this->assertEquals('Peso Chileno', $result->currency);
        $this->assertEquals(1, Currency::count());
    }
}

