@inject('seasonService', 'App\Service\Season\SeasonService')
<?php

use App\Features\CreatorProfiles;
use App\Http\Requests\DungeonRoute\AjaxDungeonRouteDeleteBulkFormRequest;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\RouteAttribute;
use App\Models\Tags\Tag;
use App\Models\Tags\TagCategory;
use App\Models\Team;
use App\Service\Season\SeasonService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Laravel\Pennant\Feature;

/**
 * @var SeasonService                   $seasonService
 * @var Collection<int, Tag>            $searchTags
 * @var Collection<int, Tag>            $autoCompleteTags
 * @var Collection<int, RouteAttribute> $allRouteAttributes
 * @var string|null                     $inlineId
 * @var Team|null                       $team
 * @var DungeonRoute                    $model
 */

if (!isset($affixgroups)) {
    $affixgroups = $seasonService->getCurrentSeason()->affixGroups()->with('affixes')->get();
}

// Ok if it's not set - it's just a random default then
$inlineId             ??= bin2hex(random_bytes(16));
$team                 ??= null;
$favorites            ??= false;
$tableId              ??= 'routes_table';
$filterButtonId       ??= 'dungeonroute_filter';
$dungeonSelectId      ??= 'dungeonroute_search_dungeon_id';
$affixSelectId        ??= 'dungeonroute_affixes_select';
$attributesSelectId   ??= 'dungeonroute_attributes_select';
$requirementsSelectId ??= 'dungeonroute_requirements_select';
$tagsSelectId         ??= 'dungeonroute_tags_select';

// "Add to collection…" is offered on My routes only
$showAddToCollection = $view === 'profile' && Auth::check() && Feature::active(CreatorProfiles::class);

// Deleting several routes at once is offered on My routes only, and picks them in the route picker drawer
// instead of putting a checkbox on every row of this table; the host page renders the button that opens it
$massDeleteOpenSelector ??= null;
$showMassDelete         = $massDeleteOpenSelector !== null && $view === 'profile' && Auth::check();
$massDeletePickerId     = sprintf('%s_mass_delete_picker', $tableId);

/** @var string $view */
$cookieViewMode = isset($_COOKIE['routes_viewmode']) &&
($_COOKIE['routes_viewmode'] === 'biglist' || $_COOKIE['routes_viewmode'] === 'list') ?
    $_COOKIE['routes_viewmode'] : 'biglist';

if ($team !== null) {
    $searchTags = $team->tags;
} else if (Auth::check()) {
    $tagCategoryId = TagCategory::ALL[TagCategory::DUNGEON_ROUTE_PERSONAL];
    $searchTags    = Auth::user()->tags($tagCategoryId)->unique($tagCategoryId)->get();
} else {
    $searchTags = collect();
}


$autoCompleteTags = collect();

if (Auth::check()) {
    if ($team === null) {
        $autoCompleteTags = Auth::user()->tags()->unique(TagCategory::ALL[TagCategory::DUNGEON_ROUTE_PERSONAL])->get();
    } else {
        $autoCompleteTags = $team->getAvailableTags();
    }
} else {
    $autoCompleteTags = collect();
}
?>
@include('common.general.inline', [
        'id' => $inlineId,
        'path' => 'dungeonroute/table',
        'options' =>  [
            'currentUserId' => Auth::check() ? Auth::id() : -1,
            'currentUserPublicKey' => Auth::check() ? Auth::user()->public_key : '',
            'tableView' => $view,
            'viewMode' => $cookieViewMode,

            'tableSelector' => '#' . $tableId,
            'filterButtonSelector' => '#' . $filterButtonId,
            'dungeonSelectId' => '#' . $dungeonSelectId,
            'affixSelectId' => '#' . $affixSelectId,
            'attributesSelectId' => '#' . $attributesSelectId,
            'requirementsSelectId' => '#' . $requirementsSelectId,
            'tagsSelectId' => '#' . $tagsSelectId,
            'tableListViewToggleSelector' => sprintf('.%s_filter_container .table_list_view_toggle', $tableId),

            'teamPublicKey' => $team?->public_key ?? null,
            'teams' => Auth::check() ? Auth::user()->teams()->whereHas('teamUsers', function(Builder $teamUsersBuilder){
                $teamUsersBuilder->isModerator(Auth::id());
            })->get() : [],
            'autoCompleteTags' => $autoCompleteTags,
            'showAddToCollection' => $showAddToCollection,
            'massDeletePickerSelector' => $showMassDelete ? sprintf('#%s', $massDeletePickerId) : null,
        ],
])

@if($showAddToCollection)
    @include('common.collection.addtocollection', ['triggerSelector' => '.dungeonroute-add-to-collection'])
@endif

@section('scripts')
    @parent

    <!--suppress HtmlDeprecatedAttribute -->
    <script type="text/javascript">
        $(function () {
            let code = _inlineManager.getInlineCodeById('{{ $inlineId }}');

            // Build the table
            code.refreshTable();
        });

    </script>
    @include('common.handlebars.groupsetup')
    @include('common.handlebars.affixgroups')
    @include('common.handlebars.routeattributes')
    @include('common.handlebars.affixgroupsselect', ['id' => $affixSelectId])
    @include('common.handlebars.biglistfeatures')
    @include('common.handlebars.thumbnailcarousel')
@endsection

<div class="row g-0 {{$tableId}}_filter_container">
    @if($team instanceof Team)
        <div class="col-lg ps-1 pe-1">
            {{ html()->label(__('view_common.dungeonroute.table.team'), 'team_name') }}
            {{ html()->text('team_name', $team->name)->class('form-control')->isReadonly() }}
        </div>
    @endisset
    @include('common.dungeonroute.tablefilters', [
        'dungeonSelectId' => $dungeonSelectId,
        'affixSelectId' => $affixSelectId,
        'attributesSelectId' => $attributesSelectId,
        'requirementsSelectId' => $requirementsSelectId,
        'tagsSelectId' => $tagsSelectId,
        'affixgroups' => $affixgroups,
        'searchTags' => $searchTags,
        'showFavoriteRequirement' => Auth::check() && $view !== 'favorites',
        'showTags' => $view === 'profile' || $view === 'team',
    ])
    <div class="col-lg ps-1 pe-1">
        {{-- Block spacer matching the bare label height (24px) above the neighbouring select
             columns, so the button lines up with the select boxes. The former mb-2 added an extra
             8px that pushed the button below them. --}}
        <div>&nbsp;</div>
        <button id="{{ $filterButtonId }}" class="btn btn-info col-lg">
            <i class="fas fa-filter"></i> {{ __('view_common.dungeonroute.table.filter') }}
        </button>
    </div>
    <div class="col-lg ps-1 pe-1">
        <label>&nbsp;</label>
        <div class="mb-2 text-end">
            <button
                    class="btn {{ $cookieViewMode === 'biglist' ? 'btn-primary' : 'btn-default' }} biglist table_list_view_toggle"
                    data-viewmode="biglist">
                <i class="fas fa-th-list"></i>
            </button>
            <button
                    class="btn {{ $cookieViewMode === 'list' ? 'btn-primary' : 'btn-default' }} list table_list_view_toggle"
                    data-viewmode="list">
                <i class="fas fa-list"></i>
            </button>
        </div>
    </div>
</div>
@if($showMassDelete)
    @include('common.dungeonroute.picker', [
        'id' => $massDeletePickerId,
        'title' => __('view_common.dungeonroute.table.mass_delete_picker_title'),
        'action' => 'delete',
        'sourceScope' => 'mine',
        'lockedGameVersion' => null,
        'max' => AjaxDungeonRouteDeleteBulkFormRequest::MAX_DUNGEON_ROUTES,
        'actionUrl' => route('api.dungeonroute.delete.bulk'),
        'actionFieldName' => 'dungeon_routes',
        'openButtonSelector' => $massDeleteOpenSelector,
    ])
@endif

<table id="{{ $tableId }}" class="routes_table tablesorter default_table dt-responsive nowrap table-striped mt-2"
       width="100%">
    <thead>
    </thead>

    <tbody>
    </tbody>
</table>
