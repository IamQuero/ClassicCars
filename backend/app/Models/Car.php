<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Car extends Model
{
    use HasFactory;
    protected $fillable = [
        'brand', 'model', 'generation', 'year', 'mileage',
        'engine', 'horsepower', 'transmission', 'fuel', 'description',
    ];

    // Todos los anuncios históricos de este coche
    public function listings(): HasMany
    {
        return $this->hasMany(Listing::class);
    }

    // El anuncio activo ahora mismo (si lo hay)
    public function activeListing(): HasOne
    {
        return $this->hasOne(Listing::class)->where('status', 'published');
    }
}