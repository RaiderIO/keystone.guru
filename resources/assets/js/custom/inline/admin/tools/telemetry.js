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
        super.activate();

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

        let data = {
            measurement: $canvas.data('measurement'),
            range: this.options.range
        };

        // A gauge chart holds every name of its measurement, so it asks for the measurement as a whole. The key
        // is left out rather than sent empty - jQuery serializes null to `name=`, which only survives because
        // ConvertEmptyStringsToNull happens to be in the middleware stack.
        let name = $canvas.data('name');
        if (name) {
            data.name = name;
        }

        $.ajax({
            type: 'GET',
            url: this.options.dataUrl,
            dataType: 'json',
            data: data,
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
     * Replaces a chart that has nothing to draw with the reason why, so an empty or failing card explains itself
     * instead of leaving a blank rectangle behind.
     *
     * @param {jQuery} $canvas
     * @param {string} message
     * @private
     */
    _replaceWithMessage($canvas, message) {
        $canvas.parent().html($('<div/>')
            .addClass('text-muted text-center h-100 d-flex align-items-center justify-content-center')
            .text(message));
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
                    x: {ticks: {maxTicksLimit: 8, autoSkip: true, maxRotation: 45}},
                    // Deliberately not beginAtZero: a gauge sitting at 512k barely moves on an axis
                    // anchored to zero, and the whole point of these graphs is the variation.
                    y: {title: {display: true, text: $canvas.data('axis-label')}}
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

        if (response.failureLabels.length > 0 && response.datasets.length > 0) {
            datasets.push(this._buildFailureDataset(response));
        }

        return datasets;
    }

    /**
     * Failed runs marked on the duration line itself: a failing command is exactly what someone opens this page
     * for, and its duration alone does not show that it failed.
     *
     * The marker takes the value of the run it belongs to rather than sitting on zero, so it stays inside the
     * y axis - which is not anchored to zero and often does not contain it at all.
     *
     * @param {Object} response
     * @returns {Object}
     * @private
     */
    _buildFailureDataset(response) {
        // A scheduler chart holds exactly one command, so its first dataset is that command's durations
        let averages      = response.datasets[0].average;
        let failureLabels = response.failureLabels;

        return {
            label: this.options.failuresLegend,
            // Array.map, not $.map - jQuery drops nulls from its result, which would slide every marker
            // left onto the wrong bucket.
            data: response.labels.map(function (label, index) {
                return failureLabels.indexOf(label) === -1 ? null : averages[index];
            }),
            borderColor: '#d9534f',
            backgroundColor: '#d9534f',
            showLine: false,
            pointRadius: 5,
            pointStyle: 'triangle'
        };
    }
}
