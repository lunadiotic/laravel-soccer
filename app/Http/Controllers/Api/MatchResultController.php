<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\MatchResultRequest;
use App\Models\Goal;
use App\Models\Player;
use App\Models\SoccerMatch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class MatchResultController extends Controller
{
    public function show(SoccerMatch $match): JsonResponse
    {
        if (!$match->result) {
            return response()->json([
                'success' => false,
                'message' => 'Match result not found',
            ], Response::HTTP_NOT_FOUND);
        }

        $match->load(['result', 'goals.player', 'homeTeam', 'awayTeam']);

        return response()->json([
            'success' => true,
            'data'    => $match,
        ]);
    }

    public function store(MatchResultRequest $request, SoccerMatch $match): JsonResponse
    {
        if ($match->result) {
            return response()->json([
                'success' => false,
                'message' => 'Match result already exists',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        DB::transaction(function () use ($request, $match) {
            $match->result()->create([
                'home_score' => $request->home_score,
                'away_score' => $request->away_score,
            ]);

            // simpan detail setiap gol yang terjadi
            if ($request->has('goals')) {
                foreach ($request->goals as $goal) {
                    $player = Player::findOrFail($goal['player_id']);
                    Goal::create([
                        'match_id'  => $match->id,
                        'player_id' => $player->id,
                        'team_id'   => $player->team_id,
                        'minute'    => $goal['minute'],
                    ]);
                }
            }

            $match->update(['status' => 'finished']);
        });

        $match->load(['result', 'goals.player', 'homeTeam', 'awayTeam']);

        return response()->json([
            'success' => true,
            'message' => 'Match result created successfully',
            'data'    => $match,
        ], Response::HTTP_CREATED);
    }

    public function update(MatchResultRequest $request, SoccerMatch $match): JsonResponse
    {
        if (!$match->result) {
            return response()->json([
                'success' => false,
                'message' => 'Match result not found',
            ], Response::HTTP_NOT_FOUND);
        }

        DB::transaction(function () use ($request, $match) {
            $match->result->update([
                'home_score' => $request->home_score,
                'away_score' => $request->away_score,
            ]);

            // hapus gol lama lalu isi ulang jika ada data gol baru
            if ($request->has('goals')) {
                Goal::where('match_id', $match->id)->delete();

                foreach ($request->goals as $goal) {
                    $player = Player::findOrFail($goal['player_id']);
                    Goal::create([
                        'match_id'  => $match->id,
                        'player_id' => $player->id,
                        'team_id'   => $player->team_id,
                        'minute'    => $goal['minute'],
                    ]);
                }
            }
        });

        $match->load(['result', 'goals.player', 'homeTeam', 'awayTeam']);

        return response()->json([
            'success' => true,
            'message' => 'Hasil pertandingan berhasil diperbarui',
            'data'    => $match,
        ]);
    }
}
