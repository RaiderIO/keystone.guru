<?php
/**
 * A side drawer to pick routes and add them to a target in one go. The drawer knows nothing about the
 * target: it lists the source's routes through /ajax/routes within the locked constraints, hands the
 * ticked routes to the host page and, when it has a $addUrl, POSTs them there first.
 *
 * @var string              $id                  Prefix for every element id of the drawer.
 * @var string              $title               The drawer's heading, naming the target.
 * @var string              $sourceScope         Where the routes come from; only 'mine' (your own routes) exists.
 * @var GameVersion         $lockedGameVersion   Only routes of this game version are listed.
 * @var Season|null         $lockedSeason        When set, only routes of this season and its dungeons are listed.
 * @var Dungeon|null        $preselectedDungeon  Dungeon the dungeon filter starts on; the user may change it.
 * @var array<int, string>  $existingPublicKeys  Routes already in the target: listed, but cannot be ticked.
 * @var int|null            $max                 Most routes the target may hold, null for no limit.
 * @var string|null         $addUrl              Receives a POST of `{$addFieldName}[]` holding the ticked public keys;
 *                                               null leaves saving entirely to the host page.
 * @var string              $addFieldName
 * @var string|null         $openButtonSelector  Clicking any element matching this opens the drawer.
 */

use App\Models\AffixGroup\AffixGroup;
use App\Models\Dungeon;
use App\Models\GameVersion\GameVersion;
use App\Models\Season;
use App\Models\Tags\TagCategory;
use App\Service\Season\SeasonServiceInterface;

$sourceScope        ??= 'mine';
$lockedSeason       ??= null;
$preselectedDungeon ??= null;
$existingPublicKeys ??= [];
$max                ??= null;
$addUrl             ??= null;
$addFieldName       ??= 'dungeon_routes';
$openButtonSelector ??= null;

[$sourceParameters, $sourceLabel] = match ($sourceScope) {
    'mine'  => [['mine' => 1], __('view_common.dungeonroute.picker.source_mine')],
    default => throw new InvalidArgumentException(sprintf('Unknown route picker source scope %s', $sourceScope)),
};

$lockedParameters = ['game_version_id' => $lockedGameVersion->id];
$scopeLabels      = [$sourceLabel, __($lockedGameVersion->name)];

if ($lockedSeason !== null) {
    $lockedParameters['season_id']   = $lockedSeason->id;
    $lockedParameters['dungeon_ids'] = $lockedSeason->dungeons->pluck('id')->all();
    $scopeLabels[]                   = $lockedSeason->name_long;

    $dungeonSelectOptions = [
        'dungeons'          => $lockedSeason->dungeons,
        'showAllOfGiven'    => true,
        'ignoreGameVersion' => true,
        'activeOnly'        => false,
        'selected'          => $preselectedDungeon->id ?? -1,
    ];
} else {
    $dungeonSelectOptions = ['selectGameVersion' => $lockedGameVersion];
    if ($preselectedDungeon !== null) {
        $dungeonSelectOptions['selected'] = $preselectedDungeon->id;
    }
}

$affixSeason = $lockedSeason ?? app(SeasonServiceInterface::class)->getCurrentSeason();
$affixgroups = $affixSeason?->affixGroups()->with('affixes')->get() ?? collect();

$searchTags = Auth::check()
    ? Auth::user()->tags(TagCategory::ALL[TagCategory::DUNGEON_ROUTE_PERSONAL])->unique(TagCategory::ALL[TagCategory::DUNGEON_ROUTE_PERSONAL])->get()
    : collect();

$dungeonSelectId      = sprintf('%s_dungeon', $id);
$affixSelectId        = sprintf('%s_affixes', $id);
$attributesSelectId   = sprintf('%s_attributes', $id);
$requirementsSelectId = sprintf('%s_requirements', $id);
$tagsSelectId         = sprintf('%s_tags', $id);

$inlineOptions = [
    'drawerSelector'             => sprintf('#%s', $id),
    'openButtonSelector'         => $openButtonSelector,
    'titleSearchSelector'        => sprintf('#%s_title_search', $id),
    'dungeonSelectSelector'      => sprintf('#%s', $dungeonSelectId),
    'affixSelectSelector'        => sprintf('#%s', $affixSelectId),
    'attributesSelectSelector'   => sprintf('#%s', $attributesSelectId),
    'requirementsSelectSelector' => sprintf('#%s', $requirementsSelectId),
    'tagsSelectSelector'         => sprintf('#%s', $tagsSelectId),
    'listSelector'               => sprintf('#%s_list', $id),
    'rowTemplateSelector'        => sprintf('#%s_row_template', $id),
    'loadingSelector'            => sprintf('#%s_loading', $id),
    'emptySelector'              => sprintf('#%s_empty', $id),
    'errorSelector'              => sprintf('#%s_error', $id),
    'previousSelector'           => sprintf('#%s_previous', $id),
    'nextSelector'               => sprintf('#%s_next', $id),
    'rangeSelector'              => sprintf('#%s_range', $id),
    'selectionSelector'          => sprintf('#%s_selection', $id),
    'fullSelector'               => sprintf('#%s_full', $id),
    'addButtonSelector'          => sprintf('#%s_add', $id),
    'statusSelector'             => sprintf('#%s_status', $id),
    'listUrl'                    => '/ajax/routes',
    'pageSize'                   => 25,
    'sourceParameters'           => $sourceParameters,
    'lockedParameters'           => $lockedParameters,
    'existingPublicKeys'         => array_values($existingPublicKeys),
    'max'                        => $max,
    'addUrl'                     => $addUrl,
    'addFieldName'               => $addFieldName,
    'fallbackImageBaseUrl'       => trim(ksgAssetImage(), '/'),
    // The affix filter's options are decorated with their affix icons client side, the same way the
    // route table does it - the drawer carries the data so it survives being re-rendered
    'affixGroups'                => $affixgroups->mapWithKeys(static fn(AffixGroup $affixGroup): array => [
        $affixGroup->id => $affixGroup->affixes->map(static fn($affix): array => [
            'class' => $affix->image_name,
            'name'  => $affix->name,
        ])->all(),
    ])->all(),
    'rangeText'                  => __('view_common.dungeonroute.picker.range'),
    'keyLevelText'               => __('view_common.dungeonroute.picker.key_level'),
    'keyRangeText'               => __('view_common.dungeonroute.picker.key_range'),
    'enemyForcesText'            => __('view_common.dungeonroute.picker.enemy_forces'),
    'viewsText'                  => __('view_common.dungeonroute.cardrow.views'),
    'votesText'                  => __('view_common.dungeonroute.rating.nr_of_votes'),
    'selectedNoneText'           => __('view_common.dungeonroute.picker.selected_none'),
    'selectedOneText'            => __('view_common.dungeonroute.picker.selected_one'),
    'selectedManyText'           => __('view_common.dungeonroute.picker.selected_many'),
    'fullText'                   => __('view_common.dungeonroute.picker.full'),
    'addNoneText'                => __('view_common.dungeonroute.picker.add_none'),
    'addOneText'                 => __('view_common.dungeonroute.picker.add_one'),
    'addManyText'                => __('view_common.dungeonroute.picker.add_many'),
    'addFailedText'              => __('view_common.dungeonroute.picker.add_failed'),
];
?>
{{-- The data-inline-* attributes let a script activate the drawer again after swapping it into the page --}}
<div id="{{ $id }}" class="offcanvas offcanvas-end route_picker" tabindex="-1" aria-labelledby="{{ $id }}_title"
     data-inline-id="{{ $id }}" data-inline-path="common/dungeonroute/picker"
     data-inline-options="{{ json_encode($inlineOptions) }}">
    <div class="offcanvas-header border-bottom">
        <div class="min-w-0">
            <h2 id="{{ $id }}_title" class="offcanvas-title h5 mb-0">{{ $title }}</h2>
            <div class="small text-body-secondary">{{ implode(' · ', $scopeLabels) }}</div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"
                aria-label="{{ __('view_common.dungeonroute.picker.close') }}"></button>
    </div>

    <div class="offcanvas-body p-0">
        <div class="route_picker_filters border-bottom p-3">
            <div class="mb-3">
                <label for="{{ $id }}_title_search" class="form-label">
                    {{ __('view_common.dungeonroute.picker.title_search') }}
                </label>
                <input id="{{ $id }}_title_search" type="search" class="form-control" autocomplete="off"
                       placeholder="{{ __('view_common.dungeonroute.picker.title_search_placeholder') }}">
            </div>
            <div class="row g-0 mx-n1">
                @include('common.dungeonroute.tablefilters', [
                    'columnClass' => 'col-12 col-sm-6 px-1',
                    'dungeonSelectId' => $dungeonSelectId,
                    'affixSelectId' => $affixSelectId,
                    'attributesSelectId' => $attributesSelectId,
                    'requirementsSelectId' => $requirementsSelectId,
                    'tagsSelectId' => $tagsSelectId,
                    'affixgroups' => $affixgroups,
                    'searchTags' => $searchTags,
                    'showFavoriteRequirement' => Auth::check(),
                    'showTags' => $sourceScope === 'mine',
                    'dungeonSelectOptions' => $dungeonSelectOptions,
                ])
            </div>
        </div>

        <div class="route_picker_results" aria-busy="false">
            <p id="{{ $id }}_loading" class="route_picker_message text-body-secondary px-3 py-4 mb-0" hidden>
                <i class="fas fa-circle-notch fa-spin" aria-hidden="true"></i>
                {{ __('view_common.dungeonroute.picker.loading') }}
            </p>
            <p id="{{ $id }}_empty" class="route_picker_message text-body-secondary px-3 py-4 mb-0" hidden>
                {{ __('view_common.dungeonroute.picker.empty') }}
            </p>
            <p id="{{ $id }}_error" class="route_picker_message text-danger px-3 py-4 mb-0" hidden>
                {{ __('view_common.dungeonroute.picker.load_failed') }}
            </p>
            <ul id="{{ $id }}_list" class="list-unstyled route_picker_list mb-0"></ul>

            <nav class="d-flex align-items-center gap-2 px-3 py-2"
                 aria-label="{{ __('view_common.dungeonroute.picker.pagination') }}">
                <button id="{{ $id }}_previous" type="button" class="btn btn-sm btn-secondary" disabled>
                    <i class="fas fa-chevron-left" aria-hidden="true"></i>
                    {{ __('view_common.dungeonroute.picker.previous') }}
                </button>
                <span id="{{ $id }}_range" class="small text-body-secondary mx-auto"></span>
                <button id="{{ $id }}_next" type="button" class="btn btn-sm btn-secondary" disabled>
                    {{ __('view_common.dungeonroute.picker.next') }}
                    <i class="fas fa-chevron-right" aria-hidden="true"></i>
                </button>
            </nav>
        </div>
    </div>

    <div class="route_picker_footer border-top px-3 py-2">
        <div class="d-flex align-items-center flex-wrap gap-2">
            <div class="me-auto small">
                <span id="{{ $id }}_selection"></span>
                <span id="{{ $id }}_full" class="route_picker_full text-warning" hidden></span>
            </div>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="offcanvas">
                {{ __('view_common.dungeonroute.picker.cancel') }}
            </button>
            <button id="{{ $id }}_add" type="button" class="btn btn-primary" disabled>
                {{ __('view_common.dungeonroute.picker.add_none') }}
            </button>
        </div>
    </div>

    <div id="{{ $id }}_status" class="visually-hidden" role="status" aria-live="polite"></div>

    {{-- The same row a route gets in a list on the site (common.dungeonroute.cardrow), with the rank
         column giving way to the tick box and the per-pull graph left to the route's own page. --}}
    <template id="{{ $id }}_row_template">
        <li class="route_picker_row card_dungeonroute leaderboard_row d-flex align-items-center">
            <label class="route_picker_row_label d-flex align-items-center flex-fill mb-0">
                <span class="leaderboard_rank d-flex align-items-center justify-content-end">
                    <input type="checkbox" class="form-check-input route_picker_checkbox mt-0">
                </span>
                <span class="d-flex align-items-center flex-fill flex-wrap leaderboard_row_inner">
                    <span class="leaderboard_thumbnail route_picker_thumbnail"></span>
                    <span class="leaderboard_main">
                        <span class="leaderboard_title d-block">
                            <span class="route_picker_title"></span>
                            <span class="route_picker_unpublished badge bg-warning text-dark ms-1" hidden>
                                <i class="fas fa-eye-slash" aria-hidden="true"></i>
                                {{ __('view_common.dungeonroute.picker.unpublished') }}
                            </span>
                            <span class="route_picker_already_in badge bg-secondary ms-1" hidden>
                                <i class="fas fa-check" aria-hidden="true"></i>
                                {{ __('view_common.dungeonroute.picker.already_in') }}
                            </span>
                        </span>
                        <span class="leaderboard_author route_picker_dungeon text-muted small d-block"></span>
                    </span>
                    <span class="leaderboard_stats d-flex align-items-center text-muted small ms-auto">
                        <span class="leaderboard_enemy_forces route_picker_enemy_forces text-warning me-3" hidden></span>
                        <span class="leaderboard_rating route_picker_rating me-3" hidden></span>
                        <span class="leaderboard_level_chip route_picker_key_range me-3" hidden></span>
                        <span class="leaderboard_views route_picker_views"></span>
                    </span>
                </span>
            </label>
        </li>
    </template>
</div>

@include('common.general.inline', ['path' => 'common/dungeonroute/picker', 'id' => $id, 'options' => $inlineOptions])
