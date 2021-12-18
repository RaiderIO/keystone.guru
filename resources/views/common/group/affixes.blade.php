@inject('seasonService', 'App\Service\Season\SeasonService')
<?php
/** @var $dungeonroute \App\Models\DungeonRoute */
/** @var $defaultSelected array */
/** @var $dungeonSelector string|null */
/** @var $affixes \Illuminate\Support\Collection */
/** @var $expansionsData array */
/** @var $allAffixGroups \Illuminate\Support\Collection */
/** @var $currentAffixes array */
/** @var $dungeonExpansions array */
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
    'allAffixGroups'    => $allAffixGroups,
    'dungeonExpansions' => $dungeonExpansions,
    'currentAffixes'    => $currentAffixes
]])

<?php // @formatter:off ?>
<div class="form-group">
    {!! Form::select($id . '[]', $allAffixGroups->pluck('id', 'id'), null, ['id' => $id, 'class' => 'form-control affixselect d-none', 'multiple'=>'multiple']) !!}
    <div id="{{ $id }}_list_custom" class="affix_list col-lg-12">
        @foreach($expansionsData as $expansionData)
            @foreach($expansionData['season']['affixGroups']['all'] as $affixGroup)
                @include('common.group.affixrow', ['affixGroup' => $affixGroup, 'expansionKey' => $expansionData['expansion']->shortname])
            @endforeach
        @endforeach
    </div>
</div>

@foreach($expansionsData as $expansionData)
    @if($expansionData['season']['isAwakened'])
        @include('common.group.presets.awakened', ['expansion' => $expansionData['expansion'], 'season' => $expansionData['season']['current'], 'dungeonroute' => $dungeonroute ?? null])
    @elseif($expansionData['season']['isTormented'])
        @include('common.group.presets.tormented', ['expansion' => $expansionData['expansion'], 'season' => $expansionData['season']['current'], 'dungeonroute' => $dungeonroute ?? null])
    @endif
@endforeach
<?php // @formatter:on ?>
