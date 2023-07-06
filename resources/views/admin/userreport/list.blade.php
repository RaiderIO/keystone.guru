@extends('layouts.sitepage', ['showAds' => false, 'title' => __('views/admin.userreport.list.title')])

@section('header-title')
    {{ __('views/admin.userreport.list.header') }}
@endsection

<?php
/** @var $models \Illuminate\Support\Collection */
// eager load the classification
//dd($models);
?>

@section('scripts')
    @parent

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
            <th width="5%">{{ __('views/admin.userreport.list.table_header_id') }}</th>
            <th width="10%">{{ __('views/admin.userreport.list.table_header_author_name') }}</th>
            <th width="10%">{{ __('views/admin.userreport.list.table_header_category') }}</th>
            <th width="40%">{{ __('views/admin.userreport.list.table_header_message') }}</th>
            <th width="10%">{{ __('views/admin.userreport.list.table_header_contact_at') }}</th>
            <th width="10%">{{ __('views/admin.userreport.list.table_header_create_at') }}</th>
            <th width="15%">{{ __('views/admin.userreport.list.table_header_actions') }}</th>
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
                        <i class="fas fa-check-circle"></i> {{ __('views/admin.userreport.list.handled') }}
                    </button>
                </td>
            </tr>
        @endforeach
        </tbody>

    </table>
@endsection
