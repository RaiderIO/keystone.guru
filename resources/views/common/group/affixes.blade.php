@inject('seasonService', 'App\Service\Season\SeasonService')
<?php

use App\Models\Affix;
use App\Models\AffixGroup\AffixGroup;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Expansion;
use App\Models\Season;
use App\Service\Expansion\ExpansionData;
use Illuminate\Support\Collection;

/** This is the display of affixes when selecting them when creating a new route */

/**
 * @var DungeonRoute              $dungeonroute
 * @var array                     $defaultSelected
 * @var string|null               $dungeonSelector
 * @var Collection<Affix>         $affixes
 * @var Collection<ExpansionData> $expansionsData
 * @var Collection<AffixGroup>    $allAffixGroups
 * @var Collection<Expansion>     $allExpansions
 * @var Collection<AffixGroup>    $currentAffixes
 * @var array                     $dungeonExpansions
 * @var Season                    $currentSeason
 * @var Season|null               $nextSeason
 * @var Collection<AffixGroup>    $affixGroups
 */
// If route was set, initialize with the affixes of the current route so that the user may adjust its selection
if (isset($dungeonroute)) {
    $defaultSelected     = $dungeonroute->affixgroups->pluck(['affix_group_id'])->toArray();
    $defaultExpansionKey = $dungeonroute->dungeon->expansion->shortname;
} // Fill it by default with the current week's affix group for the current user
else if (empty($defaultSelected)) {
    $defaultSelected = $currentAffixes->pluck(['id'])->values();
}

$teemingSelector ??= null;
$names           ??= true;
$id              ??= 'route_select_affixes';

$allAffixGroupsWithSeasons = $allAffixGroups
    ->merge($currentSeason->affixGroups)
    ->merge($nextSeason?->affixGroups ?? collect());
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
    {!! Form::select($id . '[]', $allAffixGroupsWithSeasons->pluck('id', 'id'), null, ['id' => $id, 'class' => 'form-control affixselect d-none', 'multiple' => 'multiple']) !!}
    <div id="{{ $id }}_list_custom" class="affix_list col-lg-12">
        @if($nextSeason !== null)
            @foreach($nextSeason->affixGroups as $affixGroup)
                @include('common.group.affixrow', ['affixGroup' => $affixGroup, 'season' => $nextSeason, 'expansionKey' => $nextSeason->expansion->shortname])
            @endforeach
        @endif

        @foreach($expansionsData as $expansionData)
            @php($expansionSeason = $expansionData->getExpansionSeason())
            @foreach($expansionSeason->getAffixGroups()->getAllAffixGroups() as $affixGroup)
                @include('common.group.affixrow', ['affixGroup' => $affixGroup, 'season' => $expansionSeason->getSeason(), 'expansionKey' => $expansionData->getExpansion()->shortname])
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
