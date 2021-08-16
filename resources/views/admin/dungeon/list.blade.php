@extends('layouts.sitepage', ['showAds' => false, 'title' => __('list.views/admin.dungeon.list.title')])

@section('header-title')
    {{ __('views/admin.dungeon.list.header') }}
@endsection
{{--Disabled since dungeons should only be created through seeders--}}
{{--@section('header-addition')--}}
{{--    <a href="{{ route('admin.dungeon.new') }}" class="btn btn-success text-white float-right" role="button">--}}
{{--        <i class="fas fa-plus"></i> {{ __('Create dungeon') }}--}}
{{--    </a>--}}
{{--@endsection--}}
<?php
/**
 * @var $models \App\Models\Dungeon
 * @var $floor \App\Models\Floor
 */
?>

@section('scripts')
    <script type="text/javascript">
        $(function () {
            var dt = $('#admin_dungeon_table').DataTable({
                'order': [[1, 'desc']],
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
            <th width="50px">{{ __('views/admin.dungeon.list.table_header_active') }}</th>
            <th width="50px">{{ __('views/admin.dungeon.list.table_header_expansion') }}</th>
            <th width="45%">{{ __('views/admin.dungeon.list.table_header_name') }}</th>
            <th width="10%">{{ __('views/admin.dungeon.list.table_header_enemy_forces') }}</th>
            <th width="10%">{{ __('views/admin.dungeon.list.table_header_enemy_forces_teeming') }}</th>
            <th width="10%">{{ __('views/admin.dungeon.list.table_header_timer') }}</th>
            <th width="10%">{{ __('views/admin.dungeon.list.table_header_actions') }}</th>
        </tr>
        </thead>

        <tbody>
        @foreach ($models as $dungeon)
            <?php /** @var $dungeon \App\Models\Dungeon */?>
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
                    <img src="{{ $dungeon->expansion->iconfile->getUrl() }}"
                         title="{{ $dungeon->expansion->name }}"
                         data-toggle="tooltip"
                         style="width: 32px; height: 32px;"/>
                </td>
                <td>{{ $dungeon->name }}</td>
                <td>{{ $dungeon->enemy_forces_required }}</td>
                <td>{{ $dungeon->enemy_forces_required_teeming }}</td>
                <td data-order="{{$dungeon->timer_max_seconds}}">{{ gmdate('i:s', $dungeon->timer_max_seconds) }}</td>
                <td>
                    <a class="btn btn-primary" href="{{ route('admin.dungeon.edit', ['dungeon' => $dungeon->slug]) }}">
                        <i class="fas fa-edit"></i>&nbsp;{{ __('views/admin.dungeon.list.edit') }}
                    </a>
                </td>
            </tr>
        @endforeach
        </tbody>

    </table>
@endsection