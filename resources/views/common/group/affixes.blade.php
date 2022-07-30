@inject('seasonService', 'App\Service\Season\SeasonService')
<?php
/** @var $dungeonroute \App\Models\DungeonRoute */
/** @var $defaultSelected array */
/** @var $dungeonSelector string|null */
/** @var $affixes \Illuminate\Support\Collection */
/** @var $expansionsData \Illuminate\Support\Collection|\App\Service\Expansion\ExpansionData[] */
/** @var $allAffixGroups \Illuminate\Support\Collection|\App\Models\AffixGroup\AffixGroup[] */
/** @var $allExpansions \Illuminate\Support\Collection */
/** @var $currentAffixes array */
/** @var $dungeonExpansions array */
/** @var $currentSeason \Illuminate\Support\Collection|\App\Models\Season */
/** @var $nextSeason \Illuminate\Support\Collection|\App\Models\Season|null */
/** This is the display of affixes when selecting them when creating a new route */

/** @var \Illuminate\Support\Collection|\App\Models\AffixGroup\AffixGroup[] $affixGroups */
// If route was set, initialize with the affixes of the current route so that the user may adjust its selection
if (isset($dungeonroute)) {
    $defaultSelected     = $dungeonroute->affixgroups->pluck(['affix_group_id'])->toArray();
    $defaultExpansionKey = $dungeonroute->dungeon->expansion->shortname;
} // Fill it by default with the current week's affix group for the current user
else if (empty($defaultSelected)) {
    $defaultSelected = array_values($currentAffixes);
}

$teemingSelector = $teemingSelector ?? null;
$names = $names ?? true;
$id = $id ?? 'route_select_affixes';
?>

@include('common.general.inline', ['path' => 'common/group/affixes', 'options' => [
    'dungeonroute'      => $dungeonroute ?? null,
    'selectSelector'    => '#' . $id,
    'dungeonSelector'   => $dungeonSelector,
    'teemingSelector'   => $teemingSelector,
    'modal'             => $modal ?? false,
    'defaultSelected'   => $defaultSelected,
    'allExpansions'     => $allExpansions,
    'allAffixGroups'    => $allAffixGroups,
    'dungeonExpansions' => $dungeonExpansions,
    'currentAffixes'    => $currentAffixes,
    'currentSeason'     => $currentSeason,
    'nextSeason'        => $nextSeason,
]])

<?php // @formatter:off ?>
<div class="form-group">
    {!! Form::select($id . '[]', $allAffixGroups->pluck('id', 'id'), null, ['id' => $id, 'class' => 'form-control affixselect d-none', 'multiple' => 'multiple']) !!}
    <div id="{{ $id }}_list_custom" class="affix_list col-lg-12">
        @if($nextSeason !== null)
            @foreach($nextSeason->affixgroups as $affixGroup)
                @include('common.group.affixrow', ['affixGroup' => $affixGroup, 'season' => $nextSeason, 'expansionKey' => $nextSeason->expansion->shortname])
            @endforeach
        @endif
        @foreach($currentSeason->affixgroups as $affixGroup)
            @include('common.group.affixrow', ['affixGroup' => $affixGroup, 'season' => $currentSeason, 'expansionKey' => $currentSeason->expansion->shortname])
        @endforeach
        @foreach($expansionsData as $expansionData)
            @foreach($expansionData->getExpansionSeason()->getAffixGroups()->getAllAffixGroups() as $affixGroup)
                @include('common.group.affixrow', ['affixGroup' => $affixGroup, 'expansionKey' => $expansionData->getExpansion()->shortname])
            @endforeach
        @endforeach
    </div>
</div>

@foreach($expansionsData as $expansionData)
    @if($expansionData->getExpansionSeason()->isAwakened())
        @include('common.group.presets.awakened', [
            'expansion'    => $expansionData->getExpansion(),
            'season'       => $expansionData->getExpansionSeason()->getSeason(),
            'dungeonroute' => $dungeonroute ?? null,
        ])
    @elseif($expansionData->getExpansionSeason()->isTormented())
        @include('common.group.presets.tormented', [
            'expansion'    => $expansionData->getExpansion(),
            'season'       => $expansionData->getExpansionSeason()->getSeason(),
            'dungeonroute' => $dungeonroute ?? null,
        ])
    @endif
@endforeach
<?php // @formatter:on ?>
