/**
 * @typedef {Object} AdminToolsDungeonrouteGeneratetestroutesOptions
 * @property {string} generateUrl
 * @property {string} deleteBatchUrl
 * @property {number} maxCount
 * @property {string} targetSelector
 * @property {string} countSelector
 * @property {string} publishedStateSelector
 * @property {string} startBtnSelector
 * @property {string} deleteAllBtnSelector
 * @property {string} generatedCountSelector
 * @property {string} progressBarSelector
 * @property {string} logSelector
 * @property {Object.<string, string>} translations
 */

/**
 * @property {AdminToolsDungeonrouteGeneratetestroutesOptions} options
 */
class AdminToolsDungeonrouteGeneratetestroutes extends InlineCode {

    activate() {
        super.activate();

        this._running = false;

        $(this.options.startBtnSelector).on('click', this._generate.bind(this));
        $(this.options.deleteAllBtnSelector).on('click', this._deleteAll.bind(this));
    }

    _generate() {
        if (this._running) {
            return;
        }

        let count = parseInt($(this.options.countSelector).val());
        if (isNaN(count) || count < 1 || count > this.options.maxCount) {
            this._log(this.options.translations.invalidCount);
            return;
        }

        let dungeonIds     = $(this.options.targetSelector).find('option:selected').data('dungeon-ids') || [];
        let publishedState = $(this.options.publishedStateSelector).val();

        this._setRunning(true);
        this._setProgress(0);
        this._log(this._format(this.options.translations.generating, {count: count, dungeons: dungeonIds.length}));
        this._generateNext(dungeonIds, 0, count, publishedState);
    }

    /**
     * @param {number[]} dungeonIds
     * @param {number} index
     * @param {number} count
     * @param {string} publishedState
     * @private
     */
    _generateNext(dungeonIds, index, count, publishedState) {
        if (index >= dungeonIds.length) {
            this._finish();
            return;
        }

        let self = this;
        $.ajax({
            type: 'POST',
            url: this.options.generateUrl,
            dataType: 'json',
            data: {
                dungeon_id: dungeonIds[index],
                count: count,
                published_state: publishedState,
            },
            success: function (response) {
                self._log(self._format(self.options.translations.dungeonDone, {dungeon: response.dungeon, count: response.routes.length}));
                response.routes.forEach(function (route) {
                    self._logRoute(route, response.enemy_forces_required);
                });
                self._setGeneratedCount(response.generated_count);

                self._setProgress(((index + 1) / dungeonIds.length) * 100);
                self._generateNext(dungeonIds, index + 1, count, publishedState);
            },
            error: function (xhr) {
                self._onError(xhr);
            }
        });
    }

    _deleteAll() {
        if (this._running || !confirm(this.options.translations.deleteAllConfirm)) {
            return;
        }

        this._setRunning(true);
        this._setProgress(0);
        this._log(this.options.translations.deleting);
        this._deleteNext(null);
    }

    /**
     * @param {number|null} total
     * @private
     */
    _deleteNext(total) {
        let self = this;
        $.ajax({
            type: 'POST',
            url: this.options.deleteBatchUrl,
            dataType: 'json',
            success: function (response) {
                let knownTotal = total ?? (response.deleted + response.remaining);

                self._log(self._format(self.options.translations.deleted, response));
                self._setGeneratedCount(response.remaining);
                self._setProgress(knownTotal > 0 ? ((knownTotal - response.remaining) / knownTotal) * 100 : 100);

                if (response.deleted > 0 && response.remaining > 0) {
                    self._deleteNext(knownTotal);
                } else {
                    self._finish();
                }
            },
            error: function (xhr) {
                self._onError(xhr);
            }
        });
    }

    _finish() {
        this._setProgress(100);
        this._log(this.options.translations.done);
        this._setRunning(false);
    }

    /**
     * @param {Object} xhr
     * @private
     */
    _onError(xhr) {
        let message = xhr.responseJSON ? (xhr.responseJSON.message || xhr.responseText) : xhr.responseText;
        this._log(this._format(this.options.translations.error, {message: message}));
        this._setRunning(false);
    }

    /**
     * @param {number} count
     * @private
     */
    _setGeneratedCount(count) {
        $(this.options.generatedCountSelector).text(this._format(this.options.translations.generatedCount, {count: count}));
    }

    /**
     * @param {boolean} running
     * @private
     */
    _setRunning(running) {
        this._running = running;
        $(this.options.startBtnSelector).prop('disabled', running);
        $(this.options.deleteAllBtnSelector).prop('disabled', running);
    }

    /**
     * @param {number} percent
     * @private
     */
    _setProgress(percent) {
        $(this.options.progressBarSelector)
            .css('width', Math.round(percent) + '%')
            .attr('aria-valuenow', Math.round(percent));
    }

    /**
     * @param {{public_key: string, title: string, url: string, enemy_forces: number}} route
     * @param {number} enemyForcesRequired
     * @private
     */
    _logRoute(route, enemyForcesRequired) {
        let $line = $('<div>').text('  ');
        $line.append($('<a>', {href: route.url, target: '_blank', class: 'text-info'}).text(route.public_key));
        $line.append(document.createTextNode(' ' + route.title + ' - ' + route.enemy_forces + '/' + enemyForcesRequired));
        this._appendToLog($line);
    }

    /**
     * @param {string} text
     * @private
     */
    _log(text) {
        this._appendToLog($('<div>').text(text));
    }

    /**
     * @param {jQuery} $line
     * @private
     */
    _appendToLog($line) {
        let $log = $(this.options.logSelector);
        $log.append($line);
        $log.scrollTop($log[0].scrollHeight);
    }

    /**
     * Replaces Laravel-style :placeholders in a translated string.
     *
     * @param {string} template
     * @param {Object.<string, string|number>} values
     * @returns {string}
     * @private
     */
    _format(template, values) {
        return Object.keys(values).reduce(function (result, key) {
            return result.replace(':' + key, values[key]);
        }, template);
    }
}
