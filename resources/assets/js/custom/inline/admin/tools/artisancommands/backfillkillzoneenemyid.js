/**
 * @typedef {Object} AdminToolsArtisancommandsBackfillkillzoneenemyidOptions
 * @property {string}  runUrl
 * @property {string}  command
 * @property {number}  minId
 * @property {number}  maxId
 * @property {number}  count
 * @property {number}  chunkSize
 * @property {string}  progressBarSelector
 * @property {string}  progressLabelSelector
 * @property {string}  logSelector
 * @property {string}  startBtnSelector
 * @property {string}  pauseBtnSelector
 * @property {string}  stopBtnSelector
 * @property {string}  resumeBtnSelector
 * @property {string}  timerSelector
 * @property {string}  etaSelector
 * @property {string}  remainingCountSelector
 */

/**
 * @property {AdminToolsArtisancommandsBackfillkillzoneenemyidOptions} options
 */
class AdminToolsArtisancommandsBackfillkillzoneenemyid extends InlineCodeAjaxBatchProcessor {

    activate() {
        this._chunks         = [];
        this._currentIndex   = 0;
        this._remainingCount = this.options.count;

        super.activate();
    }

    _start() {
        this._chunks       = this._buildChunks();
        this._currentIndex = 0;
        this._state        = 'running';

        this._setButtonState('running');
        this._startTimerSegment();
        this._appendLog('Starting backfill: ' + this._chunks.length + ' chunks to process (IDs ' + this.options.minId + ' – ' + this.options.maxId + ').\n');
        this._runNext();
    }

    _resume() {
        super._resume();
        this._runNext();
    }

    _complete() {
        this._pauseTimerSegment();
        this._setProgress(100, this._chunks.length + ' / ' + this._chunks.length);
        this._setButtonState('completed');
        this._appendLog('\nBackfill complete.');
    }

    _computeEtaSeconds(elapsedMs) {
        if (this._currentIndex <= 0) {
            return null;
        }

        let msPerChunk = elapsedMs / this._currentIndex;
        let remaining  = this._chunks.length - this._currentIndex;
        return Math.floor((msPerChunk * remaining) / 1000);
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

    /** @private */
    _runNext() {
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
            url: this.options.runUrl,
            dataType: 'json',
            data: {
                command: this.options.command,
                options: {
                    '--min':   chunk.min,
                    '--max':   chunk.max,
                    '--chunk': this.options.chunkSize
                }
            },
            success: function (response) {
                self._appendLog(response.output + '\n');
                self._subtractProcessedFromOutput(response.output);
                self._currentIndex++;
                self._runNext();
            },
            error: function (xhr) {
                let msg = xhr.responseJSON ? (xhr.responseJSON.error || xhr.responseText) : xhr.responseText;
                self._onError('chunk ' + (self._currentIndex + 1) + ': ' + msg);
            }
        });
    }

    /**
     * Parses deleted row counts from the command output and decrements the live counter.
     *
     * @param {string} output
     * @private
     */
    _subtractProcessedFromOutput(output) {
        let deleted      = 0;
        let deletedMatch = output.match(/([\d,]+)\s+orphan rows.*were deleted/);
        if (deletedMatch) {
            deleted = parseInt(deletedMatch[1].replace(/,/g, ''), 10);
        }

        this._remainingCount = Math.max(0, this._remainingCount - deleted);
        this._setRemainingCount(this._remainingCount);
    }
}
