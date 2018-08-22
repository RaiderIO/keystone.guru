@extends('layouts.app', ['noads' => true])

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
                    {data: 'classification'},
                    {data: 'base_health'},
                    {data: 'actions'}
                ]
            });
        });
    </script>
@endsection

@section('content')
    <table id="admin_npc_table" class="tablesorter default_table">
        <thead>
        <tr>
            <th width="20%">{{ __('Id') }}</th>
            <th width="40%">{{ __('Name') }}</th>
            <th width="10%">{{ __('Classification') }}</th>
            <th width="10%">{{ __('Base health') }}</th>
            <th width="10%">{{ __('Actions') }}</th>
        </tr>
        </thead>

        <tbody>
        @foreach ($models->all() as $npc)
            <tr>
                <td>{{ $npc->id }}</td>
                <td>{{ $npc->name }}</td>
                <td>{{ $npc->classification->name }}</td>
                <td>{{ number_format($npc->base_health) }}</td>
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