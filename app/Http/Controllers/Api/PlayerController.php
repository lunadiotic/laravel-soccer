<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PlayerRequest;
use App\Models\Player;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PlayerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Player::with('team');

        if ($request->filled('team_id')) {
            $query->where('team_id', $request->team_id);
        }

        return response()->json([
            'success' => true,
            'data' => $query->paginate(10)
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PlayerRequest $request): JsonResponse
    {
        $player = Player::create($request->validated());
        $player->load('team');

        return response()->json([
            'success' => true,
            'message' => 'Player created successfully',
            'data' => $player
        ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     */
    public function show(Player $player)
    {
        $player->load('team');

        return response()->json([
            'success' => true,
            'data' => $player
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(PlayerRequest $request, Player $player): JsonResponse
    {
        $player->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Player updated successfully',
            'data' => $player
        ], Response::HTTP_OK);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Player $player): JsonResponse
    {
        $player->delete();

        return response()->json([
            'success' => true,
            'message' => 'Player deleted successfully'
        ], Response::HTTP_OK);
    }
}
