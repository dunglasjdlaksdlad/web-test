<?php

namespace Database\Factories\Dashboard_And_Reports;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Dashboard_And_Reports\District>
 */
class DistrictFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'name1' => fake()->name(),
            'area_id' => fake()->randomNumber(),
            'area_name' => fake()->name(),
        ];
    }
}
