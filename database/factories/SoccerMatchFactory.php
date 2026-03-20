<?php

namespace Database\Factories;

use App\Models\SoccerMatch;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SoccerMatch>
 */
class SoccerMatchFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'match_date'   => $this->faker->dateTimeBetween('-1 year', '+1 year')->format('Y-m-d'),
            'match_time'   => $this->faker->time('H:i'),
            'home_team_id' => Team::factory(),
            'away_team_id' => Team::factory(),
            'status'       => 'scheduled',
        ];
    }

    public function finished(): static
    {
        return $this->state(['status' => 'finished']);
    }
}
