<?php
/** @var $user \App\User */
?>
<div class="tab-pane fade" id="privacy" role="tabpanel" aria-labelledby="privacy-tab">
    <h4>
        {{ __('views/profile.edit.privacy') }}
    </h4>
    {{ Form::model($user, ['route' => ['profile.updateprivacy', $user->id], 'method' => 'patch']) }}
    <div class="form-group{{ $errors->has('analytics_cookie_opt_out') ? ' has-error' : '' }}">
        {!! Form::label('analytics_cookie_opt_out', __('views/profile.edit.ga_cookies_opt_out')) !!}
        {!! Form::checkbox('analytics_cookie_opt_out', 1, $user->analytics_cookie_opt_out, ['class' => 'form-control left_checkbox']) !!}
    </div>
    {!! Form::submit(__('views/profile.edit.submit'), ['class' => 'btn btn-info']) !!}
    {!! Form::close() !!}
</div>

<div class="tab-pane fade" id="reports" role="tabpanel" aria-labelledby="reports-tab">
    <h4>
        {{ __('views/profile.edit.reports') }}
    </h4>
    <p>
        {{ __('views/profile.edit.reports_description') }}
    </p>

    <table id="user_reports_table" class="tablesorter default_table table-striped">
        <thead>
        <tr>
            <th width="5%">{{ __('views/profile.edit.reports_table_header_id') }}</th>
            <th width="10%">{{ __('views/profile.edit.reports_table_header_category') }}</th>
            <th width="60%">{{ __('views/profile.edit.reports_table_header_message') }}</th>
            <th width="15%">{{ __('views/profile.edit.reports_table_header_created_at') }}</th>
            <th width="10%">{{ __('views/profile.edit.reports_table_header_status') }}</th>
        </tr>
        </thead>

        <tbody>
        @foreach ($user->reports()->orderByDesc('id')->get() as $report)
                <?php /** @var $user \App\Models\UserReport */ ?>
            <tr>
                <td>{{ $report->id }}</td>
                <td>{{ $report->category }}</td>
                <td>{{ $report->message }}</td>
                <td>{{ $report->contact_ok ? $report->user->email : '-' }}</td>
                <td>{{ $report->created_at }}</td>
                <td>
                    <button class="btn btn-success mark_as_handled_btn" data-id="{{$report->id}}">
                        <i class="fas fa-check-circle"></i> {{ __('views/profile.edit.reports_table_action_handled') }}
                    </button>
                </td>
            </tr>
        @endforeach
        </tbody>

    </table>
</div>
