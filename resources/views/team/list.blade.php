<?php
setcookie('viewed_teams', true);
?>
@extends('layouts.sitepage', ['showAds' => false, 'title' => __('views/team.list.title')])

@section('header-title', __('views/team.list.header'))
@section('header-addition')
    <a href="{{ route('team.new') }}" class="btn btn-success text-white float-right" role="button">
        <i class="fas fa-plus"></i> {{ __('views/team.list.create_team') }}
    </a>
@endsection
@include('common.general.inline', ['path' => 'team/list'])

@section('content')
    <table id="team_table" class="tablesorter default_table table-striped">
        <thead>
        <tr>
            <th width="80%">{{ __('views/team.list.table_header_team') }}</th>
            <th width="10%">{{ __('views/team.list.table_header_members') }}</th>
            <th width="10%">{{ __('views/team.list.table_header_routes') }}</th>
        </tr>
        </thead>

        <tbody>
        @foreach ($models->all() as $team)
            <tr data-teamid="{{ $team->public_key }}">
                <td class="clickable">
                    @isset($team->iconfile)
                        <img class="mr-1" src="{{ $team->iconfile->getURL() }}"
                             style="max-width: 48px"/>
                    @endisset
                    {{ $team->name }}
                </td>
                <td class="clickable">{{ $team->members->count() }}</td>
                <td class="clickable">{{ $team->dungeonroutes->count() }}</td>
            </tr>
        @endforeach
        </tbody>

    </table>
@endsection()