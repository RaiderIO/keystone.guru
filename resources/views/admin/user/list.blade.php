@extends('layouts.app', ['showAds' => false, 'title' => __('User list')])

@section('header-title')
    {{ __('View Users') }}
@endsection

<?php
/** @var $models \Illuminate\Support\Collection */
// eager load the classification
//dd($models);
?>
@include('common.general.inline', ['path' => 'admin/user/list',
        'options' =>  [
            'patreon_select_selector' => 'select.patreon_paid_tiers'
        ]
])

@section('scripts')
    <script type="text/javascript">
        $(function () {
            $('#admin_user_table').DataTable({});

            refreshSelectPickers();
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

        <tbody>
        @foreach ($models as $user)
            <?php /** @var $user \App\User */?>
            <tr>
                <td>{{ $user->id }}</td>
                <td>{{ $user->name }}</td>
                <td>{{ $user->dungeonroutes->count() }}</td>
                <td>{{ implode(', ', $user->roles->pluck('display_name')->toArray())}}</td>
                <td>{{ $user->created_at->setTimezone('Europe/Amsterdam') }}</td>
                <td>
                    <?php
                    // I really want to be the only one doing this
                    if( Auth::user()->name === 'Admin' ){ ?>

                    <div class="dropdown">
                        <button class="btn btn-secondary dropdown-toggle" type="button" id="userActionsButton"
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            {{ __('Actions') }}
                        </button>
                        <div class="dropdown-menu" aria-labelledby="userActionsButton">
                            <a class="dropdown-item" href="#">
                                {{ Form::model($user, ['route' => ['admin.user.makeadmin', $user->id], 'autocomplete' => 'off', 'method' => 'post']) }}
                                {!! Form::submit(__('Make admin'), ['class' => 'btn btn-info', 'name' => 'submit']) !!}
                                {!! Form::close() !!}
                            </a>
                            <a class="dropdown-item" href="#">
                                {{ Form::model($user, ['route' => ['admin.user.makeuser', $user->id], 'autocomplete' => 'off', 'method' => 'post']) }}
                                {!! Form::submit(__('Make user'), ['class' => 'btn btn-info ml-1', 'name' => 'submit']) !!}
                                {!! Form::close() !!}
                            </a>
                            <a class="dropdown-item" href="#">
                                {{ Form::model($user, ['route' => ['admin.user.delete', $user->id], 'autocomplete' => 'off', 'method' => 'delete']) }}
                                {!! Form::submit(__('Delete user'), ['class' => 'btn btn-danger ml-1', 'name' => 'submit']) !!}
                                {!! Form::close() !!}
                            </a>
                        </div>
                    </div>
                    <?php } else {
                        echo __('Please login as "Admin"');
                    }
                    ?>
                </td>
                <td>
                    @if(!$user->hasRole('admin'))
                        {!! Form::select('patreon_paid_tiers', $paidTiers->pluck('name', 'id'),
                                isset($user->patreondata) ? $user->patreondata->paidtiers->pluck('id') : null, [
                            'class' => 'form-control selectpicker patreon_paid_tiers',
                            'multiple' => 'multiple',
                            'data-selected-text-format' => 'count > 1',
                            'data-count-selected-text' => __('{0} paid tiers'),
                            'data-userid' => $user->id
                            ])
                        !!}
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>

    </table>
@endsection