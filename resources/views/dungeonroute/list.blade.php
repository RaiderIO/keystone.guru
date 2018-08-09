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
        $(function () {
            $('#routes_table').DataTable({
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
                'columns': [
                    {
                        'data': 'title',
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
                        'className': 'd-none d-lg-table-cell'
                    },
                    {
                        'data': 'setup',
                        'render': function (data, type, row, meta) {
                            return handlebarsGroupSetupParse(data);
                        },
                        'className': 'd-none d-lg-table-cell'
                    },
                    {
                        'data': 'author.name',
                        'className': 'd-none d-lg-table-cell'
                    },
                    {
                        'render': function (data, type, row, meta) {
                            return 'some rating';
                        }
                    }
                ]
            }).on('draw.dt', function (e, settings, json, xhr) {
                refreshTooltips();
            });
        });
    </script>
    @include('common.handlebars.groupsetup')
    @include('common.handlebars.affixgroups')
@endsection

@section('content')
    <table id="routes_table" class="tablesorter default_table dt-responsive nowrap" width="100%">
        <thead>
        <tr>
            <th width="30%">{{ __('Title') }}</th>
            <th width="15%">{{ __('Dungeon') }}</th>
            <th width="15%" class="d-none d-lg-table-cell">{{ __('Affixes') }}</th>
            <th width="15%" class="d-none d-lg-table-cell">{{ __('Setup') }}</th>
            <th width="15%" class="d-none d-lg-table-cell">{{ __('Author') }}</th>
            <th width="10%">{{ __('Rating') }}</th>
        </tr>
        </thead>

        <tbody>
        </tbody>
    </table>
@endsection