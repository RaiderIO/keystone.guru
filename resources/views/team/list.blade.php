@extends('layouts.app', ['showAds' => false, 'title' => __('My teams')])

@section('header-title', __('My teams'))
@section('header-addition')
    <a href="{{ route('team.new') }}" class="btn btn-success text-white float-right" role="button">
        <i class="fas fa-plus"></i> {{ __('Create team') }}
    </a>
@endsection
@include('common.general.inline', ['path' => 'team/list'])

@section('content')
    <div class="container">
        @if(!isAlertDismissed('3479213'))
            <div class="alert alert-info alert-dismissible">
                <a href="#" class="close" data-dismiss="alert" aria-label="close" data-alert-dismiss-id="3479213">
                    <i class="fas fa-times"></i>
                </a>
                <i class="fas fa-info-circle"></i>
                {{ __('Welcome to the new teams feature of Keystone.guru! A team is a group of people that (frequently)
                play together. You can attach routes to a team which can then be easily viewed by other team
                members. This allows for a better organization than ever before!') }}
                <br><br>
                {{ __('To get started, create a team for your group or ask your friends to invite you to their group
                using their invite link.') }}
            </div>
        @endif
    </div>

    <table id="team_table" class="tablesorter default_table table-striped">
        <thead>
        <tr>
            <th width="80%">{{ __('Team') }}</th>
            <th width="10%">{{ __('Members') }}</th>
            <th width="10%">{{ __('Routes') }}</th>
        </tr>
        </thead>

        <tbody>
        @foreach ($models->all() as $team)
            <tr data-teamid="{{ $team->id }}">
                <td class="clickable">
                    @isset($team->iconfile)
                        <img class="mr-1" src="{{ url('storage/' . $team->iconfile->getUrl()) }}"
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