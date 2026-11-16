<?php

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tenant>
 */
class TenantFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nama' => fake()->name(),
            'telepon' => '08'.fake()->numerify('##########'),
            'email' => fake()->unique()->safeEmail(),
        ];
    }
}
