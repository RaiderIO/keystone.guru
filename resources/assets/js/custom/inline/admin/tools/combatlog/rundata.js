/**
 * @typedef {Object} AdminToolsCombatlogRundataOptions
 * @property {string} pruneBatchUrl
 * @property {string} seasonsFormSelector
 * @property {number} minId
 * @property {number} maxId
 * @property {number} chunkSize
 * @property {string} progressBarSelector
 * @property {string} progressLabelSelector
 * @property {string} logSelector
 * @property {string} startBtnSelector
 * @property {string} pauseBtnSelector
 * @property {string} resumeBtnSelector
 * @property {string} stopBtnSelector
 * @property {string} timerSelector
 * @property {string} remainingCountSelector
 */

/**
 * @property {AdminToolsCombatlogRundataOptions} options
 */
class AdminToolsCombatlogRundata extends InlineCodeAjaxBatchProcessor {

    activate() {
        this._chunks       = [];
        this._currentIndex = 0;
        this._prunedSoFar  = 0;

        super.activate();
    }

    _start() {
        let seasons = this._selectedSeasons();

        if (seasons.length === 0) {
            this._appendLog('No seasons selected to keep — check at least one season before pruning.\n');
            return;
        }

        this._chunks       = this._buildChunks();
        this._currentIndex = 0;
        this._prunedSoFar  = 0;
        this._state        = 'running';

        this._setButtonState('running');
        this._startTimerSegment();
        this._appendLog(
            'Starting prune. Keeping: ' + seasons.join(', ') + '.\n' +
            this._chunks.length + ' chunks to process (IDs ' + this.options.minId + ' – ' + this.options.maxId + ').\n'
        );
        this._runNext(seasons);
    }

    _resume() {
        super._resume();
        this._runNext(this._selectedSeasons());
    }

    _complete() {
        this._pauseTimerSegment();
        this._setProgress(100, this._chunks.length + ' / ' + this._chunks.length);
        this._setButtonState('completed');
        this._appendLog('\nDone — all selected seasons pruned. Total pruned: ' + this._prunedSoFar.toLocaleString() + ' rows.');
    }

    _computeEtaSeconds(elapsedMs) {
        if (this._currentIndex <= 0) {
            return null;
        }

        let msPerChunk = elapsedMs / this._currentIndex;
        let remaining  = this._chunks.length - this._currentIndex;
        return Math.floor((msPerChunk * remaining) / 1000);
    }

    /** @returns {string[]} */
    _selectedSeasons() {
        return $(this.options.seasonsFormSelector).map(function () {
            return $(this).val();
        }).get();
    }

    /**
     * @returns {Array<{min: number, max: number}>}
     * @private
     */
    _buildChunks() {
        let chunks = [];
        for (let start = this.options.maxId; start >= this.options.minId; start -= this.options.chunkSize) {
            chunks.push({
                min: Math.max(start - this.options.chunkSize + 1, this.options.minId),
                max: start
            });
        }
        return chunks;
    }

    /**
     * @param {string[]} seasons
     * @private
     */
    _runNext(seasons) {
        if (this._state !== 'running') {
            return;
        }

        if (this._currentIndex >= this._chunks.length) {
            this._complete();
            return;
        }

        let self    = this;
        let chunk   = this._chunks[this._currentIndex];
        let percent = Math.round((this._currentIndex / this._chunks.length) * 100);
        this._setProgress(percent, this._currentIndex + ' / ' + this._chunks.length);

        $.ajax({
            type: 'POST',
            url: this.options.pruneBatchUrl,
            dataType: 'json',
            data: {
                seasons: seasons,
                min_id:  chunk.min,
                max_id:  chunk.max,
            },
            success: function (response) {
                self._prunedSoFar += response.pruned;

                if (response.pruned > 0) {
                    self._appendLog('Chunk ' + (self._currentIndex + 1) + '/' + self._chunks.length + ': pruned ' + response.pruned + ' rows (IDs ' + chunk.min + '–' + chunk.max + ').\n');
                }

                self._currentIndex++;
                self._runNext(seasons);
            },
            error: function (xhr) {
                let msg = xhr.responseJSON ? (xhr.responseJSON.message || xhr.responseText) : xhr.responseText;
                self._onError('chunk ' + (self._currentIndex + 1) + ': ' + msg);
            }
        });
    }
}
