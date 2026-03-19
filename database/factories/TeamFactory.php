<?php

namespace Database\Factories;

use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Team>
 */
class TeamFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->company() . ' FC',
            'logo' => null,
            'founded_year' => $this->faker->numberBetween(1950, date('Y')),
            'address' => $this->faker->address(),
            'city' => $this->faker->city(),
        ];
    }
}
