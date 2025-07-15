@extends('layouts.sitepage', ['showAds' => false, 'title' => __('view_admin.dungeon.list.title')])

@section('header-title')
    {{ __('view_admin.dungeon.list.header') }}
@endsection
{{--Disabled since dungeons should only be created through seeders--}}
@section('header-addition')
    <a href="{{ route('admin.dungeon.new') }}" class="btn btn-success text-white float-right" role="button">
        <i class="fas fa-plus"></i> {{ __('Create dungeon') }}
    </a>
@endsection
<?php

use App\Models\Dungeon;
use App\Models\Floor\Floor;
use App\Models\Mapping\MappingVersion;
use Illuminate\Support\Collection;

/**
 * @var Collection<Dungeon> $models
 * @var Floor               $floor
 */
?>

@section('scripts')
    @parent

    <script type="text/javascript">
        $(function () {
            var dt = $('#admin_dungeon_table').DataTable({
                'aaSorting': [],
                'lengthMenu': [50],
            });

            dt.on('draw.dt', function (e, settings, json, xhr) {
                refreshTooltips();
            });
        });
    </script>
@endsection

@section('content')
    <table id="admin_dungeon_table" class="tablesorter default_table table-striped">
        <thead>
        <tr>
            <th width="50px">{{ __('view_admin.dungeon.list.table_header_active') }}</th>
            <th width="50px">{{ __('view_admin.dungeon.list.table_header_expansion') }}</th>
            <th width="45%">{{ __('view_admin.dungeon.list.table_header_name') }}</th>
            <th width="10%">{{ __('view_admin.dungeon.list.table_header_enemy_forces') }}</th>
            <th width="10%">{{ __('view_admin.dungeon.list.table_header_enemy_forces_teeming') }}</th>
            <th width="10%">{{ __('view_admin.dungeon.list.table_header_timer') }}</th>
            <th width="10%">{{ __('view_admin.dungeon.list.table_header_actions') }}</th>
        </tr>
        </thead>

        <tbody>
        @foreach ($models as $dungeon)
                <?php
                /** @var MappingVersion|null $mappingVersion */
                $mappingVersion = $dungeon->loadMappingVersions()->mappingVersions->first();
                ?>
            <tr>
                @if($dungeon->active)
                    <td data-order="{{ $dungeon->id }}">
                        <i class="fas fa-check-circle text-success"></i>
                    </td>
                @else
                    <td data-order="{{ $dungeon->id + 1000 }}">
                        <i class="fas fa-times-circle text-danger"></i>
                    </td>
                @endif
                <td data-order="{{ $dungeon->expansion_id }}">
                    <img src="{{ ksgAssetImage(sprintf('expansions/%s.png', $dungeon->expansion->shortname)) }}"
                         alt="{{ __($dungeon->expansion->name) }}"
                         title="{{ __($dungeon->expansion->name) }}"
                         data-toggle="tooltip"
                         style="width: 50px;"/>
                </td>
                <td>{{ __($dungeon->name) }}</td>
                <td>{{ $mappingVersion?->enemy_forces_required }} </td>
                <td>{{ $mappingVersion?->enemy_forces_required_teeming }}</td>
                <td data-order="{{$mappingVersion?->timer_max_seconds}}">{{ gmdate('i:s', $mappingVersion?->timer_max_seconds) }}</td>
                <td>
                    <a class="btn btn-primary" href="{{ route('admin.dungeon.edit', ['dungeon' => $dungeon->slug]) }}">
                        <i class="fas fa-edit"></i>&nbsp;{{ __('view_admin.dungeon.list.edit') }}
                    </a>
                </td>
            </tr>
        @endforeach
        </tbody>

    </table>
@endsection
