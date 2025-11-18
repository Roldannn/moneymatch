<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Primero ejecutar CurrencySeeder para crear las monedas base
        $this->call([
            CurrencySeeder::class,
        ]);

        // Luego ejecutar CurrencyEquivalenceSeeder para scrapear y cargar equivalencias
        // Nota: Este seeder puede tardar mucho tiempo ya que hace web scraping
        // $this->call([
        //     CurrencyEquivalenceSeeder::class,
        // ]);
    }
}
