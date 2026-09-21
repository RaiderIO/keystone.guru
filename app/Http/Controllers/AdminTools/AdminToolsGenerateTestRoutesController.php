<?php

namespace App\Http\Controllers\AdminTools;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminToolsDungeonRouteGenerateTestRoutesRequest;
use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\PublishedState;
use App\Models\Season;
use App\Models\User;
use App\Service\DungeonRoute\Exceptions\TestDungeonRouteGeneratorException;
use App\Service\DungeonRoute\TestDungeonRouteGeneratorServiceInterface;
use App\Service\Season\SeasonServiceInterface;
use Auth;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class AdminToolsGenerateTestRoutesController extends Controller
{
    private const int DELETE_BATCH_SIZE = 10;

    public function index(
        TestDungeonRouteGeneratorServiceInterface $testDungeonRouteGeneratorService,
        SeasonServiceInterface                    $seasonService,
    ): View {
        abort_unless($testDungeonRouteGeneratorService->isAvailable(), 404);

        return view('admin.tools.dungeonroute.generatetestroutes', [
            'seasons'         => Season::query()->with(['expansion', 'dungeons'])->orderByDesc('start')->get(),
            'currentSeason'   => $seasonService->getCurrentSeason(),
            'dungeons'        => Dungeon::query()->active()->with('expansion')->get()->sortBy(static fn(Dungeon $dungeon) => __($dungeon->name)),
            'publishedStates' => array_keys(PublishedState::ALL),
            'maxCount'        => TestDungeonRouteGeneratorServiceInterface::MAX_ROUTES_PER_DUNGEON,
            'generatedCount'  => $testDungeonRouteGeneratorService->countGenerated($this->getUser()),
        ]);
    }

    public function generateBatch(
        AdminToolsDungeonRouteGenerateTestRoutesRequest $request,
        TestDungeonRouteGeneratorServiceInterface       $testDungeonRouteGeneratorService,
    ): JsonResponse {
        abort_unless($testDungeonRouteGeneratorService->isAvailable(), 404);

        $dungeon = $request->getDungeon();
        $user    = $this->getUser();

        try {
            $dungeonRoutes = $testDungeonRouteGeneratorService->generate(
                $dungeon,
                $user,
                (int)$request->validated('count'),
                PublishedState::ALL[$request->validated('published_state')],
            );
        } catch (TestDungeonRouteGeneratorException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'processed' => $dungeonRoutes->count(),
            'dungeon'   => __($dungeon->name),
            'routes'    => $dungeonRoutes->map(static fn(DungeonRoute $dungeonRoute) => [
                'public_key'   => $dungeonRoute->public_key,
                'title'        => $dungeonRoute->title,
                'url'          => route('dungeonroute.view', ['dungeon' => $dungeon, 'dungeonroute' => $dungeonRoute, 'title' => $dungeonRoute->getTitleSlug()]),
                'enemy_forces' => $dungeonRoute->enemy_forces,
            ])->values(),
            'enemy_forces_required' => $dungeonRoutes->first()?->mappingVersion->enemy_forces_required,
            'generated_count'       => $testDungeonRouteGeneratorService->countGenerated($user),
        ]);
    }

    public function deleteBatch(TestDungeonRouteGeneratorServiceInterface $testDungeonRouteGeneratorService): JsonResponse
    {
        abort_unless($testDungeonRouteGeneratorService->isAvailable(), 404);

        try {
            $result = $testDungeonRouteGeneratorService->deleteGenerated(self::DELETE_BATCH_SIZE, $this->getUser());
        } catch (TestDungeonRouteGeneratorException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'processed' => $result['deleted'],
            'remaining' => $result['remaining'],
        ]);
    }

    private function getUser(): User
    {
        /** @var User $user */
        $user = Auth::user();

        return $user;
    }
}
