<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\Website;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebsiteApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        /** @var Team $team */
        $team = $request->attributes->get('api_team');

        return response()->json([
            'data' => $team->websites()->get()->map(fn (Website $w) => $this->serialize($w)),
        ]);
    }

    public function show(Request $request, Website $website): JsonResponse
    {
        $this->authorizeTeam($request, $website);

        return response()->json(['data' => $this->serialize($website, detailed: true)]);
    }

    private function authorizeTeam(Request $request, Website $website): void
    {
        if ($website->team_id !== $request->attributes->get('api_team')->id) {
            abort(404);
        }
    }

    private function serialize(Website $website, bool $detailed = false): array
    {
        $data = $website->only(['id', 'name', 'url', 'domain', 'group', 'status', 'verified_method', 'verified_at', 'created_at']);

        if ($detailed) {
            $data['scores'] = $website->latestScores;
        }

        return $data;
    }
}
