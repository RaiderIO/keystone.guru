@extends('layouts.app', ['showAds' => false, 'title' => __('Dungeon listing')])

@section('header-title')
    {{ __('View dungeons') }}
@endsection
@section('header-addition')
    <a href="{{ route('admin.dungeon.new') }}" class="btn btn-success text-white float-right" role="button">
        <i class="fas fa-plus"></i> {{ __('Create dungeon') }}
    </a>
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
        var dt = $('#admin_dungeon_table').DataTable({
            'lengthMenu': [25],
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
        <th width="10%">{{ __('Exp.') }}</th>
        <th width="80%">{{ __('Name') }}</th>
        <th width="10%">{{ __('Actions') }}</th>
    </tr>
    </thead>

    <tbody>
    @foreach ($models as $dungeon)
        <?php /** @var $dungeon \App\Models\Dungeon */?>
    <tr>
        <td>
            <img src="{{ Image::url($dungeon->expansion->iconfile->getUrl(), 32, 32) }}"
                 title="{{ $dungeon->expansion->name }}"
                 data-toggle="tooltip"/>
        </td>
        <td>{{ $dungeon->name }}</td>
        <td>
            <a class="btn btn-primary" href="{{ route('admin.dungeon.edit', ['id' => $dungeon->id]) }}">
                <i class="fas fa-edit"></i>&nbsp;{{ __('Edit') }}
            </a>
        </td>
    </tr>
    @endforeach
    </tbody>

</table>
@endsection