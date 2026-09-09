<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Photo extends Model
{
    use HasFactory;

    protected $fillable = ['listing_id', 'path', 'thumbnail_path', 'type', 'order'];

    protected static function booted(): void
    {
        static::deleting(function (Photo $photo) {
            Storage::disk('public')->delete(array_filter([$photo->path, $photo->thumbnail_path]));
        });
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }
}
