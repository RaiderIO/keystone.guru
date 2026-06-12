/**
 * Base class for admin tools that execute a long-running operation as a series of
 * AJAX batch requests, with a progress bar, elapsed timer, start/pause/stop controls,
 * and an activity log.
 *
 * Subclasses must implement:
 *   - _start()          called when the Start button is clicked
 *
 * Subclasses may override:
 *   - _resume()         called when the Resume button is clicked (default: restart timer + re-enter running state)
 *   - _complete()       called when the last batch finishes
 *   - _computeEtaSeconds(elapsedMs)  return remaining seconds for ETA display, or null to show '–'
 *
 * Required options (all subclasses must provide these selectors):
 *   progressBarSelector, progressLabelSelector, logSelector,
 *   startBtnSelector, pauseBtnSelector, stopBtnSelector, timerSelector
 *
 * Optional options:
 *   resumeBtnSelector   — if provided, a Resume button is wired up
 *   etaSelector         — if provided, ETA is displayed next to the timer
 *   remainingCountSelector — if provided, updated by the subclass via _setRemainingCount()
 *
 * @property {Object} options
 */
class InlineCodeAjaxBatchProcessor extends InlineCode {

    activate() {
        /** @type {'idle'|'running'|'paused'|'stopped'|'completed'} */
        this._state         = 'idle';
        this._accumulatedMs = 0;
        this._segmentStart  = null;
        this._timerInterval = null;

        let self = this;
        $(this.options.startBtnSelector).on('click', function () { self._start(); });
        $(this.options.pauseBtnSelector).on('click', function () { self._pause(); });
        $(this.options.stopBtnSelector).on('click', function () { self._stop(); });

        if (this.options.resumeBtnSelector) {
            $(this.options.resumeBtnSelector).on('click', function () { self._resume(); });
        }
    }

    // ── Template methods (subclasses implement/override) ──────────────────────

    /** Must be implemented by subclass — called when Start is clicked. */
    _start() {}

    /** Called when Resume is clicked. Subclass may override to continue work after this. */
    _resume() {
        this._state = 'running';
        this._startTimerSegment();
        this._setButtonState('running');
        this._appendLog('\nResuming...\n');
    }

    /** Called when all work is done. Subclass should call _pauseTimerSegment() and update UI. */
    _complete() {
        this._pauseTimerSegment();
        this._setProgress(100, '');
        this._setButtonState('completed');
    }

    /**
     * Returns the estimated remaining seconds, or null to show '–'.
     * Only called when options.etaSelector is provided.
     *
     * @param {number} elapsedMs   total elapsed milliseconds so far
     * @returns {number|null}
     */
    _computeEtaSeconds(elapsedMs) { // eslint-disable-line no-unused-vars
        return null;
    }

    // ── Shared control actions ─────────────────────────────────────────────────

    /** @protected */
    _pause() {
        this._state = 'paused';
        this._pauseTimerSegment();
        this._setButtonState('paused');
        this._appendLog('\nPaused — click Resume to continue.\n');
    }

    /** @protected */
    _stop() {
        this._state = 'stopped';
        this._pauseTimerSegment();
        this._setButtonState('stopped');
        this._appendLog('\nStopped.');
    }

    /**
     * Call from an AJAX error handler to automatically pause and surface the error.
     *
     * @param {string} message
     * @protected
     */
    _onError(message) {
        this._appendLog('\nError: ' + message + '\n');
        this._state = 'paused';
        this._pauseTimerSegment();
        this._setButtonState('paused');
    }

    // ── Button state ──────────────────────────────────────────────────────────

    /**
     * @param {'idle'|'running'|'paused'|'stopped'|'completed'} state
     * @protected
     */
    _setButtonState(state) {
        $(this.options.startBtnSelector).toggleClass('d-none', state !== 'idle');
        $(this.options.pauseBtnSelector).toggleClass('d-none', state !== 'running');
        $(this.options.stopBtnSelector).toggleClass('d-none', state === 'idle' || state === 'completed' || state === 'stopped');

        if (this.options.resumeBtnSelector) {
            $(this.options.resumeBtnSelector).toggleClass('d-none', state !== 'paused');
        }
    }

    // ── Timer ─────────────────────────────────────────────────────────────────

    /** @protected */
    _startTimerSegment() {
        this._segmentStart = Date.now();

        let self = this;
        this._timerInterval = setInterval(function () {
            self._updateTimerDisplay();
        }, 1000);
    }

    /** @protected */
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

        if (this.options.etaSelector) {
            let eta = this._computeEtaSeconds(this._accumulatedMs + currentMs);
            $(this.options.etaSelector).text(eta !== null ? ('~' + this._formatDuration(eta)) : '–');
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
        return [h, m, s].map(function (v) { return String(v).padStart(2, '0'); }).join(':');
    }

    // ── Progress & log ────────────────────────────────────────────────────────

    /**
     * @param {number} percent   0–100
     * @param {string} label
     * @protected
     */
    _setProgress(percent, label) {
        $(this.options.progressBarSelector)
            .css('width', percent + '%')
            .attr('aria-valuenow', percent);
        $(this.options.progressLabelSelector).text(label);
    }

    /**
     * Updates the remaining-count display if options.remainingCountSelector is set.
     *
     * @param {number} count
     * @protected
     */
    _setRemainingCount(count) {
        if (this.options.remainingCountSelector) {
            $(this.options.remainingCountSelector).text(count.toLocaleString());
        }
    }

    /**
     * Appends text to the log without reading back its content (avoids O(n²) DOM thrash).
     *
     * @param {string} text
     * @protected
     */
    _appendLog(text) {
        let el = $(this.options.logSelector)[0];
        el.appendChild(document.createTextNode(text));
        el.scrollTop = el.scrollHeight;
    }
}
