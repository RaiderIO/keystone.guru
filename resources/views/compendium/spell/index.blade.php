<?php
?>
@extends('layouts.sitepage', ['title' => __('view_compendium.spell.index.title')])

@section('header-title')
    {{ __('view_compendium.spell.index.header') }}
@endsection

@section('scripts')
    @parent

    <script type="text/javascript">
        $(function () {
            const spellShowBaseUrl = '{{ url('/compendium/spell') }}';
            const spellTemplate = Handlebars.templates['spell_template'];

            const table = $('#compendium_spell_table').DataTable({
                'processing': true,
                'serverSide': true,
                'responsive': true,
                'searching': true,
                'ajax': {
                    'url': '{{ route('ajax.spell.compendium.search') }}',
                    'data': function (d) {
                        const dungeonId = $('#compendium_filter_dungeon').val();
                        if (dungeonId) {
                            d.dungeon_id = dungeonId;
                        }
                    },
                },
                'lengthMenu': [25],
                'bLengthChange': false,
                'order': [[0, 'asc']],
                'columns': [
                    {
                        'title': '{{ __('view_compendium.spell.index.table_header_name') }}',
                        'data': 'name',
                        'name': 'name',
                        'render': function (data, type, row) {
                            return spellTemplate({
                                compendium_url: `${spellShowBaseUrl}/${row.id}`,
                                icon_url: row.icon_url,
                                name: data ?? '',
                            });
                        },
                    },
                    {
                        'title': '{{ __('view_compendium.spell.index.table_header_dungeons') }}',
                        'data': 'dungeon_names',
                        'name': 'dungeon_id',
                        'searchable': false,
                    },
                    {
                        'title': '{{ __('view_compendium.spell.index.table_header_used_by') }}',
                        'data': 'npc_names',
                        'name': 'npc_names',
                        'orderable': false,
                        'searchable': false,
                    },
                ],
                'createdRow': function (row, data) {
                    $(row).css('cursor', 'pointer').on('click', function (e) {
                        if (!$(e.target).closest('a').length) {
                            window.location.href = `${spellShowBaseUrl}/${data.id}`;
                        }
                    });
                },
                'language': $.extend({}, lang.messages[`${lang.locale}.datatables`], {
                    'emptyTable': lang.get('js.datatable_no_spells_in_table'),
                }),
            });

            $('#compendium_filter_dungeon').on('change', function () {
                table.ajax.reload();
            });
        });
    </script>
@endsection

@section('content')
    <div class="row mb-3">
        <div class="col-md-4">
            @include('common.dungeon.select', [
                'id'          => 'compendium_filter_dungeon',
                'label'       => false,
                'showAll'     => false,
                'showSeasons' => true,
                'required'    => false,
            ])
        </div>
    </div>

    <table id="compendium_spell_table" class="tablesorter default_table table-striped">
        <thead>
        <tr>
            <th width="25%">{{ __('view_compendium.spell.index.table_header_name') }}</th>
            <th width="25%">{{ __('view_compendium.spell.index.table_header_dungeons') }}</th>
            <th width="50%">{{ __('view_compendium.spell.index.table_header_used_by') }}</th>
        </tr>
        </thead>
    </table>
@endsection
