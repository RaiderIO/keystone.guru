<?php
/** @var \App\Models\Team $model */
$title = __('Edit team');
$user = Auth::user();
$userRole = $model->getUserRole($user);
$userIsModerator = $userRole === 'moderator' || $userRole === 'admin';
$menuItems = [
    ['icon' => 'fa-route', 'text' => __('Routes'), 'target' => '#routes'],
    ['icon' => 'fa-users', 'text' => __('Members'), 'target' => '#members'],
    ['icon' => 'fa-edit', 'text' => __('Team details'), 'target' => '#details']
];
?>
@extends('layouts.app', ['title' => $title,
    'menuTitle' => __('Teams'), 'menuItems' => $menuItems,
    // The models to display as an option in the menu, plus the route to take when selecting them
    'menuModels' => $user->teams, 'menuModelsRoute' => 'team.edit',
    'model' => $model])
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
            <?php
            $data = [];
            foreach ($model->teamusers as $teamuser) {
                /** @var $teamuser \App\Models\TeamUser */
                $data[] = [
                    'user_id' => $teamuser->user->id,
                    'name' => $teamuser->user->name,
                    'join_date' => $teamuser->created_at->toDateTimeString(),
                    'role' => $teamuser->role,
                    // Any and all roles that the user may assign to other users
                    'assignable_roles' => $model->getAssignableRoles(Auth::user(), $teamuser->user)
                ];
            }
            ?>
        var _data = {!! json_encode($data) !!};
        var _teamId = {!! $model->id !!};
        var _userIsModerator = {!! $userRole === 'admin' || $userRole === 'moderator' ? 'true' : 'false' !!};
        var _currentUserName = "{{ $user->name }}";

        $(function () {
            let code = _inlineManager.getInlineCode('dungeonroute/table');
            // Add route to team button
            $('#add_route_btn').bind('click', function () {
                let tableView = code.getTableView();
                tableView.setAddMode(true);

                code.refreshTable();
                $(this).hide();
                $('#view_existing_routes').show();
            });

            // Cancel button when done adding routes
            $('#view_existing_routes').bind('click', function () {
                let tableView = code.getTableView();
                tableView.setAddMode(false);

                code.refreshTable();
                $(this).hide();
                $('#add_route_btn').show();
            });

            $('#delete_team').bind('click', function (clickEvent) {
                showConfirmYesCancel(lang.get('messages.delete_team_confirm_label'), function () {
                    // Change the method to DELETE
                    $('#details [name="_method"]').val('DELETE');
                    // Submit the form
                    $('#details form').submit();
                }, null, {type: 'error'});

                clickEvent.preventDefault();
            });


            let columns = [{'data': 'name'},
                {'data': 'join_date'},
                {
                    'data': 'assignable_roles',
                    'render': function (data, type, row, meta) {
                        let roles = [];

                        // Match the valid roles with roles above
                        let assignableRoles = row.assignable_roles;
                        for (let roleIndex in assignableRoles) {
                            if (assignableRoles.hasOwnProperty(roleIndex)) {
                                // Fetch the role..
                                let assignableRole = assignableRoles[roleIndex];

                                let icon = _getIcon(assignableRole);
                                if (icon !== false) {
                                    roles.push(icon);
                                }
                            }
                        }

                        let result = '';
                        if (roles.length === 0) {
                            let icon = _getIcon(data);

                            // Handlebars the entire thing
                            let template = Handlebars.templates['team_member_table_permissions_self_template'];
                            let templateData = $.extend({
                                icon: icon.icon,
                                label: icon.label,
                                self: _currentUserName === row.name
                            }, getHandlebarsDefaultVariables());

                            result = template(templateData);
                        } else {
                            // Handlebars the entire thing
                            let template = Handlebars.templates['team_member_table_permissions_template'];
                            let templateData = $.extend({
                                username: row.name,
                                role: data,
                                is_admin: data === 'admin',
                                roles: roles,
                                self: _currentUserName === data.name
                            }, getHandlebarsDefaultVariables());

                            result = template(templateData);
                        }
                        return result;
                    },
                    'orderable': false
                }];

            // Only admins/moderators have the option to remove members from a team
            if (_userIsModerator) {
                columns.push({
                    'data': 'join_date',
                    'render': function (data, type, row, meta) {
                        // Handlebars the entire thing
                        let template = Handlebars.templates['team_member_table_actions_template'];
                        let templateData = $.extend({
                            user_id: row.user_id
                        }, getHandlebarsDefaultVariables());

                        return template(templateData);
                    }
                });
            }

            $('#team_members_table').DataTable({
                'data': _data,
                'searching': false,
                'bLengthChange': false,
                'columns': columns
            });

            // Fix members data table being in a separate tab ignoring width
            // https://datatables.net/examples/api/tabs_and_scrolling.html
            $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
                $.fn.dataTable.tables({visible: true, api: true}).columns.adjust();
            });

            $('select.role_selection').bind('change', function (e) {
                $.ajax({
                    type: 'POST',
                    url: '/ajax/team/' + _teamId + '/changerole',
                    dataType: 'json',
                    data: {
                        username: $(this).data('username'),
                        role: $(this).val()
                    },
                    success: function () {
                        showSuccessNotification(lang.get('messages.change_role_success'));
                    }
                });
            });

            $('.remove_user_btn').bind('click', function (e) {
                $.ajax({
                    type: 'POST',
                    url: '/ajax/team/' + _teamId + '/member/' + $(this).data('userid'),
                    dataType: 'json',
                    data: {
                        _method: 'DELETE'
                    },
                    success: function () {
                        showSuccessNotification(lang.get('messages.remove_member_success'));
                    }
                });
            });
        });

        /**
         * Gets icon data for a role.
         * @param roleName The name of the role you want icon data for.
         * @returns {boolean}
         * @private
         */
        function _getIcon(roleName) {
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

            let result = false;

            // For each role there exists
            for (let roleCandidateIndex in icons) {
                let roleCandidate = icons[roleCandidateIndex];
                // Match assignable role with candidate
                if (roleName === roleCandidate.name) {
                    // Found what we're looking for, push the result
                    result = roleCandidate;
                    break;
                }
            }

            return result;
        }
    </script>
@endsection

@section('content')

    <div class="tab-content">
        <div class="tab-pane fade show active" id="routes" role="tabpanel" aria-labelledby="routes-tab">
            <div class="form-group m-2">
                <div class="row">
                    <div class="col-8">
                        <h4>
                            {{ __('Route list') }}
                        </h4>
                    </div>
                    <div class="col-4">
                        @if($userIsModerator)
                            <button id="add_route_btn" class="btn btn-success col-md-4 float-right">
                                <i class="fas fa-plus"></i> {{ __('Add route') }}
                            </button>
                            <button id="view_existing_routes" class="btn btn-danger col-md-4 float-right"
                                    style="display: none;">
                                <i class="fas fa-times"></i> {{ __('Cancel') }}
                            </button>
                        @endif
                    </div>
                </div>

                @include('common.dungeonroute.table', ['view' => 'team', 'team' => $model])
            </div>
        </div>

        <div class="tab-pane fade" id="members" role="tabpanel" aria-labelledby="members-tab">
            <div class="form-group m-2">
                <h4>
                    {{ __('Invite new members') }}
                </h4>
                <div class="col-xl-4">
                    <div class="input-group-append">
                        {!! Form::text('team_members_invite_link', route('team.invite', ['invitecode' => $model->invite_code]),
                            ['id' => 'team_members_invite_link', 'class' => 'form-control', 'readonly' => 'readonly']) !!}
                        <div class="input-group-append">
                            <button id="team_invite_link_copy_to_clipboard" class="btn btn-info"
                                    data-toggle="tooltip" title="{{ __('Copy to clipboard') }}">
                                <i class="far fa-copy"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-group m-2">
                <h4>
                    {{ __('Member list') }}
                </h4>
                <table id="team_members_table" class="tablesorter default_table table-striped w-100" width="100%">
                    <thead>
                    <tr>
                        @if(!$userIsModerator)
                            <th width="60%">{{ __('Name') }}</th>
                            <th width="20%">{{ __('Join date') }}</th>
                            <th width="20%">{{ __('Permissions') }}</th>
                        @else
                            <th width="45%">{{ __('Name') }}</th>
                            <th width="20%">{{ __('Join date') }}</th>
                            <th width="20%">{{ __('Permissions') }}</th>
                            <th width="15%">{{ __('Actions') }}</th>
                        @endif
                    </tr>
                    </thead>
                </table>
            </div>
        </div>

        <div class="tab-pane fade" id="details" role="tabpanel"
             aria-labelledby="details-tab">
            <div class=" m-2">
                <h4>
                    {{ __('Details') }}
                </h4>

                @include('team.details', ['model' => $model])
            </div>
        </div>
    </div>
@endsection
