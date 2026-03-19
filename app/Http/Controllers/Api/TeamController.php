<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TeamRequest;
use App\Models\Team;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class TeamController extends Controller
{
    public function index(): JsonResponse
    {
        $teams = Team::paginate(10);

        return response()->json([
            'success' => true,
            'data' => $teams
        ], Response::HTTP_OK);
    }

    public function show(Team $team): JsonResponse
    {
        $team->load('players');

        return response()->json([
            'success' => true,
            'data' => $team
        ], Response::HTTP_OK);
    }

    public function store(TeamRequest $request): JsonResponse
    {
        $data = $request->validated();

        if (isset($data['logo'])) {
            $data['logo'] = $request->file('logo')->store('logos', 'public');
        }

        $team = Team::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Team created successfully',
            'data' => $team
        ], Response::HTTP_CREATED);
    }

    public function update(TeamRequest $request, Team $team): JsonResponse
    {
        $data = $request->validated();

        if ($request->hasFile('logo')) {
            if ($team->logo) {
                Storage::disk('public')->delete($team->logo);
            }
            $data['logo'] = $request->file('logo')->store('logos', 'public');
        }

        $team->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Team updated successfully',
            'data' => $team
        ], Response::HTTP_OK);
    }

    public function destroy(Team $team): JsonResponse
    {
        $team->delete();

        return response()->json([
            'success' => true,
            'message' => 'Team deleted successfully'
        ], Response::HTTP_OK);
    }
}
