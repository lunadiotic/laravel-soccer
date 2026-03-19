<?php

namespace Database\Factories;

use App\Models\Player;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Player>
 */
class PlayerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'name' => $this->faker->name(),
            'height' => $this->faker->randomFloat(1, 160, 200),
            'weight' => $this->faker->randomFloat(1, 50, 100),
            'position' => $this->faker->randomElement(['penyerang', 'gelandang', 'bertahan', 'penjaga_gawang']),
            'jersey_number' => $this->faker->randomNumber(1, 99),
        ];
    }
}
