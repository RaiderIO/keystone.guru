<?php
$profile = isset($profile) ? $profile : false;
// Whitelist
$cookieViewMode = isset($_COOKIE['routes_viewmode']) &&
($_COOKIE['routes_viewmode'] === 'biglist' || $_COOKIE['routes_viewmode'] === 'list') ? $_COOKIE['routes_viewmode'] : 'biglist';
?>
@include('common.general.inline', ['path' => 'dungeonroute/table'])

@section('scripts')
    @parent

    <script type="text/javascript">
        $(function () {
            let code = _inlineManager.getInlineCode('dungeonroute/table');

            // Init the code
            code.setProfileMode({{ $profile ? 'true' : 'false'}});
            code.setViewMode("{{ $cookieViewMode }}");

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

@section('content')
    @parent

    <div class="row">
        <div class="col-lg-2"></div>
        <div id="affixgroup_select_container" class="col-lg-2">
            {!! Form::label('dungeon_id', __('Dungeon')) !!}
            {!! Form::select('dungeon_id', [0 => 'All'] + \App\Models\Dungeon::active()->pluck('name', 'id')->toArray(), 0, ['id' => 'dungeonroute_search_dungeon_id', 'class' => 'form-control']) !!}
        </div>
        <div class="col-lg-2">
            {!! Form::label('affixes[]', __('Affixes')) !!}
            {!! Form::select('affixes[]', \App\Models\AffixGroup::active()->get()->pluck('text', 'id'), null,
                ['id' => 'affixes',
                'class' => 'form-control affixselect selectpicker',
                'multiple' => 'multiple',
                'data-selected-text-format' => 'count > 1',
                'data-count-selected-text' => __('{0} affixes selected')]) !!}
        </div>
        <div class="col-lg-2">
            @include('common.dungeonroute.attributes', [
            'selectedIds' => array_merge( [-1], \App\Models\RouteAttribute::all()->pluck('id')->toArray() ),
            'showNoAttributes' => true])
        </div>
        <div class="col-lg-2">
            <div class="row">
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
                    {!! Form::button(__('Filter'), ['id' => 'dungeonroute_filter', 'class' => 'btn btn-info col-lg']) !!}
                </div>
            </div>
        </div>
        <div class="col-lg-2">
            <div class="mb-2">
                &nbsp;
            </div>
            <div class="mb-2 text-right">
                <div id="table_biglist_btn"
                     class="btn {{ $cookieViewMode === 'biglist' ? 'btn-primary' : 'btn-default' }} table_list_view_toggle"
                     data-viewmode="biglist">
                    <i class="fas fa-th-list"></i>
                </div>
                <div id="table_list_btn"
                     class="btn {{ $cookieViewMode === 'list' ? 'btn-primary' : 'btn-default' }}  table_list_view_toggle"
                     data-viewmode="list">
                    <i class="fas fa-list"></i>
                </div>
            </div>
        </div>
    </div>
    <div id="routes_table_biglist_wrapper" class="{{ !$profile ? 'row' : '' }} routes_table_wrapper">
        <div class="{{ !$profile ? 'col-xl-8 offset-xl-2' : '' }}">
            <table id="routes_table_biglist" data-viewmode="biglist"
                   class="routes_table tablesorter default_table dt-responsive nowrap table-striped mt-2"
                   width="100%">
                <thead>
                <tr>
                    <th width="15%">{{ __('Preview') }}</th>
                    <th width="10%" class="d-none d-md-table-cell">{{ __('Dungeon') }}</th>
                    <th width="25%">{{ __('Features') }}</th>
                    <!-- Dummy header to allow for filtering based on attributes -->
                    <th width="15%" class="d-none">{{ __('Attributes') }}</th>
                    <th width="10%" class="d-none {{ $profile ? '' : 'd-lg-table-cell'}}">{{ __('Author') }}</th>
                    <th width="5%">{{ __('Views') }}</th>
                    <th width="5%">{{ __('Rating') }}</th>
                    <?php if( $profile ) { ?>
                    <th width="5%" class="d-none d-lg-table-cell">{{ __('Published') }}</th>
                    <th width="10%">{{ __('Actions') }}</th>
                    <?php } ?>
                </tr>
                </thead>

                <tbody>
                </tbody>
            </table>
        </div>
    </div>
    <div id="routes_table_list_wrapper" class="routes_table_wrapper" style="display: none;">
        <table id="routes_table_list" data-viewmode="list"
               class="routes_table tablesorter default_table dt-responsive nowrap table-striped mt-2"
               width="100%">
            <thead>
            <tr>
                <th width="15%">{{ __('Dungeon') }}</th>
                <th width="15%" class="d-none d-md-table-cell">{{ __('Affixes') }}</th>
                <th width="15%">{{ __('Attributes') }}</th>
                <th width="15%" class="d-none d-lg-table-cell">{{ __('Setup') }}</th>
                <th width="15%" class="d-none {{ $profile ? '' : 'd-lg-table-cell'}}">{{ __('Author') }}</th>
                <th width="5%" class="d-none d-md-table-cell">{{ __('Views') }}</th>
                <th width="5%">{{ __('Rating') }}</th>
                <?php if( $profile ) { ?>
                <th width="5%" class="d-none d-lg-table-cell">{{ __('Published') }}</th>
                <th width="10%">{{ __('Actions') }}</th>
                <?php } ?>
            </tr>
            </thead>

            <tbody>
            </tbody>
        </table>
    </div>
@endsection