@inject('seasonService', 'App\Service\Season\SeasonService')
<?php

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\RouteAttribute;
use App\Models\Tags\Tag;
use App\Models\Tags\TagCategory;
use App\Models\Team;
use App\Service\Season\SeasonService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * @var SeasonService              $seasonService
 * @var Collection<Tag>            $searchTags
 * @var Collection<Tag>            $autoCompleteTags
 * @var Collection<RouteAttribute> $allRouteAttributes
 */

/** @var DungeonRoute $model */
if (!isset($affixgroups)) {
    $affixgroups = $seasonService->getCurrentSeason()->affixGroups()->with('affixes')->get();
}

/** @var App\Models\Team|null $team */
$team                 ??= null;
$favorites            ??= false;
$tableId              ??= 'routes_table';
$filterButtonId       ??= 'dungeonroute_filter';
$dungeonSelectId      ??= 'dungeonroute_search_dungeon_id';
$affixSelectId        ??= 'dungeonroute_affixes_select';
$attributesSelectId   ??= 'dungeonroute_attributes_select';
$requirementsSelectId ??= 'dungeonroute_requirements_select';
$tagsSelectId         ??= 'dungeonroute_tags_select';

/** @var string $view */
$cookieViewMode = isset($_COOKIE['routes_viewmode']) &&
($_COOKIE['routes_viewmode'] === 'biglist' || $_COOKIE['routes_viewmode'] === 'list') ?
    $_COOKIE['routes_viewmode'] : 'biglist';

if ($team !== null) {
    $searchTags = $team->getAvailableTags();
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
@include('common.general.inline', ['path' => 'dungeonroute/table',
        'options' =>  [
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

            'teamPublicKey' => $team ? $team->public_key : '',
            'teams' => Auth::check() ? Auth::user()->teams()->whereHas('teamUsers', function(Builder $builder){
                $builder->isModerator(Auth::id());
            })->get() : [],
            'autoCompleteTags' => $autoCompleteTags,
        ],
])

@section('scripts')
    @parent

    <!--suppress HtmlDeprecatedAttribute -->
    <script type="text/javascript">
        $(function () {
            let code = _inlineManager.getInlineCode('dungeonroute/table');

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

<div class="row no-gutters {{$tableId}}_filter_container">
    @if($team instanceof Team)
        <div class="col-lg pl-1 pr-1">
            {!! Form::label('team_name', __('view_common.dungeonroute.table.team')) !!}
            {!! Form::text('team_name', $team->name, ['class' => 'form-control', 'readonly' => 'readonly']) !!}
        </div>
    @endisset
    <div class="col-lg pl-1 pr-1">
        @include('common.dungeon.select', [
            'id' => $dungeonSelectId,
            'allowSeasonSelection' => true,
            'showSeasons' => true,
            'showAll' => true,
            'showExpansions' => true,
            'required' => false,
        ])
    </div>
    <div class="col-lg pl-1 pr-1">
        {!! Form::label(sprintf('%s[]', $affixSelectId), __('view_common.dungeonroute.table.affixes')) !!}
        {!! Form::select(sprintf('%s[]', $affixSelectId), $affixgroups->pluck('text', 'id'), null,
            ['id' => $affixSelectId,
            'class' => 'form-control affixselect selectpicker',
            'multiple' => 'multiple',
            'data-selected-text-format' => 'count > 1',
            'data-count-selected-text' => __('view_common.dungeonroute.table.affixes_selected')]) !!}
    </div>
    <div class="col-lg pl-1 pr-1">
        @include('common.dungeonroute.attributes', [
            'id' => $attributesSelectId,
            'selectedIds' => array_merge( [-1], $allRouteAttributes->pluck('id')->toArray() ),
            'showNoAttributes' => true
        ])
    </div>
    <div class="col-lg pl-1 pr-1">
        <?php
        $requirements = ['enough_enemy_forces' => __('view_common.dungeonroute.table.enemy_enemy_forces')];
        if (Auth::check() && $view !== 'favorites') {
            $requirements['favorite'] = __('view_common.dungeonroute.table.favorite');
        }
        ?>
        {!! Form::label($requirementsSelectId, __('view_common.dungeonroute.table.requirements')) !!}
        {!! Form::select($requirementsSelectId, $requirements, 0, [
            'id' => $requirementsSelectId,
            'class' => 'form-control selectpicker',
            'multiple' => 'multiple',
            'data-selected-text-format' => 'count > 1',
            'data-count-selected-text' => __('view_common.dungeonroute.table.requirements_selected'),
        ]) !!}
    </div>
    @if(($view === 'profile' || $view === 'team'))
        <div class="col-lg pl-1 pr-1">
            {!! Form::label(sprintf('%s[]', $tagsSelectId), __('view_common.dungeonroute.table.tags')) !!}
            {!! Form::select(sprintf('%s[]', $tagsSelectId), $searchTags->pluck('name', 'name'), null,
                ['id' => $tagsSelectId,
                'class' => 'form-control selectpicker',
                'multiple' => 'multiple',
                // Change the original text
                'title' => $searchTags->isEmpty() ? __('view_common.dungeonroute.table.tags_title') : false,
                'data-selected-text-format' => 'count > 1',
                'data-count-selected-text' => __('view_common.dungeonroute.table.tags_selected')]) !!}
        </div>
    @endif
    <div class="col-lg pl-1 pr-1">
        <div class="mb-2">
            &nbsp;
        </div>
        <button id="{{ $filterButtonId }}" class="btn btn-info col-lg">
            <i class="fas fa-filter"></i> {{ __('view_common.dungeonroute.table.filter') }}
        </button>
    </div>
    <div class="col-lg pl-1 pr-1">
        <div class="mb-2">
            &nbsp;
        </div>
        <div class="mb-2 text-right">
            <button
                class="btn {{ $cookieViewMode === 'biglist' ? 'btn-primary' : 'btn-default' }} biglist table_list_view_toggle"
                data-viewmode="biglist">
                <i class="fas fa-th-list"></i>
            </button>
            <button class="btn {{ $cookieViewMode === 'list' ? 'btn-primary' : 'btn-default' }} list table_list_view_toggle"
                    data-viewmode="list">
                <i class="fas fa-list"></i>
            </button>
        </div>
    </div>
</div>
<table id="{{ $tableId }}" class="routes_table tablesorter default_table dt-responsive nowrap table-striped mt-2"
       width="100%">
    <thead>
    </thead>

    <tbody>
    </tbody>
</table>
