<?php

use App\Models\Dungeon;
use App\Models\Npc\NpcClassification;

/**
 * @var Dungeon $contextDungeon
 */
?>
@extends('layouts.sitepage', ['title' => __('view_compendium.npc.index.title')])

@section('header-title')
    {{ __('view_compendium.npc.index.header') }}
@endsection

@section('scripts')
    @parent

    <script type="text/javascript">
        $(function () {
            const assetsBaseUrl = '{{ config('keystoneguru.assets_base_url') }}';
            const bossClassificationIds = @json([
                    NpcClassification::ALL[NpcClassification::NPC_CLASSIFICATION_BOSS],
                    NpcClassification::ALL[NpcClassification::NPC_CLASSIFICATION_FINAL_BOSS]
                ])

            const skullIconUrl = '{{ ksgAssetImage('mapicon/raid_marker_skull.png') }}';
            const npcShowBaseUrl = '{{ url('/compendium/npc') }}';

            const table = $('#compendium_npc_table').DataTable({
                'processing': true,
                'serverSide': true,
                'responsive': true,
                'searching': true,
                'ajax': {
                    'url': '{{ route('ajax.npc.compendium.search') }}',
                    'data': function (d) {
                        d.dungeon_id = $('#compendium_filter_dungeon').val();
                    },
                },
                'lengthMenu': [25],
                'bLengthChange': false,
                'order': [[0, 'asc']],
                'columns': [
                    {
                        'title': '{{ __('view_compendium.npc.index.table_header_name') }}',
                        'data': 'name',
                        'name': 'name',
                        'render': function (data, type, row) {
                            const portrait = row.enemy_portrait_url
                                ? `<img src="${assetsBaseUrl}/${row.enemy_portrait_url}" width="20" height="20" class="mr-1" loading="lazy"/>`
                                : '';
                            const bossIcon = bossClassificationIds.includes(row.classification_id)
                                ? `<img src="${skullIconUrl}" width="16" height="16" class="mr-1" title="{{ __('view_compendium.npc.index.boss') }}" data-toggle="tooltip"/>`
                                : '';

                            return `<a href="${npcShowBaseUrl}/${row.id}">${portrait}${data ?? ''}${bossIcon}</a>`;
                        },
                    },
                    {
                        'title': '{{ __('view_compendium.npc.index.table_header_dungeons') }}',
                        'data': 'dungeon_names',
                        'name': 'dungeon_id',
                        'searchable': false,
                    },
                    {
                        'title': '{{ __('view_compendium.npc.index.table_header_spells') }}',
                        'data': 'spells',
                        'name': 'spells',
                        'orderable': false,
                        'searchable': false,
                        'render': function (data) {
                            if (!data || !data.length) {
                                return '';
                            }

                            return data.filter((spell) => !spell.hidden_on_map).map(function (spell) {
                                return `<a href="${spell.wowhead_url}" data-wh-icon-size="small">` +
                                    `<img src="${spell.icon_url}" width="16" height="16" loading="lazy"/>` +
                                    `</a>`;
                            }).join('');
                        },
                    },
                ],
                'createdRow': function (row, data) {
                    $(row).css('cursor', 'pointer').on('click', function (e) {
                        if (!$(e.target).closest('a').length) {
                            window.location.href = `${npcShowBaseUrl}/${data.id}`;
                        }
                    });
                },
                'drawCallback': function () {
                    if (typeof $WowheadPower !== 'undefined') {
                        $WowheadPower.refreshLinks();
                    }
                },
                'language': $.extend({}, lang.messages[`${lang.locale}.datatables`], {
                    'emptyTable': lang.get('js.datatable_no_npcs_in_table'),
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
                'id'       => 'compendium_filter_dungeon',
                'label'    => false,
                'showAll' => false,
                'showSeasons' => true,
                'required' => false,
                'selected' => $contextDungeon->id,
            ])
        </div>
    </div>

    <table id="compendium_npc_table" class="tablesorter default_table table-striped">
        <thead>
        <tr>
            <th width="25%">{{ __('view_compendium.npc.index.table_header_name') }}</th>
            <th width="25%">{{ __('view_compendium.npc.index.table_header_dungeons') }}</th>
            <th width="50%">{{ __('view_compendium.npc.index.table_header_spells') }}</th>
        </tr>
        </thead>
    </table>
@endsection
