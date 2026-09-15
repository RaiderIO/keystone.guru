<?php

namespace App\Http\Controllers\Dungeon;

use App\Http\Controllers\Controller;
use App\Models\Dungeon;
use App\Models\GameVersion\GameVersion;
use App\Models\Mapping\MappingVersion;
use App\Service\Mapping\MappingServiceInterface;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Session;
use Teapot\StatusCode;

class MappingVersionController extends Controller
{
    public function saveNew(
        Request                 $request,
        Dungeon                 $dungeon,
        MappingServiceInterface $mappingService,
    ): RedirectResponse {
        $gameVersionId = $request->get('game_version');
        $action        = $request->get('action');

        $gameVersion = GameVersion::findOrFail($gameVersionId);

        if ($action === 'Add mapping version') {
            DB::transaction(static function () use ($mappingService, $dungeon, $gameVersion) {
                $mappingService->createNewMappingVersionFromPreviousMapping($dungeon, $gameVersion);
            });

            Session::flash('status', __('controller.mappingversion.created_successfully'));

            return redirect()->route('admin.dungeon.edit', [
                'dungeon' => $dungeon,
            ]);
        } elseif ($action === 'Add bare mapping version') {
            $currentMappingVersion = $dungeon->getCurrentMappingVersion($gameVersion);

            DB::transaction(static function () use ($mappingService, $dungeon, $gameVersion, $currentMappingVersion) {
                if ($currentMappingVersion === null) {
                    $mappingService->createNewBareMappingVersion($dungeon, $gameVersion);
                } else {
                    $newMappingVersion = $mappingService->copyMappingVersionToDungeon(
                        $currentMappingVersion,
                        $dungeon,
                    );

                    $mappingService->copyMappingVersionContentsToDungeon(
                        $currentMappingVersion,
                        $newMappingVersion,
                    );
                }
            });

            Session::flash('status', __('controller.mappingversion.created_bare_successfully'));

            return redirect()->route('admin.dungeon.edit', [
                'dungeon' => $dungeon,
            ]);
        } else {
            abort(StatusCode::INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @throws Exception
     */
    public function delete(Request $request, Dungeon $dungeon, MappingVersion $mappingVersion): RedirectResponse
    {
        $deleted = DB::transaction(static fn() => $mappingVersion->delete());

        if ($deleted) {
            Session::flash('status', __('controller.mappingversion.deleted_successfully'));

            $result = redirect()->route('admin.dungeon.edit', [
                'dungeon' => $dungeon,
            ]);
        } else {
            throw new Exception('Unable to delete mapping version!');
        }

        return $result;
    }
}
