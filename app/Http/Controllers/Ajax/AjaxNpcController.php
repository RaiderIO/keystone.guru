<?php

namespace App\Http\Controllers\Ajax;

use App\Events\Models\Npc\NpcDeletedEvent;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\ChangesMapping;
use App\Logic\Datatables\ColumnHandler\Compendium\DungeonColumnHandler;
use App\Logic\Datatables\ColumnHandler\Npc\IdColumnHandler;
use App\Logic\Datatables\ColumnHandler\Npc\NameColumnHandler;
use App\Logic\Datatables\NpcsDatatablesHandler;
use App\Models\Npc\Npc;
use App\Models\User;
use App\Repositories\Interfaces\Npc\NpcRepositoryInterface;
use Exception;
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
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
    public function get(Request $request, NpcRepositoryInterface $npcRepository): array
    {
        $npcs = $npcRepository->getAdminListBuilder(app()->getLocale());

        $datatablesHandler = (new NpcsDatatablesHandler($request));

        return $datatablesHandler->setBuilder($npcs)
            ->addColumnHandler([
                new IdColumnHandler($datatablesHandler),
                new NameColumnHandler($datatablesHandler),
                new DungeonColumnHandler($datatablesHandler, NpcRepositoryInterface::ADMIN_LIST_DUNGEON_NAME_EXPRESSION),
            ])
            ->applyRequestToBuilder()
            ->getResult();
    }
}
