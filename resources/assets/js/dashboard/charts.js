//
// Charts
//

'use strict';

let Charts = (function () {

    // Variable

    let $toggle = $('[data-toggle="chart"]');
    let mode = 'light';//(themeMode) ? themeMode : 'light';
    let fonts = {
        base: 'Open Sans'
    }

    // Colors
    let colors = {
        gray: {
            100: '#f6f9fc',
            200: '#e9ecef',
            300: '#dee2e6',
            400: '#ced4da',
            500: '#adb5bd',
            600: '#8898aa',
            700: '#525f7f',
            800: '#32325d',
            900: '#212529'
        },
        theme: {
            'default': '#172b4d',
            'primary': '#5e72e4',
            'secondary': '#f4f5f7',
            'info': '#11cdef',
            'success': '#2dce89',
            'danger': '#f5365c',
            'warning': '#fb6340'
        },
        black: '#12263F',
        white: '#FFFFFF',
        transparent: 'transparent',
    };


    // Methods

    // Chart.js global options
    function chartOptions() {

        // Options
        let options = {
            defaults: {
                global: {
                    responsive: true,
                    maintainAspectRatio: false,
                    defaultColor: (mode == 'dark') ? colors.gray[700] : colors.gray[600],
                    defaultFontColor: (mode == 'dark') ? colors.gray[700] : colors.gray[600],
                    defaultFontFamily: fonts.base,
                    defaultFontSize: 13,
                    layout: {
                        padding: 0
                    },
                    legend: {
                        display: false,
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            padding: 16
                        }
                    },
                    elements: {
                        point: {
                            radius: 0,
                            backgroundColor: colors.theme['primary']
                        },
                        line: {
                            tension: .4,
                            borderWidth: 4,
                            borderColor: colors.theme['primary'],
                            backgroundColor: colors.transparent,
                            borderCapStyle: 'rounded'
                        },
                        rectangle: {
                            backgroundColor: colors.theme['warning']
                        },
                        arc: {
                            backgroundColor: colors.theme['primary'],
                            borderColor: (mode == 'dark') ? colors.gray[800] : colors.white,
                            borderWidth: 4
                        }
                    },
                    tooltips: {
                        enabled: false,
                        mode: 'index',
                        intersect: false,
                        custom: function (model) {

                            // Get tooltip
                            let $tooltip = $('#chart-tooltip');

                            // Create tooltip on first render
                            if (!$tooltip.length) {
                                $tooltip = $('<div id="chart-tooltip" class="popover bs-popover-top" role="tooltip"></div>');

                                // Append to body
                                $('body').append($tooltip);
                            }

                            // Hide if no tooltip
                            if (model.opacity === 0) {
                                $tooltip.css('display', 'none');
                                return;
                            }

                            function getBody(bodyItem) {
                                return bodyItem.lines;
                            }

                            // Fill with content
                            if (model.body) {
                                let titleLines = model.title || [];
                                let bodyLines = model.body.map(getBody);
                                let html = '';

                                // Add arrow
                                html += '<div class="arrow"></div>';

                                // Add header
                                titleLines.forEach(function (title) {
                                    html += '<h3 class="popover-header text-center">' + title + '</h3>';
                                });

                                // Add body
                                bodyLines.forEach(function (body, i) {
                                    let colors = model.labelColors[i];
                                    let styles = 'background-color: ' + colors.backgroundColor;
                                    let indicator = '<span class="badge badge-dot"><i class="bg-primary"></i></span>';
                                    let align = (bodyLines.length > 1) ? 'justify-content-left' : 'justify-content-center';
                                    html += '<div class="popover-body d-flex align-items-center ' + align + '">' + indicator + body + '</div>';
                                });

                                $tooltip.html(html);
                            }

                            // Get tooltip position
                            let $canvas = $(this._chart.canvas);

                            let canvasWidth = $canvas.outerWidth();
                            let canvasHeight = $canvas.outerHeight();

                            let canvasTop = $canvas.offset().top;
                            let canvasLeft = $canvas.offset().left;

                            let tooltipWidth = $tooltip.outerWidth();
                            let tooltipHeight = $tooltip.outerHeight();

                            let top = canvasTop + model.caretY - tooltipHeight - 16;
                            let left = canvasLeft + model.caretX - tooltipWidth / 2;

                            // Display tooltip
                            $tooltip.css({
                                'top': top + 'px',
                                'left': left + 'px',
                                'display': 'block',
                                'z-index': '100'
                            });

                        },
                        callbacks: {
                            label: function (item, data) {
                                let label = data.datasets[item.datasetIndex].label || '';
                                let yLabel = item.yLabel;
                                let content = '';

                                if (data.datasets.length > 1) {
                                    content += '<span class="badge badge-primary mr-auto">' + label + '</span>';
                                }

                                content += '<span class="popover-body-value">' + yLabel + '</span>';
                                return content;
                            }
                        }
                    }
                },
                doughnut: {
                    cutoutPercentage: 83,
                    tooltips: {
                        callbacks: {
                            title: function (item, data) {
                                let title = data.labels[item[0].index];
                                return title;
                            },
                            label: function (item, data) {
                                let value = data.datasets[0].data[item.index];
                                let content = '';

                                content += '<span class="popover-body-value">' + value + '</span>';
                                return content;
                            }
                        }
                    },
                    legendCallback: function (chart) {
                        let data = chart.data;
                        let content = '';

                        data.labels.forEach(function (label, index) {
                            let bgColor = data.datasets[0].backgroundColor[index];

                            content += '<span class="chart-legend-item">';
                            content += '<i class="chart-legend-indicator" style="background-color: ' + bgColor + '"></i>';
                            content += label;
                            content += '</span>';
                        });

                        return content;
                    }
                }
            }
        }

        // yAxes
        Chart.scaleService.updateScaleDefaults('linear', {
            gridLines: {
                borderDash: [2],
                borderDashOffset: [2],
                color: (mode == 'dark') ? colors.gray[900] : colors.gray[300],
                drawBorder: false,
                drawTicks: false,
                lineWidth: 0,
                zeroLineWidth: 0,
                zeroLineColor: (mode == 'dark') ? colors.gray[900] : colors.gray[300],
                zeroLineBorderDash: [2],
                zeroLineBorderDashOffset: [2]
            },
            ticks: {
                beginAtZero: true,
                padding: 10,
                callback: function (value) {
                    if (!(value % 10)) {
                        return value
                    }
                }
            }
        });

        // xAxes
        Chart.scaleService.updateScaleDefaults('category', {
            gridLines: {
                drawBorder: false,
                drawOnChartArea: false,
                drawTicks: false
            },
            ticks: {
                padding: 20
            },
            maxBarThickness: 10
        });

        return options;

    }

    // Parse global options
    function parseOptions(parent, options) {
        for (let item in options) {
            if (typeof options[item] !== 'object') {
                parent[item] = options[item];
            } else {
                parseOptions(parent[item], options[item]);
            }
        }
    }

    // Push options
    function pushOptions(parent, options) {
        for (let item in options) {
            if (Array.isArray(options[item])) {
                options[item].forEach(function (data) {
                    parent[item].push(data);
                });
            } else {
                pushOptions(parent[item], options[item]);
            }
        }
    }

    // Pop options
    function popOptions(parent, options) {
        for (let item in options) {
            if (Array.isArray(options[item])) {
                options[item].forEach(function (data) {
                    parent[item].pop();
                });
            } else {
                popOptions(parent[item], options[item]);
            }
        }
    }

    // Toggle options
    function toggleOptions(elem) {
        let options = elem.data('add');
        let $target = $(elem.data('target'));
        let $chart = $target.data('chart');

        if (elem.is(':checked')) {

            // Add options
            pushOptions($chart, options);

            // Update chart
            $chart.update();
        } else {

            // Remove options
            popOptions($chart, options);

            // Update chart
            $chart.update();
        }
    }

    // Update options
    function updateOptions(elem) {
        let options = elem.data('update');
        let $target = $(elem.data('target'));
        let $chart = $target.data('chart');

        // Parse options
        parseOptions($chart, options);

        // Toggle ticks
        toggleTicks(elem, $chart);

        // Update chart
        $chart.update();
    }

    // Toggle ticks
    function toggleTicks(elem, $chart) {

        if (elem.data('prefix') !== undefined || elem.data('prefix') !== undefined) {
            let prefix = elem.data('prefix') ? elem.data('prefix') : '';
            let suffix = elem.data('suffix') ? elem.data('suffix') : '';

            // Update ticks
            $chart.options.scales.yAxes[0].ticks.callback = function (value) {
                if (!(value % 10)) {
                    return prefix + value + suffix;
                }
            }

            // Update tooltips
            $chart.options.tooltips.callbacks.label = function (item, data) {
                let label = data.datasets[item.datasetIndex].label || '';
                let yLabel = item.yLabel;
                let content = '';

                if (data.datasets.length > 1) {
                    content += '<span class="popover-body-label mr-auto">' + label + '</span>';
                }

                content += '<span class="popover-body-value">' + prefix + yLabel + suffix + '</span>';
                return content;
            }

        }
    }


    // Events

    // Parse global options
    if (window.Chart) {
        parseOptions(Chart, chartOptions());
    }

    // Toggle options
    $toggle.on({
        'change': function () {
            let $this = $(this);

            if ($this.is('[data-add]')) {
                toggleOptions($this);
            }
        },
        'click': function () {
            let $this = $(this);

            if ($this.is('[data-update]')) {
                updateOptions($this);
            }
        }
    });


    // Return

    return {
        colors: colors,
        fonts: fonts,
        mode: mode
    };

})();