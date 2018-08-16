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
                        // Prevent this variable from busting cache
                        d.draw = null;
                        <?php if( isset($author_id)) {?>
                            d.author_id = {{ $author_id }};
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
                            console.log(row);
                            <?php // @todo Use laravel route for this link ?>
                                return '<a href="/dungeonroute?v=' + row.public_key + '" >' + data + '</a>';
                        }
                    },
                    {
                        'data': 'dungeon.name',
                        'name': 'dungeon_id'
                    },
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
                        'className': 'd-none d-md-table-cell'
                    },
                    {
                        'render': function (data, type, row, meta) {
                            return 'some rating';
                        }
                    }
                ]
            });

            $(document).ready(function () {
                $('#example').DataTable({
                    'processing': true,
                    'serverSide': true,
                    'responsive': true,
                    "ajax": "https://wofje.nl/test/dt.php",
                    'columns': [
                        {
                            'data': 'name',
                            'name': 'name',
                            'render': function (data, type, row, meta) {
                                return 'test';
                            }
                        }
                    ]
                });
            });


            _dt.on('draw.dt', function (e, settings, json, xhr) {
                refreshTooltips();
            });

            $("#dungeonroute_filter").bind('click', function () {

                // Build the search parameters
                let title = $("#dungeonroute_search_title").val();
                let dungeonId = $("#dungeonroute_search_dungeon_id").val();
                if (parseInt(dungeonId) < 1) {
                    dungeonId = '';
                }
                let affixes = $("#affixes").val();
                let rating = $("#rating").val();

                _dt.column(0).search(title);
                _dt.column(1).search(dungeonId);
                _dt.column(2).search(affixes);
                _dt.column(5).search(rating);
                _dt.draw();
            });
            // Do this asap
            // $("#affixgroup_select_container").html(handlebarsAffixGroupSelectParse({}));
        });
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
            {!! Form::select('dungeon_id', array_merge([0 => 'All'], \App\Models\Dungeon::all()->pluck('name', 'id')->toArray()), 0, ['id' => 'dungeonroute_search_dungeon_id', 'class' => 'form-control']) !!}
        </div>
        <div id="affixgroup_select_container" class="col-lg-2">
            {!! Form::label('affixes[]', __('Select affixes') . "*") !!}
            {!! Form::select('affixes[]', \App\Models\AffixGroup::all()->pluck('text', 'id'), null,
                ['id' => 'affixes',
                'class' => 'form-control affixselect selectpicker',
                'multiple' => 'multiple',
                'data-selected-text-format' => 'count > 1']) !!}
        </div>
        <div class="col-lg-2">
            {!! Form::label('rating', __('Rating')) !!}
            {!! Form::text('rating', null, ['id' => 'dungeonroute_search_rating', 'class' => 'form-control']) !!}
        </div>
        <div class="col-lg-2">
            {!! Form::button(__('Filter'), ['id' => 'dungeonroute_filter', 'class' => 'btn btn-info']) !!}
        </div>
    </div>
    <table id="routes_table" class="tablesorter default_table dt-responsive nowrap" width="100%">
        <thead>
        <tr>
            <th width="30%">{{ __('Title') }}</th>
            <th width="15%">{{ __('Dungeon') }}</th>
            <th width="15%" class="d-none d-md-table-cell">{{ __('Affixes') }}</th>
            <th width="15%" class="d-none d-lg-table-cell">{{ __('Setup') }}</th>
            <th width="15%" class="d-none d-md-table-cell">{{ __('Author') }}</th>
            <th width="10%">{{ __('Rating') }}</th>
        </tr>
        </thead>

        <tbody>
        </tbody>
    </table>
@endsection