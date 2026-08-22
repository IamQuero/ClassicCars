<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable
{
    use HasFactory;

    protected $fillable = ['name', 'email', 'password', 'role'];

    protected $hidden = ['password', 'remember_token'];

    // Anuncios que este usuario ha publicado
    public function listings(): HasMany
    {
        return $this->hasMany(Listing::class, 'seller_id');
    }

    // Anuncios que este usuario ha guardado como favoritos
    public function favorites(): BelongsToMany
    {
        return $this->belongsToMany(Listing::class, 'favorites')
                    ->withTimestamps();
    }

    // Mensajes enviados y recibidos
    public function sentMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function receivedMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'receiver_id');
    }
}