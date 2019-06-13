//
// Charts
//

'use strict';

class LineChart {

    constructor() {
        this.timeFormat = 'YYYY/MM/DD HH:mm';
    }

    // Methods
    init($chart) {
        let chartOptions = $chart.data('options');

        // Find the min and the max values so we can fit the graph
        let data = chartOptions.data.datasets[0].data;

        let min = data[0].y;
        let max = data[data.length - 1].y;

        let options = $.extend({
                type: 'line',
                options: {
                    scales: {
                        xAxes: [{
                            type: 'time',
                            time: {
                                parser: this.timeFormat,
                                // round: 'day'
                                tooltipFormat: 'll HH:mm'
                            },
                            scaleLabel: {
                                display: true,
                                labelString: 'Date'
                            }
                        }],
                        yAxes: [{
                            gridLines: {
                                color: Charts.colors.gray[900],
                                zeroLineColor: Charts.colors.gray[900]
                            },
                            ticks: {
                                min: min,
                                max: max,
                                callback: function (value) {
                                    if (!(value % 1)) {
                                        return value;
                                    }
                                }
                            }
                        }]
                    },
                    tooltips: {
                        callbacks: {
                            label: function (item, data) {
                                let label = data.datasets[item.datasetIndex].label || '';
                                let yLabel = item.yLabel;
                                let content = '';

                                if (data.datasets.length > 1) {
                                    content += '<span class="popover-body-label mr-auto">' + label + '</span>';
                                }

                                content += '<span class="popover-body-value">' + yLabel + '</span>';
                                return content;
                            }
                        }
                    }
                }
            },
            // Merge with options received from the chart itself
            chartOptions);

        let lineChart = new Chart($chart, options);
        $chart.data('chart', lineChart);
    }
}

//
// Users chart
//

class BarChart {
    // Init chart
    init($chart) {

        // Create chart
        let barChart = new Chart($chart, $.extend({
                type: 'bar',
                options: {
                    scales: {
                        yAxes: [{
                            ticks: {
                                callback: function (value) {
                                    if (!(value % 1)) {
                                        return value
                                    }
                                }
                            }
                        }]
                    },
                    tooltips: {
                        callbacks: {
                            label: function (item, data) {
                                let label = data.datasets[item.datasetIndex].label || '';
                                let yLabel = item.yLabel;
                                let content = '';

                                if (data.datasets.length > 1) {
                                    content += '<span class="popover-body-label mr-auto">' + label + '</span>';
                                }

                                content += '<span class="popover-body-value">' + yLabel + '</span>';

                                return content;
                            }
                        }
                    }
                }
            },
            // Merge with options received from the chart itself
            $chart.data('options')));

        // Save to jQuery object
        $chart.data('chart', barChart);
    }
}


$(function () {
    let $lineCharts = $('.line-chart-canvas');
    $.each($lineCharts, function (index, value) {
        new LineChart().init($(value));
    });

    let $barCharts = $('.bar-chart-canvas');
    $.each($barCharts, function (index, value) {
        new BarChart().init($(value));
    });
});