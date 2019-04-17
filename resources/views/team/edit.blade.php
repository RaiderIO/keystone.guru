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

@isset($model)
@section('scripts')
    @parent

    <script type="text/javascript">
            <?php
            $data = [];
            foreach ($model->teamusers as $teamuser) {
                /** @var $teamuser \App\Models\TeamUser */
                $data[] = [
                    $teamuser->user->name,
                    $teamuser->created_at->toDateTimeString(),
                    $teamuser->role,
                    // Any and all roles that the user may assign to other users
                    $model->getAssignableRoles($teamuser->user)
                ];
            }
            ?>
        var _data = {!! json_encode($data) !!};
        var _teamId = {!! $model->id !!};

        $(function () {
            $('#team_members_table').DataTable({
                'data': _data,
                'columnDefs': [{
                    'targets': 2,
                    'render': function (data, type, row, meta) {
                        // Matching roles to icons
                        let icons = [{
                            name: 'member',
                            icon: 'fa-eye',
                            label: lang.get('messages.team_member')
                        }, {
                            name: 'collaborator',
                            icon: 'fa-edit',
                            label: lang.get('messages.team_collaborator')
                        }, {
                            name: 'moderator',
                            icon: 'fa-user-cog',
                            label: lang.get('messages.team_moderator')
                        }, {
                            name: 'admin',
                            icon: 'fa-crown',
                            label: lang.get('messages.team_admin')
                        }];

                        let roles = [];

                        // Match the valid roles with roles above
                        for (let roleIndex in row[3]) {
                            let role = row[3][roleIndex];
                            for (let roleCandidateIndex in icons) {
                                let roleCandidate = icons[roleCandidateIndex];
                                if (role === roleCandidate.name) {
                                    roles.push(roleCandidate);
                                }
                            }
                        }

                        console.log('roles', roles);

                        // Handlebars the entire thing
                        let template = Handlebars.templates['team_member_table_actions_template'];
                        let templateData = $.extend({
                            username: row[0],
                            role: data,
                            is_admin: data === 'admin',
                            roles: roles
                        }, getHandlebarsDefaultVariables());

                        return template(templateData);
                    },
                    'orderable': false
                }]
            });

            // Fix members data table being in a separate tab ignoring width
            // https://datatables.net/examples/api/tabs_and_scrolling.html
            $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
                $.fn.dataTable.tables({visible: true, api: true}).columns.adjust();
            });

            $('select.role_selection').bind('change', function (e) {
                $.ajax({
                    type: 'POST',
                    url: '/ajax/team/changerole',
                    dataType: 'json',
                    data: {
                        team_id: _teamId,
                        username: $(this).data('username'),
                        role: $(this).val()
                    },
                    success: function () {
                        showSuccessNotification(lang.get('messages.change_role_success'));
                    }
                });
            });
        });
    </script>
@endsection
@endisset

@section('content')
    @isset($model)
        <div class="container">
            <ul class="nav nav-tabs mb-3" id="myTab" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" id="routes-tab" data-toggle="tab" href="#routes" role="tab"
                       aria-controls="routes" aria-selected="false"><i class="fas fa-route"></i> {{ __('Routes') }}
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="patreon-tab" data-toggle="tab" href="#members" role="tab"
                       aria-controls="patreon" aria-selected="false"><i class="fas fa-users"></i> {{ __('Members') }}
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="details-tab" data-toggle="tab" href="#details" role="tab"
                       aria-controls="details" aria-selected="true"><i class="fas fa-edit"></i> {{ __('Team details') }}
                    </a>
                </li>
            </ul>
        </div>
    @endisset

    <div class="tab-content">
        @isset($model)
            <div class="tab-pane fade show active" id="routes" role="tabpanel" aria-labelledby="routes-tab">
                <div class="form-group">
                    <button class="btn btn-success col-md"><i class="fas fa-plus"></i> {{ __('Add route') }}</button>
                </div>

                <div class="form-group mt-2">
                    <h4>
                        {{ __('Route list') }}
                    </h4>

                    @include('common.dungeonroute.table', ['team' => $model])
                </div>
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
                    <table id="team_members_table" class="tablesorter default_table table-striped w-100" width="100%">
                        <thead>
                        <tr>
                            <th width="65%">{{ __('Name') }}</th>
                            <th width="20%">{{ __('Join date') }}</th>
                            <th width="15%">{{ __('Permissions') }}</th>
                        </tr>
                        </thead>
                    </table>
                </div>
            </div>
        @endisset

        <div class="tab-pane fade" id="details" role="tabpanel" aria-labelledby="details-tab">
            @isset($model)
                {{ Form::model($model, ['route' => ['team.update', $model->id], 'method' => 'patch', 'files' => true]) }}
            @else
                {{ Form::open(['route' => 'team.savenew', 'files' => true]) }}
            @endisset

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
    </div>
@endsection
