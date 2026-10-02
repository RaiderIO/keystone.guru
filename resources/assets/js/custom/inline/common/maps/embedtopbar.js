/**
 * @typedef {Object} CommonMapsEmbedtopbarOptions
 * @property {string} switchDungeonFloorSelect
 * @property {number} defaultSelectedFloorId
 * @property {boolean} mdtStringCopyEnabled
 */

/**
 * @property {CommonMapsEmbedtopbarOptions} options
 */
class CommonMapsEmbedtopbar extends InlineCode {

    constructor(id, bladePath, options) {
        super(id, bladePath, options);

        this.sidebar = new SidebarNavigation(options);
        this.validMessageHostnames = [
            'localhost',
            'keystone.guru',
            'raider.io',
            'raiderio.dev',
        ];
        this._onMessageListener = this._onMessage.bind(this);
    }

    activate() {
        super.activate();

        this.sidebar.activate();

        $('#embed_copy_mdt_string').unbind('click').bind('click', this._fetchMdtExportStringAndCopy.bind(this));

        window.removeEventListener('message', this._onMessageListener);
        window.addEventListener('message', this._onMessageListener);

        refreshTooltips();
    }

    cleanup() {
        super.cleanup();

        this.sidebar.cleanup();

        window.removeEventListener('message', this._onMessageListener);
    }

    /**
     * Answers { function: 'getMdtString', requestId } from the embedding page with
     * { function: 'mdtString', requestId, mdtString }, plus an `error` when no string could be made.
     *
     * @param {MessageEvent} event
     * @returns {boolean}
     * @private
     */
    _onMessage(event) {
        if (event.data === null || typeof event.data !== 'object' || event.data.function !== 'getMdtString') {
            return false;
        }

        if (!this._isValidMessageOrigin(event.origin)) {
            console.warn('Invalid hostname - not processing message!');
            return false;
        }

        let requestId = event.data.requestId;
        let reply = function (mdtString, error = null) {
            let response = {function: 'mdtString', requestId: requestId, mdtString: mdtString};
            if (error !== null) {
                response.error = error;
            }

            event.source.postMessage(response, event.origin);
        };

        let mdtExportUrl = this._getMdtExportUrl();
        if (mdtExportUrl === null) {
            reply(null, 'MDT export is not available for this route');
            return true;
        }

        $.ajax({
            type: 'GET',
            url: mdtExportUrl,
            dataType: 'json',
            success: function (json) {
                reply(json.mdt_string);

                getState().sendMetricForDungeonRoute(METRIC_CATEGORY_DUNGEON_ROUTE_MDT_COPY, METRIC_TAG_MDT_COPY_EMBED);
            },
            error: function (xhr) {
                reply(null, xhr.status === 403 ? 'MDT export url expired' : 'MDT export failed');
            },
        });

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
     * @returns {string|null}
     * @private
     */
    _getMdtExportUrl() {
        if (!this.options.mdtStringCopyEnabled) {
            console.log(`Not exporting MDT string - not enabled for dungeon`);
            return null;
        }

        // The explore and heatmap embeds share this top bar with a map context that has no dungeon
        // route, hence no signed export url - they pass mdtStringCopyEnabled false, but don't rely on it
        let mdtExportUrl = getState().getMapContext().getMdtExportUrl?.();
        if (!mdtExportUrl) {
            console.log(`Not exporting MDT string - no export url in the map context`);
            return null;
        }

        return mdtExportUrl;
    }

    /**
     * @private
     */
    _fetchMdtExportStringAndCopy() {
        let mdtExportUrl = this._getMdtExportUrl();
        if (mdtExportUrl === null) {
            return;
        }

        $.ajax({
            type: 'GET',
            url: mdtExportUrl,
            dataType: 'json',
            beforeSend: function () {
                $('#embed_copy_mdt_string_loader').show();
                $('#embed_copy_mdt_string').hide();
            },
            success: function (json) {
                copyToClipboard(json.mdt_string, null, 2000);

                getState().sendMetricForDungeonRoute(METRIC_CATEGORY_DUNGEON_ROUTE_MDT_COPY, METRIC_TAG_MDT_COPY_EMBED);
            },
            error: mdtExportAjaxErrorFn,
            complete: function () {
                $('#embed_copy_mdt_string_loader').hide();
                $('#embed_copy_mdt_string').show();
            }
        });
    }
}

// Guarded export for the test runner (Vitest). This is a no-op in the browser,
// where `module` is undefined, so it does not affect the concatenated bundle.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {CommonMapsEmbedtopbar};
}
