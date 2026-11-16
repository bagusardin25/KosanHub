<?php

namespace Database\Factories;

use App\Models\Property;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Property>
 */
class PropertyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nama' => 'Kos '.fake()->lastName(),
            'alamat' => fake()->streetAddress(),
            'nama_pemilik' => fake()->name(),
        ];
    }
}
