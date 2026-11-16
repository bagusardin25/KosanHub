<?php

namespace Database\Factories;

use App\Models\Property;
use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Room>
 */
class RoomFactory extends Factory
{
    public function definition(): array
    {
        return [
            'property_id' => Property::factory(),
            'nomor' => strtoupper(fake()->unique()->bothify('?##')),
            'harga_bulanan' => 1_200_000,
        ];
    }
}
