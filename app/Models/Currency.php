<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    use HasFactory;

    protected $table = 'currencies';
    protected $fillable = ['country', 'currency', 'equivalence'];

    public function equivalences()
    {
        return $this->hasMany(CurrencyEquivalence::class);
    }

    public function getEquivalenceForDate($year, $month)
    {
        return $this->equivalences()
            ->where('year', $year)
            ->where('month', $month)
            ->first();
    }
}
