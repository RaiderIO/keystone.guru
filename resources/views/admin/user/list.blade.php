@extends('layouts.app', ['noads' => true])

@section('header-title')
    {{ __('View Users') }}
@endsection

<?php
/** @var $models \Illuminate\Support\Collection */
// eager load the classification
//dd($models);
?>

@section('scripts')
    <script type="text/javascript">
        $(function () {
            $('#admin_user_table').DataTable({});
        });
    </script>
@endsection

@section('content')
    <table id="admin_user_table" class="tablesorter default_table table-striped">
        <thead>
        <tr>
            <th width="10%">{{ __('Id') }}</th>
            <th width="20%">{{ __('Name') }}</th>
            <th width="20%">{{ __('Roles') }}</th>
            <th width="20%">{{ __('Registered') }}</th>
            <th width="30%">{{ __('Actions') }}</th>
        </tr>
        </thead>

        <tbody>
        @foreach ($models as $user)
            <?php /** @var $user \App\User */?>
            <tr>
                <td>{{ $user->id }}</td>
                <td>{{ $user->name }}</td>
                <td>{{ implode(', ', $user->roles->pluck('display_name')->toArray())}}</td>
                <td>{{ $user->created_at }}</td>
                <td>
                    <?php
                    // I really want to be the only one doing this
                    if( Auth::user()->name === 'Admin' ){ ?>
                    <div class="row">
                        {{ Form::model($user, ['route' => ['admin.user.makeadmin', $user->name], 'autocomplete' => 'off', 'method' => 'post']) }}
                        {!! Form::submit(__('Make admin'), ['class' => 'btn btn-info', 'name' => 'submit', 'value' => 'submit']) !!}
                        {!! Form::close() !!}

                        {{ Form::model($user, ['route' => ['admin.user.makeuser', $user->name], 'autocomplete' => 'off', 'method' => 'post']) }}
                        {!! Form::submit(__('Make user'), ['class' => 'btn btn-info ml-1', 'name' => 'submit', 'value' => 'submit']) !!}
                        {!! Form::close() !!}
                    </div>
                    <?php } ?>
                </td>
            </tr>
        @endforeach
        </tbody>

    </table>
@endsection