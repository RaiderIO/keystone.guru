<?php
/** @var $allDungeons \Illuminate\Support\Collection|\App\Models\Dungeon[] */
/** @var $allActiveDungeons \Illuminate\Support\Collection|\App\Models\Dungeon[] */
/** @var $allExpansions \Illuminate\Support\Collection|\App\Models\Expansion[] */
/** @var $siegeOfBoralus \App\Models\Dungeon */

$id = $id ?? 'dungeon_id_select';
$name = $name ?? 'dungeon_id';
$label = $label ?? __('Dungeon');
$required = $required ?? true;
$showAll = !isset($showAll) || $showAll;
$activeOnly = $activeOnly ?? true;
$showSiegeWarning = $showSiegeWarning ?? false;

$dungeonsSelect = [];
if ($showAll)
{
    $dungeonsSelect = ['All' => [-1 => __('All dungeons')]];
}

// If the user didn't pass us any dungeons, resort to some defaults we may have set
if (!isset($dungeons))
{
    $dungeons = $activeOnly ? $allActiveDungeons : $allDungeons;
}
$dungeonsByExpansion = $dungeons->groupBy('expansion_id');

// Group the dungeons by expansion
foreach ($dungeonsByExpansion as $expansionId => $dungeons)
{
    $dungeonsSelect[$allExpansions->where('id', $expansionId)->first()->name] = $dungeons->pluck('name', 'id')->toArray();
}
?>

@if($showSiegeWarning)
@section('scripts')
    @parent

    <script>
        $(function () {
            let $dungeonIdSelect = $('#{{ $id }}');
            $dungeonIdSelect.bind('change', function () {
                let $factionWarning = $('#siege_of_boralus_faction_warning');
                if (parseInt($dungeonIdSelect.val()) === {{ $siegeOfBoralus->id }} ) {
                    $factionWarning.show();
                } else {
                    $factionWarning.hide();
                }
            });
        })
    </script>
@endsection
@endif

<div class="form-group">
    @if($label !== false)
        {!! Form::label($name, $label . ($required ? '<span class="form-required">*</span>' : ''), [], false) !!}
    @endif
    {!! Form::select($name, $dungeonsSelect, null, array_merge(['id' => $id], ['class' => 'form-control selectpicker'])) !!}
    @if( $showSiegeWarning )
        <div id="siege_of_boralus_faction_warning" class="text-warning mt-2" style="display: none;">
            <i class="fa fa-exclamation-triangle"></i> {{ __('views/common.dungeon.select.siege_of_boralus_warning') }}
        </div>
    @endif

</div>
