<?php
$profile = isset($profile) ? $profile : false;
?>

@section('scripts')
    @parent

    <script type="text/javascript">
        let _viewMode = 'biglist';
        let _dt;

        $(function () {
            let $routesTable = $('#routes_table');
            _dt = $routesTable.DataTable({
                'processing': true,
                'serverSide': true,
                'responsive': true,
                'ajax': {
                    'url': '{{ route('api.dungeonroutes') }}',
                    'data': function (d) {
                        d.favorites = $("#favorites").is(':checked') ? 1 : 0;
                        <?php if( $profile ) {?>
                            d.mine = true;
                        <?php } ?>
                    }, <?php // Enable caching when in production mode, disable it when developing ?>
                    'cache': '{{ env('APP_DEBUG', true) ? 'false' : 'true' }}',
                },
                'drawCallback': function (settings) {
                    // Don't do anything when the message "no data available" is showing
                    if (settings.json.data.length > 0) {
                        $.each($routesTable.find('tbody tr'), function (index, value) {
                            $(value).data('publickey', settings.json.data[index].public_key);
                        });
                    }
                },
                'lengthMenu': [25],
                'bLengthChange': false,
                // Order by affixes by default
                "order": [[1, "asc"]],
                'columns': _getColumns()
            });

            _dt.on('draw.dt', function (e, settings, json, xhr) {
                refreshTooltips();
                let $deleteBtns = $('.dungeonroute-delete');
                $deleteBtns.unbind('click');
                $deleteBtns.bind('click', _promptDeleteDungeonRoute);

                let $cloneBtns = $('.dungeonroute-clone');
                $cloneBtns.unbind('click');
                $cloneBtns.bind('click', function (clickEvent) {
                    let key = $(clickEvent.target).data('publickey');
                    $("<a>").attr("href", '{{ route('dungeonroute.clone', ['dungeonroute' => 'replace_me']) }}'.replace('replace_me', key)).attr("target", "_blank")[0].click();
                });
            });

            $routesTable.on('click', 'tbody tr', function (clickEvent) {
                let key = $(clickEvent.currentTarget).data('publickey');

                window.open('{{ route('dungeonroute.' . ($profile ? 'edit' : 'view'), ['dungeonroute' => 'replace_me']) }}'.replace('replace_me', key));
            });

            $routesTable.on('mouseenter', 'tbody tr', function () {
                _dt.$('tr.selected').removeClass('selected');
                $(this).addClass('selected');
            });

            $routesTable.on('mouseleave', 'tbody tr', function () {
                if ($(this).hasClass('selected')) {
                    $(this).removeClass('selected');
                }
            });

            $("#dungeonroute_filter").bind('click', function () {

                // Build the search parameters
                let dungeonId = $("#dungeonroute_search_dungeon_id").val();
                if (parseInt(dungeonId) < 1) {
                    dungeonId = '';
                }
                let affixes = $("#affixes").val();
                let attributes = $("#attributes").val();

                _dt.column(0).search(dungeonId);
                _dt.column(1).search(affixes);
                _dt.column(2).search(attributes);
                _dt.draw();
            });
            // Do this asap
            // $("#affixgroup_select_container").html(handlebarsAffixGroupSelectParse({}));

            $('.table_list_view_toggle').bind('click', _tableListViewClicked);
        });

        /**
         * Get the columns based on the current view for the table.
         **/
        function _getColumns() {

            let columns = [];

            columns.push({
                'data': 'dungeon.name',
                'name': 'dungeon_id',
                'render': function (data, type, row, meta) {
                    let result = data;
                    switch (_viewMode) {
                        case 'biglist':
                            let url = "{{ sprintf('/images/route_thumbnails/replace_me_%s.png', 1) }}";
                            result = '<div><img src="' + url.replace('replace_me', row.public_key) + '"></img></div>';
                            break;
                    }
                    return result;
                },
            });

            columns.push({
                'data': 'affixes',
                'name': 'affixes.id',
                'render': function (data, type, row, meta) {
                    return handlebarsAffixGroupsParse(data);
                },
                'className': 'd-none d-md-table-cell'
            });
            columns.push({
                'data': 'routeattributes',
                'name': 'routeattributes.name',
                'render': function (data, type, row, meta) {
                    return handlebarsRouteAttributesParse(data);
                }
            });
            columns.push({
                'data': 'setup',
                'render': function (data, type, row, meta) {
                    return handlebarsGroupSetupParse(data);
                },
                'className': 'd-none d-lg-table-cell',
                'orderable': false
            });
            columns.push({
                'data': 'author.name',
                'name': 'author.name',
                'className': 'd-none {{ $profile ? '' : 'd-lg-table-cell'}}'
            });
            columns.push({
                'data': 'views',
                'name': 'views',
                'className': 'd-none {{ $profile ? '' : 'd-lg-table-cell'}}'
            });
            columns.push({
                'name': 'rating',
                'render': function (data, type, row, meta) {
                    let result = '-';

                    if (row.rating_count !== 0) {
                        result = row.avg_rating;
                        if (row.rating_count === 1) {
                            result += ' (' + row.rating_count + ' {{ __('vote') }})';
                        } else {
                            result += ' (' + row.rating_count + ' {{ __('votes') }})';
                        }
                    }

                    return result;
                }
            });

            <?php if($profile){ ?>
            columns.push({
                'render': function (data, type, row, meta) {
                    return row.published === 1 ? 'Yes' : 'No';
                },
                'className': 'd-none d-lg-table-cell',
            });

            columns.push({
                'render': function (data, type, row, meta) {
                    let actionsHtml = $("#dungeonroute_table_profile_actions_template").html();

                    let template = handlebars.compile(actionsHtml);
                    return template({public_key: row.public_key});
                }
            });
            <?php } ?>

                return columns;
        }

        function _tableListViewClicked() {
            // Reset to default
            $('.table_list_view_toggle').removeClass('btn-default').removeClass('btn-primary').addClass('btn-default');

            // This is now the selected button
            $(this).removeClass('btn-default').addClass('btn-primary');

            _viewMode = $(this).data('viewmode');
            _dt.draw();
        }

        /**
         * Prompts the user to delete a route (called by button press)
         * @param clickEvent
         * @private
         */
        function _promptDeleteDungeonRoute(clickEvent) {
            if (confirm('{{ __('Are you sure you wish to delete this route?') }}')) {
                let publicKey = $(clickEvent.target).data('publickey');

                $.ajax({
                    type: 'DELETE',
                    url: '{{ route('api.dungeonroute.delete', ['dungeonroute' => '']) }}/' + publicKey,
                    dataType: 'json',
                    success: function (json) {
                        addFixedFooterSuccess("{{ __('Route deleted successfully') }}");
                        $("#dungeonroute_filter").trigger('click');
                    }
                });
            }
        }
    </script>
    <script id="dungeonroute_table_profile_actions_template" type="text/x-handlebars-template">
        <div class="row no-gutters">
            <div class="col">
                <div class="btn btn-danger dungeonroute-clone"
                     data-publickey="@{{ public_key }}">{{ __('Clone') }}</div>
            </div>
            <div class="col mt-2 mt-xl-0">
                <div class="btn btn-danger dungeonroute-delete"
                     data-publickey="@{{ public_key }}">{{ __('Delete') }}</div>
            </div>
        </div>
    </script>
    @include('common.handlebars.groupsetup')
    @include('common.handlebars.affixgroups')
    @include('common.handlebars.routeattributes')
    @include('common.handlebars.affixgroupsselect')
@endsection

@section('content')
    @parent

    <div class="row">
        <div class="col-lg-2">
            {!! Form::label('dungeon_id', __('Dungeon')) !!}
            {!! Form::select('dungeon_id', [0 => 'All'] + \App\Models\Dungeon::active()->pluck('name', 'id')->toArray(), 0, ['id' => 'dungeonroute_search_dungeon_id', 'class' => 'form-control']) !!}
        </div>
        <div id="affixgroup_select_container" class="col-lg-2">
            {!! Form::label('affixes[]', __('Affixes')) !!}
            {!! Form::select('affixes[]', \App\Models\AffixGroup::all()->pluck('text', 'id'), null,
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
            @auth
                {!! Form::label('favorites', __('Favorites')) !!}
                {!! Form::checkbox('favorites', 1, 0, ['id' => 'favorites', 'class' => 'form-control left_checkbox']) !!}
            @endauth
        </div>
        <div class="col-lg-2">
            <div class="mb-2">
                &nbsp;
            </div>
            {!! Form::button(__('Filter'), ['id' => 'dungeonroute_filter', 'class' => 'btn btn-info col-lg']) !!}
        </div>
        <div class="col-lg-2">
            <div class="mb-2">
                &nbsp;
            </div>
            <div class="mb-2 text-right">
                <div id="table_list_big_btn" class="btn btn-primary table_list_view_toggle" data-viewmode="biglist">
                    <i class="fas fa-th-list"></i>
                </div>
                <div id="table_list_btn" class="btn btn-default table_list_view_toggle" data-viewmode="list">
                    <i class="fas fa-list"></i>
                </div>
            </div>
        </div>
    </div>
    <table id="routes_table" class="tablesorter default_table dt-responsive nowrap table-striped mt-2" width="100%">
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
@endsection