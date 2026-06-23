<?php

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\MapIcon;
use App\Models\MapIconType;
use Illuminate\Support\Collection;

/**
 * @var Collection<int, Collection<int, array{id: int, text: string}>> $dungeonStartsByDungeonId
 * @var DungeonRoute|null                                              $dungeonroute
 * @var string                                                         $dungeonSelectId
 **/

$id                       ??= 'dungeon_start_map_icon_id';
$dungeonroute             ??= null;
$dungeonStartsByDungeonId = collect($dungeonStartsByDungeonId);

// Edit mode: the route may use an older mapping version than the dungeon's current one, so source the
// options from the route's own mapping version to ensure the stored value is present and selectable.
if ($dungeonroute !== null) {
    $routeStarts = MapIcon::where('mapping_version_id', $dungeonroute->mapping_version_id)
        ->where('map_icon_type_id', MapIconType::ALL[MapIconType::MAP_ICON_TYPE_DUNGEON_START])
        ->get(['id', 'comment'])
        ->values()
        ->map(static fn(MapIcon $mapIcon, int $index) => [
            'id'   => $mapIcon->id,
            'text' => ($mapIcon->comment ?? '') !== '' ?
                (string)__($mapIcon->comment) :
                sprintf('%s #%d', __('mapicontypes.dungeon_start'), $index + 1),
        ]);

    if ($routeStarts->count() > 1) {
        $dungeonStartsByDungeonId = $dungeonStartsByDungeonId->put($dungeonroute->dungeon_id, $routeStarts);
    }
}

$selectedId = $dungeonroute?->dungeon_start_map_icon_id;
?>
@section('scripts')
    @parent

    <script>
        $(function () {
            let dungeonStartsByDungeonId = {!! $dungeonStartsByDungeonId->toJson() !!};
            let selectedDungeonStartId = {{ $selectedId !== null ? (int)$selectedId : 'null' }};

            let $dungeonSelect = $('#{{ $dungeonSelectId }}');
            let dungeonSelectionChanged = function () {
                let $select = $('#{{ $id }}');
                let $container = $('#{{ $id }}_container');

                let selectedDungeonId = parseInt($dungeonSelect.val());
                let dungeonStarts = dungeonStartsByDungeonId[selectedDungeonId] || [];

                $select.find('option').remove();

                // Only offer a choice when the dungeon actually has more than one start
                if (dungeonStarts.length > 1) {
                    for (let i = 0; i < dungeonStarts.length; i++) {
                        let dungeonStart = dungeonStarts[i];
                        $select.append(jQuery('<option>', {
                            value: dungeonStart.id,
                            text: dungeonStart.text,
                            selected: dungeonStart.id === selectedDungeonStartId
                        }));
                    }

                    refreshSelectPickers();
                    $container.show();
                } else {
                    refreshSelectPickers();
                    $container.hide();
                }
            };

            $dungeonSelect.bind('change', dungeonSelectionChanged);

            dungeonSelectionChanged();
        })
    </script>
@endsection

<div id="{{ $id }}_container" class="form-group" style="display: none;">
    <label for="{{ $id }}">
        {{ __('view_common.forms.createroute.dungeon_start') }}
        <i class="fas fa-info-circle" data-toggle="tooltip" title="{{
            __('view_common.forms.createroute.dungeon_start_title')
             }}"></i>
    </label>
    {{ html()->select('dungeon_start_map_icon_id', [], $selectedId)->id($id)->class('form-control selectpicker') }}
</div>
