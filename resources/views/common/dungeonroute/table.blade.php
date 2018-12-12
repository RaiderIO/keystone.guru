<?php
$profile = isset($profile) ? $profile : false;
// Whitelist
$cookieViewMode = isset($_COOKIE['routes_viewmode']) &&
($_COOKIE['routes_viewmode'] === 'biglist' || $_COOKIE['routes_viewmode'] === 'list') ? $_COOKIE['routes_viewmode'] : 'biglist';
?>

@section('scripts')
    @parent

    <script type="text/javascript">
        let _viewMode = "{{ $cookieViewMode }}";
        let _dt = {};

        $(function () {
            // Default display
            _setViewMode(_viewMode);

            $("#dungeonroute_filter").bind('click', function () {

                // Build the search parameters
                let dungeonId = $("#dungeonroute_search_dungeon_id").val();
                if (parseInt(dungeonId) < 1) {
                    dungeonId = '';
                }
                let affixes = $("#affixes").val();
                let attributes = $("#attributes").val();

                let offset = _viewMode === 'biglist' ? 1 : 0;
                _dt[_viewMode].column(0 + offset).search(dungeonId);
                _dt[_viewMode].column(1 + offset).search(affixes);
                _dt[_viewMode].column(2 + offset).search(attributes);
                _dt[_viewMode].draw();
            });

            $('.table_list_view_toggle').bind('click', _tableListViewClicked);
        });

        /**
         * Binds a datatables instance to a jquery element.
         **/
        function _setViewMode(viewMode) {
            _viewMode = viewMode;
            // Send cookie only on the current page
            Cookies.set('routes_viewmode', _viewMode, {path: ''});

            let $element = $('#routes_table_' + _viewMode);

            // Hide all wrappers
            $('.routes_table_wrapper').hide();

            // Show the appropriate wrapper
            $('#routes_table_' + _viewMode + '_wrapper').show();

            // Set buttons to the correct state
            $('.table_list_view_toggle').removeClass('btn-default').removeClass('btn-primary').addClass('btn-default');

            // This is now the selected button
            $('#table_' + _viewMode + '_btn').removeClass('btn-default').addClass('btn-primary');

            // If not initialized
            if (!_dt.hasOwnProperty(_viewMode)) {
                _dt[_viewMode] = $element.DataTable({
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
                            $.each(_dt[_viewMode].$('tbody tr'), function (index, value) {
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

                _dt[_viewMode].on('draw.dt', function (e, settings, json, xhr) {
                    refreshTooltips();
                    let $deleteBtns = $('.dungeonroute-delete');
                    $deleteBtns.unbind('click');
                    $deleteBtns.bind('click', _promptDeleteDungeonRoute);

                    let $cloneBtns = $('.dungeonroute-clone');
                    $cloneBtns.unbind('click');
                    $cloneBtns.bind('click', _cloneDungeonRoute);

                    $('.owl-carousel').owlCarousel({
                        nav: true,
                        dots: false,
                        lazyLoad: true,
                        lazyLoadEager: 1,
                        items: 1
                    });
                });

                _dt[_viewMode].on('click', 'tbody tr', function (clickEvent) {
                    let key = $(clickEvent.currentTarget).data('publickey');

                    window.open('{{ route('dungeonroute.' . ($profile ? 'edit' : 'view'), ['dungeonroute' => 'replace_me']) }}'.replace('replace_me', key));
                });

                _dt[_viewMode].on('mouseenter', 'tbody tr', function () {
                    _dt[_viewMode].$('tr.selected').removeClass('selected');
                    $(this).addClass('selected');
                });

                _dt[_viewMode].on('mouseleave', 'tbody tr', function () {
                    if ($(this).hasClass('selected')) {
                        $(this).removeClass('selected');
                    }
                });
            } else {
                // Force a click on the filter button to refresh the table
                $("#dungeonroute_filter").click();
            }
        }

        /**
         * Get the columns based on the current view for the table.
         **/
        function _getColumns() {

            let columns = [];

            if (_viewMode === 'biglist') {
                columns.push({
                    'data': 'dungeon.id',
                    'name': 'dungeon_id',
                    'render': function (data, type, row, meta) {
                        return handlebarsThumbnailCarouselParse(row);
                    },
                    'orderable': false
                });
            }

            columns.push({
                'data': 'dungeon.name',
                'name': 'dungeon_id',
                'render': function (data, type, row, meta) {
                    return data;
                },
                'className': _viewMode === 'biglist' ? 'd-none d-md-table-cell' : '',
            });

            if (_viewMode === 'biglist') {
                columns.push({
                    'data': 'affixes',
                    'name': 'affixes.id',
                    'render': function (data, type, row, meta) {
                        return handlebarsBiglistFeaturesParse(row);
                    },
                });
            } else {
                columns.push({
                    'data': 'affixes',
                    'name': 'affixes.id',
                    'render': function (data, type, row, meta) {
                        return handlebarsAffixGroupsParse(data);
                    },
                    'className': 'd-none d-md-table-cell'
                });
            }

            columns.push({
                'data': 'routeattributes',
                'name': 'routeattributes.name',
                'render': function (data, type, row, meta) {
                    return handlebarsRouteAttributesParse(data);
                },
                // Hide this column when in big list mode; we can't remove it since we need it in order for the filtering
                // to work on the server-side
                'className': _viewMode === 'biglist' ? 'd-none' : ''
            });

            if (_viewMode === 'list') {
                columns.push({
                    'data': 'setup',
                    'render': function (data, type, row, meta) {
                        return handlebarsGroupSetupParse(data);
                    },
                    'className': 'd-none d-lg-table-cell',
                    'orderable': false
                });
            }
            columns.push({
                'data': 'author.name',
                'name': 'author.name',
                'className': 'd-none {{ $profile ? '' : 'd-lg-table-cell'}}'
            });
            columns.push({
                'data': 'views',
                'name': 'views',
                // 'className': 'd-none {{ $profile ? '' : 'd-lg-table-cell'}}'
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

        /**
         * User wants to change view mode of the table.
         **/
        function _tableListViewClicked() {
            // Display the correct table
            _setViewMode($(this).data('viewmode'));
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

            // Prevent clicking delete from opening the route after it returns
            clickEvent.preventDefault();
            return false;
        }

        /**
         * Clones a dungeon route.
         * @param clickEvent
         * @returns {boolean}
         * @private
         */
        function _cloneDungeonRoute(clickEvent) {
            let key = $(clickEvent.target).data('publickey');
            $("<a>").attr("href", '{{ route('dungeonroute.clone', ['dungeonroute' => 'replace_me']) }}'.replace('replace_me', key)).attr("target", "_blank")[0].click();

            // Prevent clicking delete from opening the route after it returns
            clickEvent.preventDefault();
            return false;
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
            <div class="row">
                <div class="col">
                    @auth
                        {!! Form::label('favorites', __('Favorites')) !!}
                        {!! Form::checkbox('favorites', 1, 0, ['id' => 'favorites', 'class' => 'form-control left_checkbox']) !!}
                    @endauth
                </div>
                <div class="col">
                    <div class="mb-2">
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