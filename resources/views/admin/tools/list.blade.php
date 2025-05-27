@extends('layouts.sitepage', ['showAds' => false, 'title' => __('view_admin.tools.list.title')])

@section('header-title', __('view_admin.tools.list.header'))

@section('content')
    <h3>{{ __('view_admin.tools.list.header_tools') }}</h3>
    <h4>{{ __('view_admin.tools.list.subheader_message_banner') }}</h4>
    <div class="form-group">
        <a href="{{ route('admin.tools.messagebanner.set') }}">{{ __('view_admin.tools.list.set_message_banner') }}</a>
    </div>
    <h4>{{ __('view_admin.tools.list.subheader_npcs') }}</h4>
    <div class="form-group">
        <a href="{{ route('admin.tools.npc.import') }}">{{ __('view_admin.tools.list.mass_import_npcs') }}</a>
    </div>
    <div class="form-group">
        <a href="{{ route('admin.tools.npc.managespellvisibility') }}">{{ __('view_admin.tools.list.manage_spell_visibility') }}</a>
    </div>

    <h4>{{ __('view_admin.tools.list.subheader_dungeonroute') }}</h4>
    <div class="form-group">
        <a href="{{ route('admin.tools.dungeonroute.view') }}">{{ __('view_admin.tools.list.view_dungeonroute_details') }}</a>
    </div>
    <div class="form-group">
        <a href="{{ route('admin.tools.dungeonroute.mappingversionusage') }}">{{ __('view_admin.tools.list.view_dungeonroute_mapping_version_usage') }}</a>
    </div>

    <h4>{{ __('view_admin.tools.list.subheader_mdt') }}</h4>
    <div class="form-group">
        <a href="{{ route('admin.tools.mdt.string.view') }}">{{ __('view_admin.tools.list.view_mdt_string') }}</a>
    </div>
    <div class="form-group">
        <a href="{{ route('admin.tools.mdt.string.viewasdungeonroute') }}">{{ __('view_admin.tools.list.view_mdt_string_as_dungeonroute') }}</a>
    </div>
    <div class="form-group">
        <a href="{{ route('admin.tools.mdt.string.list') }}">{{ __('view_admin.tools.list.list_mdt_strings') }}</a>
    </div>
    <div class="form-group">
        <a href="{{ route('admin.tools.mdt.dungeonroute.viewasstring') }}">{{ __('view_admin.tools.list.view_dungeonroute_as_mdt_string') }}</a>
    </div>
    <div class="form-group">
        <a href="{{ route('admin.tools.mdt.diff') }}">{{ __('view_admin.tools.list.view_mdt_diff') }}</a>
    </div>
    <div class="form-group">
        <a href="{{ route('admin.tools.mdt.dungeonmappinghash') }}">{{ __('view_admin.tools.list.view_dungeon_mapping_hash') }}</a>
    </div>
    <div class="form-group">
        <a href="{{ route('admin.tools.mdt.dungeonmappingversiontomdtmapping') }}">{{ __('view_admin.tools.list.view_dungeon_mapping_version_to_mdt_mapping') }}</a>
    </div>

    <h4>{{ __('view_admin.tools.list.subheader_enemy_forces') }}</h4>
    <div class="form-group">
        <a href="{{ route('admin.tools.enemyforces.import.view') }}">{{ __('view_admin.tools.list.enemy_forces_import') }}</a>
    </div>
    <div class="form-group">
        <a href="{{ route('admin.tools.enemyforces.recalculate.view') }}">{{ __('view_admin.tools.list.enemy_forces_recalculate') }}</a>
    </div>

    <h4>{{ __('view_admin.tools.list.subheader_thumbnails') }}</h4>
    <div class="form-group">
        <a href="{{ route('admin.tools.thumbnails.regenerate.view') }}">{{ __('view_admin.tools.list.thumbnails_regenerate') }}</a>
    </div>

    <h4>{{ __('view_admin.tools.list.subheader_wowtools') }}</h4>
    <div class="form-group">
        <a href="{{ route('admin.tools.wowtools.import_ingame_coordinates') }}">{{ __('view_admin.tools.list.wowtools_import_ingame_coordinates') }}</a>
    </div>

    <h4>{{ __('view_admin.tools.list.subheader_features') }}</h4>
    <div class="form-group">
        <a href="{{ route('admin.tools.features.list') }}">{{ __('view_admin.tools.list.manage_features') }}</a>
    </div>

    @if(config('app.type') === 'mapping')
        <h4>{{ __('view_admin.tools.list.subheader_mapping') }}</h4>
        <div class="form-group">
            <a class="btn btn-primary"
               href="{{ route('admin.tools.mapping.forcesync') }}">{{ __('view_admin.tools.list.force_sync_mapping') }}</a>
        </div>
    @endif

    <h4>{{ __('view_admin.tools.list.subheader_misc') }}</h4>
    <div class="form-group">
        <a href="{{ route('admin.tools.exception.select') }}">{{ __('view_admin.tools.list.throw_an_exception') }}</a>
    </div>

    <h3>{{ __('view_admin.tools.list.subheader_actions') }}</h3>
    <div class="form-group">
        <a class="btn btn-primary"
           href="{{ route('admin.tools.cache.drop') }}">{{ __('view_admin.tools.list.drop_caches') }}</a>
    </div>
    <div class="form-group">
        <a class="btn btn-primary"
           href="{{ route('admin.tools.datadump.exportdungeondata') }}">{{ __('view_admin.tools.list.export_dungeon_data') }}</a>
    </div>
    <div class="form-group">
        <a class="btn btn-primary"
           href="{{ route('admin.tools.datadump.exportreleases') }}">{{ __('view_admin.tools.list.export_releases') }}</a>
    </div>
    <div class="form-group">
        <a class="btn btn-primary"
           href="{{ route('admin.tools.readonly.toggle') }}">{{ __('view_admin.tools.list.toggle_readonly_mode') }}</a>
    </div>
@endsection
