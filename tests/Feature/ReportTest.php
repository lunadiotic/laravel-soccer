<?php

namespace Tests\Feature;

use App\Models\Goal;
use App\Models\MatchResult;
use App\Models\Player;
use App\Models\SoccerMatch;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    // helper: buat pertandingan selesai lengkap dengan hasil dan gol
    private function createFinishedMatch(
        Team $home,
        Team $away,
        int $homeScore,
        int $awayScore,
        string $date = '2024-08-10',
        array $goals = []
    ): SoccerMatch {
        $match = SoccerMatch::factory()->finished()->create([
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'match_date'   => $date,
            'match_time'   => '15:00',
        ]);

        MatchResult::factory()->create([
            'match_id'   => $match->id,
            'home_score' => $homeScore,
            'away_score' => $awayScore,
        ]);

        foreach ($goals as $goal) {
            Goal::create([
                'match_id'  => $match->id,
                'player_id' => $goal['player_id'],
                'team_id'   => $goal['team_id'],
                'minute'    => $goal['minute'],
            ]);
        }

        return $match;
    }

    public function test_report_only_shows_finished_matches(): void
    {
        $this->actingAsAdmin();

        // 2 pertandingan selesai
        $home = Team::factory()->create();
        $away = Team::factory()->create();
        $this->createFinishedMatch($home, $away, 2, 1);
        $this->createFinishedMatch($home, $away, 1, 1, '2024-08-17');

        // 1 pertandingan terjadwal — tidak boleh muncul di laporan
        SoccerMatch::factory()->create(['home_team_id' => $home->id, 'away_team_id' => $away->id]);

        $response = $this->getJson('/api/reports/matches');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data');
    }

    public function test_report_contains_required_fields(): void
    {
        $this->actingAsAdmin();
        $home = Team::factory()->create();
        $away = Team::factory()->create();
        $match = $this->createFinishedMatch($home, $away, 2, 0);

        $response = $this->getJson("/api/reports/matches/{$match->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'match_id',
                    'match_date',
                    'match_time',
                    'home_team',
                    'away_team',
                    'home_score',
                    'away_score',
                    'final_status',
                    'top_scorer',
                    'home_team_wins',
                    'away_team_wins',
                    'goals',
                ],
            ]);
    }

    public function test_final_status_home_wins(): void
    {
        $this->actingAsAdmin();
        $home  = Team::factory()->create();
        $away  = Team::factory()->create();
        $match = $this->createFinishedMatch($home, $away, 3, 1);

        $response = $this->getJson("/api/reports/matches/{$match->id}");

        $response->assertJsonPath('data.final_status', 'Tim Home Menang');
    }

    public function test_final_status_away_wins(): void
    {
        $this->actingAsAdmin();
        $home  = Team::factory()->create();
        $away  = Team::factory()->create();
        $match = $this->createFinishedMatch($home, $away, 0, 2);

        $response = $this->getJson("/api/reports/matches/{$match->id}");

        $response->assertJsonPath('data.final_status', 'Tim Away Menang');
    }

    public function test_final_status_draw(): void
    {
        $this->actingAsAdmin();
        $home  = Team::factory()->create();
        $away  = Team::factory()->create();
        $match = $this->createFinishedMatch($home, $away, 1, 1);

        $response = $this->getJson("/api/reports/matches/{$match->id}");

        $response->assertJsonPath('data.final_status', 'Draw');
    }

    public function test_top_scorer_is_player_with_most_goals(): void
    {
        $this->actingAsAdmin();
        $home      = Team::factory()->create();
        $away      = Team::factory()->create();
        $scorer    = Player::factory()->create(['team_id' => $home->id]);
        $nonScorer = Player::factory()->create(['team_id' => $away->id]);

        $match = $this->createFinishedMatch($home, $away, 2, 1, '2024-08-10', [
            ['player_id' => $scorer->id, 'team_id' => $home->id, 'minute' => 10],
            ['player_id' => $scorer->id, 'team_id' => $home->id, 'minute' => 55],
            ['player_id' => $nonScorer->id, 'team_id' => $away->id, 'minute' => 80],
        ]);

        $response = $this->getJson("/api/reports/matches/{$match->id}");

        $response->assertJsonPath('data.top_scorer.player.id', $scorer->id)
            ->assertJsonPath('data.top_scorer.goal_count', 2);
    }

    public function test_top_scorer_is_null_when_no_goals(): void
    {
        $this->actingAsAdmin();
        $home  = Team::factory()->create();
        $away  = Team::factory()->create();
        $match = $this->createFinishedMatch($home, $away, 0, 0);

        $response = $this->getJson("/api/reports/matches/{$match->id}");

        $response->assertJsonPath('data.top_scorer', null);
    }

    public function test_cumulative_home_wins_counted_correctly(): void
    {
        $this->actingAsAdmin();
        $home = Team::factory()->create();
        $away = Team::factory()->create();

        // tim home menang 2 kali sebelum pertandingan ini
        $this->createFinishedMatch($home, $away, 2, 0, '2024-06-01');
        $this->createFinishedMatch($home, $away, 3, 1, '2024-07-01');
        $this->createFinishedMatch($home, $away, 0, 1, '2024-07-15'); // kalah

        // pertandingan ke-4: tim home menang lagi
        $match = $this->createFinishedMatch($home, $away, 1, 0, '2024-08-10');

        $response = $this->getJson("/api/reports/matches/{$match->id}");

        // total kemenangan home sampai pertandingan ini: 3
        $response->assertJsonPath('data.home_team_wins', 3);
        // total kemenangan away: 1
        $response->assertJsonPath('data.away_team_wins', 1);
    }

    public function test_report_returns_404_for_scheduled_match(): void
    {
        $this->actingAsAdmin();
        $match = SoccerMatch::factory()->create(['status' => 'scheduled']);

        $response = $this->getJson("/api/reports/matches/{$match->id}");

        $response->assertStatus(404)
            ->assertJsonPath('success', false);
    }
}
