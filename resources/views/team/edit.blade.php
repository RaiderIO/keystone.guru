<?php
$title = isset($model) ? __('Edit team') : __('New team');
?>
@extends('layouts.app', ['showAds' => false, 'title' => $title])
@section('header-title', $title)
@section('header-addition')
    <a href="{{ route('team.list') }}" class="btn btn-info text-white float-right" role="button">
        <i class="fas fa-backward"></i> {{ __('Team list') }}
    </a>
@endsection
@include('common.general.inline', ['path' => 'team/edit'])

@section('scripts')
    @parent

    <script type="text/javascript">
        $(function () {
            $('#team_members_table').DataTable({});
        });
    </script>
@endsection

@section('content')
    @isset($model)
        {{ Form::model($model, ['route' => ['team.update', $model->id], 'method' => 'patch', 'files' => true]) }}
    @else
        {{ Form::open(['route' => 'team.savenew', 'files' => true]) }}
    @endisset
    <div class="container">
        <ul class="nav nav-tabs mb-3" id="myTab" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="profile-tab" data-toggle="tab" href="#details" role="tab"
                   aria-controls="profile" aria-selected="true"><i class="fas fa-edit"></i> {{ __('Details') }}</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="patreon-tab" data-toggle="tab" href="#members" role="tab"
                   aria-controls="patreon" aria-selected="false"><i class="fas fa-users"></i> {{ __('Members') }}</a>
            </li>
        </ul>
    </div>

    <div class="tab-content">
        <div class="tab-pane fade show active" id="details" role="tabpanel" aria-labelledby="details-tab">

            <div class="form-group{{ $errors->has('name') ? ' has-error' : '' }}">
                {!! Form::label('name', __('Name')) !!}
                {!! Form::text('name', null, ['class' => 'form-control']) !!}
                @include('common.forms.form-error', ['key' => 'name'])
            </div>

            <div class="form-group{{ $errors->has('name') ? ' has-error' : '' }}">
                {!! Form::label('description', __('Description')) !!}
                {!! Form::text('description', null, ['class' => 'form-control']) !!}
                @include('common.forms.form-error', ['key' => 'description'])
            </div>

            <div class="form-group{{ $errors->has('logo') ? ' has-error' : '' }}">
                {!! Form::label('logo', __('Logo')) !!}
                {!! Form::file('logo', ['class' => 'form-control']) !!}
                @include('common.forms.form-error', ['key' => 'logo'])
            </div>

            @if(isset($model) && isset($model->iconfile))
                <div class="form-group">
                    {{__('Current logo:')}} <img src="{{ Image::url($model->iconfile->getUrl(), 32, 32) }}"
                                                 alt="{{ __('Team logo') }}"/>
                </div>
            @endif

            {!! Form::submit(isset($model) ? __('Save') : __('Submit'), ['class' => 'btn btn-info']) !!}

            {!! Form::close() !!}
        </div>
        <div class="tab-pane fade" id="members" role="tabpanel" aria-labelledby="members-tab">
            <div class="form-group">
                <h4>
                    {{ __('Invite new members') }}
                </h4>
                <div class="row">
                    <div class="col-lg">
                        {!! Form::text('team_members_invite_link', route('team.invite', ['invitecode' => $model->invite_code]),
                        ['id' => 'team_members_invite_link', 'class' => 'form-control', 'readonly' => 'readonly']) !!}
                    </div>
                    <div class="col-lg">
                        {!! Form::button('<i class="far fa-copy"></i> ' . __('Copy to clipboard'), ['id' => 'team_invite_link_copy_to_clipboard', 'class' => 'btn btn-info col-md']) !!}
                    </div>
                </div>
            </div>

            <div class="form-group mt-2">
                <h4>
                    {{ __('Member list') }}
                </h4>
                <table id="team_members_table" class="tablesorter default_table table-striped">
                    <thead>
                    <tr>
                        <th width="10%">{{ __('Icon') }}</th>
                        <th width="70%">{{ __('Name') }}</th>
                        <th width="10%">{{ __('#Routes') }}</th>
                        <th width="10%">{{ __('Actions') }}</th>
                    </tr>
                    </thead>

                    <tbody>
                    @foreach ($model->members as $member)
                        <tr>
                            <td>{{ $member->name }}</td>
                            <td>
                                Actions
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
