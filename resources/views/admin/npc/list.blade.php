@extends('layouts.app', ['noads' => true, 'title' => __('Npc listing')])

@section('header-title')
    {{ __('View NPCs') }}
@endsection
@section('header-addition')
    <a href="{{ route('admin.npc.new') }}" class="btn btn-success text-white float-right" role="button">
        <i class="fas fa-plus"></i> {{ __('Create NPC') }}
    </a>
@endsection

<?php
/** @var $models \Illuminate\Support\Collection */
// eager load the classification
?>

@section('scripts')
    <script type="text/javascript">
        $(function () {
            $('#admin_npc_table').DataTable({
                columns: [
                    {data: 'id'},
                    {data: 'name'},
                    {
                        data: 'dungeon.name',
                        name: 'dungeon_id'
                    },
                    {data: 'enemy_forces'},
                    {data: 'classification'},
                    {data: 'actions'}
                ]
            });
        });
    </script>
@endsection

@section('content')
    <table id="admin_npc_table" class="tablesorter default_table table-striped">
        <thead>
        <tr>
            <th width="15%">{{ __('Id') }}</th>
            <th width="30%">{{ __('Name') }}</th>
            <th width="15%">{{ __('Dungeon') }}</th>
            <th width="10%">{{ __('Enemy forces') }}</th>
            <th width="10%">{{ __('Classification') }}</th>
            <th width="10%">{{ __('Actions') }}</th>
        </tr>
        </thead>

        <tbody>
        @foreach ($models->all() as $npc)
            <tr>
                <td>{{ $npc->id }}</td>
                <td>{{ $npc->name }}</td>
                <td>{{ $npc->dungeon->name }}</td>
                <td>{{ $npc->enemy_forces }}</td>
                <td>{{ $npc->classification->name }}</td>
                <td>
                    <a class="btn btn-primary" href="{{ route('admin.npc.edit', ['id' => $npc->id]) }}">
                        <i class="fas fa-edit"></i>&nbsp;{{ __('Edit') }}
                    </a>
                </td>
            </tr>
        @endforeach
        </tbody>

    </table>
@endsection