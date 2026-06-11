/**
 * @typedef {Object} AdminToolsCombatlogRundataOptions
 * @property {string} pruneBatchUrl
 * @property {string} seasonsFormSelector
 * @property {string} progressBarSelector
 * @property {string} progressLabelSelector
 * @property {string} logSelector
 * @property {string} startBtnSelector
 * @property {string} pauseBtnSelector
 * @property {string} stopBtnSelector
 * @property {string} timerSelector
 * @property {string} remainingCountSelector
 */

/**
 * @property {AdminToolsCombatlogRundataOptions} options
 */
class AdminToolsCombatlogRundata extends InlineCodeAjaxBatchProcessor {

    activate() {
        this._totalRows   = 0;
        this._prunedSoFar = 0;

        super.activate();
    }

    _start() {
        let seasons = this._selectedSeasons();

        if (seasons.length === 0) {
            this._appendLog('No seasons selected to keep — check at least one season before pruning.\n');
            return;
        }

        this._state       = 'running';
        this._prunedSoFar = 0;
        this._totalRows   = 0;
        this._setButtonState('running');
        this._startTimerSegment();

        this._appendLog('Starting prune. Keeping: ' + seasons.join(', ') + '.\n');
        this._runBatch(seasons);
    }

    _resume() {
        let seasons = this._selectedSeasons();
        super._resume();
        this._runBatch(seasons);
    }

    _complete() {
        this._pauseTimerSegment();
        this._setProgress(100, this._prunedSoFar.toLocaleString() + ' pruned');
        this._setButtonState('completed');
        this._appendLog('\nDone — all selected seasons pruned.');
    }

    /** @returns {string[]} */
    _selectedSeasons() {
        return $(this.options.seasonsFormSelector).map(function () {
            return $(this).val();
        }).get();
    }

    /**
     * @param {string[]} seasons
     * @private
     */
    _runBatch(seasons) {
        if (this._state !== 'running') {
            return;
        }

        let self = this;

        $.ajax({
            type: 'POST',
            url: this.options.pruneBatchUrl,
            dataType: 'json',
            data: {seasons: seasons},
            success: function (response) {
                self._prunedSoFar += response.pruned;

                let remaining = response.remaining;

                // Learn the total from the first batch response.
                if (self._totalRows === 0) {
                    self._totalRows = self._prunedSoFar + remaining;
                }

                let processed = self._totalRows - remaining;
                let percent   = self._totalRows > 0
                    ? Math.min(100, Math.round((processed / self._totalRows) * 100))
                    : 100;

                self._setProgress(percent, self._prunedSoFar.toLocaleString() + ' pruned');
                self._setRemainingCount(remaining);
                self._appendLog('Pruned ' + response.pruned + ' rows — ' + remaining.toLocaleString() + ' remaining.\n');

                if (remaining === 0) {
                    self._complete();
                } else {
                    self._runBatch(seasons);
                }
            },
            error: function (xhr) {
                let msg = xhr.responseJSON ? (xhr.responseJSON.message || xhr.responseText) : xhr.responseText;
                self._onError(msg);
            }
        });
    }
}
