<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CurrencyEquivalence extends Model
{
    use HasFactory;

    protected $fillable = ['currency_id', 'year', 'month', 'equivalence'];

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }
}
