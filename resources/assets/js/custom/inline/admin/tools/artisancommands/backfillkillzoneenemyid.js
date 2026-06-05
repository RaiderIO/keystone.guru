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
class AdminToolsArtisancommandsBackfillkillzoneenemyid extends InlineCode {

    activate() {
        /** @type {'idle'|'running'|'paused'|'stopped'|'completed'} */
        this._state          = 'idle';
        this._chunks         = [];
        this._currentIndex   = 0;
        this._remainingCount = this.options.count;
        this._accumulatedMs  = 0;
        this._segmentStart   = null;
        this._timerInterval  = null;

        let self = this;

        $(this.options.startBtnSelector).on('click', function () {
            self._start();
        });
        $(this.options.pauseBtnSelector).on('click', function () {
            self._pause();
        });
        $(this.options.stopBtnSelector).on('click', function () {
            self._stop();
        });
        $(this.options.resumeBtnSelector).on('click', function () {
            self._resume();
        });
    }

    /** @private */
    _start() {
        this._chunks        = this._buildChunks();
        this._currentIndex  = 0;
        this._accumulatedMs = 0;
        this._state         = 'running';

        this._setButtonState('running');
        this._startTimerSegment();
        this._appendLog(`Starting backfill: ${this._chunks.length} chunks to process (IDs ${this.options.minId} – ${this.options.maxId}).\n`);
        this._runNext();
    }

    /** @private */
    _pause() {
        this._state = 'paused';
        this._pauseTimerSegment();
        this._setButtonState('paused');
        this._appendLog('\nPaused — click Resume to continue.\n');
    }

    /** @private */
    _stop() {
        this._state = 'stopped';
        this._pauseTimerSegment();
        this._setButtonState('stopped');
        this._appendLog('\nStopped.');
    }

    /** @private */
    _resume() {
        this._state = 'running';
        this._startTimerSegment();
        this._setButtonState('running');
        this._appendLog('\nResuming...\n');
        this._runNext();
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
        this._setProgress(percent, `${this._currentIndex} / ${this._chunks.length}`);

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
                self._appendLog(response.output);
                self._subtractProcessedFromOutput(response.output);
                self._currentIndex++;
                self._runNext();
            },
            error: function (xhr) {
                let msg = xhr.responseJSON ? (xhr.responseJSON.error || xhr.responseText) : xhr.responseText;
                self._appendLog(`\nError on chunk ${self._currentIndex + 1}: ${msg}\n`);
                // Automatically pause so the user can resume after fixing the problem
                self._state = 'paused';
                self._pauseTimerSegment();
                self._setButtonState('paused');
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
        let deleted = 0;

        let deletedMatch = output.match(/([\d,]+)\s+orphan rows.*were deleted/);
        if (deletedMatch) {
            deleted = parseInt(deletedMatch[1].replace(/,/g, ''), 10);
        }

        this._remainingCount = Math.max(0, this._remainingCount - deleted);
        $(this.options.remainingCountSelector).text(this._remainingCount.toLocaleString());
    }

    /** @private */
    _complete() {
        this._pauseTimerSegment();
        this._setProgress(100, `${this._chunks.length} / ${this._chunks.length}`);
        this._setButtonState('completed');
        this._appendLog('\nBackfill complete.');
    }

    /**
     * @param {'idle'|'running'|'paused'|'stopped'|'completed'} state
     * @private
     */
    _setButtonState(state) {
        $(this.options.startBtnSelector).toggleClass('d-none', state !== 'idle');
        $(this.options.pauseBtnSelector).toggleClass('d-none', state !== 'running');
        $(this.options.stopBtnSelector).toggleClass('d-none', state === 'idle' || state === 'completed' || state === 'stopped');
        $(this.options.resumeBtnSelector).toggleClass('d-none', state !== 'paused');
    }

    /** @private */
    _startTimerSegment() {
        this._segmentStart = Date.now();

        let self = this;
        this._timerInterval = setInterval(function () {
            self._updateTimerDisplay();
        }, 1000);
    }

    /** @private */
    _pauseTimerSegment() {
        if (this._segmentStart !== null) {
            this._accumulatedMs += Date.now() - this._segmentStart;
            this._segmentStart  = null;
        }

        clearInterval(this._timerInterval);
        this._timerInterval = null;
        this._updateTimerDisplay();
    }

    /** @private */
    _updateTimerDisplay() {
        let currentMs = this._segmentStart !== null ? (Date.now() - this._segmentStart) : 0;
        let elapsed   = Math.floor((this._accumulatedMs + currentMs) / 1000);
        $(this.options.timerSelector).text(this._formatDuration(elapsed));

        if (this._currentIndex > 0) {
            let msPerChunk  = (this._accumulatedMs + currentMs) / this._currentIndex;
            let remaining   = this._chunks.length - this._currentIndex;
            let etaSeconds  = Math.floor((msPerChunk * remaining) / 1000);
            $(this.options.etaSelector).text(`~${this._formatDuration(etaSeconds)}`);
        } else {
            $(this.options.etaSelector).text('–');
        }
    }

    /**
     * @param {number} totalSeconds
     * @returns {string}
     * @private
     */
    _formatDuration(totalSeconds) {
        let h = Math.floor(totalSeconds / 3600);
        let m = Math.floor((totalSeconds % 3600) / 60);
        let s = totalSeconds % 60;
        return [h, m, s].map(v => String(v).padStart(2, '0')).join(':');
    }

    /**
     * @param {number} percent
     * @param {string} label
     * @private
     */
    _setProgress(percent, label) {
        $(this.options.progressBarSelector)
            .css('width', `${percent}%`)
            .attr('aria-valuenow', percent);
        $(this.options.progressLabelSelector).text(label);
    }

    /**
     * Appends text without reading back the full log content, avoiding O(n²) DOM thrash.
     *
     * @param {string} text
     * @private
     */
    _appendLog(text) {
        let el = $(this.options.logSelector)[0];
        el.appendChild(document.createTextNode(text + '\n'));
        el.scrollTop = el.scrollHeight;
    }
}
