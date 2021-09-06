@extends('layouts.sitepage', ['showAds' => false, 'title' => __('views/admin.npc.list.title')])

@section('header-title')
    {{ __('views/admin.npc.list.header') }}
@endsection
@section('header-addition')
    <a href="{{ route('admin.npc.new') }}" class="btn btn-success text-white float-right" role="button">
        <i class="fas fa-plus"></i> {{ __('views/admin.npc.list.create_npc') }}
    </a>
@endsection

<?php
/** @var $models \Illuminate\Support\Collection|\App\Models\Npc[] */
// eager load the classification
?>

@section('scripts')
    <script type="text/javascript">
        $(function () {
            $('#admin_npc_table').DataTable({
                'processing': true,
                'serverSide': true,
                'responsive': true,
                'searching': true,
                'ajax': {
                    'url': '/ajax/admin/npc'
                },
                'lengthMenu': [25],
                'bLengthChange': false,
                // Order by affixes by default
                'order': [[0, 'asc']],
                'columns': [
                    {
                        'title': lang.get('messages.id_label'),
                        'data': 'id',
                        'name': 'id',
                    },
                    {
                        'title': lang.get('messages.name_label'),
                        'data': 'name',
                        'name': 'name',
                    },
                    {
                        'title': lang.get('messages.dungeon_label'),
                        'data': 'dungeon.name',
                        'name': 'dungeon_id',
                        'render': function (data, type, row, meta) {
                            return row.dungeon_id === -1 ? '{{ __('views/admin.npc.list.all_dungeons') }}' : lang.get(row.dungeon.name);
                        },
                    },
                    {
                        'title': lang.get('messages.enemy_forces_label'),
                        'data': 'enemy_forces',
                        'name': 'enemy_forces',
                        'searchable': false
                    },
                    {
                        'title': lang.get('messages.classification_label'),
                        'data': 'classification.name',
                        'name': 'classification.name',
                        'searchable': false
                    },
                    {
                        'title': lang.get('messages.actions_label'),
                        'data': 'id',
                        'name': 'id',
                        'orderable': false,
                        'searchable': false,
                        'render': function (data, type, row, meta) {
                            return `<a class="btn btn-primary" href="/admin/npc/${row.id}">` +
                                `    <i class="fas fa-edit"></i> ${lang.get('messages.edit_label')}` +
                                `</a>`;
                        }
                    }
                ],
                'language': {
                    'emptyTable': lang.get('messages.datatable_no_npcs_in_table')
                }
            });
        });
    </script>
@endsection

@section('content')
    <table id="admin_npc_table" class="tablesorter default_table table-striped">
        <thead>
        <tr>
            <th width="15%">{{ __('views/admin.npc.list.table_header_id') }}</th>
            <th width="30%">{{ __('views/admin.npc.list.table_header_name') }}</th>
            <th width="15%">{{ __('views/admin.npc.list.table_header_dungeon') }}</th>
            <th width="10%">{{ __('views/admin.npc.list.table_header_enemy_forces') }}</th>
            <th width="10%">{{ __('views/admin.npc.list.table_header_classification') }}</th>
            <th width="10%">{{ __('views/admin.npc.list.table_header_actions') }}</th>
        </tr>
        </thead>
    </table>
@endsection
