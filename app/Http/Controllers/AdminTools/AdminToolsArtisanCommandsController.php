<?php

namespace App\Http\Controllers\AdminTools;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminToolsArtisanCommandsController extends Controller
{
    private const array ALLOWED_COMMANDS = [
        'ksg:backfill-kill-zone-enemy-id',
    ];

    public function backfillKillZoneEnemyId(): View
    {
        $query = DB::table('kill_zone_enemies')->whereNull('enemy_id');
        $count = (int)$query->count();
        $minId = $count > 0 ? (int)$query->min('id') : 0;
        $maxId = $count > 0 ? (int)$query->max('id') : 0;

        return view('admin.tools.artisancommands.backfillkillzoneenemyid', [
            'count' => $count,
            'minId' => $minId,
            'maxId' => $maxId,
        ]);
    }

    public function run(Request $request): JsonResponse
    {
        $command = (string)$request->input('command');
        $options = (array)$request->input('options', []);

        if (!in_array($command, self::ALLOWED_COMMANDS, true)) {
            return response()->json(['error' => sprintf('Command "%s" is not allowed.', $command)], 422);
        }

        Artisan::call($command, $options);

        return response()->json([
            'exit_code' => 0,
            'output'    => Artisan::output(),
        ]);
    }
}
