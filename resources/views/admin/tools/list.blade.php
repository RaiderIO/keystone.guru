@extends('layouts.sitepage', ['showAds' => false, 'title' => __('view_admin.tools.list.title')])

@section('header-title', __('view_admin.tools.list.header'))

@section('content')
    <div class="row">

        {{-- Message Banner --}}
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <i class="fas fa-bullhorn"></i> {{ __('view_admin.tools.list.subheader_message_banner') }}
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item">
                        <a href="{{ route('admin.tools.messagebanner.set') }}">{{ __('view_admin.tools.list.set_message_banner') }}</a>
                        <small class="text-muted d-block">{{ __('view_admin.tools.list.set_message_banner_description') }}</small>
                    </li>
                </ul>
            </div>
        </div>

        {{-- NPCs --}}
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <i class="fas fa-dragon"></i> {{ __('view_admin.tools.list.subheader_npcs') }}
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item">
                        <a href="{{ route('admin.tools.npc.import') }}">{{ __('view_admin.tools.list.mass_import_npcs') }}</a>
                        <small class="text-muted d-block">{{ __('view_admin.tools.list.mass_import_npcs_description') }}</small>
                    </li>
                    <li class="list-group-item">
                        <a href="{{ route('admin.tools.npc.managespellvisibility') }}">{{ __('view_admin.tools.list.manage_spell_visibility') }}</a>
                        <small class="text-muted d-block">{{ __('view_admin.tools.list.manage_spell_visibility_description') }}</small>
                    </li>
                    <li class="list-group-item">
                        <a href="{{ route('admin.tools.npcs.showmissingdisplayid') }}">{{ __('view_admin.tools.list.show_missing_npc_display_id') }}</a>
                        <small class="text-muted d-block">{{ __('view_admin.tools.list.show_missing_npc_display_id_description') }}</small>
                    </li>
                    <li class="list-group-item">
                        <a href="{{ route('admin.tools.npcs.savetoseeder') }}">{{ __('view_admin.tools.list.download_npcs_seeder') }}</a>
                        <small class="text-muted d-block">{{ __('view_admin.tools.list.download_npcs_seeder_description') }}</small>
                    </li>
                </ul>
            </div>
        </div>

        {{-- Dungeonroute --}}
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <i class="fas fa-route"></i> {{ __('view_admin.tools.list.subheader_dungeonroute') }}
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item">
                        <a href="{{ route('admin.tools.dungeonroute.view') }}">{{ __('view_admin.tools.list.view_dungeonroute_details') }}</a>
                        <small class="text-muted d-block">{{ __('view_admin.tools.list.view_dungeonroute_details_description') }}</small>
                    </li>
                    <li class="list-group-item">
                        <a href="{{ route('admin.tools.dungeonroute.mappingversionusage') }}">{{ __('view_admin.tools.list.view_dungeonroute_mapping_version_usage') }}</a>
                        <small class="text-muted d-block">{{ __('view_admin.tools.list.view_dungeonroute_mapping_version_usage_description') }}</small>
                    </li>
                </ul>
            </div>
        </div>

        {{-- MDT --}}
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <i class="fas fa-file-code"></i> {{ __('view_admin.tools.list.subheader_mdt') }}
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item">
                        <a href="{{ route('admin.tools.mdt.string.view') }}">{{ __('view_admin.tools.list.view_mdt_string') }}</a>
                        <small class="text-muted d-block">{{ __('view_admin.tools.list.view_mdt_string_description') }}</small>
                    </li>
                    <li class="list-group-item">
                        <a href="{{ route('admin.tools.mdt.string.viewasdungeonroute') }}">{{ __('view_admin.tools.list.view_mdt_string_as_dungeonroute') }}</a>
                        <small class="text-muted d-block">{{ __('view_admin.tools.list.view_mdt_string_as_dungeonroute_description') }}</small>
                    </li>
                    <li class="list-group-item">
                        <a href="{{ route('admin.tools.mdt.string.list') }}">{{ __('view_admin.tools.list.list_mdt_strings') }}</a>
                        <small class="text-muted d-block">{{ __('view_admin.tools.list.list_mdt_strings_description') }}</small>
                    </li>
                    <li class="list-group-item">
                        <a href="{{ route('admin.tools.mdt.dungeonroute.viewasstring') }}">{{ __('view_admin.tools.list.view_dungeonroute_as_mdt_string') }}</a>
                        <small class="text-muted d-block">{{ __('view_admin.tools.list.view_dungeonroute_as_mdt_string_description') }}</small>
                    </li>
                    <li class="list-group-item">
                        <a href="{{ route('admin.tools.mdt.diff') }}">{{ __('view_admin.tools.list.view_mdt_diff') }}</a>
                        <small class="text-muted d-block">{{ __('view_admin.tools.list.view_mdt_diff_description') }}</small>
                    </li>
                    <li class="list-group-item">
                        <a href="{{ route('admin.tools.mdt.dungeonmappinghash') }}">{{ __('view_admin.tools.list.view_dungeon_mapping_hash') }}</a>
                        <small class="text-muted d-block">{{ __('view_admin.tools.list.view_dungeon_mapping_hash_description') }}</small>
                    </li>
                    <li class="list-group-item">
                        <a href="{{ route('admin.tools.mdt.dungeonmappingversiontomdtmapping') }}">{{ __('view_admin.tools.list.view_dungeon_mapping_version_to_mdt_mapping') }}</a>
                        <small class="text-muted d-block">{{ __('view_admin.tools.list.view_dungeon_mapping_version_to_mdt_mapping_description') }}</small>
                    </li>
                    <li class="list-group-item">
                        <a href="{{ route('admin.tools.mdt.dungeon_mapping_version_accuracy') }}">{{ __('view_admin.tools.list.view_dungeon_mapping_version_accuracy') }}</a>
                        <small class="text-muted d-block">{{ __('view_admin.tools.list.view_dungeon_mapping_version_accuracy_description') }}</small>
                    </li>
                </ul>
            </div>
        </div>

        {{-- Enemy Forces --}}
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <i class="fas fa-skull"></i> {{ __('view_admin.tools.list.subheader_enemy_forces') }}
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item">
                        <a href="{{ route('admin.tools.enemyforces.import.view') }}">{{ __('view_admin.tools.list.enemy_forces_import') }}</a>
                        <small class="text-muted d-block">{{ __('view_admin.tools.list.enemy_forces_import_description') }}</small>
                    </li>
                    <li class="list-group-item">
                        <a href="{{ route('admin.tools.enemyforces.recalculate.view') }}">{{ __('view_admin.tools.list.enemy_forces_recalculate') }}</a>
                        <small class="text-muted d-block">{{ __('view_admin.tools.list.enemy_forces_recalculate_description') }}</small>
                    </li>
                </ul>
            </div>
        </div>

        {{-- Thumbnails --}}
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <i class="fas fa-image"></i> {{ __('view_admin.tools.list.subheader_thumbnails') }}
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item">
                        <a href="{{ route('admin.tools.thumbnails.regenerate.view') }}">{{ __('view_admin.tools.list.thumbnails_regenerate') }}</a>
                        <small class="text-muted d-block">{{ __('view_admin.tools.list.thumbnails_regenerate_description') }}</small>
                    </li>
                </ul>
            </div>
        </div>

        {{-- Combat Log --}}
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <i class="fas fa-scroll"></i> {{ __('view_admin.tools.list.subheader_combatlog') }}
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item">
                        <a href="{{ route('admin.tools.combatlog.regenerate.view') }}">{{ __('view_admin.tools.list.combatlog_regenerate') }}</a>
                        <small class="text-muted d-block">{{ __('view_admin.tools.list.combatlog_regenerate_description') }}</small>
                    </li>
                    <li class="list-group-item">
                        <a href="{{ route('admin.tools.combatlog.criteria.view') }}">{{ __('view_admin.tools.list.combatlog_criteria') }}</a>
                        <small class="text-muted d-block">{{ __('view_admin.tools.list.combatlog_criteria_description') }}</small>
                    </li>
                    <li class="list-group-item">
                        <a href="{{ route('admin.tools.combatlog.rundata') }}">{{ __('view_admin.tools.list.combatlog_run_data') }}</a>
                        <small class="text-muted d-block">{{ __('view_admin.tools.list.combatlog_run_data_description') }}</small>
                    </li>
                    <li class="list-group-item">
                        <a href="{{ route('admin.tools.combatlog.route.enemy_failures.view') }}">{{ __('view_admin.tools.list.combatlog_route_enemy_failures') }}</a>
                        <small class="text-muted d-block">{{ __('view_admin.tools.list.combatlog_route_enemy_failures_description') }}</small>
                    </li>
                </ul>
            </div>
        </div>

        {{-- Wago.gg --}}
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <i class="fas fa-globe"></i> {{ __('view_admin.tools.list.subheader_wagogg') }}
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item">
                        <a href="{{ route('admin.tools.wagogg.import_ingame_coordinates') }}">{{ __('view_admin.tools.list.wagogg_import_ingame_coordinates') }}</a>
                        <small class="text-muted d-block">{{ __('view_admin.tools.list.wagogg_import_ingame_coordinates_description') }}</small>
                    </li>
                </ul>
            </div>
        </div>

        {{-- Features --}}
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <i class="fas fa-flag"></i> {{ __('view_admin.tools.list.subheader_features') }}
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item">
                        <a href="{{ route('admin.tools.features.list') }}">{{ __('view_admin.tools.list.manage_features') }}</a>
                        <small class="text-muted d-block">{{ __('view_admin.tools.list.manage_features_description') }}</small>
                    </li>
                </ul>
            </div>
        </div>

        {{-- Mapping (conditional) --}}
        @if(config('app.type') === 'mapping')
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card h-100">
                    <div class="card-header">
                        <i class="fas fa-map"></i> {{ __('view_admin.tools.list.subheader_mapping') }}
                    </div>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item">
                            <a href="{{ route('admin.tools.mapping.forcesync') }}">{{ __('view_admin.tools.list.force_sync_mapping') }}</a>
                            <small class="text-muted d-block">{{ __('view_admin.tools.list.force_sync_mapping_description') }}</small>
                        </li>
                    </ul>
                </div>
            </div>
        @endif

        {{-- Misc --}}
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <i class="fas fa-wrench"></i> {{ __('view_admin.tools.list.subheader_misc') }}
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item">
                        <a href="{{ route('admin.tools.exception.select') }}">{{ __('view_admin.tools.list.throw_an_exception') }}</a>
                        <small class="text-muted d-block">{{ __('view_admin.tools.list.throw_an_exception_description') }}</small>
                    </li>
                </ul>
            </div>
        </div>

        {{-- Spells --}}
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <i class="fas fa-magic"></i> {{ __('view_admin.tools.list.subheader_spells') }}
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item">
                        <a href="{{ route('admin.tools.spells.showmissingspellinfo') }}">{{ __('view_admin.tools.list.show_missing_spell_info') }}</a>
                        <small class="text-muted d-block">{{ __('view_admin.tools.list.show_missing_spell_info_description') }}</small>
                    </li>
                    <li class="list-group-item">
                        <a href="{{ route('admin.tools.spells.savetoseeder') }}">{{ __('view_admin.tools.list.download_spells_seeder') }}</a>
                        <small class="text-muted d-block">{{ __('view_admin.tools.list.download_spells_seeder_description') }}</small>
                    </li>
                </ul>
            </div>
        </div>

        {{-- Artisan Commands --}}
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <i class="fas fa-terminal"></i> {{ __('view_admin.tools.list.subheader_artisan_commands') }}
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item">
                        <a href="{{ route('admin.tools.artisancommands.backfillkillzoneenemyid.view') }}">{{ __('view_admin.tools.list.backfill_kill_zone_enemy_id') }}</a>
                        <small class="text-muted d-block">{{ __('view_admin.tools.list.backfill_kill_zone_enemy_id_description') }}</small>
                    </li>
                </ul>
            </div>
        </div>

        {{-- Actions (destructive) --}}
        <div class="col-12 mb-4">
            <div class="card border-danger">
                <div class="card-header bg-danger text-white">
                    <i class="fas fa-exclamation-triangle"></i> {{ __('view_admin.tools.list.subheader_actions') }}
                </div>
                <div class="card-body">
                    <a class="btn btn-danger mr-2 mb-2"
                       href="{{ route('admin.tools.cache.drop') }}">{{ __('view_admin.tools.list.drop_caches') }}</a>
                    <a class="btn btn-primary mr-2 mb-2"
                       href="{{ route('admin.tools.datadump.exportdungeondata') }}">{{ __('view_admin.tools.list.export_dungeon_data') }}</a>
                    <a class="btn btn-primary mr-2 mb-2"
                       href="{{ route('admin.tools.datadump.exportreleases') }}">{{ __('view_admin.tools.list.export_releases') }}</a>
                    <a class="btn btn-danger mr-2 mb-2"
                       href="{{ route('admin.tools.readonly.toggle') }}">{{ __('view_admin.tools.list.toggle_readonly_mode') }}</a>
                </div>
            </div>
        </div>

    </div>
@endsection
