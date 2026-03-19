<?php

namespace Tests\Feature;

use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TeamTest extends TestCase
{
    use RefreshDatabase;

    private function teamData(array $override = []): array
    {
        return array_merge([
            'name'         => 'Persebaya Surabaya',
            'founded_year' => 1927,
            'address'      => 'Jl. Stadion Gelora Bung Tomo',
            'city'         => 'Surabaya',
        ], $override);
    }

    public function test_can_get_list_of_teams(): void
    {
        $this->actingAsAdmin();
        Team::factory()->count(3)->create();

        $response = $this->getJson('/api/teams');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    public function test_can_get_single_team(): void
    {
        $this->actingAsAdmin();
        $team = Team::factory()->create();

        $response = $this->getJson("/api/teams/{$team->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $team->id)
            ->assertJsonPath('data.name', $team->name);
    }

    public function test_returns_404_for_nonexistent_team(): void
    {
        $this->actingAsAdmin();

        $response = $this->getJson('/api/teams/999');

        $response->assertStatus(404);
    }

    public function test_can_create_team(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson('/api/teams', $this->teamData());

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Persebaya Surabaya')
            ->assertJsonPath('data.city', 'Surabaya');

        $this->assertDatabaseHas('teams', ['name' => 'Persebaya Surabaya']);
    }

    public function test_can_create_team_with_logo(): void
    {
        Storage::fake('public');
        $this->actingAsAdmin();

        $response = $this->postJson('/api/teams', array_merge(
            $this->teamData(),
            ['logo' => UploadedFile::fake()->image('logo.jpg', 100, 100)]
        ));

        $response->assertStatus(201)
            ->assertJsonPath('success', true);

        // pastikan file logo tersimpan
        $logoPath = $response->json('data.logo');
        Storage::disk('public')->assertExists($logoPath);
    }

    public function test_create_team_requires_mandatory_fields(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson('/api/teams', []);

        $response->assertStatus(422)
            ->assertJsonStructure(['errors' => ['name', 'founded_year', 'address', 'city']]);
    }

    public function test_logo_must_be_image_file(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson('/api/teams', array_merge(
            $this->teamData(),
            ['logo' => UploadedFile::fake()->create('document.pdf', 100)]
        ));

        $response->assertStatus(422);
    }

    public function test_can_update_team(): void
    {
        $this->actingAsAdmin();
        $team = Team::factory()->create();

        $response = $this->putJson("/api/teams/{$team->id}", [
            'name' => 'Persebaya Baru',
            'city' => 'Sidoarjo',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Persebaya Baru');

        $this->assertDatabaseHas('teams', ['id' => $team->id, 'name' => 'Persebaya Baru']);
    }

    public function test_can_delete_team(): void
    {
        $this->actingAsAdmin();
        $team = Team::factory()->create();

        $response = $this->deleteJson("/api/teams/{$team->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        // soft delete: record masih ada di database
        $this->assertSoftDeleted('teams', ['id' => $team->id]);
    }

    public function test_soft_deleted_team_not_in_list(): void
    {
        $this->actingAsAdmin();
        $team = Team::factory()->create();
        $team->delete();

        $response = $this->getJson('/api/teams');

        $response->assertStatus(200);
        $ids = collect($response->json('data'))->pluck('id');
        $this->assertNotContains($team->id, $ids);
    }
}
