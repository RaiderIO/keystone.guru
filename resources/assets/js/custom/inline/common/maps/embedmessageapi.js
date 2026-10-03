/**
 * @typedef {Object} CommonMapsEmbedmessageapiOptions
 * @property {boolean} mdtStringCopyEnabled
 */

/**
 * The postMessage API through which the page embedding us drives the embed. Documented in
 * docs/developer/embed_heatmap.md.
 *
 * @property {CommonMapsEmbedmessageapiOptions} options
 */
class CommonMapsEmbedmessageapi extends InlineCode {

    constructor(id, bladePath, options) {
        super(id, bladePath, options);

        this.validMessageHostnames = [
            'localhost',
            'keystone.guru',
            'raider.io',
            'raiderio.dev',
        ];
        /** @type {Object<string, function(Object, function(string, Object))>} */
        this.handlers = {
            getMdtString: this._getMdtString.bind(this),
            setFilters: this._setFilters.bind(this),
        };
        this._onMessageListener = this._onMessage.bind(this);
    }

    activate() {
        super.activate();

        window.removeEventListener('message', this._onMessageListener);
        window.addEventListener('message', this._onMessageListener);
    }

    cleanup() {
        super.cleanup();

        window.removeEventListener('message', this._onMessageListener);
    }

    /**
     * Dispatches { function, requestId, ... } from the embedding page to its handler. A reply is
     * { function, requestId, ... } sent back to the embedding page's origin.
     *
     * @param {MessageEvent} event
     * @returns {boolean}
     * @private
     */
    _onMessage(event) {
        if (event.data === null || typeof event.data !== 'object' || typeof event.data.function !== 'string') {
            return false;
        }

        if (!this._isValidMessageOrigin(event.origin)) {
            console.warn('Invalid hostname - not processing message!');
            return false;
        }

        let requestId = event.data.requestId;
        let reply = function (functionName, data) {
            event.source.postMessage({function: functionName, requestId: requestId, ...data}, event.origin);
        };

        if (!this.handlers.hasOwnProperty(event.data.function)) {
            reply('error', {error: 'Unknown function'});
            return true;
        }

        this.handlers[event.data.function](event.data, reply);

        return true;
    }

    /**
     * @param {string} origin
     * @returns {boolean}
     * @private
     */
    _isValidMessageOrigin(origin) {
        let hostname;
        try {
            hostname = (new URL(origin)).hostname;
        } catch (e) {
            return false;
        }

        return this.validMessageHostnames.some(
            validHostname => hostname === validHostname || hostname.endsWith(`.${validHostname}`)
        );
    }

    /**
     * Replies { mdtString }, or { mdtString: null, error } when no string could be made.
     *
     * @param {Object} data
     * @param {function(string, Object)} reply
     * @private
     */
    _getMdtString(data, reply) {
        let replyError = function (error) {
            reply('mdtString', {mdtString: null, error: error});
        };

        // The explore and heatmap embeds have a map context without a dungeon route, hence no signed export url
        let mdtExportUrl = this.options.mdtStringCopyEnabled ? getState().getMapContext().getMdtExportUrl?.() : null;
        if (!mdtExportUrl) {
            replyError('MDT export is not available for this route');
            return;
        }

        $.ajax({
            type: 'GET',
            url: mdtExportUrl,
            dataType: 'json',
            success: function (json) {
                reply('mdtString', {mdtString: json.mdt_string});

                getState().sendMetricForDungeonRoute(METRIC_CATEGORY_DUNGEON_ROUTE_MDT_COPY, METRIC_TAG_MDT_COPY_EMBED);
            },
            error: function (xhr) {
                replyError(xhr.status === 403 ? 'MDT export url expired' : 'MDT export failed');
            },
        });
    }

    /**
     * Applies the filters to the heatmap search and searches. Does not reply.
     *
     * @param {Object} data
     * @private
     */
    _setFilters(data) {
        /** @type CommonMapsHeatmapsearchsidebar|InlineCode[] */
        let heatmapSearchSidebar = _inlineManager.getInlineCode('common/maps/heatmapsearchsidebar');
        if (!(heatmapSearchSidebar instanceof InlineCode)) {
            console.error('Unable to find sidebar!');
            return;
        }

        let {function: functionName, requestId, ...filters} = data;

        console.log('Applying filters', filters);
        heatmapSearchSidebar.searchWithFilters(filters);
    }
}

// Guarded export for the test runner (Vitest). This is a no-op in the browser,
// where `module` is undefined, so it does not affect the concatenated bundle.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {CommonMapsEmbedmessageapi};
}
