@extends('layouts.sitepage', ['showAds' => false, 'title' => __('view_admin.tools.combatlog.parsefailures.title')])

@section('header-title', __('view_admin.tools.combatlog.parsefailures.header'))

@section('content')
    <p class="text-muted">{{ __('view_admin.tools.combatlog.parsefailures.description') }}</p>

    @if($failures->isEmpty())
        <div class="alert alert-success">
            <i class="fas fa-check"></i> {{ __('view_admin.tools.combatlog.parsefailures.empty') }}
        </div>
    @else
        <table class="table table-sm table-striped mb-4">
            <thead>
            <tr>
                <th>{{ __('view_admin.tools.combatlog.parsefailures.column_run_id') }}</th>
                <th>{{ __('view_admin.tools.combatlog.parsefailures.column_version') }}</th>
                <th>{{ __('view_admin.tools.combatlog.parsefailures.column_line') }}</th>
                <th>{{ __('view_admin.tools.combatlog.parsefailures.column_message') }}</th>
                <th>{{ __('view_admin.tools.combatlog.parsefailures.column_class') }}</th>
                <th>{{ __('view_admin.tools.combatlog.parsefailures.column_created') }}</th>
                <th>{{ __('view_admin.tools.combatlog.parsefailures.column_status') }}</th>
                <th class="text-right">{{ __('view_admin.tools.combatlog.parsefailures.column_actions') }}</th>
            </tr>
            </thead>
            <tbody>
            @foreach($failures as $failure)
                <tr class="{{ $failure->resolved_at !== null ? 'text-muted' : '' }}">
                    <td>{{ $failure->run_id }}</td>
                    <td>{{ $failure->combat_log_version }}</td>
                    <td>{{ $failure->line_number }}</td>
                    <td>
                        <div>{{ $failure->message }}</div>
                        @if($failure->raw_line !== null)
                            <code class="small">{{ $failure->raw_line }}</code>
                        @endif
                    </td>
                    <td>{{ $failure->exception_class }}</td>
                    <td>{{ $failure->created_at }}</td>
                    <td>
                        @if($failure->resolved_at !== null)
                            <span class="badge badge-success badge-pill">
                                {{ __('view_admin.tools.combatlog.parsefailures.status_resolved') }}
                            </span>
                        @else
                            <span class="badge badge-warning badge-pill">
                                {{ __('view_admin.tools.combatlog.parsefailures.status_open') }}
                            </span>
                        @endif
                    </td>
                    <td class="text-right" style="white-space: nowrap;">
                        <button type="button"
                                class="btn btn-sm btn-info view-log-btn"
                                data-segments-url="{{ route('admin.tools.combatlog.parsefailures.segments', ['parseFailure' => $failure->id]) }}"
                                data-run-id="{{ $failure->run_id }}">
                            <i class="fas fa-download"></i> {{ __('view_admin.tools.combatlog.parsefailures.view_log') }}
                        </button>
                        @if($failure->resolved_at === null)
                            <form method="POST"
                                  action="{{ route('admin.tools.combatlog.parsefailures.resolve', ['parseFailure' => $failure->id]) }}"
                                  style="display: inline;">
                                {{ csrf_field() }}
                                <button type="submit" class="btn btn-sm btn-success">
                                    <i class="fas fa-check"></i> {{ __('view_admin.tools.combatlog.parsefailures.resolve') }}
                                </button>
                            </form>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif

    <div class="modal fade" id="parsefailures_segments_modal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        {{ __('view_admin.tools.combatlog.parsefailures.segments_modal_title') }}
                        <span id="parsefailures_segments_run_id"></span>
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">
                        {{ __('view_admin.tools.combatlog.parsefailures.segments_modal_description') }}
                    </p>
                    <div id="parsefailures_segments_body"></div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    @parent

    <script type="text/javascript">
        $(function () {
            let $modal = $('#parsefailures_segments_modal');
            let $body = $('#parsefailures_segments_body');
            let $runId = $('#parsefailures_segments_run_id');

            $('.view-log-btn').on('click', function () {
                let url = $(this).data('segments-url');

                $runId.text($(this).data('run-id'));
                $body.html('<i class="fas fa-spinner fa-spin"></i>');
                $modal.modal('show');

                $.getJSON(url)
                    .done(function (response) {
                        let $list = $('<div class="list-group"></div>');
                        response.segments.forEach(function (segment) {
                            $('<a class="list-group-item list-group-item-action" target="_blank"></a>')
                                .attr('href', segment.downloadUrl)
                                .text('{{ __('view_admin.tools.combatlog.parsefailures.segment') }} #' + segment.id + ' (' + segment.type + ')')
                                .appendTo($list);
                        });
                        $body.empty().append($list);
                    })
                    .fail(function (xhr) {
                        let message = xhr.responseJSON && xhr.responseJSON.error
                            ? xhr.responseJSON.error
                            : '{{ __('view_admin.tools.combatlog.parsefailures.segments_error') }}';
                        $body.html('<div class="alert alert-danger mb-0">' + message + '</div>');
                    });
            });
        });
    </script>
@endsection
