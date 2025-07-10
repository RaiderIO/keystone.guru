<?php

namespace App\Http\Controllers\Dungeon;

use App\Http\Controllers\Controller;
use App\Models\Dungeon;
use App\Models\Mapping\MappingVersion;
use App\Service\Mapping\MappingServiceInterface;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Session;

class MappingVersionController extends Controller
{
    public function saveNew(Request $request, Dungeon $dungeon, MappingServiceInterface $mappingService): RedirectResponse
    {
        $mappingService->createNewMappingVersionFromPreviousMapping($dungeon);

        Session::flash('status', __('controller.mappingversion.created_successfully'));

        return redirect()->route('admin.dungeon.edit', [
            'dungeon' => $dungeon,
        ]);
    }

    public function saveNewBare(Request $request, Dungeon $dungeon, MappingServiceInterface $mappingService): RedirectResponse
    {
        $currentMappingVersion = $dungeon->getCurrentMappingVersion();

        if ($currentMappingVersion === null) {
            return redirect()->route('admin.mappingversion.new', [
                'dungeon' => $dungeon,
            ]);
        }

        $newMappingVersion = $mappingService->copyMappingVersionToDungeon(
            $currentMappingVersion,
            $dungeon
        );

        $mappingService->copyMappingVersionContentsToDungeon(
            $currentMappingVersion,
            $newMappingVersion
        );

        Session::flash('status', __('controller.mappingversion.created_bare_successfully'));

        return redirect()->route('admin.dungeon.edit', [
            'dungeon' => $dungeon,
        ]);
    }

    /**
     * @throws Exception
     */
    public function delete(Request $request, Dungeon $dungeon, MappingVersion $mappingVersion): RedirectResponse
    {
        if ($mappingVersion->delete()) {
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
