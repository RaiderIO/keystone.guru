@extends('layouts.app')

@section('header-title')
    {{ __('View dungeons') }}
    <a href="{{ route('admin.dungeon.new') }}" class="btn btn-success text-white pull-right" role="button">
        <i class="fa fa-plus"></i> {{ __('Create dungeon') }}
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
        $('#admin_dungeon_table').DataTable({
        });
    });
</script>
@endsection

@section('content')
<table id="admin_dungeon_table" class="tablesorter default_table">
    <thead>
    <tr>
        <th width="10%">{{ __('Id') }}</th>
        <th width="80%">{{ __('Name') }}</th>
        <th width="10%">{{ __('Actions') }}</th>
    </tr>
    </thead>

    <tbody>
    @foreach ($models->all() as $dungeon)
    <tr>
        <td>{{ $dungeon->id }}</td>
        <td>{{ $dungeon->name }}</td>
        <td>
            <a class="btn btn-primary" href="{{ route('admin.dungeon.edit', ['id' => $dungeon->id]) }}">
                <i class="fa fa-pencil"></i>&nbsp;{{ __('Edit') }}
            </a>
        </td>
    </tr>
    @endforeach
    </tbody>

</table>
@endsection