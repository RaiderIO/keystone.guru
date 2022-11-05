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
    /**
     * @param Request $request
     * @param Dungeon $dungeon
     * @param MappingServiceInterface $mappingService
     * @return RedirectResponse
     */
    public function savenew(Request $request, Dungeon $dungeon, MappingServiceInterface $mappingService): RedirectResponse
    {
        $mappingService->createNewMappingVersion($dungeon);

        Session::flash('status', __('controller.mappingversion.created_successfully'));

        return redirect()->route('admin.dungeon.edit', [
            'dungeon' => $dungeon,
        ]);
    }

    /**
     * @param Request $request
     * @param Dungeon $dungeon
     * @param MappingVersion $mappingVersion
     * @return RedirectResponse
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
