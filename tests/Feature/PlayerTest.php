<?php

namespace Tests\Feature;

use App\Models\Player;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class PlayerTest extends TestCase
{
    use RefreshDatabase;

    private function playerData(int $teamId, array $override = []): array
    {
        return array_merge([
            'team_id'       => $teamId,
            'name'          => 'Budi Santoso',
            'height'        => 175.0,
            'weight'        => 68.0,
            'position'      => 'penyerang',
            'jersey_number' => 9,
        ], $override);
    }

    public function test_can_get_list_of_players()
    {
        $this->actingAsAdmin();
        Player::factory()->count(3)->create();

        $reponse = $this->getJson('/api/players');

        $reponse->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.total', 3)
            ->assertJsonCount(3, 'data.data');
    }

    public function test_can_filter_players_by_team(): void
    {
        $this->actingAsAdmin();
        $team = Team::factory()->create();
        Player::factory()->count(2)->create(['team_id' => $team->id]);
        Player::factory()->count(3)->create(); // pemain tim lain

        $response = $this->getJson("/api/players?team_id={$team->id}");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data.data');
    }

    public function test_can_get_single_player(): void
    {
        $this->actingAsAdmin();
        $player = Player::factory()->create();

        $response = $this->getJson("/api/players/{$player->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $player->id);
    }

    public function test_can_create_player(): void
    {
        $this->actingAsAdmin();
        $team = Team::factory()->create();

        $response = $this->postJson('/api/players', $this->playerData($team->id));

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Budi Santoso')
            ->assertJsonPath('data.position', 'penyerang')
            ->assertJsonPath('data.jersey_number', 9);

        $this->assertDatabaseHas('players', [
            'team_id'       => $team->id,
            'jersey_number' => 9,
        ]);
    }

    public function test_jersey_number_must_be_unique_per_team(): void
    {
        $this->actingAsAdmin();
        $team = Team::factory()->create();
        Player::factory()->create(['team_id' => $team->id, 'jersey_number' => 9]);

        // coba tambah pemain kedua di tim yang sama dengan nomor punggung sama
        $response = $this->postJson('/api/players', $this->playerData($team->id, ['name' => 'Andi']));

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['errors' => ['jersey_number']]);
    }

    public function test_same_jersey_number_allowed_in_different_teams(): void
    {
        $this->actingAsAdmin();
        $team1 = Team::factory()->create();
        $team2 = Team::factory()->create();
        Player::factory()->create(['team_id' => $team1->id, 'jersey_number' => 9]);

        // nomor 9 di tim berbeda harus boleh
        $response = $this->postJson('/api/players', $this->playerData($team2->id));

        $response->assertStatus(201)
            ->assertJsonPath('success', true);
    }

    public function test_position_must_be_valid_enum(): void
    {
        $this->actingAsAdmin();
        $team = Team::factory()->create();

        $response = $this->postJson('/api/players', $this->playerData($team->id, ['position' => 'kiper']));

        $response->assertStatus(422)
            ->assertJsonStructure(['errors' => ['position']]);
    }

    public function test_can_update_player(): void
    {
        $this->actingAsAdmin();
        $player = Player::factory()->create();

        $response = $this->putJson("/api/players/{$player->id}", [
            'position' => 'gelandang',
            'weight'   => 70.0,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.position', 'gelandang');
    }

    public function test_update_jersey_number_unique_constraint_ignores_self(): void
    {
        $this->actingAsAdmin();
        $team   = Team::factory()->create();
        $player = Player::factory()->create(['team_id' => $team->id, 'jersey_number' => 9]);

        // update pemain itu sendiri dengan jersey_number yang sama — harus boleh
        $response = $this->putJson("/api/players/{$player->id}", [
            'jersey_number' => 9,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    public function test_can_delete_player(): void
    {
        $this->actingAsAdmin();
        $player = Player::factory()->create();

        $response = $this->deleteJson("/api/players/{$player->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('players', ['id' => $player->id]);
    }

    public function test_team_must_exist_when_creating_player(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson('/api/players', $this->playerData(999));

        $response->assertStatus(422)
            ->assertJsonStructure(['errors' => ['team_id']]);
    }
}
