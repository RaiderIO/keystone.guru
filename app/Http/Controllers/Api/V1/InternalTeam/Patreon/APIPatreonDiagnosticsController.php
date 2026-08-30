<?php

namespace App\Http\Controllers\Api\V1\InternalTeam\Patreon;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Patreon\PatreonUserDiagnosticsRequest;
use App\Http\Resources\Patreon\PatreonCampaignDiagnosticsResource;
use App\Http\Resources\Patreon\PatreonSyncDryRunResource;
use App\Http\Resources\Patreon\PatreonSyncRunResource;
use App\Http\Resources\Patreon\PatreonUserDiagnosticsResource;
use App\Repositories\Interfaces\Patreon\PatreonSyncRunRepositoryInterface;
use App\Service\Patreon\PatreonDiagnosticsServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Teapot\StatusCode;

/**
 * Read-only diagnostics for the hourly Patreon sync, so a member who did not get their benefits can be
 * investigated from a local dev machine against production data (#4373).
 *
 * All four endpoints are GET and none of them grants, revokes or links anything. They are not entirely
 * without side effects: any of them that reaches the Patreon API goes through
 * `PatreonService::loadAdminUser()`, which writes a refreshed admin token back when the stored one has
 * expired - the same write the hourly command performs.
 *
 * Patron emails are masked in every response, and the campaign-wide endpoints carry no email at all
 * beyond the members they single out, so the `ai_agent` role these are open to cannot enumerate patrons.
 */
class APIPatreonDiagnosticsController extends Controller
{
    /** How many runs the history returns by default - a bit over a day at one run per hour. */
    private const int DEFAULT_SYNC_RUN_LIMIT = 30;

    private const int MAX_SYNC_RUN_LIMIT = 500;

    /**
     * @OA\Get(
     *     operationId="getPatreonSyncRuns",
     *     path="/api/v1/patreon/sync-runs",
     *     summary="The recorded history of patreon:refreshmembers runs, newest first",
     *     tags={"Patreon"},
     *
     *     @OA\Parameter(name="limit", in="query", required=false, description="How many runs to return, 1..500 (default 30)", @OA\Schema(type="integer", minimum=1, maximum=500)),
     *
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=403, description="Not an admin or AI agent"),
     * )
     */
    public function syncRuns(
        Request                           $request,
        PatreonSyncRunRepositoryInterface $patreonSyncRunRepository,
    ): JsonResponse {
        $limit = max(1, min(self::MAX_SYNC_RUN_LIMIT, (int)$request->query('limit', (string)self::DEFAULT_SYNC_RUN_LIMIT)));

        // Costs nothing and touches no external API, which is why it is the first call to make: a
        // members_fetched that drops between consecutive runs is a truncated fetch, and every member the
        // short run never saw kept whatever benefits they already had
        return response()->json([
            'data' => PatreonSyncRunResource::collection($patreonSyncRunRepository->getMostRecent($limit)),
        ]);
    }

    /**
     * @OA\Get(
     *     operationId="getPatreonCampaignDiagnostics",
     *     path="/api/v1/patreon/campaign",
     *     summary="Every campaign tier with the benefits it resolves to, and anything the code cannot map",
     *     tags={"Patreon"},
     *
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=403, description="Not an admin or AI agent"),
     *     @OA\Response(response=502, description="The campaign could not be loaded from Patreon"),
     * )
     */
    public function campaign(PatreonDiagnosticsServiceInterface $patreonDiagnosticsService): JsonResponse
    {
        $campaignDiagnostics = $patreonDiagnosticsService->getCampaignDiagnostics();

        if ($campaignDiagnostics === null) {
            return response()->json(['error' => 'Unable to load the campaign from Patreon'], StatusCode::BAD_GATEWAY);
        }

        return new PatreonCampaignDiagnosticsResource($campaignDiagnostics)->response();
    }

    /**
     * @OA\Get(
     *     operationId="getPatreonSyncDryRun",
     *     path="/api/v1/patreon/sync-dry-run",
     *     summary="Runs the hourly sync in plan-only mode and reports what it would change",
     *     tags={"Patreon"},
     *
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=403, description="Not an admin or AI agent"),
     *     @OA\Response(response=502, description="The campaign or its members could not be loaded from Patreon"),
     * )
     */
    public function syncDryRun(PatreonDiagnosticsServiceInterface $patreonDiagnosticsService): JsonResponse
    {
        $dryRun = $patreonDiagnosticsService->getSyncDryRun();

        if ($dryRun === null) {
            // Includes a truncated member fetch - conclusions drawn from half a campaign would be worse
            // than none, since every unfetched member looks like a member who left
            return response()->json(['error' => 'Unable to load the campaign members from Patreon'], StatusCode::BAD_GATEWAY);
        }

        return new PatreonSyncDryRunResource($dryRun)->response();
    }

    /**
     * @OA\Get(
     *     operationId="getPatreonUserDiagnostics",
     *     path="/api/v1/patreon/user",
     *     summary="One account's Patreon state, from the database and the campaign at once",
     *     tags={"Patreon"},
     *
     *     @OA\Parameter(name="user_id", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="username", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="email", in="query", required=false, @OA\Schema(type="string")),
     *
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=403, description="Not an admin or AI agent"),
     *     @OA\Response(response=422, description="No account was named, or none matched"),
     * )
     */
    public function user(
        PatreonUserDiagnosticsRequest      $request,
        PatreonDiagnosticsServiceInterface $patreonDiagnosticsService,
    ): JsonResponse {
        // The request's validator rejects anything that does not resolve, so this is never null here
        $user = $request->getTargetUser();

        return new PatreonUserDiagnosticsResource($patreonDiagnosticsService->getUserDiagnostics($user))->response();
    }
}
