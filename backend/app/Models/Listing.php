<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Listing extends Model
{
    use HasFactory;

    protected $fillable = [
        'car_id',
        'seller_id',
        'price',
        'status',
        'published_at',
        'expires_at',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'published_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function car(): BelongsTo
    {
        return $this->belongsTo(Car::class);
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(Photo::class)->orderBy('order');
    }

    // Usuarios que han guardado este anuncio
    public function favoritedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'favorites')
            ->withTimestamps();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when(
                $filters['brand'] ?? null,
                fn ($q, $brand) => $q->whereHas('car', fn ($c) => self::whereLike($c, 'brand', $brand))
            )
            ->when(
                $filters['model'] ?? null,
                fn ($q, $model) => $q->whereHas('car', fn ($c) => self::whereLike($c, 'model', $model))
            )
            ->when(
                $filters['fuel'] ?? null,
                fn ($q, $fuel) => $q->whereHas('car', fn ($c) => $c->where('fuel', $fuel))
            )
            ->when(
                $filters['transmission'] ?? null,
                fn ($q, $t) => $q->whereHas('car', fn ($c) => $c->where('transmission', $t))
            )
            ->when(
                $filters['year_min'] ?? null,
                fn ($q, $year) => $q->whereHas('car', fn ($c) => $c->where('year', '>=', $year))
            )
            ->when(
                $filters['year_max'] ?? null,
                fn ($q, $year) => $q->whereHas('car', fn ($c) => $c->where('year', '<=', $year))
            )
            ->when(
                $filters['mileage_max'] ?? null,
                fn ($q, $km) => $q->whereHas('car', fn ($c) => $c->where('mileage', '<=', $km))
            )
            ->when($filters['price_min'] ?? null, fn ($q, $p) => $q->where('price', '>=', $p))
            ->when($filters['price_max'] ?? null, fn ($q, $p) => $q->where('price', '<=', $p));
    }

    /**
     * Búsqueda parcial sin distinguir mayúsculas, válida en SQLite y en Postgres
     * (ilike es exclusivo de Postgres y rompía los tests sobre SQLite).
     */
    protected static function whereLike(Builder $query, string $column, string $value): Builder
    {
        return $query->whereRaw(
            'LOWER('.$query->getGrammar()->wrap($column).') LIKE ?',
            ['%'.mb_strtolower($value).'%']
        );
    }

    public function scopeSorted(Builder $query, ?string $sort): Builder
    {
        return match ($sort) {
            'price_asc' => $query->orderBy('price'),
            'price_desc' => $query->orderByDesc('price'),
            'year_desc' => $query->orderByDesc(
                Car::select('year')->whereColumn('cars.id', 'listings.car_id')
            ),
            default => $query->latest('published_at'),
        };
    }
}
