<?php
$profile = isset($profile) ? $profile : false;
?>

@section('scripts')
    @parent

    <script type="text/javascript">
        let _dt;

        $(function () {
            _dt = $('#routes_table').DataTable({
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
                    'cache': '{{ env('APP_DEBUG', true) ? 'false' : 'true' }}'
                },
                'lengthMenu': [25],
                'bLengthChange': false,
                'columns': [
                    {
                        'data': 'title',
                        'name': 'title',
                        'render': function (data, type, row, meta) {
                            <?php if( $profile ) { ?>
                                return '<a href="{{ route('dungeonroute.edit', ['dungeonroute' => '']) }}/' + row.public_key + '" >' + data + '</a>';
                            <?php } else { ?>
                                return '<a href="{{ route('dungeonroute.view', ['dungeonroute' => '']) }}/' + row.public_key + '" >' + data + '</a>';
                            <?php } ?>
                        },
                        'className': 'limit_width'
                    },
                    {
                        'data': 'dungeon.name',
                        'name': 'dungeon_id'
                    },
                    // {
                    //     'data': 'difficulty',
                    //     'className': 'd-none d-md-table-cell'
                    // },
                    {
                        'data': 'affixes',
                        'name': 'affixes.id',
                        'render': function (data, type, row, meta) {
                            return handlebarsAffixGroupsParse(data);
                        },
                        'className': 'd-none d-md-table-cell',
                        'orderable': false
                    },
                    {
                        'data': 'setup',
                        'render': function (data, type, row, meta) {
                            return handlebarsGroupSetupParse(data);
                        },
                        'className': 'd-none d-lg-table-cell',
                        'orderable': false
                    },
                    {
                        'data': 'author.name',
                        'className': 'd-none {{ $profile ? '' : 'd-lg-table-cell'}}',
                        'orderable': false
                    },
                    {
                        'render': function (data, type, row, meta) {
                            let result = '-';

                            if (row.avg_rating !== 0) {
                                result = row.avg_rating;
                                if (row.rating_count == 0 || row.rating_count > 1) {
                                    result += ' (' + row.rating_count + ' {{ __('votes') }})';
                                } else {
                                    result += ' (' + row.rating_count + ' {{ __('vote') }})';
                                }
                            }

                            return result;
                        },
                        'className': 'd-none d-lg-table-cell',
                        'orderable': false
                    }
                    <?php if($profile){ ?>
                    , {
                        'render': function (data, type, row, meta) {
                            return row.published === '1' ? 'Yes' : 'No';
                        },
                        'className': 'd-none d-lg-table-cell',
                    }, {
                        'render': function (data, type, row, meta) {
                            return '<div class="btn btn-danger dungeonroute-delete" data-publickey="' + row.public_key + '">{{ __('Delete') }}</div>';
                        }
                    }
                    <?php } ?>
                ]
            });

            _dt.on('draw.dt', function (e, settings, json, xhr) {
                refreshTooltips();

                let $deleteBtns = $('.dungeonroute-delete');
                $deleteBtns.unbind('click');
                $deleteBtns.bind('click', _promptDeleteDungeonKey);
            });

            $("#dungeonroute_filter").bind('click', function () {

                // Build the search parameters
                let title = $("#dungeonroute_search_title").val();
                let dungeonId = $("#dungeonroute_search_dungeon_id").val();
                if (parseInt(dungeonId) < 1) {
                    dungeonId = '';
                }
                let affixes = $("#affixes").val();
                let difficulty = $("#difficulty").val();
                if (parseInt(difficulty) < 1) {
                    difficulty = '';
                }

                _dt.column(0).search(title);
                _dt.column(1).search(dungeonId);
                _dt.column(2).search(difficulty);
                _dt.column(3).search(affixes);
                _dt.draw();
            });
            // Do this asap
            // $("#affixgroup_select_container").html(handlebarsAffixGroupSelectParse({}));
        });

        /**
         * Prompts the user to delete a route (called by button press)
         * @param clickEvent
         * @private
         */
        function _promptDeleteDungeonKey(clickEvent) {
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
    @include('common.handlebars.groupsetup')
    @include('common.handlebars.affixgroups')
    @include('common.handlebars.affixgroupsselect')
@endsection

@section('content')
    @parent

    <div class="row">
        <div class="col-lg-2">
            {!! Form::label('title', __('Title')) !!}
            {!! Form::text('title', null, ['id' => 'dungeonroute_search_title', 'class' => 'form-control']) !!}
        </div>
        <div class="col-lg-2">
            {!! Form::label('dungeon_id', __('Dungeon')) !!}
            {!! Form::select('dungeon_id', [0 => 'All'] + \App\Models\Dungeon::active()->pluck('name', 'id')->toArray(), 0, ['id' => 'dungeonroute_search_dungeon_id', 'class' => 'form-control']) !!}
        </div>
    <!--
        <div class="col-lg-2">
            {!! Form::label('difficulty', __('Difficulty')) !!}
    {!! Form::select('difficulty',
    array_merge([0 => 'All'], array_combine(config('keystoneguru.dungeonroute_difficulty'), config('keystoneguru.dungeonroute_difficulty'))), 0,
    ['id' => 'difficulty', 'class' => 'form-control']) !!}
            </div>
-->
        <div id="affixgroup_select_container" class="col-lg-2">
            {!! Form::label('affixes[]', __('Affixes') . "*") !!}
            {!! Form::select('affixes[]', \App\Models\AffixGroup::all()->pluck('text', 'id'), null,
                ['id' => 'affixes',
                'class' => 'form-control affixselect selectpicker',
                'multiple' => 'multiple',
                'data-selected-text-format' => 'count > 1',
                'data-count-selected-text' => __('{0} affixes selected')]) !!}
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
    </div>
    <table id="routes_table" class="tablesorter default_table dt-responsive nowrap table-striped mt-2" width="100%">
        <thead>
        <tr>
            <th width="{{ $profile ? '20' : '30' }}%">{{ __('Title') }}</th>
            <th width="15%">{{ __('Dungeon') }}</th>
        <!-- <th width="10%" class="d-none d-md-table-cell">{{ __('Difficulty') }}</th> -->
            <th width="15%" class="d-none d-md-table-cell">{{ __('Affixes') }}</th>
            <th width="15%" class="d-none d-lg-table-cell">{{ __('Setup') }}</th>
            <th width="15%" class="d-none {{ $profile ? '' : 'd-lg-table-cell'}}">{{ __('Author') }}</th>
            <th width="10%" class="d-none d-lg-table-cell">{{ __('Rating') }}</th>
            <?php if( $profile ) { ?>
            <th width="5%" class="d-none d-lg-table-cell">{{ __('Published') }}</th>
            <th width="5%">{{ __('Actions') }}</th>
            <?php } ?>
        </tr>
        </thead>

        <tbody>
        </tbody>
    </table>
@endsection