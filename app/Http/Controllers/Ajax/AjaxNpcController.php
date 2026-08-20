<?php

namespace App\Http\Controllers\Ajax;

use App\Events\Models\Npc\NpcDeletedEvent;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\ChangesMapping;
use App\Logic\Datatables\ColumnHandler\Npc\DungeonColumnHandler;
use App\Logic\Datatables\ColumnHandler\Npc\IdColumnHandler;
use App\Logic\Datatables\ColumnHandler\Npc\NameColumnHandler;
use App\Logic\Datatables\NpcsDatatablesHandler;
use App\Models\Npc\Npc;
use App\Models\User;
use Exception;
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Teapot\StatusCode\Http;

class AjaxNpcController extends Controller
{
    use ChangesMapping;

    /** @return array<string, mixed> */
    public function delete(Request $request): array|Response
    {
        try {
            /** @var Npc $npc */
            $npc = Npc::findOrFail($request->get('id'));

            if ($npc->delete()) {
                /** @var User $user */
                $user = Auth::user();
                foreach ($npc->dungeons as $dungeon) {
                    try {
                        broadcast(new NpcDeletedEvent($dungeon, $user, $npc));
                    } catch (BroadcastException) {
                        // Ignore broadcast failures
                    }
                }
            }

            // Trigger mapping changed event so the mapping gets saved across all environments
            $this->mappingChanged($npc, null);

            $result = response()->noContent();
        } catch (Exception) {
            $result = response(__('controller.generic.error.not_found'), Http::NOT_FOUND);
        }

        return $result;
    }

    /**
     * @return array<string, mixed>
     *
     * @throws Exception
     */
    public function get(Request $request): array
    {
        $npcs = Npc::with([
            'type',
            'classification',
            'enemyForces',
        ])
            ->selectRaw('npcs.*, npc_name_translations.translation as name, GROUP_CONCAT(DISTINCT translations.translation SEPARATOR ", ") AS dungeon_names, COUNT(enemies.id) as enemy_count')
            ->join('npc_dungeons', 'npcs.id', '=', 'npc_dungeons.npc_id')
            ->leftJoin('dungeons', 'npc_dungeons.dungeon_id', '=', 'dungeons.id')
            ->leftJoin('translations', static function (JoinClause $clause) {
                $clause->on('translations.key', '=', 'dungeons.name')
                    ->on('translations.locale', '=', DB::raw('"en_US"'));
            })
            ->leftJoin('translations as npc_name_translations', function (JoinClause $clause) {
                $clause->on('npc_name_translations.key', '=', 'npcs.name')
                    ->where('npc_name_translations.locale', '=', 'en_US');
            })
            ->leftJoin('mapping_versions', function (JoinClause $clause) {
                $clause->on('mapping_versions.dungeon_id', '=', 'dungeons.id')
                    ->whereRaw('mapping_versions.id = (SELECT MAX(mv2.id) FROM mapping_versions mv2 WHERE mv2.dungeon_id = dungeons.id)');
            })
            ->leftJoin('enemies', function (JoinClause $clause) {
                $clause->on('enemies.npc_id', '=', 'npcs.id')
                    ->on('enemies.mapping_version_id', '=', 'mapping_versions.id');
            })
            ->groupBy('npcs.id');

        $datatablesHandler = (new NpcsDatatablesHandler($request));

        return $datatablesHandler->setBuilder($npcs)
            ->addColumnHandler([
                new IdColumnHandler($datatablesHandler),
                new NameColumnHandler($datatablesHandler),
                new DungeonColumnHandler($datatablesHandler),
            ])
            ->applyRequestToBuilder()
            ->getResult();
    }
}
