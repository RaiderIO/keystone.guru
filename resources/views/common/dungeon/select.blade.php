<?php
/**
 * @var $currentUserGameVersion \App\Models\GameVersion\GameVersion
 * @var $allDungeons \Illuminate\Support\Collection|\App\Models\Dungeon[]
 * @var $allActiveDungeons \Illuminate\Support\Collection|\App\Models\Dungeon[]
 * @var $allExpansions \Illuminate\Support\Collection|\App\Models\Expansion[]
 * @var $siegeOfBoralus \App\Models\Dungeon
 * @var $currentSeason \App\Models\Season
 * @var $nextSeason \App\Models\Season|null
 */
$id             = $id ?? 'dungeon_id_select';
$name           = $name ?? 'dungeon_id';
$label          = $label ?? __('views/common.dungeon.select.dungeon');
$required       = $required ?? true;
$showAll        = !isset($showAll) || $showAll;
$showSeasons    = isset($showSeasons) && $showSeasons && $currentUserGameVersion->has_seasons;
$showExpansions = isset($showExpansions) && $showExpansions;
// Show all dungeons if we're debugging
$activeOnly        = $activeOnly ?? true; // !config('app.debug');
$showSiegeWarning  = $showSiegeWarning ?? false;
$selected          = $selected ?? null;
$ignoreGameVersion = $ignoreGameVersion ?? false;
$dungeonsSelect    = [];

// If we didn't get any specific dungeons to display, resort to some defaults we may have set
if (!isset($dungeons)) {
    if ($selected === null && $showSeasons) {
        $selected = sprintf('season-%d', ($nextSeason ?? $currentSeason)->id);
    }
    // Build a list of seasons that we use to make selections of
    $seasons = [];
    if ($nextSeason !== null) {
        $seasons[] = $nextSeason;
    }
    $seasons[] = $currentSeason;

    // Show a selector to only show all dungeons in a specific season
    if ($showSeasons) {
        $dungeonsSelect[__('views/common.dungeon.select.seasons')] = [];
        foreach ($seasons as $season) {
            $dungeonsSelect[__('views/common.dungeon.select.seasons')][sprintf('season-%d', $season->id)] = $season->name;
        }
    }

    if ($showAll) {
        $dungeonsSelect[__('views/common.dungeon.select.all')] = [-1 => __('views/common.dungeon.select.all_dungeons')];
    }

    if ($showExpansions) {
        $validExpansions = $allExpansions->when(!$ignoreGameVersion, function (\Illuminate\Support\Collection $collection) use ($currentUserGameVersion) {
            return $collection->filter(function (\App\Models\Expansion $expansion) use ($currentUserGameVersion) {
                return $expansion->hasDungeonForGameVersion($currentUserGameVersion);
            });
        });

        foreach ($validExpansions as $expansion) {
            $key                                                         = sprintf('expansion-%d', $expansion->id);
            $dungeonsSelect[__('views/common.dungeon.select.all')][$key] =
                __('views/common.dungeon.select.all_expansion_dungeons', ['expansion' => __($expansion->name)]);

            if ($selected === null) {
                $selected = $key;
            }
        }
    }

    if ($showSeasons) {
        foreach ($seasons as $season) {
            $dungeonsSelect[__($season->name)] = $season->dungeons
                ->mapWithKeys(function (\App\Models\Dungeon $dungeon) {
                    return [$dungeon->id => __($dungeon->name)];
                })
                ->toArray();
        }
    }

    $dungeons = $activeOnly ? $allActiveDungeons : $allDungeons;
}
$dungeonsByExpansion = $dungeons->groupBy('expansion_id');


// Group the dungeons by expansion
// @TODO Fix the odd sorting of the expansions here, but it's late atm and can't think of a good way
foreach ($dungeonsByExpansion as $expansionId => $dungeonsOfExpansion) {
    /** @var \Illuminate\Support\Collection $dungeonsOfExpansion */
    /** @var \App\Models\Expansion $expansion */
    $expansion = $allExpansions->where('id', $expansionId)->first();

    if ($expansion->active || !$activeOnly) {
        $dungeonsOfExpansionFiltered = $dungeonsOfExpansion
            ->when(!$ignoreGameVersion, function (\Illuminate\Support\Collection $collection) use ($currentUserGameVersion) {
                return $collection->filter(function (\App\Models\Dungeon $dungeon) use ($currentUserGameVersion) {
                    return $dungeon->game_version_id === $currentUserGameVersion->id;
                });
        });

        // Only if there's something to display for this expansion
        if ($dungeonsOfExpansionFiltered->isNotEmpty()) {
            $dungeonsSelect[__($expansion->name)] = $dungeonsOfExpansionFiltered
                ->mapWithKeys(function (\App\Models\Dungeon $dungeon) {
                    return [$dungeon->id => __($dungeon->name)];
                });
        }
    }
}
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

<div class="form-group">
    @if($label !== false)
        {!! Form::label($name, $label . ($required ? '<span class="form-required">*</span>' : ''), [], false) !!}
    @endif
    {!! Form::select($name, $dungeonsSelect, $selected, array_merge(['id' => $id], ['class' => 'form-control selectpicker', 'data-live-search' => 'true'])) !!}
    @if( $showSiegeWarning )
        <div id="siege_of_boralus_faction_warning" class="text-warning mt-2" style="display: none;">
            <i class="fa fa-exclamation-triangle"></i> {{ __('views/common.dungeon.select.siege_of_boralus_warning') }}
        </div>
    @endif

</div>
