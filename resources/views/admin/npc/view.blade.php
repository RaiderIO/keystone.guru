@extends('layouts.app')

@section('header-title')
    {{ __('View NPCs') }}
    <a href="{{ route('admin.npc.new') }}" class="btn btn-success text-white pull-right" role="button">
        <i class="fa fa-plus"></i> {{ __('Create NPC') }}
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
                    {data: 'classification'},
                    {data: 'base_health'},
                    {data: 'game_id'},
                    {data: 'actions'}
                ]
            });
        });
    </script>
@endsection

@section('content')
    <table id="admin_npc_table" class="tablesorter">
        <thead>
        <tr>
            <th>{{ __('Id') }}</th>
            <th>{{ __('Name') }}</th>
            <th>{{ __('Classification') }}</th>
            <th>{{ __('Base health') }}</th>
            <th>{{ __('Game ID') }}</th>
            <th>{{ __('Actions') }}</th>
        </tr>
        </thead>

        <tbody>
        @foreach ($models->all() as $npc)
            <tr>
                <td>{{ $npc->id }}</td>
                <td>{{ $npc->name }}</td>
                <td>{{ $npc->classification->name }}</td>
                <td>{{ number_format($npc->base_health) }}</td>
                <td>{{ $npc->game_id }}</td>
                <td>
                    <a class="btn btn-primary" href="{{ route('admin.npc.edit', ['id' => $npc->id]) }}">
                        <i class="fa fa-pencil"></i>&nbsp;{{ __('Edit') }}
                    </a>
                </td>
            </tr>
        @endforeach
        </tbody>

    </table>
@endsection