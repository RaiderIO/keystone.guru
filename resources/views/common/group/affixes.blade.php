@inject('seasonService', 'App\Service\Season\SeasonService')
<?php
/** @var $dungeonroute \App\Models\DungeonRoute */
/** @var $defaultSelected array */
/** @var $currentAffixGroup \App\Models\AffixGroup\AffixGroup */
/** @var $currentExpansion \App\Models\Expansion */
/** @var $dungeonExpansions array */
/** @var $dungeonSelector string|null */
/** @var $seasonService \App\Service\Season\SeasonService */
/** @var $affixes \Illuminate\Support\Collection */
/** @var $affixGroups \Illuminate\Support\Collection|\App\Models\AffixGroup\AffixGroup[] */
/** @var $timewalkingAffixGroups \Illuminate\Support\Collection */
/** This is the display of affixes when selecting them when creating a new route */

/** @var \Illuminate\Support\Collection|\App\Models\AffixGroup\AffixGroup[] $affixGroups */
$currentSeason = $seasonService->getCurrentSeason();
$defaultExpansionKey = $currentExpansion->shortname;

// If route was set, initialize with the affixes of the current route so that the user may adjust its selection
if (isset($dungeonroute)) {
    if ($dungeonroute->dungeon->expansion->hasTimewalkingEvent()) {
        $defaultSelected = $dungeonroute->timewalkingeventaffixgroups->pluck(['timewalking_event_affix_group_id'])->toArray();
    } else {
        $defaultSelected = $dungeonroute->affixgroups->pluck(['affix_group_id'])->toArray();
    }
//    dd($dungeonroute->timewalkingeventaffixgroups);
    $defaultExpansionKey = $dungeonroute->dungeon->expansion->shortname;
}

// Fill it by default with the current week's affix group
if (empty($defaultSelected)) {
    $defaultSelected[] = $currentAffixGroup->id;
}

// Convert a list of ints to <expansion>-<int>
if (isset($defaultSelected) && is_array($defaultSelected)) {
    // Convert the IDs to something the select box uses
    $converted = [];
    foreach ($defaultSelected as $affixGroupId) {
        $converted[] = sprintf('%d-%s', $affixGroupId, $defaultExpansionKey);
    }
    $defaultSelected = $converted;
}

$teemingSelector = $teemingSelector ?? null;
$names = $names ?? true;
$id = $id ?? 'route_select_affixes';

$presets = [];
for ($i = 0; $i < $currentSeason->presets; $i++) {
    $presets[$i] = __('views/common.group.affixes.seasonal_index_preset', ['count' => $i + 1]);
}

/** Convert to a list like this:
"70-shadowlands" => 70
"71-shadowlands" => 71
"72-shadowlands" => 72
"1-legion" => 1
"2-legion" => 2
 */
$selectValues = $affixGroups->pluck('id')->mapWithKeys(function (int $value) use ($currentExpansion) {
    return [sprintf('%d-%s', $value, $currentExpansion->shortname) => $value];
});

foreach ($timewalkingAffixGroups as $expansionKey => $timewalkingAffixGroupsList) {
    $selectValues = $selectValues->merge($timewalkingAffixGroupsList->mapWithKeys(function (\App\Models\Timewalking\TimewalkingEventAffixGroup $affixGroup) use ($expansionKey) {
        return [sprintf('%d-%s', $affixGroup->id, $expansionKey) => $affixGroup->id];
    }));
}
?>

@include('common.general.inline', ['path' => 'common/group/affixes', 'options' => [
    'selectSelector'         => '#' . $id,
    'dungeonSelector'        => $dungeonSelector,
    'teemingSelector'        => $teemingSelector,
    'defaultSelected'        => $defaultSelected,
    'defaultExpansionKey'    => $defaultExpansionKey,
    'affixGroups'            => $affixGroups,
    'timewalkingAffixGroups' => $timewalkingAffixGroups,
    'modal'                  => $modal ?? false,
    'dungeonExpansions'      => $dungeonExpansions,
    'currentAffixGroupId'    => $currentAffixGroup->id,
    'currentExpansionKey'    => $currentExpansion->shortname,
]])

<div class="form-group">
    {!! Form::select($id . '[]', $selectValues, null, ['id' => $id, 'class' => 'form-control affixselect d-none', 'multiple'=>'multiple']) !!}
    <?php // formatter:off ?>
    <div id="{{ $id }}_list_custom" class="affix_list col-lg-12">
        @foreach($affixGroups as $affixGroup)
            @include('common.group.affixrow', ['affixGroup' => $affixGroup, 'expansionKey' => $currentExpansion->shortname])
        @endforeach

        @foreach($timewalkingAffixGroups as $expansionKey => $affixGroups)
            @foreach($affixGroups as $affixGroup)
                @include('common.group.affixrow', ['affixGroup' => $affixGroup, 'expansionKey' => $expansionKey])
            @endforeach
        @endforeach
    </div>
    <?php // formatter:on ?>
</div>

@if($isAwakened)
    <div class="form-group">
        {!! Form::label('seasonal_index', __('views/common.group.affixes.awakened_enemy_set')) !!} <span
            class="form-required">*</span>
        <i class="fas fa-info-circle" data-toggle="tooltip" title="{{
    __('views/common.group.affixes.awakened_enemy_set_title')
     }}"></i>
        {!! Form::select('seasonal_index', $presets, isset($dungeonroute) ? $dungeonroute->seasonal_index : 0,
            ['id' => 'seasonal_index', 'class' => 'form-control selectpicker']) !!}
    </div>
@endif

@if($isTormented)
    <div class="form-group">
        {!! Form::label('seasonal_index', __('views/common.group.affixes.tormented_preset')) !!} <span
            class="form-required">*</span>
        <i class="fas fa-info-circle" data-toggle="tooltip" title="{{
    sprintf(__('views/common.group.affixes.tormented_preset_title'), $currentSeason->presets)
     }}"></i>
        {!! Form::select('seasonal_index', $presets,
            isset($dungeonroute) ? $dungeonroute->seasonal_index : 0,
            ['id' => 'seasonal_index', 'class' => 'form-control selectpicker']) !!}
    </div>
@endif
