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
            const spellShowBaseUrl = '{{ url('/compendium/spell') }}';
            const npcTemplate = Handlebars.templates['npc'];
            const spellTemplate = Handlebars.templates['spell_template'];

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
                            return npcTemplate({
                                compendium_url: `${npcShowBaseUrl}/${row.id}-${slugify(data ?? '')}`,
                                portrait_url: `${assetsBaseUrl}/${row.enemy_portrait_url}`,
                                is_boss: bossClassificationIds.includes(row.classification_id),
                                boss_icon_url: skullIconUrl,
                                name: data ?? '',
                            });
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
                                return spellTemplate({
                                    compendium_url: `${spellShowBaseUrl}/${spell.id}-${slugify(lang.get(spell.name))}`,
                                    icon_url: spell.icon_url,
                                    name: lang.get(spell.name),
                                });
                            }).join('');
                        },
                    },
                ],
                'createdRow': function (row, data) {
                    $(row).css('cursor', 'pointer').on('click', function (e) {
                        if (!$(e.target).closest('a').length) {
                            window.location.href = `${npcShowBaseUrl}/${data.id}-${slugify(data.name)}`;
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
