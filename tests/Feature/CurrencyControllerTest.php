<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Currency;
use App\Models\CurrencyEquivalence;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CurrencyControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Prueba mostrar la página principal
     */
    public function test_index_displays_form(): void
    {
        $currency = Currency::factory()->create();
        CurrencyEquivalence::factory()->create(['currency_id' => $currency->id]);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertViewIs('currency.index');
        $response->assertSee('MoneyMatch');
        $response->assertSee('Calculadora de Divisas');
    }

    /**
     * Prueba conversión exitosa
     */
    public function test_convert_success(): void
    {
        $currency = Currency::factory()->create(['currency' => 'Dólar']);
        CurrencyEquivalence::factory()->create([
            'currency_id' => $currency->id,
            'year' => 2024,
            'month' => 6,
            'equivalence' => 1.5
        ]);

        $response = $this->post('/convert', [
            'country' => $currency->id,
            'amount' => '1000',
            'year' => 2024,
            'month' => 6
        ]);

        $response->assertRedirect('/');
        $response->assertSessionHas('result');
        
        $result = session('result');
        $this->assertEquals('Dólar', $result['currencyName']);
        $this->assertEquals(1.5, $result['rate']);
        $this->assertEquals(1500, $result['converted']);
        $this->assertEquals(1000, $result['amount']);
    }

    /**
     * Prueba conversión con monto con coma decimal
     */
    public function test_convert_with_comma_decimal(): void
    {
        $currency = Currency::factory()->create();
        CurrencyEquivalence::factory()->create([
            'currency_id' => $currency->id,
            'year' => 2024,
            'month' => 6,
            'equivalence' => 2.0
        ]);

        $response = $this->post('/convert', [
            'country' => $currency->id,
            'amount' => '1.234,56',
            'year' => 2024,
            'month' => 6
        ]);

        $response->assertRedirect('/');
        $response->assertSessionHas('result');
        
        $result = session('result');
        $this->assertEquals(2469.12, $result['converted']);
    }

    /**
     * Prueba validación cuando falta país
     */
    public function test_convert_validation_missing_country(): void
    {
        $response = $this->post('/convert', [
            'amount' => '1000',
            'year' => 2024,
            'month' => 6
        ]);

        $response->assertSessionHasErrors(['country']);
    }

    /**
     * Prueba validación cuando falta monto
     */
    public function test_convert_validation_missing_amount(): void
    {
        $currency = Currency::factory()->create();
        
        $response = $this->post('/convert', [
            'country' => $currency->id,
            'year' => 2024,
            'month' => 6
        ]);

        $response->assertSessionHasErrors(['amount']);
    }

    /**
     * Prueba validación cuando año no tiene datos
     */
    public function test_convert_validation_year_not_exists(): void
    {
        $currency = Currency::factory()->create();

        $response = $this->post('/convert', [
            'country' => $currency->id,
            'amount' => '1000',
            'year' => 2020,
            'month' => 1
        ]);

        $response->assertSessionHasErrors(['year']);
    }

    /**
     * Prueba validación cuando mes no tiene datos
     */
    public function test_convert_validation_month_not_exists(): void
    {
        $currency = Currency::factory()->create();
        CurrencyEquivalence::factory()->create([
            'currency_id' => $currency->id,
            'year' => 2024,
            'month' => 1
        ]);

        $response = $this->post('/convert', [
            'country' => $currency->id,
            'amount' => '1000',
            'year' => 2024,
            'month' => 6
        ]);

        $response->assertSessionHasErrors(['month']);
    }

    /**
     * Prueba conversión usando equivalencia de moneda cuando no hay equivalencia específica
     */
    public function test_convert_uses_currency_equivalence_fallback(): void
    {
        $currency = Currency::factory()->create(['equivalence' => 1.25]);
        
        CurrencyEquivalence::factory()->create([
            'currency_id' => $currency->id,
            'year' => 2024,
            'month' => 1
        ]);

        $response = $this->post('/convert', [
            'country' => $currency->id,
            'amount' => '1000',
            'year' => 2024,
            'month' => 1
        ]);

        $response->assertRedirect('/');
        $response->assertSessionHas('result');
        
        $result = session('result');
        $this->assertArrayHasKey('rate', $result);
        $this->assertArrayHasKey('converted', $result);
    }
}

