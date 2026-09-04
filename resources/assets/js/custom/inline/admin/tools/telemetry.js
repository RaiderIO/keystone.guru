/**
 * @typedef {Object} AdminToolsTelemetryOptions
 * @property {string} dataUrl
 * @property {string} range
 * @property {string} chartSelector
 * @property {string} averageLegendFormat
 * @property {string} maximumLegendFormat
 * @property {string} noDataText
 * @property {string} loadFailedText
 * @property {string} failuresLegend
 */

/**
 * @property {AdminToolsTelemetryOptions} options
 */
class AdminToolsTelemetry extends InlineCode {

    /**
     * Chart.js has no categorical palette of its own, so lines are coloured by their index in this list. Ten
     * colours is well past the widest measurement (users, at five names) - beyond that they start repeating.
     *
     * @type {string[]}
     */
    static COLORS = [
        '#4e9cd6', '#e8843c', '#5cb85c', '#d9534f', '#9b6bcc',
        '#c9a227', '#4bc0c0', '#e377c2', '#7f7f7f', '#17becf'
    ];

    activate() {
        let self = this;

        $(this.options.chartSelector).each(function () {
            self._loadChart($(this));
        });
    }

    /**
     * @param {jQuery} $canvas
     * @private
     */
    _loadChart($canvas) {
        let self = this;

        $.ajax({
            type: 'GET',
            url: this.options.dataUrl,
            dataType: 'json',
            data: {
                measurement: $canvas.data('measurement'),
                // A gauge chart holds every name of its measurement, so it asks for the measurement as a whole
                name: $canvas.data('name') || null,
                range: this.options.range
            },
            success: function (response) {
                if (response.labels.length === 0) {
                    self._replaceWithMessage($canvas, self.options.noDataText);
                    return;
                }

                self._renderChart($canvas, response);
            },
            error: function () {
                self._replaceWithMessage($canvas, self.options.loadFailedText);
            }
        });
    }

    /**
     * @param {jQuery} $canvas
     * @param {Object} response
     * @private
     */
    _renderChart($canvas, response) {
        new Chart($canvas.get(0).getContext('2d'), {
            type: 'line',
            data: {
                labels: response.labels,
                datasets: this._buildDatasets(response)
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {mode: 'index', intersect: false},
                plugins: {
                    legend: {position: 'bottom'}
                },
                scales: {
                    x: {ticks: {maxTicksLimit: 12, autoSkip: true}},
                    y: {
                        beginAtZero: true,
                        title: {display: true, text: $canvas.data('axis-label')}
                    }
                }
            }
        });
    }

    /**
     * @param {Object} response
     * @returns {Object[]}
     * @private
     */
    _buildDatasets(response) {
        let self     = this;
        let datasets = [];

        $.each(response.datasets, function (index, dataset) {
            let color = AdminToolsTelemetry.COLORS[index % AdminToolsTelemetry.COLORS.length];

            datasets.push({
                label: response.rollup ? self.options.averageLegendFormat.replace(':label', dataset.label) : dataset.label,
                data: dataset.average,
                borderColor: color,
                backgroundColor: color,
                borderWidth: 2,
                pointRadius: 0,
                spanGaps: false,
                tension: 0.2
            });

            // In a rolled up bucket the average is a summary of many samples - without the maximum next to it,
            // the spikes that make an operational graph worth looking at are averaged away entirely.
            if (response.rollup) {
                datasets.push({
                    label: self.options.maximumLegendFormat.replace(':label', dataset.label),
                    data: dataset.maximum,
                    borderColor: color,
                    backgroundColor: color,
                    borderWidth: 1,
                    borderDash: [4, 4],
                    pointRadius: 0,
                    spanGaps: false,
                    tension: 0.2
                });
            }
        });

        if (response.failureLabels.length > 0) {
            datasets.push(this._buildFailureDataset(response));
        }

        return datasets;
    }

    /**
     * Failed runs as red dots on the zero line: a failing command is exactly what someone opens this page for,
     * and its duration alone does not show that it failed.
     *
     * @param {Object} response
     * @returns {Object}
     * @private
     */
    _buildFailureDataset(response) {
        let failureLabels = response.failureLabels;

        return {
            label: this.options.failuresLegend,
            data: $.map(response.labels, function (label) {
                return failureLabels.indexOf(label) === -1 ? null : 0;
            }),
            borderColor: '#d9534f',
            backgroundColor: '#d9534f',
            showLine: false,
            pointRadius: 4,
            pointStyle: 'triangle'
        };
    }
}
