<?php

namespace Database\Seeders;

use App\Models\Car;
use App\Models\Listing;
use App\Models\Message;
use App\Models\Photo;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Usuario fijo para que siempre puedas entrar con las mismas credenciales
        $me = User::factory()->create([
            'name' => 'Adrián',
            'email' => 'adrian@classiccars.test',
            'role' => 'seller',
        ]);

        $buyers = User::factory(10)->create(['role' => 'buyer']);
        $sellers = User::factory(5)->create(['role' => 'seller']);

        // 30 anuncios publicados, repartidos entre los vendedores
        $listings = Listing::factory(30)
            ->recycle($sellers)
            ->create();

        // Entre 3 y 6 fotos por anuncio, con el orden correlativo
        $listings->each(function (Listing $listing) {
            collect(range(0, rand(2, 5)))->each(
                fn ($i) => Photo::factory()->create([
                    'listing_id' => $listing->id,
                    'order' => $i,
                ])
            );
        });

        // Un coche con historial de precios: mismo coche, tres anuncios
        $classic = Car::factory()->create([
            'brand' => 'BMW',
            'model' => 'E30 325i',
            'year' => 1989,
            'mileage' => 186000,
        ]);

        Listing::factory()->sold()->create([
            'car_id' => $classic->id,
            'seller_id' => $sellers->first()->id,
            'price' => 21000,
            'published_at' => now()->subYears(2),
        ]);

        Listing::factory()->sold()->create([
            'car_id' => $classic->id,
            'seller_id' => $sellers->last()->id,
            'price' => 24500,
            'published_at' => now()->subYear(),
        ]);

        Listing::factory()->create([
            'car_id' => $classic->id,
            'seller_id' => $me->id,
            'price' => 28500,
        ]);

        // Favoritos: cada comprador guarda 3 anuncios al azar
        $buyers->each(
            fn (User $buyer) => $buyer->favorites()->attach(
                $listings->random(3)->pluck('id')
            )
        );

        // Algunos mensajes sobre anuncios concretos
        $listings->random(8)->each(function (Listing $listing) use ($buyers) {
            $buyer = $buyers->random();

            Message::factory()->create([
                'sender_id' => $buyer->id,
                'receiver_id' => $listing->seller_id,
                'listing_id' => $listing->id,
                'message' => '¿El coche sigue disponible?',
            ]);
        });
    }
}