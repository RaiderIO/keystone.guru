@extends('layouts.app', ['wide' => true])

@section('header-title')
    {{ __('View routes') }}
@endsection
<?php
/**
 * @var $models \App\Models\Dungeon
 * @var $floor \App\Models\Floor
 */
?>

@section('scripts')
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
                    }, <?php // Enable caching when in production mode, disable it when developing ?>
                    'cache': '{{ env('APP_DEBUG', true) ? 'false' : 'true' }}'
                },
                'lengthMenu': [25],
                'bLengthChange': false,
                'bFilter': false,
                'columns': [
                    {
                        'data': 'title',
                        'name': 'title',
                        'render': function (data, type, row, meta) {
                            <?php // @todo Use laravel route for this link ?>
                                return '<a href="/dungeonroute/view/' + row.id + '" >' + data + '</a>';
                        }
                    },
                    {'data': 'dungeon.name'},
                    {
                        'data': 'affixes',
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

            _dt.on('draw.dt', function (e, settings, json, xhr) {
                refreshTooltips();
            });

            $("#dungeonroute_filter").bind('click', function(){
                _dt.column(0).search($("#dungeonroute_search_title").val()).draw();
            });
        });
    </script>
    @include('common.handlebars.groupsetup')
    @include('common.handlebars.affixgroups')
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-2">
            {!! Form::label('title', __('Title')) !!}
            {!! Form::text('title', null, ['id' => 'dungeonroute_search_title', 'class' => 'form-control']) !!}
        </div>
        <div class="col-lg-2">
            {!! Form::label('dungeon_id', __('Dungeon')) !!}
            {!! Form::select('dungeon_id', \App\Models\Dungeon::all()->pluck('name', 'id'), 0, ['class' => 'form-control']) !!}
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