/**
 * @typedef {Object} AdminToolsDungeonrouteGeneratetestroutesOptions
 * @property {string} generateBatchUrl
 * @property {string} deleteBatchUrl
 * @property {number} maxCount
 * @property {string} targetSelector
 * @property {string} countSelector
 * @property {string} publishedStateSelector
 * @property {string} deleteAllBtnSelector
 * @property {string} generatedCountSelector
 * @property {string} progressBarSelector
 * @property {string} progressLabelSelector
 * @property {string} logSelector
 * @property {string} startBtnSelector
 * @property {string} pauseBtnSelector
 * @property {string} resumeBtnSelector
 * @property {string} stopBtnSelector
 * @property {string} timerSelector
 * @property {string} etaSelector
 * @property {string} remainingCountSelector
 */

/**
 * @property {AdminToolsDungeonrouteGeneratetestroutesOptions} options
 */
class AdminToolsDungeonrouteGeneratetestroutes extends InlineCodeAjaxBatchProcessor {

    activate() {
        /** @type {'generate'|'delete'} */
        this._mode            = 'generate';
        this._runId           = 0;
        this._requestInFlight = false;
        this._dungeonIds      = [];
        this._currentIndex    = 0;
        this._params          = {};
        this._deleteTotal     = null;
        this._processedSoFar  = 0;

        super.activate();

        let self = this;
        $(this.options.deleteAllBtnSelector).on('click', function () {
            self._startDelete();
        });
    }

    _start() {
        let count = parseInt($(this.options.countSelector).val());
        if (isNaN(count) || count < 1 || count > this.options.maxCount) {
            this._appendLog(lang.get('js.admin_generate_test_routes_invalid_count', {max: this.options.maxCount}) + '\n');
            return;
        }

        this._mode         = 'generate';
        this._dungeonIds   = $(this.options.targetSelector).find('option:selected').data('dungeon-ids') || [];
        this._currentIndex = 0;
        this._params       = {
            count: count,
            published_state: $(this.options.publishedStateSelector).val(),
        };

        this._begin(lang.get('js.admin_generate_test_routes_generating', {count: count, dungeons: this._dungeonIds.length}));
        this._setRemainingCount(this._dungeonIds.length);
        this._runNext();
    }

    _startDelete() {
        if (this._state === 'running' || !confirm(lang.get('js.admin_generate_test_routes_delete_all_confirm'))) {
            return;
        }

        this._mode        = 'delete';
        this._deleteTotal = null;

        this._begin(lang.get('js.admin_generate_test_routes_deleting'));
        this._runNext();
    }

    _resume() {
        super._resume();
        this._runNext();
    }

    _complete() {
        this._state = 'completed';
        this._pauseTimerSegment();
        this._setProgress(100, lang.get('js.admin_generate_test_routes_processed', {count: this._processedSoFar}));
        this._setButtonState('completed');
        this._appendLog('\n' + lang.get('js.admin_generate_test_routes_done') + '\n');
    }

    _computeEtaSeconds(elapsedMs) {
        if (this._mode !== 'generate' || this._currentIndex <= 0) {
            return null;
        }

        let msPerDungeon = elapsedMs / this._currentIndex;
        return Math.floor((msPerDungeon * (this._dungeonIds.length - this._currentIndex)) / 1000);
    }

    /**
     * The page is used for more than one run, so a finished or stopped run hands the controls back.
     *
     * @param {'idle'|'running'|'paused'|'stopped'|'completed'} state
     * @protected
     */
    _setButtonState(state) {
        let buttonState = (state === 'completed' || state === 'stopped') ? 'idle' : state;

        super._setButtonState(buttonState);
        $(this.options.deleteAllBtnSelector).toggleClass('d-none', buttonState !== 'idle');
    }

    /**
     * @param {string} message
     * @private
     */
    _begin(message) {
        this._runId++;
        this._state          = 'running';
        this._accumulatedMs  = 0;
        this._processedSoFar = 0;

        this._setButtonState('running');
        this._setProgress(0, '');
        this._startTimerSegment();
        this._appendLog(message + '\n');
    }

    /**
     * Sends the next request of the current run; a request still in flight (after a quick pause/resume or
     * stop/start) calls this again once it returns.
     *
     * @private
     */
    _runNext() {
        if (this._state !== 'running' || this._requestInFlight) {
            return;
        }

        if (this._mode === 'delete') {
            this._deleteNext();
        } else {
            this._generateNext();
        }
    }

    /**
     * @param {Object} ajaxOptions
     * @param {function(Object)} onSuccess
     * @param {function(Object)} onError
     * @private
     */
    _request(ajaxOptions, onSuccess, onError) {
        let self  = this;
        let runId = this._runId;

        this._requestInFlight = true;
        $.ajax($.extend({type: 'POST', dataType: 'json'}, ajaxOptions))
            .done(function (response) {
                self._requestInFlight = false;
                if (runId === self._runId) {
                    onSuccess(response);
                } else {
                    self._runNext();
                }
            })
            .fail(function (xhr) {
                self._requestInFlight = false;
                if (runId === self._runId) {
                    onError(xhr);
                } else {
                    self._runNext();
                }
            });
    }

    /** @private */
    _generateNext() {
        if (this._currentIndex >= this._dungeonIds.length) {
            this._complete();
            return;
        }

        let self  = this;
        let total = this._dungeonIds.length;
        this._setProgress(Math.round((this._currentIndex / total) * 100), this._currentIndex + ' / ' + total);

        this._request({
            url: this.options.generateBatchUrl,
            data: $.extend({dungeon_id: this._dungeonIds[this._currentIndex]}, this._params),
        }, function (response) {
            self._processedSoFar += response.processed;
            self._appendLog(lang.get('js.admin_generate_test_routes_dungeon_done', {
                dungeon: response.dungeon,
                count: response.processed
            }) + '\n');
            response.routes.forEach(function (route) {
                self._appendRoute(route, response.enemy_forces_required);
            });
            self._setGeneratedCount(response.generated_count);
            self._advanceGeneration();
        }, function (xhr) {
            // A dungeon the generator cannot handle (no mapping or no enemies) should not stop the rest of a season
            if (xhr.status === 422) {
                self._appendLog(lang.get('js.admin_generate_test_routes_error', {message: self._errorMessage(xhr)}) + '\n');
                self._advanceGeneration();
            } else {
                self._onError(self._errorMessage(xhr));
            }
        });
    }

    /** @private */
    _advanceGeneration() {
        this._currentIndex++;
        this._setRemainingCount(this._dungeonIds.length - this._currentIndex);
        this._runNext();
    }

    /** @private */
    _deleteNext() {
        let self = this;

        this._request({
            url: this.options.deleteBatchUrl,
        }, function (response) {
            if (self._deleteTotal === null) {
                self._deleteTotal = response.processed + response.remaining;
            }
            self._processedSoFar += response.processed;

            self._setProgress(
                self._deleteTotal > 0 ? Math.round((self._processedSoFar / self._deleteTotal) * 100) : 100,
                self._processedSoFar + ' / ' + self._deleteTotal
            );
            self._setRemainingCount(response.remaining);
            self._setGeneratedCount(response.remaining);
            self._appendLog(lang.get('js.admin_generate_test_routes_deleted', {
                processed: response.processed,
                remaining: response.remaining
            }) + '\n');

            if (response.processed > 0 && response.remaining > 0) {
                self._runNext();
            } else {
                self._complete();
            }
        }, function (xhr) {
            self._onError(self._errorMessage(xhr));
        });
    }

    /**
     * @param {{public_key: string, title: string, url: string, enemy_forces: number}} route
     * @param {number} enemyForcesRequired
     * @private
     */
    _appendRoute(route, enemyForcesRequired) {
        let el = $(this.options.logSelector)[0];
        el.appendChild(document.createTextNode('  '));
        el.appendChild($('<a>', {href: route.url, target: '_blank', class: 'text-info'}).text(route.public_key)[0]);
        this._appendLog(' ' + route.title + ' - ' + route.enemy_forces + '/' + enemyForcesRequired + '\n');
    }

    /**
     * @param {number} count
     * @private
     */
    _setGeneratedCount(count) {
        $(this.options.generatedCountSelector).text(count.toLocaleString());
    }

    /**
     * @param {Object} xhr
     * @returns {string}
     * @private
     */
    _errorMessage(xhr) {
        return xhr.responseJSON ? (xhr.responseJSON.message || xhr.responseText) : xhr.responseText;
    }
}
