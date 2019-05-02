<?php
$team = isset($team) ? $team : null;
/** @var string $view */
$cookieViewMode = isset($_COOKIE['routes_viewmode']) &&
($_COOKIE['routes_viewmode'] === 'biglist' || $_COOKIE['routes_viewmode'] === 'list') ?
    $_COOKIE['routes_viewmode'] : 'biglist';
?>
@include('common.general.inline', ['path' => 'dungeonroute/table'])

@section('scripts')
    @parent

    <script type="text/javascript">
        $(function () {
            let code = _inlineManager.getInlineCode('dungeonroute/table');

            // Init the code
            code.setViewMode('{{ $cookieViewMode }}');
            let tableView = code.setTableView('{{ $view}}');
            // Make sure the TeamID is set if we need it
            if (typeof tableView.setTeamId === 'function') {
                tableView.setTeamId({{ $team ? $team->id : -1}});
            }

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
    <div class="col-lg-2 pl-1 pr-1">
        @isset($team)
            {!! Form::label('team_name', __('Team')) !!}
            {!! Form::text('team_name', $team->name, ['class' => 'form-control', 'readonly' => 'readonly']) !!}
        @endisset
    </div>
    <div class="col-lg-2 pl-1 pr-1">
        {!! Form::label('dungeon_id', __('Dungeon')) !!}
        {!! Form::select('dungeon_id', [0 => 'All'] + \App\Models\Dungeon::active()->pluck('name', 'id')->toArray(), 0,
            ['id' => 'dungeonroute_search_dungeon_id', 'class' => 'form-control selectpicker']) !!}
    </div>
    <div class="col-lg-2 pl-1 pr-1">
        {!! Form::label('affixes[]', __('Affixes')) !!}
        {!! Form::select('affixes[]', \App\Models\AffixGroup::active()->get()->pluck('text', 'id'), null,
            ['id' => 'affixes',
            'class' => 'form-control affixselect selectpicker',
            'multiple' => 'multiple',
            'data-selected-text-format' => 'count > 1',
            'data-count-selected-text' => __('{0} affixes selected')]) !!}
    </div>
    <div class="col-lg-2 pl-1 pr-1">
        @include('common.dungeonroute.attributes', [
        'selectedIds' => array_merge( [-1], \App\Models\RouteAttribute::all()->pluck('id')->toArray() ),
        'showNoAttributes' => true])
    </div>
    <div class="col-lg-2 pl-1 pr-1">
        <div class="row no-gutters">
            @auth
                <div class="col">
                    {!! Form::label('favorites', __('Favorites')) !!}
                    {!! Form::checkbox('favorites', 1, 0, ['id' => 'favorites', 'class' => 'form-control left_checkbox']) !!}
                </div>
            @endauth
            <div class="col">
                <div class="d-none d-md-flex mb-2">
                    &nbsp;
                </div>
                <button id="dungeonroute_filter" class="btn btn-info col-lg">
                    <i class="fas fa-filter"></i> {{ __('Filter') }}
                </button>
            </div>
        </div>
    </div>
    <div class="col-lg-2 pl-1 pr-1">
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