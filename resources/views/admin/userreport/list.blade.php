@extends('layouts.sitepage', ['showAds' => false, 'title' => __('User reports')])

@section('header-title')
    {{ __('View User Reports') }}
@endsection

<?php
/** @var $models \Illuminate\Support\Collection */
// eager load the classification
//dd($models);
?>

@section('scripts')
    <script type="text/javascript">
        $(function () {
            let userReportsDatatable = $('#admin_user_reports_table').DataTable({
                'drawCallback': function (settings) {
                    refreshTooltips();

                    $('.mark_as_handled_btn').unbind('click').bind('click', function () {
                        let $this = $(this);
                        let id = $this.data('id');
                        $.ajax({
                            type: 'PUT',
                            url: `/ajax/userreport/${id}/status`,
                            dataType: 'json',
                            data: {
                                status: 1
                            },
                            success: function (json) {
                                showSuccessNotification(lang.get('messages.user_report_handled_success'));

                                // Refresh the table
                                userReportsDatatable.row($this.closest('tr')).remove().draw();
                            },
                        });
                    });
                },
            });
        });
    </script>
@endsection

@section('content')
    <table id="admin_user_reports_table" class="tablesorter default_table table-striped">
        <thead>
        <tr>
            <th width="5%">{{ __('Id') }}</th>
            <th width="10%">{{ __('Author name') }}</th>
            <th width="10%">{{ __('Category') }}</th>
            <th width="40%">{{ __('Message') }}</th>
            <th width="10%">{{ __('Contact at') }}</th>
            <th width="10%">{{ __('Created at') }}</th>
            <th width="15%">{{ __('Actions') }}</th>
        </tr>
        </thead>

        <tbody>
        @foreach ($models as $report)
            <?php /** @var $user \App\Models\UserReport */?>
            <tr>
                <td>{{ $report->id }}</td>
                <td>{{ $report->user->name }}</td>
                <td>
                    <span data-toggle="tooltip"
                          title="{{ isset($report->model) ? json_encode($report->model->toArray(), JSON_PRETTY_PRINT) : '' }}">{{ $report->category }}
                    </span>
                </td>
                <td>{{ $report->message }}</td>
                <td>{{ $report->contact_ok ? $report->user->email : '-' }}</td>
                <td>{{ $report->created_at }}</td>
                <td>
                    <button class="btn btn-success mark_as_handled_btn" data-id="{{$report->id}}">
                        <i class="fas fa-check-circle"></i> {{ __('Handled') }}
                    </button>
                </td>
            </tr>
        @endforeach
        </tbody>

    </table>
@endsection