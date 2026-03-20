<?php

namespace Tests\Feature;

use App\Models\SoccerMatch;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class MatchTest extends TestCase
{
    use RefreshDatabase;

    private function matchData(int $homeId, int $awayId, array $override = []): array
    {
        return array_merge([
            'match_date'   => '2024-08-10',
            'match_time'   => '15:30',
            'home_team_id' => $homeId,
            'away_team_id' => $awayId,
        ], $override);
    }

    public function test_can_get_list_of_matches(): void
    {
        $this->actingAsAdmin();
        SoccerMatch::factory()->count(3)->create();

        $response = $this->getJson('/api/matches');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(3, 'data.data');
    }

    public function test_can_get_single_match(): void
    {
        $this->actingAsAdmin();
        $match = SoccerMatch::factory()->create();

        $response = $this->getJson("/api/matches/{$match->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $match->id);
    }

    public function test_can_create_match(): void
    {
        $this->actingAsAdmin();
        $home = Team::factory()->create();
        $away = Team::factory()->create();

        $response = $this->postJson('/api/matches', $this->matchData($home->id, $away->id));

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'scheduled')
            ->assertJsonPath('data.home_team.id', $home->id)
            ->assertJsonPath('data.away_team.id', $away->id);
    }

    public function test_home_and_away_team_cannot_be_the_same(): void
    {
        $this->actingAsAdmin();
        $team = Team::factory()->create();

        $response = $this->postJson('/api/matches', $this->matchData($team->id, $team->id));

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['errors' => ['away_team_id']]);
    }

    public function test_create_match_requires_mandatory_fields(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson('/api/matches', []);

        $response->assertStatus(422)
            ->assertJsonStructure(['errors' => ['match_date', 'match_time', 'home_team_id', 'away_team_id']]);
    }

    public function test_match_time_must_be_valid_format(): void
    {
        $this->actingAsAdmin();
        $home = Team::factory()->create();
        $away = Team::factory()->create();

        $response = $this->postJson('/api/matches', $this->matchData($home->id, $away->id, [
            'match_time' => '25:99', // format tidak valid
        ]));

        $response->assertStatus(422)
            ->assertJsonStructure(['errors' => ['match_time']]);
    }

    public function test_can_update_scheduled_match(): void
    {
        $this->actingAsAdmin();
        $match = SoccerMatch::factory()->create(['status' => 'scheduled']);

        $response = $this->putJson("/api/matches/{$match->id}", [
            'match_date' => '2024-09-01',
            'match_time' => '19:00',
        ]);

        $response->dump();

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.match_date', '2024-09-01');
    }

    public function test_cannot_update_finished_match(): void
    {
        $this->actingAsAdmin();
        $match = SoccerMatch::factory()->finished()->create();

        $response = $this->putJson("/api/matches/{$match->id}", [
            'match_date' => '2024-09-01',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_can_delete_match(): void
    {
        $this->actingAsAdmin();
        $match = SoccerMatch::factory()->create();

        $response = $this->deleteJson("/api/matches/{$match->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('matches', ['id' => $match->id]);
    }
}
