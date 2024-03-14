<?php

namespace App\Http\Controllers\Floor;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\ChangesMapping;
use App\Http\Requests\Floor\FloorFormRequest;
use App\Models\Dungeon;
use App\Models\Floor\Floor;
use App\Models\Floor\FloorCoupling;
use App\Models\Mapping\MappingVersion;
use App\Service\MapContext\MapContextServiceInterface;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Session;

class FloorController extends Controller
{
    use ChangesMapping;

    /**
     * @throws Exception
     */
    public function store(FloorFormRequest $request, Dungeon $dungeon, ?Floor $floor = null): Floor
    {
        $beforeFloor = $floor === null ? null : clone $floor;

        $validated = $request->validated();

        if ($floor === null) {
            $floor   = Floor::create(array_merge([
                'dungeon_id' => $dungeon->id,
            ], $validated));
            $success = $floor instanceof Floor;
        } else {
            $success = $floor->update($validated);
        }

        // Update or insert it
        if ($success) {
            // Delete all directly connected floors
            $floor->floorcouplings()->delete();

            $floorCouplingsAttributes = [];
            foreach ($floor->dungeon->floors as $connectedFloorCandidate) {
                $isConnected = $request->get(sprintf('floor_%s_connected', $connectedFloorCandidate->id), false);

                if ($isConnected) {
                    $direction = $request->get(sprintf('floor_%s_direction', $connectedFloorCandidate->id));

                    // Recreate one by one
                    $floorCouplingsAttributes[] = [
                        'floor1_id' => $floor->id,
                        'floor2_id' => $connectedFloorCandidate->id,
                        'direction' => $direction,
                    ];
                }
            }

            FloorCoupling::insert($floorCouplingsAttributes);

            $this->mappingChanged($beforeFloor, $floor);
        } else {
            abort(500, 'Unable to save floor');
        }

        return $floor;
    }

    /**
     * @return Factory|View
     */
    public function create(Request $request, Dungeon $dungeon): View
    {
        return view('admin.floor.edit', [
            'dungeon' => $dungeon,
        ]);
    }

    /**
     * @return Application|Factory|RedirectResponse|View
     */
    public function edit(Request $request, Dungeon $dungeon, Floor $floor)
    {
        if ($floor->dungeon->id === $dungeon->id) {
            $dungeon = $floor->dungeon->load('floors');

            return view('admin.floor.edit', [
                'dungeon'        => $dungeon,
                'floor'          => $floor,
                'floorCouplings' => FloorCoupling::where('floor1_id', $floor->id)->get(),
            ]);
        } else {
            Session::flash('warning', sprintf(__('view_admin.floor.flash.invalid_floor_id'), __($floor->name), __($dungeon->name)));

            return redirect()->route('admin.dungeon.edit', ['dungeon' => $dungeon]);
        }
    }

    /**
     * @return Application|Factory|View|RedirectResponse
     */
    public function mapping(
        Request                    $request,
        MapContextServiceInterface $mapContextService,
        Dungeon                    $dungeon,
        Floor                      $floor)
    {
        /** @var MappingVersion $mappingVersion */
        $mappingVersion = MappingVersion::findOrFail($request->get('mapping_version'));

        if ($dungeon->id === $mappingVersion->dungeon_id) {
            $dungeon = $floor->dungeon->load('floors');

            return view('admin.floor.mapping', [
                'floor'          => $floor,
                'mapContext'     => $mapContextService->createMapContextMappingVersionEdit($dungeon, $floor, $mappingVersion),
                'mappingVersion' => $mappingVersion,
            ]);
        } else {
            Session::flash('warning', sprintf(__('view_admin.floor.flash.invalid_mapping_version_id'), __($dungeon->name)));

            return redirect()->route('admin.dungeon.edit', ['dungeon' => $dungeon]);
        }
    }

    /**
     * @return Factory|View
     *
     * @throws Exception
     */
    public function update(FloorFormRequest $request, Dungeon $dungeon, Floor $floor)
    {
        // Store it and show the edit page again
        $floor = $this->store($request, $dungeon, $floor);

        // Message to the user
        Session::flash('status', __('view_admin.floor.flash.floor_updated'));

        // Display the edit page
        return $this->edit($request, $dungeon, $floor);
    }

    /**
     * @throws Exception
     */
    public function savenew(FloorFormRequest $request, Dungeon $dungeon): RedirectResponse
    {
        // Store it and show the edit page
        $floor = $this->store($request, $dungeon);

        // Message to the user
        Session::flash('status', __('view_admin.floor.flash.floor_created'));

        return redirect()->route('admin.floor.edit', [
            'dungeon' => $dungeon,
            'floor'   => $floor,
        ]);
    }
}
