<?php

namespace Tests\Feature;

use App\Models\MatchResult;
use App\Models\Player;
use App\Models\SoccerMatch;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class MatchResultTest extends TestCase
{
    use RefreshDatabase;

    private function makeMatchWithTeams(): array
    {
        $home  = Team::factory()->create();
        $away  = Team::factory()->create();
        $match = SoccerMatch::factory()->create([
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
        ]);
        return [$match, $home, $away];
    }

    public function test_can_record_match_result(): void
    {
        $this->actingAsAdmin();
        [$match, $home, $away] = $this->makeMatchWithTeams();

        $homePlayer = Player::factory()->create(['team_id' => $home->id]);
        $awayPlayer = Player::factory()->create(['team_id' => $away->id]);

        $response = $this->postJson("/api/matches/{$match->id}/result", [
            'home_score' => 2,
            'away_score' => 1,
            'goals'      => [
                ['player_id' => $homePlayer->id, 'minute' => 23],
                ['player_id' => $homePlayer->id, 'minute' => 67],
                ['player_id' => $awayPlayer->id, 'minute' => 89],
            ],
        ]);

        $response->dump();

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.result.home_score', 2)
            ->assertJsonPath('data.result.away_score', 1)
            ->assertJsonPath('data.status', 'finished');

        $this->assertDatabaseHas('match_results', ['match_id' => $match->id, 'home_score' => 2]);
        $this->assertDatabaseCount('goals', 3);
    }

    public function test_can_record_result_without_goals(): void
    {
        $this->actingAsAdmin();
        [$match] = $this->makeMatchWithTeams();

        $response = $this->postJson("/api/matches/{$match->id}/result", [
            'home_score' => 0,
            'away_score' => 0,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.result.home_score', 0)
            ->assertJsonPath('data.result.away_score', 0);

        $this->assertDatabaseCount('goals', 0);
    }

    public function test_match_status_changes_to_finished_after_result_recorded(): void
    {
        $this->actingAsAdmin();
        [$match] = $this->makeMatchWithTeams();
        $this->assertEquals('scheduled', $match->status);

        $this->postJson("/api/matches/{$match->id}/result", [
            'home_score' => 1,
            'away_score' => 0,
        ]);

        $this->assertDatabaseHas('matches', ['id' => $match->id, 'status' => 'finished']);
    }

    public function test_cannot_record_result_twice(): void
    {
        $this->actingAsAdmin();
        [$match] = $this->makeMatchWithTeams();
        MatchResult::factory()->create(['match_id' => $match->id]);

        $response = $this->postJson("/api/matches/{$match->id}/result", [
            'home_score' => 1,
            'away_score' => 0,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_can_update_match_result(): void
    {
        $this->actingAsAdmin();
        [$match, $home] = $this->makeMatchWithTeams();
        MatchResult::factory()->create(['match_id' => $match->id, 'home_score' => 1, 'away_score' => 0]);
        $match->update(['status' => 'finished']);

        $player = Player::factory()->create(['team_id' => $home->id]);

        $response = $this->putJson("/api/matches/{$match->id}/result", [
            'home_score' => 3,
            'away_score' => 1,
            'goals'      => [
                ['player_id' => $player->id, 'minute' => 10],
                ['player_id' => $player->id, 'minute' => 55],
                ['player_id' => $player->id, 'minute' => 80],
            ],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.result.home_score', 3)
            ->assertJsonPath('data.result.away_score', 1);

        $this->assertDatabaseCount('goals', 3);
    }

    public function test_update_goals_replaces_old_goals(): void
    {
        $this->actingAsAdmin();
        [$match, $home] = $this->makeMatchWithTeams();

        $player = Player::factory()->create(['team_id' => $home->id]);
        MatchResult::factory()->create(['match_id' => $match->id]);
        $match->update(['status' => 'finished']);

        // catat 3 gol awal
        $match->goals()->createMany([
            ['player_id' => $player->id, 'team_id' => $home->id, 'minute' => 10],
            ['player_id' => $player->id, 'team_id' => $home->id, 'minute' => 20],
            ['player_id' => $player->id, 'team_id' => $home->id, 'minute' => 30],
        ]);

        // update dengan 1 gol baru — gol lama di-soft-delete, hanya 1 gol aktif tersisa
        $this->putJson("/api/matches/{$match->id}/result", [
            'home_score' => 1,
            'away_score' => 0,
            'goals'      => [
                ['player_id' => $player->id, 'minute' => 45],
            ],
        ]);

        // pakai Goal::count() agar soft-deleted record tidak ikut terhitung
        $this->assertEquals(1, \App\Models\Goal::count());
    }

    public function test_cannot_update_result_that_doesnt_exist(): void
    {
        $this->actingAsAdmin();
        [$match] = $this->makeMatchWithTeams();

        $response = $this->putJson("/api/matches/{$match->id}/result", [
            'home_score' => 1,
            'away_score' => 0,
        ]);

        $response->assertStatus(404)
            ->assertJsonPath('success', false);
    }

    public function test_can_view_match_result(): void
    {
        $this->actingAsAdmin();
        [$match] = $this->makeMatchWithTeams();
        MatchResult::factory()->create(['match_id' => $match->id, 'home_score' => 2, 'away_score' => 1]);

        $response = $this->getJson("/api/matches/{$match->id}/result");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.result.home_score', 2);
    }

    public function test_view_result_returns_404_if_not_recorded(): void
    {
        $this->actingAsAdmin();
        [$match] = $this->makeMatchWithTeams();

        $response = $this->getJson("/api/matches/{$match->id}/result");

        $response->assertStatus(404)
            ->assertJsonPath('success', false);
    }

    public function test_goal_player_must_exist(): void
    {
        $this->actingAsAdmin();
        [$match] = $this->makeMatchWithTeams();

        $response = $this->postJson("/api/matches/{$match->id}/result", [
            'home_score' => 1,
            'away_score' => 0,
            'goals'      => [
                ['player_id' => 9999, 'minute' => 45], // player tidak ada
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure(['errors' => ['goals.0.player_id']]);
    }
}
