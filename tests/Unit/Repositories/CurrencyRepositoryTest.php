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

}

