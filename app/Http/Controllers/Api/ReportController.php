<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;

use App\Http\Controllers\Controller;
use App\Models\SoccerMatch;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ReportController extends Controller
{
    public function index(): JsonResponse
    {
        $matches = SoccerMatch::with(['homeTeam', 'awayTeam', 'result', 'goals.player'])
            ->where('status', 'finished')
            ->orderBy('match_date')
            ->orderBy('match_time')
            ->get()
            ->map(fn($match) => $this->buildReport($match));

        return response()->json([
            'success' => true,
            'data'    => $matches,
        ]);
    }

    public function show(SoccerMatch $match): JsonResponse
    {
        if ($match->status !== 'finished' || !$match->result) {
            return response()->json([
                'success' => false,
                'message' => 'Report only available for finished matches',
            ], Response::HTTP_NOT_FOUND);
        }

        $match->load(['homeTeam', 'awayTeam', 'result', 'goals.player']);

        return response()->json([
            'success' => true,
            'data'    => $this->buildReport($match),
        ]);
    }

    private function buildReport(SoccerMatch $match): array
    {
        $result     = $match->result;
        $homeScore  = $result->home_score;
        $awayScore  = $result->away_score;

        // tentukan status akhir pertandingan
        if ($homeScore > $awayScore) {
            $finalStatus = 'Tim Home Menang';
        } elseif ($awayScore > $homeScore) {
            $finalStatus = 'Tim Away Menang';
        } else {
            $finalStatus = 'Draw';
        }

        // hitung pencetak gol terbanyak di pertandingan ini
        $topScorer = null;
        if ($match->goals->isNotEmpty()) {
            $scorerCounts = $match->goals->groupBy('player_id')
                ->map(fn($goals) => [
                    'player'     => $goals->first()->player,
                    'goal_count' => $goals->count(),
                ])
                ->sortByDesc('goal_count')
                ->first();

            $topScorer = [
                'player'     => $scorerCounts['player'],
                'goal_count' => $scorerCounts['goal_count'],
            ];
        }

        // akumulasi kemenangan tim home dari pertandingan pertama sampai pertandingan ini
        $homeWins = $this->countCumulativeWins($match->home_team_id, $match);
        $awayWins = $this->countCumulativeWins($match->away_team_id, $match);

        return [
            'match_id'          => $match->id,
            'match_date'        => $match->match_date,
            'match_time'        => $match->match_time,
            'home_team'         => $match->homeTeam,
            'away_team'         => $match->awayTeam,
            'home_score'        => $homeScore,
            'away_score'        => $awayScore,
            'final_status'      => $finalStatus,
            'top_scorer'        => $topScorer,
            'home_team_wins'    => $homeWins,
            'away_team_wins'    => $awayWins,
            'goals'             => $match->goals->sortBy('minute')->values(),
        ];
    }

    private function countCumulativeWins(int $teamId, SoccerMatch $upToMatch): int
    {
        // ambil semua pertandingan selesai sampai dengan pertandingan ini (inklusif)
        $finishedMatches = SoccerMatch::with('result')
            ->where('status', 'finished')
            ->where(function ($q) use ($teamId) {
                $q->where('home_team_id', $teamId)
                    ->orWhere('away_team_id', $teamId);
            })
            ->where(function ($q) use ($upToMatch) {
                $q->where('match_date', '<', $upToMatch->match_date)
                    ->orWhere(function ($q2) use ($upToMatch) {
                        $q2->where('match_date', $upToMatch->match_date)
                            ->where('match_time', '<=', $upToMatch->match_time);
                    });
            })
            ->get();

        $wins = 0;
        foreach ($finishedMatches as $m) {
            if (!$m->result) continue;

            $isHome      = $m->home_team_id === $teamId;
            $homeScore   = $m->result->home_score;
            $awayScore   = $m->result->away_score;

            if ($isHome && $homeScore > $awayScore) {
                $wins++;
            } elseif (!$isHome && $awayScore > $homeScore) {
                $wins++;
            }
        }

        return $wins;
    }
}
