<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    use HasFactory;

    protected $table = 'currencies';
    protected $fillable = ['country', 'currency', 'equivalence'];

    /**
     * Relación con las equivalencias de la moneda
     */
    public function equivalences()
    {
        return $this->hasMany(CurrencyEquivalence::class);
    }

    /**
     * Obtiene la equivalencia para una fecha específica
     */
    public function getEquivalenceForDate(int $year, int $month)
    {
        return $this->equivalences()
            ->where('year', $year)
            ->where('month', $month)
            ->first();
    }
}
