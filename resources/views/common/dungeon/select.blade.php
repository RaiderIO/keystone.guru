<?php

use App\Models\Dungeon;
use App\Models\Expansion;
use App\Models\GameVersion\GameVersion;
use App\Models\Season;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * @var GameVersion                $currentUserGameVersion
 * @var Collection<int, Dungeon>   $allDungeons
 * @var Collection<int, Dungeon>   $allRaids
 * @var Collection<int, Dungeon>   $allActiveDungeons
 * @var Collection<int, Dungeon>   $allActiveRaids
 * @var Collection<int, Expansion> $allExpansions
 * @var Dungeon                    $siegeOfBoralus
 * @var Season                     $currentSeason
 * @var Season|null                $nextSeason
 */

$id                   ??= 'dungeon_id_select';
$name                 ??= 'dungeon_id';
$label                ??= __('view_common.dungeon.select.dungeon');
$required             ??= true;
$showAll              = !isset($showAll) || $showAll;
$showSeasons          = isset($showSeasons) && $showSeasons && $currentUserGameVersion->has_seasons;
$allowSeasonSelection = isset($allowSeasonSelection) && $allowSeasonSelection && $currentUserGameVersion->has_seasons;
$showExpansions       = isset($showExpansions) && $showExpansions;
// Show all dungeons if we're debugging
$activeOnly        ??= true; // !config('app.debug');
$showSiegeWarning  ??= false;
$selected          ??= null;
$ignoreGameVersion ??= false;
$multiple          ??= false;
$dungeonsSelect   = [];
$seasonDungeonIds = collect();

// If we didn't get any specific dungeons to display, resort to some defaults we may have set
if (!isset($dungeons)) {
    if ($selected === null && $allowSeasonSelection) {
        $selected = sprintf('season-%d', $currentSeason->id);
    }

    // Build a list of seasons that we use to make selections of
    $seasons      = [];
    $showLongName = false;
    if ($nextSeason !== null) {
        $seasons[] = $nextSeason;

        $showLongName = $nextSeason->expansion_id !== $currentSeason->expansion_id;
    }

    $seasons[] = $currentSeason;

    // Show a selector to only show all dungeons in a specific season
    if ($allowSeasonSelection) {
        $dungeonsSelect[__('view_common.dungeon.select.seasons')] = [];
        foreach ($seasons as $season) {
            $dungeonsSelect[__('view_common.dungeon.select.seasons')][sprintf('season-%d', $season->id)] =
                $showLongName && $season->id === $nextSeason->id ? $season->name_long : $season->name;
        }
    }

    if ($showAll) {
        $dungeonsSelect[__('view_common.dungeon.select.all')] = [-1 => __('view_common.dungeon.select.all_dungeons')];
    }

    if ($showExpansions) {
        $validExpansions = $allExpansions->when(!$ignoreGameVersion, static fn(Collection $collection) => $collection->filter(static fn(\App\Models\Expansion $expansion) => $expansion->hasDungeonForGameVersion($currentUserGameVersion)));

        foreach ($validExpansions as $expansion) {
            $key                                                        = sprintf('expansion-%d', $expansion->id);
            $dungeonsSelect[__('view_common.dungeon.select.all')][$key] =
                __('view_common.dungeon.select.all_expansion_dungeons', ['expansion' => __($expansion->name)]);

            if ($selected === null) {
                $selected = $key;
            }
        }
    }

    if ($showSeasons) {
        foreach ($seasons as $season) {
            $dungeonsSelect[$showLongName && $season->id === $nextSeason->id ? $season->name_long : $season->name] = $season->dungeons
                ->mapWithKeys(static fn(Dungeon $dungeon) => [$dungeon->id => __($dungeon->name)])
                ->toArray();

            $seasonDungeonIds = $seasonDungeonIds->merge($season->dungeons->pluck('id'));
        }
    }

    $dungeons = $activeOnly ? $allActiveDungeons->merge($allActiveRaids) : $allDungeons->merge($allRaids);
}

$dungeonsByExpansion = $dungeons->load([
    'mappingVersions' => fn(HasMany $query) => $query->without('dungeon'),
])->groupBy('expansion_id');


// Group the dungeons by expansion
// @TODO Fix the odd sorting of the expansions here, but it's late atm and can't think of a good way
foreach ($dungeonsByExpansion as $expansionId => $dungeonsOfExpansion) {
    /**
     * @var Collection<int, Dungeon> $dungeonsOfExpansion
     * @var Expansion                $expansion
     */
    $expansion = $allExpansions->where('id', $expansionId)->first();

    if ($expansion->active || !$activeOnly) {
        $dungeonsOfExpansionFiltered = $dungeonsOfExpansion
            ->when(
                !$ignoreGameVersion,
                static fn(Collection $collection) => $collection->filter(
                    static fn(Dungeon $dungeon) => $dungeon->mappingVersions->contains(
                        'game_version_id',
                        $currentUserGameVersion->id
                    )
                )
            )
            ->when(
                $seasonDungeonIds->isNotEmpty(),
                static fn(Collection $collection) => $collection->reject(
                    static fn(Dungeon $dungeon) => $seasonDungeonIds->contains($dungeon->id)
                )
            );


        // Only if there's something to display for this expansion
        if ($dungeonsOfExpansionFiltered->isNotEmpty()) {
            $dungeonsSelect[__($expansion->name)] = $dungeonsOfExpansionFiltered
                ->filter(static fn(Dungeon $dungeon) => !$dungeon->raid)
                ->mapWithKeys(static fn(Dungeon $dungeon) => [$dungeon->id => __($dungeon->name)]);

            $dungeonsSelect[sprintf('%s (%s)', __($expansion->name), __('view_common.dungeon.select.raid'))] = $dungeonsOfExpansionFiltered
                ->filter(static fn(Dungeon $dungeon) => $dungeon->raid)
                ->mapWithKeys(static fn(Dungeon $dungeon) => [$dungeon->id => __($dungeon->name)]);
        }
    }
}

// Drop empty optgroups (e.g. expansions without raids) so they do not render as blank groups in the dropdown
$dungeonsSelect = array_filter($dungeonsSelect, static fn($groupOptions) => count($groupOptions) > 0);
?>

@if($showSiegeWarning && $siegeOfBoralus)
    @section('scripts')
        @parent

        <script>
            $(function () {
                let $dungeonIdSelect = $('#{{ $id }}');
                $dungeonIdSelect.bind('change', function () {
                    let $factionWarning = $('#siege_of_boralus_faction_warning');
                    if (parseInt($dungeonIdSelect.val()) === {{ $siegeOfBoralus->id }}) {
                        $factionWarning.show();
                    } else {
                        $factionWarning.hide();
                    }
                });
            })
        </script>
    @endsection
@endif

<div class="mb-3">
    @if($label !== false)
        {{ html()->label($label . ($required ? '<span class="form-required">*</span>' : ''), $id) }}
    @endif
    {{ html()->select($name, $dungeonsSelect, $selected)->attributes(array_merge(['id' => $id],
        $multiple ? ['multiple' => 'multiple'] : [], [
            'class' => 'form-control selectpicker', 'data-live-search' => 'true'
        ])) }}
    @if( $showSiegeWarning )
        <div id="siege_of_boralus_faction_warning" class="text-warning mt-2" style="display: none;">
            <i class="fa fa-exclamation-triangle"></i> {{ __('view_common.dungeon.select.siege_of_boralus_warning') }}
        </div>
    @endif

</div>
