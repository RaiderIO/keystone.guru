@inject('seasonService', 'App\Service\Season\SeasonService')
<?php
/** @var $seasonService \App\Service\Season\SeasonService */
/** @var \App\Models\Tags\Tag[]|\Illuminate\Support\Collection $searchTags */
/** @var \App\Models\Tags\Tag[]|\Illuminate\Support\Collection $autocompletetags */
/** This is the template for the Affix Selection when using it in a dropdown */

/** @var \App\Models\DungeonRoute $model */
if (!isset($affixgroups)) {
    $affixgroups = $seasonService->getCurrentSeason()->affixgroups()->with('affixes')->get();
}

/** @var App\Models\Team|null $team */
$team = $team ?? null;
$favorites = $favorites ?? false;

/** @var string $view */
$cookieViewMode = isset($_COOKIE['routes_viewmode']) &&
($_COOKIE['routes_viewmode'] === 'biglist' || $_COOKIE['routes_viewmode'] === 'list') ?
    $_COOKIE['routes_viewmode'] : 'biglist';

if ($team !== null) {
    $searchTags = $team->getAvailableTags();
} elseif (Auth::check()) {
    $tagCategory = \App\Models\Tags\TagCategory::fromName(\App\Models\Tags\TagCategory::DUNGEON_ROUTE_PERSONAL);
    $searchTags = Auth::user()->tags($tagCategory)->unique($tagCategory)->get();
} else {
    $searchTags = collect();
}

$autocompleteTags = collect();

if (Auth::check()) {
    if ($team === null) {
        $autocompleteTags = Auth::user()->tags()->unique(\App\Models\Tags\TagCategory::fromName(\App\Models\Tags\TagCategory::DUNGEON_ROUTE_PERSONAL))->get();
    } else {
        $autocompleteTags = $team->getAvailableTags();
    }
} else {
    $autocompletetags = collect();
}
?>
@include('common.general.inline', ['path' => 'dungeonroute/table',
        'options' =>  [
            'tableView' => $view,
            'viewMode' => $cookieViewMode,
            'teamPublicKey' => $team ? $team->public_key : '',
            'teams' => Auth::check() ? \App\User::findOrFail(Auth::id())->teams()->whereHas('teamusers', function($teamuser){
                /** @var $teamuser \App\Models\TeamUser  */
                $teamuser->isModerator(Auth::id());
            })->get() : [],
            'autocompletetags' => $autocompleteTags,
        ]
])

@section('scripts')
    @parent

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
    @include('common.handlebars.affixgroupsselect')
    @include('common.handlebars.biglistfeatures')
    @include('common.handlebars.thumbnailcarousel')
@endsection

<div class="row no-gutters">
    @if($team instanceof \App\Models\Team)
        <div class="col-lg pl-1 pr-1">
            {!! Form::label('team_name', __('Team')) !!}
            {!! Form::text('team_name', $team->name, ['class' => 'form-control', 'readonly' => 'readonly']) !!}
        </div>
    @endisset
    <div class="col-lg pl-1 pr-1">
        @include('common.dungeon.select', ['id' => 'dungeonroute_search_dungeon_id', 'showAll' => true, 'required' => false])
    </div>
    <div class="col-lg pl-1 pr-1">
        {!! Form::label('affixes[]', __('Affixes')) !!}
        {!! Form::select('affixes[]', $affixgroups->pluck('text', 'id'), null,
            ['id' => 'affixes',
            'class' => 'form-control affixselect selectpicker',
            'multiple' => 'multiple',
            'data-selected-text-format' => 'count > 1',
            'data-count-selected-text' => __('views/common.dungeonroute.table.affixes_selected')]) !!}
    </div>
    <div class="col-lg pl-1 pr-1">
        @include('common.dungeonroute.attributes', [
        'selectedIds' => array_merge( [-1], \App\Models\RouteAttribute::all()->pluck('id')->toArray() ),
        'showNoAttributes' => true])
    </div>
    <div class="col-lg pl-1 pr-1">
        {!! Form::label('dungeonroute_requirements_select', __('views/common.dungeonroute.table.requirements')) !!}
        <?php
        $requirements = ['enough_enemy_forces' => __('views/common.dungeonroute.table.enemy_enemy_forces')];
        if (Auth::check() && $view !== 'favorites') {
            $requirements['favorite'] = __('views/common.dungeonroute.table.favorite');
        }
        ?>
        {!! Form::select('dungeon_id', $requirements, 0, [
            'id' => 'dungeonroute_requirements_select',
            'class' => 'form-control selectpicker',
            'multiple' => 'multiple',
            'data-selected-text-format' => 'count > 1',
            'data-count-selected-text' => __('{0} requirements')
        ]) !!}
    </div>
    @if(($view === 'profile' || $view === 'team'))
        <div class="col-lg pl-1 pr-1">
            {!! Form::label('dungeonroute_tags_select[]', __('Tags')) !!}
            {!! Form::select('dungeonroute_tags_select[]', $searchTags->pluck('name', 'name'), null,
                ['id' => 'dungeonroute_tags_select',
                'class' => 'form-control selectpicker',
                'multiple' => 'multiple',
                // Change the original text
                'title' => $searchTags->isEmpty() ? __('No tags available') : false,
                'data-selected-text-format' => 'count > 1',
                'data-count-selected-text' => __('{0} tags selected')]) !!}
        </div>
    @endif
    <div class="col-lg pl-1 pr-1">
        <div class="mb-2">
            &nbsp;
        </div>
        <button id="dungeonroute_filter" class="btn btn-info col-lg">
            <i class="fas fa-filter"></i> {{ __('Filter') }}
        </button>
    </div>
    <div class="col-lg pl-1 pr-1">
        <div class="mb-2">
            &nbsp;
        </div>
        <div class="mb-2 text-right">
            <button id="table_biglist_btn"
                    class="btn {{ $cookieViewMode === 'biglist' ? 'btn-primary' : 'btn-default' }} table_list_view_toggle"
                    data-viewmode="biglist">
                <i class="fas fa-th-list"></i>
            </button>
            <button id="table_list_btn"
                    class="btn {{ $cookieViewMode === 'list' ? 'btn-primary' : 'btn-default' }}  table_list_view_toggle"
                    data-viewmode="list">
                <i class="fas fa-list"></i>
            </button>
        </div>
    </div>
</div>
<table id="routes_table" class="routes_table tablesorter default_table dt-responsive nowrap table-striped mt-2"
       width="100%">
    <thead>
    </thead>

    <tbody>
    </tbody>
</table>