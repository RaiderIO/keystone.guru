globalThis.$ = globalThis.jQuery = require('jquery');

const {InlineCode} = require('../../inlinecode');
globalThis.InlineCode = InlineCode;

globalThis.METRIC_CATEGORY_DUNGEON_ROUTE_MDT_COPY = 'dungeon_route_mdt_copy';
globalThis.METRIC_TAG_MDT_COPY_EMBED = 'embed';

const {CommonMapsEmbedmessageapi} = require('./embedmessageapi');

describe('CommonMapsEmbedmessageapi', () => {
    let mdtExportUrl;
    let sendMetricForDungeonRoute;
    let heatmapSearchSidebar;

    beforeEach(() => {
        mdtExportUrl = 'http://localhost:8008/ajax/abc123/mdtExport?signature=sig';
        sendMetricForDungeonRoute = vi.fn();

        globalThis.getState = vi.fn(() => ({
            getMapContext: () => ({getMdtExportUrl: () => mdtExportUrl}),
            sendMetricForDungeonRoute: sendMetricForDungeonRoute,
        }));
        heatmapSearchSidebar = new (class extends InlineCode {
            searchWithFilters = vi.fn();
        })('sidebar', 'common/maps/heatmapsearchsidebar', {});
        globalThis._inlineManager = {
            getInlineCode: vi.fn((bladePath) => bladePath === 'common/maps/heatmapsearchsidebar' ? heatmapSearchSidebar : []),
        };
        vi.spyOn(console, 'error').mockImplementation(() => {
        });
        vi.spyOn(console, 'log').mockImplementation(() => {
        });
        vi.spyOn(console, 'warn').mockImplementation(() => {
        });
    });

    afterEach(() => {
        vi.restoreAllMocks();
    });

    /**
     * Activates the API and returns the message listener it registered on the window.
     * @param {Object} options
     * @returns {Function}
     */
    function activateAndCaptureMessageListener(options = {mdtStringCopyEnabled: true}) {
        const addEventListener = vi.spyOn(window, 'addEventListener').mockImplementation(() => {
        });

        new CommonMapsEmbedmessageapi('id', 'common/maps/embedmessageapi', options).activate();

        const call = addEventListener.mock.calls.find(([type]) => type === 'message');
        return call[1];
    }

    /**
     * @param {Object} data
     * @param {string} origin
     * @returns {{origin: string, data: Object, source: {postMessage: Function}}}
     */
    function buildMessageEvent(data, origin = 'https://raider.io') {
        return {origin: origin, data: data, source: {postMessage: vi.fn()}};
    }

    it('onMessage_givenGetMdtString_repliesWithMdtStringAndRequestId', () => {
        // Arrange
        const ajaxSpy = vi.spyOn($, 'ajax').mockImplementation((options) => {
            options.success({mdt_string: 'dGhlIG1kdCBzdHJpbmc='});
        });
        const listener = activateAndCaptureMessageListener();
        const event = buildMessageEvent({function: 'getMdtString', requestId: 'req-42'});

        // Act
        const result = listener(event);

        // Assert
        expect(result).toBe(true);
        expect(ajaxSpy).toHaveBeenCalledWith(expect.objectContaining({type: 'GET', url: mdtExportUrl}));
        expect(event.source.postMessage).toHaveBeenCalledWith(
            {function: 'mdtString', requestId: 'req-42', mdtString: 'dGhlIG1kdCBzdHJpbmc='},
            'https://raider.io'
        );
        expect(sendMetricForDungeonRoute).toHaveBeenCalledWith('dungeon_route_mdt_copy', 'embed');
    });

    it('onMessage_givenSubdomainOfValidHostname_replies', () => {
        // Arrange
        vi.spyOn($, 'ajax').mockImplementation((options) => {
            options.success({mdt_string: 'abc'});
        });
        const listener = activateAndCaptureMessageListener();
        const event = buildMessageEvent({function: 'getMdtString', requestId: 1}, 'https://staging.raiderio.dev');

        // Act
        listener(event);

        // Assert
        expect(event.source.postMessage).toHaveBeenCalledWith(
            {function: 'mdtString', requestId: 1, mdtString: 'abc'},
            'https://staging.raiderio.dev'
        );
    });

    it('onMessage_givenExportRequestFails_repliesWithNullAndError', () => {
        // Arrange
        vi.spyOn($, 'ajax').mockImplementation((options) => {
            options.error({status: 500});
        });
        const listener = activateAndCaptureMessageListener();
        const event = buildMessageEvent({function: 'getMdtString', requestId: 'req-1'});

        // Act
        listener(event);

        // Assert
        expect(event.source.postMessage).toHaveBeenCalledWith(
            {function: 'mdtString', requestId: 'req-1', mdtString: null, error: 'MDT export failed'},
            'https://raider.io'
        );
    });

    it('onMessage_givenExpiredExportUrl_repliesWithExpiredError', () => {
        // Arrange
        vi.spyOn($, 'ajax').mockImplementation((options) => {
            options.error({status: 403});
        });
        const listener = activateAndCaptureMessageListener();
        const event = buildMessageEvent({function: 'getMdtString', requestId: 'req-1'});

        // Act
        listener(event);

        // Assert
        expect(event.source.postMessage).toHaveBeenCalledWith(
            {function: 'mdtString', requestId: 'req-1', mdtString: null, error: 'MDT export url expired'},
            'https://raider.io'
        );
    });

    it('onMessage_givenMdtStringCopyDisabled_repliesWithErrorWithoutRequest', () => {
        // Arrange
        const ajaxSpy = vi.spyOn($, 'ajax').mockImplementation(() => {
        });
        const listener = activateAndCaptureMessageListener({mdtStringCopyEnabled: false});
        const event = buildMessageEvent({function: 'getMdtString', requestId: 'req-1'});

        // Act
        const result = listener(event);

        // Assert
        expect(result).toBe(true);
        expect(ajaxSpy).not.toHaveBeenCalled();
        expect(event.source.postMessage).toHaveBeenCalledWith(
            {function: 'mdtString', requestId: 'req-1', mdtString: null, error: 'MDT export is not available for this route'},
            'https://raider.io'
        );
    });

    it('onMessage_givenNoExportUrlInMapContext_repliesWithErrorWithoutRequest', () => {
        // Arrange
        mdtExportUrl = undefined;
        const ajaxSpy = vi.spyOn($, 'ajax').mockImplementation(() => {
        });
        const listener = activateAndCaptureMessageListener();
        const event = buildMessageEvent({function: 'getMdtString', requestId: 'req-1'});

        // Act
        listener(event);

        // Assert
        expect(ajaxSpy).not.toHaveBeenCalled();
        expect(event.source.postMessage).toHaveBeenCalledWith(
            {function: 'mdtString', requestId: 'req-1', mdtString: null, error: 'MDT export is not available for this route'},
            'https://raider.io'
        );
    });

    it.each([
        ['https://evil.example'],
        ['https://raider.io.evil.example'],
        ['https://notraider.io'],
        ['null'],
    ])('onMessage_givenUntrustedOrigin_%s_ignoresMessage', (origin) => {
        // Arrange
        const ajaxSpy = vi.spyOn($, 'ajax').mockImplementation((options) => {
            options.success({mdt_string: 'abc'});
        });
        const listener = activateAndCaptureMessageListener();
        const event = buildMessageEvent({function: 'getMdtString', requestId: 'req-1'}, origin);

        // Act
        const result = listener(event);

        // Assert
        expect(result).toBe(false);
        expect(ajaxSpy).not.toHaveBeenCalled();
        expect(event.source.postMessage).not.toHaveBeenCalled();
    });

    it('onMessage_givenSetFilters_appliesFiltersToHeatmapSearchSidebarWithoutReply', () => {
        // Arrange
        const listener = activateAndCaptureMessageListener();
        const event = buildMessageEvent({
            function: 'setFilters',
            requestId: 'req-1',
            type: 'player_spell',
            includeSpecIds: '62,63',
        });

        // Act
        const result = listener(event);

        // Assert
        expect(result).toBe(true);
        expect(heatmapSearchSidebar.searchWithFilters).toHaveBeenCalledWith({type: 'player_spell', includeSpecIds: '62,63'});
        expect(event.data.function).toBe('setFilters');
        expect(event.source.postMessage).not.toHaveBeenCalled();
    });

    it('onMessage_givenSetFiltersWithoutHeatmapSearchSidebar_logsErrorWithoutThrowingOrReplying', () => {
        // Arrange
        _inlineManager.getInlineCode.mockReturnValue([]);
        const listener = activateAndCaptureMessageListener();
        const event = buildMessageEvent({function: 'setFilters', type: 'player_spell'});
        let result;

        // Act
        const act = () => {
            result = listener(event);
        };

        // Assert
        expect(act).not.toThrow();
        expect(result).toBe(true);
        expect(_inlineManager.getInlineCode).toHaveBeenCalledWith('common/maps/heatmapsearchsidebar');
        expect(heatmapSearchSidebar.searchWithFilters).not.toHaveBeenCalled();
        expect(console.error).toHaveBeenCalledWith('Unable to find sidebar!');
        expect(event.source.postMessage).not.toHaveBeenCalled();
    });

    it('onMessage_givenSetFiltersFromUntrustedOrigin_ignoresMessage', () => {
        // Arrange
        const listener = activateAndCaptureMessageListener();
        const event = buildMessageEvent({function: 'setFilters', type: 'player_spell'}, 'https://evil.example');

        // Act
        const result = listener(event);

        // Assert
        expect(result).toBe(false);
        expect(heatmapSearchSidebar.searchWithFilters).not.toHaveBeenCalled();
    });

    it.each([
        ['doesNotExist'],
        ['constructor'],
        ['toString'],
    ])('onMessage_givenUnknownFunction_%s_repliesWithUnknownFunctionError', (functionName) => {
        // Arrange
        const ajaxSpy = vi.spyOn($, 'ajax').mockImplementation(() => {
        });
        const listener = activateAndCaptureMessageListener();
        const event = buildMessageEvent({function: functionName, requestId: 'req-1'});

        // Act
        const result = listener(event);

        // Assert
        expect(result).toBe(true);
        expect(ajaxSpy).not.toHaveBeenCalled();
        expect(heatmapSearchSidebar.searchWithFilters).not.toHaveBeenCalled();
        expect(event.source.postMessage).toHaveBeenCalledWith(
            {function: 'error', requestId: 'req-1', error: 'Unknown function'},
            'https://raider.io'
        );
    });

    it.each([
        [null],
        ['getMdtString'],
        [{requestId: 'req-1'}],
        [{function: 42, requestId: 'req-1'}],
    ])('onMessage_givenMessageWithoutFunctionName_%j_ignoresMessage', (data) => {
        // Arrange
        const listener = activateAndCaptureMessageListener();
        const event = buildMessageEvent(data);

        // Act
        const result = listener(event);

        // Assert
        expect(result).toBe(false);
        expect(event.source.postMessage).not.toHaveBeenCalled();
    });

    it('cleanup_givenActivated_removesMessageListener', () => {
        // Arrange
        vi.spyOn(window, 'addEventListener').mockImplementation(() => {
        });
        const removeEventListener = vi.spyOn(window, 'removeEventListener').mockImplementation(() => {
        });
        const messageApi = new CommonMapsEmbedmessageapi('id', 'common/maps/embedmessageapi', {mdtStringCopyEnabled: true});
        messageApi.activate();
        removeEventListener.mockClear();

        // Act
        messageApi.cleanup();

        // Assert
        expect(removeEventListener).toHaveBeenCalledWith('message', messageApi._onMessageListener);
    });
});
