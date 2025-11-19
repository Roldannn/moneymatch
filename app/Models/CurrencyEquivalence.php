<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CurrencyEquivalence extends Model
{
    use HasFactory;

    protected $fillable = ['currency_id', 'year', 'month', 'equivalence'];

    /**
     * Relación con la moneda
     */
    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }
}
