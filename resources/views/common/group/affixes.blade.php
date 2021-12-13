@inject('seasonService', 'App\Service\Season\SeasonService')
<?php
/** @var $dungeonExpansions array */
/** @var $dungeonSelector string|null */
/** @var $seasonService \App\Service\Season\SeasonService */
/** @var $affixes \Illuminate\Support\Collection */
/** @var $affixGroups \Illuminate\Support\Collection|\App\Models\AffixGroup\AffixGroup[] */
/** @var $timewalkingAffixGroups \Illuminate\Support\Collection */
/** This is the display of affixes when selecting them when creating a new route */

/** @var \Illuminate\Support\Collection|\App\Models\AffixGroup\AffixGroup[] $affixGroups */
$currentSeason = $seasonService->getCurrentSeason();
$defaultSelected = $defaultSelected ?? [];
$teemingSelector = $teemingSelector ?? null;
$names = $names ?? true;
$id = $id ?? 'affixes';

$presets = [];
for ($i = 0; $i < $currentSeason->presets; $i++) {
    $presets[$i] = __('views/common.group.affixes.seasonal_index_preset', ['count' => $i + 1]);
}
?>

@include('common.general.inline', ['path' => 'common/group/affixes', 'options' => [
    'selectSelector'            => '#' . $id,
    'dungeonSelector'           => $dungeonSelector,
    'teemingSelector'           => $teemingSelector,
    'affixGroups'               => $affixGroups,
    'timewalkingAffixGroups'    => $timewalkingAffixGroups,
    'modal'                     => $modal ?? false,
    'dungeonExpansions'         => $dungeonExpansions,
]])

<div class="form-group">
    {!! Form::select($id . '[]', $affixGroups->pluck('id', 'id'),
        !isset($dungeonroute) ? $defaultSelected : $dungeonroute->affixgroups->pluck(['affix_group_id']),
        ['id' => $id, 'class' => 'form-control affixselect d-none', 'multiple'=>'multiple']) !!}
    <?php // formatter:off ?>
    <div id="{{ $id }}_list_custom" class="affix_list col-lg-12">
        @foreach($affixGroups as $affixGroup)
            @include('common.group.affixrow', ['affixGroup' => $affixGroup, 'cssClasses' => 'season'])
        @endforeach

        @foreach($timewalkingAffixGroups as $expansionKey => $affixGroups)
            @foreach($affixGroups as $affixGroup)
                @include('common.group.affixrow', ['affixGroup' => $affixGroup, 'cssClasses' => $expansionKey])
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
