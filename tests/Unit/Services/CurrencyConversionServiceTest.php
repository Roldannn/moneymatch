<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\CurrencyConversionService;
use App\Repositories\CurrencyRepository;
use App\Repositories\CurrencyEquivalenceRepository;
use App\Models\Currency;
use App\Models\CurrencyEquivalence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

class CurrencyConversionServiceTest extends TestCase
{
    use RefreshDatabase;

    private CurrencyConversionService $service;
    private CurrencyRepository $currencyRepository;
    private CurrencyEquivalenceRepository $equivalenceRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->currencyRepository = new CurrencyRepository();
        $this->equivalenceRepository = new CurrencyEquivalenceRepository();
        $this->service = new CurrencyConversionService(
            $this->currencyRepository,
            $this->equivalenceRepository
        );
    }

    /**
     * Prueba normalizar monto con coma
     */
    public function test_normalize_amount_with_comma(): void
    {
        $result = $this->service->normalizeAmount('1234,56');
        $this->assertEquals(1234.56, $result);
    }

    /**
     * Prueba normalizar monto con formato europeo (punto miles, coma decimal)
     */
    public function test_normalize_amount_european_format(): void
    {
        $result = $this->service->normalizeAmount('1.234,56');
        $this->assertEquals(1234.56, $result);
    }

    /**
     * Prueba normalizar monto con punto
     */
    public function test_normalize_amount_with_dot(): void
    {
        $result = $this->service->normalizeAmount('1234.56');
        $this->assertEquals(1234.56, $result);
    }

    /**
     * Prueba validar disponibilidad de fecha cuando existe
     */
    public function test_validate_date_availability_exists(): void
    {
        $currency = Currency::factory()->create();
        CurrencyEquivalence::factory()->create([
            'currency_id' => $currency->id,
            'year' => 2024,
            'month' => 6
        ]);

        $errors = $this->service->validateDateAvailability(2024, 6);

        $this->assertEmpty($errors);
    }

    /**
     * Prueba validar disponibilidad de fecha cuando no existe año
     */
    public function test_validate_date_availability_year_not_exists(): void
    {
        $errors = $this->service->validateDateAvailability(2020, 1);

        $this->assertArrayHasKey('year', $errors);
        $this->assertStringContainsString('no tiene datos disponibles', $errors['year']);
    }

    /**
     * Prueba validar disponibilidad de fecha cuando no existe mes
     */
    public function test_validate_date_availability_month_not_exists(): void
    {
        $currency = Currency::factory()->create();
        CurrencyEquivalence::factory()->create([
            'currency_id' => $currency->id,
            'year' => 2024,
            'month' => 1
        ]);

        $errors = $this->service->validateDateAvailability(2024, 6);

        $this->assertArrayHasKey('month', $errors);
        $this->assertStringContainsString('no tiene datos disponibles', $errors['month']);
    }

    /**
     * Prueba obtener valor de equivalencia desde equivalencia específica
     */
    public function test_get_equivalence_value_from_equivalence(): void
    {
        $currency = Currency::factory()->create(['equivalence' => 1.0]);
        CurrencyEquivalence::factory()->create([
            'currency_id' => $currency->id,
            'year' => 2024,
            'month' => 6,
            'equivalence' => 1.5
        ]);

        $result = $this->service->getEquivalenceValue($currency->id, 2024, 6);

        $this->assertEquals(1.5, $result);
    }

    /**
     * Prueba obtener valor de equivalencia desde moneda cuando no hay equivalencia específica
     */
    public function test_get_equivalence_value_from_currency_fallback(): void
    {
        $currency = Currency::factory()->create(['equivalence' => 1.2]);

        $result = $this->service->getEquivalenceValue($currency->id, 2024, 6);

        $this->assertEquals(1.2, $result);
    }

    /**
     * Prueba obtener valor de equivalencia por defecto cuando es cero
     */
    public function test_get_equivalence_value_default_when_zero(): void
    {
        $currency = Currency::factory()->create(['equivalence' => 0]);

        $result = $this->service->getEquivalenceValue($currency->id, 2024, 6);

        $this->assertEquals(1.0, $result);
    }

    /**
     * Prueba obtener valor de equivalencia por defecto cuando no existe moneda
     */
    public function test_get_equivalence_value_default_when_currency_not_found(): void
    {
        $result = $this->service->getEquivalenceValue(999, 2024, 6);

        $this->assertEquals(1.0, $result);
    }

    /**
     * Prueba convertir a dólares
     * Si 1 USD = 1.5 unidades de la moneda extranjera, entonces 1000 unidades = 1000/1.5 = 666.67 USD
     */
    public function test_convert_to_dollars(): void
    {
        $result = $this->service->convertToDollars(1000, 1.5);

        $this->assertEquals(666.6666666666666, $result);
    }

    /**
     * Prueba convertir pesos argentinos a dólares con tasa específica
     * 1000 ARS con tasa 1,168.666700 debería dar aproximadamente 0.855675959621336 USD
     */
    public function test_convert_to_dollars_argentine_pesos(): void
    {
        $result = $this->service->convertToDollars(1000, 1168.666700);

        $this->assertEqualsWithDelta(0.855675959621336, $result, 0.0000000001);
    }

    /**
     * Prueba convertir a dólares cuando la equivalencia es cero
     */
    public function test_convert_to_dollars_zero_equivalence(): void
    {
        $result = $this->service->convertToDollars(1000, 0);

        $this->assertEquals(0, $result);
    }

    /**
     * Prueba obtener nombre del mes
     */
    public function test_get_month_name(): void
    {
        $this->assertEquals('Enero', $this->service->getMonthName(1));
        $this->assertEquals('Junio', $this->service->getMonthName(6));
        $this->assertEquals('Diciembre', $this->service->getMonthName(12));
    }
}

