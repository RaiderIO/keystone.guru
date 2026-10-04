globalThis.$ = globalThis.jQuery = require('jquery');

const {InlineCode} = require('../../inlinecode');
globalThis.InlineCode = InlineCode;

globalThis.METRIC_CATEGORY_DUNGEON_ROUTE_MDT_COPY = 1;
globalThis.METRIC_TAG_MDT_COPY_VIEW = 'view';

const {CommonMapsCopymdtstring} = require('./copymdtstring');

describe('CommonMapsCopymdtstring', () => {
    const cachedUrl = '/ajax/abc123/mdtExport?useCache=1&signature=cached';
    const uncachedUrl = '/ajax/abc123/mdtExport?useCache=0&signature=uncached';

    let sendMetricForDungeonRoute;
    let clipboardWrite;
    let clipboardWriteText;
    let modalShow;
    let $button;

    beforeEach(() => {
        sendMetricForDungeonRoute = vi.fn();
        globalThis.getState = vi.fn(() => ({
            getMapContext: () => ({
                getMdtExportUrl: () => cachedUrl,
                getMdtExportUrlUncached: () => uncachedUrl,
            }),
            sendMetricForDungeonRoute: sendMetricForDungeonRoute,
        }));
        globalThis.lang = {get: vi.fn((key) => key)};
        globalThis.showSuccessNotification = vi.fn();
        globalThis.showWarningNotification = vi.fn();
        globalThis.mdtExportAjaxErrorFn = vi.fn();

        modalShow = vi.fn();
        globalThis.bootstrap = {Modal: {getOrCreateInstance: vi.fn(() => ({show: modalShow}))}};

        clipboardWrite = vi.fn(async (items) => {
            // Resolve the pending blob the way a browser does, so a failed export rejects the write
            await items[0].data['text/plain'];
        });
        clipboardWriteText = vi.fn(async () => {
        });
        Object.defineProperty(globalThis.navigator, 'clipboard', {
            value: {write: clipboardWrite, writeText: clipboardWriteText},
            configurable: true,
        });
        globalThis.ClipboardItem = class {
            constructor(data) {
                this.data = data;
            }
        };

        document.body.innerHTML = `
            <div id="share_modal"></div>
            <button class="copy_mdt_string_button">
                <i class="far fa-copy copy_mdt_string_icon"></i>
                <span class="copy_mdt_string_label">Copy MDT string</span>
            </button>`;
        $button = $('.copy_mdt_string_button');
    });

    afterEach(() => {
        vi.restoreAllMocks();
        vi.useRealTimers();
        delete globalThis.ClipboardItem;
    });

    /**
     * @param {boolean} edit
     * @returns {CommonMapsCopymdtstring}
     */
    function createCopier(edit = false) {
        return new CommonMapsCopymdtstring('id', 'common/maps/copymdtstring', {
            buttonSelector: '.copy_mdt_string_button',
            shareModalSelector: '#share_modal',
            edit: edit,
        });
    }

    /**
     * @param {Object} json
     * @returns {import('vitest').MockInstance}
     */
    function mockExportSuccess(json = {mdt_string: 'dGhlIG1kdCBzdHJpbmc=', warnings: []}) {
        return vi.spyOn($, 'ajax').mockImplementation((options) => {
            options.success(json);
        });
    }

    it('copy_givenClipboardItemSupport_writesTheMdtStringAndSendsTheCopyMetric', async () => {
        // Arrange
        const ajaxSpy = mockExportSuccess();
        const copier = createCopier();

        // Act
        const result = await copier.copy($button);

        // Assert
        expect(result).toBe(true);
        expect(ajaxSpy).toHaveBeenCalledWith(expect.objectContaining({type: 'GET', url: cachedUrl}));
        expect(clipboardWrite).toHaveBeenCalledTimes(1);
        const blob = await clipboardWrite.mock.calls[0][0][0].data['text/plain'];
        expect(await blob.text()).toBe('dGhlIG1kdCBzdHJpbmc=');
        expect(showSuccessNotification).toHaveBeenCalledWith('js.mdt_string_copied');
        expect(sendMetricForDungeonRoute).toHaveBeenCalledWith(1, 'view');
        expect($button.find('.copy_mdt_string_label').text()).toBe('js.copy_mdt_string_copied');
    });

    it('copy_givenNoClipboardItemSupport_fallsBackToWriteText', async () => {
        // Arrange
        delete globalThis.ClipboardItem;
        mockExportSuccess();
        const copier = createCopier();

        // Act
        const result = await copier.copy($button);

        // Assert
        expect(result).toBe(true);
        expect(clipboardWrite).not.toHaveBeenCalled();
        expect(clipboardWriteText).toHaveBeenCalledWith('dGhlIG1kdCBzdHJpbmc=');
        expect(sendMetricForDungeonRoute).toHaveBeenCalledTimes(1);
    });

    it('copy_givenExportWarnings_showsTheWarningInsteadOfTheSuccessNotification', async () => {
        // Arrange
        mockExportSuccess({mdt_string: 'abc', warnings: [{category: 'note', message: 'Could not export note'}]});
        const copier = createCopier();

        // Act
        const result = await copier.copy($button);

        // Assert
        expect(result).toBe(true);
        expect(showWarningNotification).toHaveBeenCalledWith('js.mdt_string_copied_with_warnings');
        expect(showSuccessNotification).not.toHaveBeenCalled();
    });

    it('copy_givenExportFails_reportsTheErrorResetsTheButtonAndSendsNoMetric', async () => {
        // Arrange
        vi.spyOn($, 'ajax').mockImplementation((options) => {
            options.error({status: 403}, 'error', 'Forbidden');
        });
        const copier = createCopier();

        // Act
        const result = await copier.copy($button);

        // Assert
        expect(result).toBe(false);
        expect(mdtExportAjaxErrorFn).toHaveBeenCalledTimes(1);
        expect(sendMetricForDungeonRoute).not.toHaveBeenCalled();
        expect(modalShow).not.toHaveBeenCalled();
        expect($button.hasClass('disabled')).toBe(false);
        expect($button.find('.copy_mdt_string_label').text()).toBe('Copy MDT string');
    });

    it('copy_givenEveryClipboardPathBlocked_opensTheShareModal', async () => {
        // Arrange
        mockExportSuccess();
        clipboardWrite.mockRejectedValue(new DOMException('Denied', 'NotAllowedError'));
        clipboardWriteText.mockRejectedValue(new DOMException('Denied', 'NotAllowedError'));
        document.execCommand = vi.fn(() => false);
        const copier = createCopier();

        // Act
        const result = await copier.copy($button);

        // Assert
        expect(result).toBe(false);
        expect(showWarningNotification).toHaveBeenCalledWith('js.mdt_string_copy_blocked');
        expect(modalShow).toHaveBeenCalledTimes(1);
        expect(sendMetricForDungeonRoute).not.toHaveBeenCalled();
    });

    it('copy_givenViewModeCopiedTwice_fetchesTheExportOnce', async () => {
        // Arrange
        const ajaxSpy = mockExportSuccess();
        const copier = createCopier(false);

        // Act
        await copier.copy($button);
        await copier.copy($button);

        // Assert
        expect(ajaxSpy).toHaveBeenCalledTimes(1);
        expect(sendMetricForDungeonRoute).toHaveBeenCalledTimes(2);
    });

    it('copy_givenEditModeCopiedTwice_fetchesTheUncachedExportEachTime', async () => {
        // Arrange
        const ajaxSpy = mockExportSuccess();
        const copier = createCopier(true);

        // Act
        await copier.copy($button);
        await copier.copy($button);

        // Assert
        expect(ajaxSpy).toHaveBeenCalledTimes(2);
        expect(ajaxSpy).toHaveBeenCalledWith(expect.objectContaining({url: uncachedUrl}));
    });

    it('activate_givenViewModePointerEnter_prefetchesTheExport', () => {
        // Arrange
        const ajaxSpy = mockExportSuccess();
        createCopier(false).activate();

        // Act
        $button.trigger('pointerenter');

        // Assert
        expect(ajaxSpy).toHaveBeenCalledTimes(1);
        expect(clipboardWrite).not.toHaveBeenCalled();
    });

    it('activate_givenEditModePointerEnter_doesNotPrefetch', () => {
        // Arrange
        const ajaxSpy = mockExportSuccess();
        createCopier(true).activate();

        // Act
        $button.trigger('pointerenter');

        // Assert
        expect(ajaxSpy).not.toHaveBeenCalled();
    });
});
