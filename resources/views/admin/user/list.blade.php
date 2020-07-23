@extends('layouts.app', ['showAds' => false, 'title' => __('User list')])

@section('header-title')
    {{ __('View Users') }}
@endsection

<?php
/** @var $models \Illuminate\Support\Collection */
?>
@include('common.general.inline', ['path' => 'admin/user/list',
        'options' =>  [
            'patreon_select_selector' => 'select.patreon_paid_tiers'
        ]
])

@section('scripts')
    <script type="text/javascript">
        /** @type object */
        let paidTiers = {!! $paidTiers; !!};

        $(function () {
            $('#admin_user_table').DataTable({
                'processing': true,
                'serverSide': true,
                'responsive': true,
                'ajax': {
                    'url': '/ajax/admin/user'
                },
                'drawCallback': function (settings) {
                    refreshSelectPickers();
                },
                'lengthMenu': [25],
                'bLengthChange': false,
                // Order by affixes by default
                'order': [[0, 'asc']],
                'columns': [
                    {
                        'title': lang.get('messages.id_label'),
                        'data': 'id',
                        'name': 'id'
                    },
                    {
                        'title': lang.get('messages.name_label'),
                        'data': 'name',
                        'name': 'name'
                    },
                    {
                        'title': lang.get('messages.route_count_label'),
                        'data': 'routes',
                        'name': 'routes'
                    },
                    {
                        'title': lang.get('messages.roles_label'),
                        'data': 'roles_string',
                        'name': 'roles_string'
                    },
                    {
                        'title': lang.get('messages.registered_label'),
                        'data': 'created_at',
                        'name': 'created_at',
                        'render': function (data, type, row, meta) {
                            let createdAtDate = (new Date(row.created_at));
                            return createdAtDate.getFullYear() +
                                '/' + _.padStart(createdAtDate.getMonth() + 1, 2, '0') +
                                '/' + _.padStart(createdAtDate.getDate(), 2, '0') +
                                ' ' + _.padStart(createdAtDate.getHours(), 2, '0') +
                                ':' + _.padStart(createdAtDate.getMinutes(), 2, '0') +
                                ':' + _.padStart(createdAtDate.getSeconds(), 2, '0');
                        }
                    },
                    {
                        'title': lang.get('messages.actions_label'),
                        'data': 'id',
                        'name': 'id',
                        'orderable': false,
                        'render': function (data, type, row, meta) {
                            let template = Handlebars.templates['admin_users_table_row_actions'];

                            return template($.extend({}, getHandlebarsDefaultVariables(), row));
                        }
                    },
                    {
                        'title': lang.get('messages.patreon_label'),
                        'data': 'id',
                        'name': 'id',
                        'orderable': false,
                        'render': function (data, type, row, meta) {
                            let result = '';
                            if (row.patreondata !== null) {
                                let template = Handlebars.templates['admin_users_table_row_patreon'];

                                let paidTiersCopy = JSON.parse(JSON.stringify(paidTiers));
                                for (let i = 0; i < row.patreondata.paidtiers.length; i++) {
                                    let userPaidTier = row.patreondata.paidtiers[i];
                                    for (let j = 0; j < paidTiersCopy.length; j++) {
                                        if (paidTiersCopy[j].id === userPaidTier.id) {
                                            paidTiersCopy[j].selected = true;
                                        }
                                    }
                                }

                                result = template($.extend({}, getHandlebarsDefaultVariables(), row, {paidtiers: paidTiersCopy}));
                            }

                            return result;
                        }
                    },
                ],
                'language': {
                    'emptyTable': lang.get('messages.datatable_no_users_in_table')
                }
            });
        });
    </script>
@endsection

@section('content')
    <table id="admin_user_table" class="tablesorter default_table table-striped">
        <thead>
        <tr>
            <th width="5%">{{ __('Id') }}</th>
            <th width="30%">{{ __('Name') }}</th>
            <th width="15%">{{ __('# routes') }}</th>
            <th width="10%">{{ __('Roles') }}</th>
            <th width="15%">{{ __('Registered') }}</th>
            <th width="10%">{{ __('Actions') }}</th>
            <th width="10%">{{ __('Patreon') }}</th>
        </tr>
        </thead>

        {{--        <tbody>--}}
        {{--        @foreach ($models as $user)--}}
        {{--            <?php /** @var $user \App\User */?>--}}
        {{--            <tr>--}}
        {{--                <td>{{ $user->id }}</td>--}}
        {{--                <td>{{ $user->name }}</td>--}}
        {{--                <td>{{ $user->dungeonroutes->count() }}</td>--}}
        {{--                <td>{{ implode(', ', $user->roles->pluck('display_name')->toArray())}}</td>--}}
        {{--                <td>{{ $user->created_at->setTimezone('Europe/Amsterdam') }}</td>--}}
        {{--                <td>--}}
        {{--                    <?php--}}
        {{--                    // I really want to be the only one doing this--}}
        {{--                    if( Auth::user()->name === 'Admin' ){ ?>--}}

        {{--                            <div class="dropdown">--}}
        {{--                                <button class="btn btn-secondary dropdown-toggle" type="button" id="userActionsButton"--}}
        {{--                                        data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">--}}
        {{--                                    {{ __('Actions') }}--}}
        {{--                                </button>--}}
        {{--                                <div class="dropdown-menu" aria-labelledby="userActionsButton">--}}
        {{--                                    <a class="dropdown-item" href="#">--}}
        {{--                                        {{ Form::model($user, ['route' => ['admin.user.makeadmin', $user->id], 'autocomplete' => 'off', 'method' => 'post']) }}--}}
        {{--                                        {!! Form::submit(__('Make admin'), ['class' => 'btn btn-info', 'name' => 'submit']) !!}--}}
        {{--                                        {!! Form::close() !!}--}}
        {{--                                    </a>--}}
        {{--                                    <a class="dropdown-item" href="#">--}}
        {{--                                        {{ Form::model($user, ['route' => ['admin.user.makeuser', $user->id], 'autocomplete' => 'off', 'method' => 'post']) }}--}}
        {{--                                        {!! Form::submit(__('Make user'), ['class' => 'btn btn-info ml-1', 'name' => 'submit']) !!}--}}
        {{--                                        {!! Form::close() !!}--}}
        {{--                                    </a>--}}
        {{--                                    <a class="dropdown-item" href="#">--}}
        {{--                                        {{ Form::model($user, ['route' => ['admin.user.delete', $user->id], 'autocomplete' => 'off', 'method' => 'delete']) }}--}}
        {{--                                        {!! Form::submit(__('Delete user'), ['class' => 'btn btn-danger ml-1', 'name' => 'submit']) !!}--}}
        {{--                                        {!! Form::close() !!}--}}
        {{--                                    </a>--}}
        {{--                                </div>--}}
        {{--                            </div>--}}
        {{--                    <?php } else {--}}
        {{--                        echo __('Please login as "Admin"');--}}
        {{--                    }--}}
        {{--                    ?>--}}
        {{--                </td>--}}
        {{--                <td>--}}
        {{--                    @if(!$user->hasRole('admin'))--}}
        {{--                        {!! Form::select('patreon_paid_tiers', $paidTiers->pluck('name', 'id'),--}}
        {{--                                isset($user->patreondata) ? $user->patreondata->paidtiers->pluck('id') : null, [--}}
        {{--                            'class' => 'form-control selectpicker patreon_paid_tiers',--}}
        {{--                            'multiple' => 'multiple',--}}
        {{--                            'data-selected-text-format' => 'count > 1',--}}
        {{--                            'data-count-selected-text' => __('{0} paid tiers'),--}}
        {{--                            'data-userid' => $user->id--}}
        {{--                            ])--}}
        {{--                        !!}--}}
        {{--                    @endif--}}
        {{--                </td>--}}
        {{--            </tr>--}}
        {{--        @endforeach--}}
        {{--        </tbody>--}}

    </table>
@endsection