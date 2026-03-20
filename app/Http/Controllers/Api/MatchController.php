<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\MatchRequest;
use App\Models\SoccerMatch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class MatchController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $matches = SoccerMatch::with(['homeTeam', 'awayTeam', 'result'])
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data'    => $matches,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(MatchRequest $request): JsonResponse
    {
        $match = SoccerMatch::create($request->validated());
        // refresh to load relationships after creation
        $match->refresh()->load(['homeTeam', 'awayTeam']);

        return response()->json([
            'success' => true,
            'message' => 'Match created successfully',
            'data'    => $match,
        ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     */
    public function show(SoccerMatch $soccerMatch)
    {
        $soccerMatch->load(['homeTeam', 'awayTeam', 'result', 'goals.player']);

        return response()->json([
            'success' => true,
            'data'    => $soccerMatch,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, SoccerMatch $soccerMatch)
    {
        // check if the match is finished
        if ($soccerMatch->status === 'finished') {
            return response()->json([
                'success' => false,
                'message' => 'Match already finished',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $soccerMatch->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Match updated successfully',
            'data'    => $soccerMatch->fresh()->load(['homeTeam', 'awayTeam']),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SoccerMatch $soccerMatch)
    {
        $soccerMatch->delete();

        return response()->json([
            'success' => true,
            'message' => 'Match deleted successfully',
        ]);
    }
}
