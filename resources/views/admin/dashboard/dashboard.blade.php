@extends('admin.dashboard.layouts.app')
--------
@section('content')
    @include('admin.dashboard.layouts.headers.cards')

    <div class="container-fluid mt--7">
        <div class="row">
            <div class="col-xl-8 mb-5 mb-xl-0">
                <div class="card bg-gradient-default shadow">
                    <div class="card-header bg-transparent">
                        <div class="row align-items-center">
                            <div class="col">
                                <h6 class="text-uppercase text-light ls-1 mb-1">{{ $lineChartTopTitle }}</h6>
                                <h2 class="text-white mb-0">{{ $lineChartBottomTitle }}</h2>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Chart -->
                        <div class="chart">
                            <!-- Chart wrapper -->
                            <canvas id="chart-users" class="chart-canvas line-chart-canvas"
                                    data-options='{!! json_encode($options) !!}'></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-4">
                <div class="card shadow">
                    <div class="card-header bg-transparent">
                        <div class="row align-items-center">
                            <div class="col">
                                <h6 class="text-uppercase text-muted ls-1 mb-1">{{ $barChartTopTitle }}</h6>
                                <h2 class="mb-0">{{ $barChartBottomTitle }}</h2>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Chart -->
                        <div class="chart">
                            <canvas id="chart-users-by-month" class="chart-canvas bar-chart-canvas"
                                    data-options='{!! json_encode($optionsByMonth) !!}'
                            ></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @include('admin.dashboard.layouts.footers.auth')
    </div>
@endsection

@push('js')
    <script src="{{ asset('argon') }}/vendor/chart.js/dist/Chart.bundle.min.js"></script>
    <script src="{{ asset('argon') }}/vendor/chart.js/dist/Chart.extension.js"></script>
@endpush