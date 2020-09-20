@extends('layouts.app', ['showAds' => false, 'title' => __('Dungeon listing')])

@section('header-title')
    {{ __('View dungeons') }}
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
        <th width="50px">{{ __('Active') }}</th>
        <th width="50px">{{ __('Exp.') }}</th>
        <th width="45%">{{ __('Name') }}</th>
        <th width="10%">{{ __('Enemy Forces') }}</th>
        <th width="10%">{{ __('Teeming EF') }}</th>
        <th width="10%">{{ __('Timer') }}</th>
        <th width="10%">{{ __('Actions') }}</th>
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
            <img src="{{ Image::url($dungeon->expansion->iconfile->getUrl(), 32, 32) }}"
                 title="{{ $dungeon->expansion->name }}"
                 data-toggle="tooltip"/>
        </td>
        <td>{{ $dungeon->name }}</td>
        <td>{{ $dungeon->enemy_forces_required }}</td>
        <td>{{ $dungeon->enemy_forces_required_teeming }}</td>
        <td data-order="{{$dungeon->timer_max_seconds}}">{{ gmdate('i:s', $dungeon->timer_max_seconds) }}</td>
        <td>
            <a class="btn btn-primary" href="{{ route('admin.dungeon.edit', ['dungeon' => $dungeon->id]) }}">
                <i class="fas fa-edit"></i>&nbsp;{{ __('Edit') }}
            </a>
        </td>
    </tr>
    @endforeach
    </tbody>

</table>
@endsection