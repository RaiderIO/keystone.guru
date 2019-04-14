@extends('layouts.app', ['showAds' => false, 'title' => __('My teams')])

@section('header-title', __('My teams'))
@section('header-addition')
    <a href="{{ route('team.new') }}" class="btn btn-success text-white float-right" role="button">
        <i class="fas fa-plus"></i> {{ __('Create team') }}
    </a>
@endsection

@section('scripts')
    <script type="text/javascript">
        $(function () {
            $('#team_table').DataTable({});
        });
    </script>
@endsection

@section('content')
    <div class="container">
        @if(!isAlertDismissed('3479213'))
        <div class="alert alert-info alert-dismissible">
            <a href="#" class="close" data-dismiss="alert" aria-label="close" data-alert-dismiss-id="3479213"><i class="fas fa-times"></i></a>
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
        @endif
    </div>

    <table id="team_table" class="tablesorter default_table table-striped">
        <thead>
        <tr>
            <th width="10%">{{ __('Icon') }}</th>
            <th width="70%">{{ __('Name') }}</th>
            <th width="10%">{{ __('#Routes') }}</th>
            <th width="10%">{{ __('Actions') }}</th>
        </tr>
        </thead>

        <tbody>
        @foreach ($models->all() as $team)
            <tr>
                @isset($team->iconfile)
                    <td><img src="{{ Image::url($team->iconfile->getUrl(), 32, 32) }}"/></td>
                @else
                    <td><i class="fas fa-users"></i></td>
                @endisset
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