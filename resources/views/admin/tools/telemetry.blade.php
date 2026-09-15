<?php
/**
 * @var string             $range
 * @var array<int, string> $ranges
 * @var array<int, string> $schedulerCommands
 * @var array<string, string> $gaugeMeasurements
 */
?>
@extends('layouts.sitepage', ['showAds' => false, 'title' => __('view_admin.tools.telemetry.title')])

@section('header-title', __('view_admin.tools.telemetry.header'))

@section('content')
    <p class="text-muted">{{ __('view_admin.tools.telemetry.description') }}</p>

    <form method="GET" action="{{ route('admin.tools.telemetry.view') }}" class="row g-2 align-items-end mb-3">
        <div class="col-auto">
            <label for="range" class="form-label">{{ __('view_admin.tools.telemetry.range') }}</label>
            <select class="form-control" id="range" name="range">
                @foreach($ranges as $rangeOption)
                    <option value="{{ $rangeOption }}" {{ $rangeOption === $range ? 'selected' : '' }}>
                        {{ __(sprintf('view_admin.tools.telemetry.range_option.%s', $rangeOption)) }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-primary">
                {{ __('view_admin.tools.telemetry.refresh') }}
            </button>
        </div>
    </form>

    <p class="text-muted small">
        <i class="fas fa-info-circle"></i>
        {{ __('view_admin.tools.telemetry.retention_note', [
            'measurements' => implode(', ', config('keystoneguru.telemetry.growth_measurements')),
            'days'         => config('keystoneguru.telemetry.retention_days'),
        ]) }}
    </p>

    @if($schedulerCommands === [] && $gaugeMeasurements === [])
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i> {{ __('view_admin.tools.telemetry.no_data') }}
        </div>
    @else
        @if($gaugeMeasurements !== [])
            <h4 class="mt-4">{{ __('view_admin.tools.telemetry.subheader_gauges') }}</h4>
            <div class="row">
                @foreach($gaugeMeasurements as $measurement => $title)
                    @include('admin.tools.telemetry.chart', [
                        'chartTitle'  => $title,
                        'measurement' => $measurement,
                        'name'        => null,
                        'axisLabel'   => __('view_admin.tools.telemetry.axis_value'),
                    ])
                @endforeach
            </div>
        @endif

        @if($schedulerCommands !== [])
            <h4 class="mt-4">{{ __('view_admin.tools.telemetry.subheader_scheduler') }}</h4>
            <div class="row">
                @foreach($schedulerCommands as $command)
                    @include('admin.tools.telemetry.chart', [
                        'chartTitle'  => $command,
                        'measurement' => 'scheduler',
                        'name'        => $command,
                        'axisLabel'   => __('view_admin.tools.telemetry.axis_duration'),
                    ])
                @endforeach
            </div>
        @endif
    @endif
@endsection

@section('scripts')
    @parent

    <script src="{{ ksgCompiledAsset('js/lib/chart.umd.js') }}"></script>
@endsection

@include('common.general.inline', ['path' => 'admin/tools/telemetry', 'options' => [
    'dataUrl'              => route('admin.tools.telemetry.data'),
    'range'                => $range,
    'chartSelector'        => '.telemetry-chart',
    'averageLegendFormat'  => __('view_admin.tools.telemetry.legend_average', ['label' => ':label']),
    'maximumLegendFormat'  => __('view_admin.tools.telemetry.legend_maximum', ['label' => ':label']),
    'noDataText'           => __('view_admin.tools.telemetry.chart_no_data'),
    'loadFailedText'       => __('view_admin.tools.telemetry.chart_load_failed'),
    'failuresLegend'       => __('view_admin.tools.telemetry.legend_failures'),
]])
