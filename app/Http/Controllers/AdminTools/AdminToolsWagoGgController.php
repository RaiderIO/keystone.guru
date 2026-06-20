<?php

namespace App\Http\Controllers\AdminTools;

use App\Http\Controllers\Controller;
use App\Models\Floor\Floor;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class AdminToolsWagoGgController extends Controller
{
    public function wagoggImportIngameCoordinates(): View
    {
        return view('admin.tools.wagogg.importingamecoordinates');
    }

    /**
     * @throws Exception
     */
    public function wagoggImportIngameCoordinatesSubmit(Request $request): void
    {
        // Parse all UI Map Assignment TABLE data and convert them to a workable format
        $uiMapAssignmentTable                   = $request->get('ui_map_assignment_table_csv');
        $uiMapAssignmentTableParsed             = str_getcsv_assoc($uiMapAssignmentTable);
        $uiMapAssignmentTableHeaders            = array_shift($uiMapAssignmentTableParsed);
        $uiMapAssignmentTableHeaderIndexUiMapId = array_search('UiMapID', $uiMapAssignmentTableHeaders, true);
        $uiMapAssignmentTableHeaderIndexMinX    = array_search('Region_0', $uiMapAssignmentTableHeaders, true);
        $uiMapAssignmentTableHeaderIndexMinY    = array_search('Region_1', $uiMapAssignmentTableHeaders, true);
        $uiMapAssignmentTableHeaderIndexMaxX    = array_search('Region_3', $uiMapAssignmentTableHeaders, true);
        $uiMapAssignmentTableHeaderIndexMaxY    = array_search('Region_4', $uiMapAssignmentTableHeaders, true);

        /** @var Collection<Floor> $allFloors */
        //        $allDungeons = Dungeon::where('key', Dungeon::DUNGEON_AZJOL_NERUB)->get()->keyBy('id');
        $allFloors = Floor::where('facade', 0)
            ->where('ui_map_id', '>', 0)
            ->where('ingame_min_x', 0)
            ->where('ingame_min_y', 0)
//            ->where('ingame_max_x', 0)
//            ->where('ingame_max_y', 0)
            ->get();

        dump('Changed floors:');

        $allUiMapIds                = $allFloors->pluck('ui_map_id')->toArray();
        $uiMapAssignmentTableParsed = array_filter($uiMapAssignmentTableParsed, fn(array $item) => in_array($item[$uiMapAssignmentTableHeaderIndexUiMapId], $allUiMapIds));

        // Go over the UI Map Assignments and find the ones we're interested in
        foreach ($allFloors as $floor) {
            foreach ($uiMapAssignmentTableParsed as $index => $uiMapAssignmentRow) {
                if (((int)$uiMapAssignmentRow[$uiMapAssignmentTableHeaderIndexUiMapId]) === $floor->ui_map_id) {
                    $beforeModel = clone $floor;

                    $floor->update([
                        'ingame_min_x' => round((float)$uiMapAssignmentRow[$uiMapAssignmentTableHeaderIndexMinX], 2),
                        'ingame_min_y' => round((float)$uiMapAssignmentRow[$uiMapAssignmentTableHeaderIndexMinY], 2),
                        'ingame_max_x' => round((float)$uiMapAssignmentRow[$uiMapAssignmentTableHeaderIndexMaxX], 2),
                        'ingame_max_y' => round((float)$uiMapAssignmentRow[$uiMapAssignmentTableHeaderIndexMaxY], 2),
                    ]);

                    dump(sprintf('Updated floor %s (id: %d, ui_map_id: %d) ', __($floor->name), $floor->id, $floor->ui_map_id));

//                    $this->mappingChanged($beforeModel, $floor);

                    break;
                }
            }
        }

        dd('done!');
    }
}
