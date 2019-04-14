@extends('layouts.app', ['showAds' => false, 'title' => __('Teams')])

@section('header-title')
    <div class="row">
        <div class="col">
            <h4>{{ __('Teams') }}</h4>
        </div>
        <div class="ml-auto">
            <a href="{{ route('team.new') }}" class="btn btn-success text-white pull-right ml-auto" role="button">
                <i class="fas fa-plus"></i> {{ __('Create team') }}
            </a>
        </div>
    </div>
@endsection

@section('scripts')
    <script type="text/javascript">
        $(function () {
            $('#team_table').DataTable({
            });
        });
    </script>
@endsection

@section('content')
    <div class="container">
        <div class="row">
            <div class="col">
                <p>
                {{ __('Welcome to the new teams feature of Keystone.guru! A team is a group of people that (frequently)
                play together. You can attach routes to a team which can then be easily viewed/edited by other team
                members. This allows for a much better cooperation than ever before!') }}
                </p>
                <p>
                    {{ __('To get started, create a team for your group, or ask your friends to invite you to theirs
                    using the invite link.') }}
                </p>
            </div>
        </div>
    </div>

    <h4>{{ __('My teams') }}</h4>
    <table id="team_table" class="tablesorter default_table table-striped">
        <thead>
        <tr>
            <th width="10%">{{ __('Icon') }}</th>
            <th width="60%">{{ __('Name') }}</th>
            <th width="10%">{{ __('#Routes') }}</th>
            <th width="20%">{{ __('Actions') }}</th>
        </tr>
        </thead>

        <tbody>
        @foreach ($models->all() as $team)
            <tr>
                <td><img src="{{ Image::url($team->iconfile->getUrl(), 32, 32) }}"/></td>
                <td>{{ $team->name }}</td>
                <td>{{ $team->dungeonroutes->count() }}</td>
                <td>
                    <a class="btn btn-primary" href="{{ route('team.edit', ['id' => $team->id]) }}">
                        <i class="fas fa-edit"></i>&nbsp;{{ __('Edit') }}
                    </a>
                </td>
            </tr>
        @endforeach
        </tbody>

    </table>
@endsection()