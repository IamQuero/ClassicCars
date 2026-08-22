<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class CarFactory extends Factory
{
    public function definition(): array
    {
        $models = [
            'BMW' => ['E30 325i', 'E28 535i', 'E24 635 CSi', '2002 tii'],
            'Porsche' => ['911 3.2 Carrera', '944 S2', '928 S4'],
            'Mercedes-Benz' => ['190E 2.3-16', 'W123 280E', 'SL 300'],
            'Alfa Romeo' => ['Giulia GT', 'Spider Duetto', '75 Twin Spark'],
            'Volkswagen' => ['Golf GTI Mk1', 'Escarabajo 1303', 'Corrado VR6'],
        ];

        $brand = fake()->randomElement(array_keys($models));

        return [
            'brand' => $brand,
            'model' => fake()->randomElement($models[$brand]),
            'generation' => fake()->optional(0.6)->bothify('Serie ##'),
            'year' => fake()->numberBetween(1965, 1995),
            'mileage' => fake()->numberBetween(20000, 320000),
            'engine' => fake()->randomElement(['1.6', '2.0', '2.5', '3.0', '3.2']),
            'horsepower' => fake()->numberBetween(75, 300),
            'transmission' => fake()->randomElement(['manual', 'automatic']),
            'fuel' => fake()->randomElement(['petrol', 'diesel']),
            'description' => fake()->paragraph(4),
        ];
    }
}