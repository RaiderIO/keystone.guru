<?php

$id = isset($id) ? $id : false;
$name = isset($name) ? $name : 'dungeon_id';
$label = isset($label) ? $label : __('Dungeon');
$required = isset($required) ? $required : true;
$showAll = isset($showAll) ? $showAll : true;
$activeOnly = isset($activeOnly) ? $activeOnly : true;
$showSiegeWarning = isset($showSiegeWarning) ? $showSiegeWarning : false;

$dungeonsSelect = [];
if ($showAll) {
    $dungeonsSelect = ['All' => [-1 => __('All dungeons')]];
}
$dungeonsBuilder = \App\Models\Dungeon::orderByRaw('expansion_id DESC, name');
if ($activeOnly) {
    $dungeonsBuilder = $dungeonsBuilder->active();
}
$dungeonsByExpansion = $dungeonsBuilder->get()->groupBy('expansion_id');

foreach ($dungeonsByExpansion as $expansionId => $dungeons) {
    $dungeonsSelect[\App\Models\Expansion::findOrFail($expansionId)->name] = $dungeons->pluck('name', 'id')->toArray();
}
?>

@if($showSiegeWarning)
    @section('scripts')
        @parent

        <script>
            $(function () {
                let $dungeonIdSelect = $('#dungeon_id_select');
                $dungeonIdSelect.bind('change', function () {
                    let $factionWarning = $('#siege_of_boralus_faction_warning');
                    if (parseInt($dungeonIdSelect.val()) === {{ \App\Models\Dungeon::siegeOfBoralus()->get()->first()->id }} ) {
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
    {!! Form::label($name, $label . ($required ? '<span class="form-required">*</span>' : ''), [], false) !!}
    {!! Form::select($name, $dungeonsSelect, null, array_merge($id ? ['id' => $id] : [], ['class' => 'form-control selectpicker'])) !!}
    @if( $showSiegeWarning )
        <div id="siege_of_boralus_faction_warning" class="text-warning mt-2" style="display: none;">
            <i class="fa fa-exclamation-triangle"></i> {{ __('Due to differences between the Horde and the Alliance version of Siege of Boralus, you are required to select a faction in the group composition.') }}
        </div>
    @endif

</div>
