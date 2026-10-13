globalThis.$ = globalThis.jQuery = require('jquery');

const {InlineCode} = require('../../inlinecode');
globalThis.InlineCode = InlineCode;

const {CommonDungeonrouteReport} = require('./report');

describe('CommonDungeonrouteReport._submitDungeonRouteUserReport', () => {
    let ajaxSpy;

    beforeEach(() => {
        // A stray username input: the submit must not pick it up
        document.body.innerHTML = `
            <div class="report_route_abc">
                <input class="dungeonroute_report_category" value="enemy">
                <input class="dungeonroute_report_username" value="Someone else">
                <textarea class="dungeonroute_report_message">This route is spam</textarea>
                <input type="checkbox" class="dungeonroute_report_contact_ok" checked>
            </div>
        `;

        ajaxSpy = vi.spyOn($, 'ajax').mockImplementation(() => {
        });
    });

    afterEach(() => {
        vi.clearAllMocks();
        document.body.innerHTML = '';
    });

    it('_submitDungeonRouteUserReport_givenForm_postsReportWithoutUsername', () => {
        // Arrange
        let report = new CommonDungeonrouteReport('report', 'common/dungeonroute/report', {
            selectorRoot: '.report_route_abc',
            publicKey: 'abc',
        });

        // Act
        report._submitDungeonRouteUserReport();

        // Assert
        expect(ajaxSpy).toHaveBeenCalledTimes(1);
        let options = ajaxSpy.mock.calls[0][0];
        expect(options.url).toBe('/ajax/userreport/dungeonroute/abc');
        expect(options.data).toEqual({category: 'enemy', message: 'This route is spam', contact_ok: 1});
        expect(options.data).not.toHaveProperty('username');
    });

    it.each([
        ['Undefined', undefined],
        ['Null', null],
        ['Empty', ''],
        ['UnresolvedAuto', 'auto'],
    ])('_submitDungeonRouteUserReport_given%sPublicKey_blocksSubmitAndShowsError', (name, publicKey) => {
        // Arrange
        globalThis.showErrorNotification = vi.fn();
        vi.spyOn(console, 'error').mockImplementation(() => {
        });
        let report = new CommonDungeonrouteReport('report', 'common/dungeonroute/report', {
            selectorRoot: '.report_route_abc',
            publicKey: publicKey,
        });

        // Act
        report._submitDungeonRouteUserReport();

        // Assert
        expect(ajaxSpy).not.toHaveBeenCalled();
        expect(globalThis.showErrorNotification).toHaveBeenCalledExactlyOnceWith('js.ajax_error_default');
    });

    it('_submitDungeonRouteUserReport_givenAutoKeyResolvedFromLinkWithoutPublicKey_blocksSubmit', () => {
        // Arrange
        globalThis.showErrorNotification = vi.fn();
        vi.spyOn(console, 'error').mockImplementation(() => {
        });
        document.body.insertAdjacentHTML('beforeend', '<div id="userreport_dungeonroute_modal"></div><a id="report_link"></a>');
        let report = new CommonDungeonrouteReport('report', 'common/dungeonroute/report', {
            selectorRoot: '.report_route_abc',
            publicKey: 'auto',
        });
        report.activate();
        $('#userreport_dungeonroute_modal').trigger($.Event('show.bs.modal', {relatedTarget: document.getElementById('report_link')}));

        // Act
        report._submitDungeonRouteUserReport();

        // Assert
        expect(ajaxSpy).not.toHaveBeenCalled();
    });

    it('_submitDungeonRouteUserReport_givenAutoKeyResolvedFromLink_postsToThatRoute', () => {
        // Arrange
        document.body.insertAdjacentHTML('beforeend', '<div id="userreport_dungeonroute_modal"></div><a id="report_link" data-publickey="xyz"></a>');
        let report = new CommonDungeonrouteReport('report', 'common/dungeonroute/report', {
            selectorRoot: '.report_route_abc',
            publicKey: 'auto',
        });
        report.activate();
        $('#userreport_dungeonroute_modal').trigger($.Event('show.bs.modal', {relatedTarget: document.getElementById('report_link')}));

        // Act
        report._submitDungeonRouteUserReport();

        // Assert
        expect(ajaxSpy).toHaveBeenCalledTimes(1);
        expect(ajaxSpy.mock.calls[0][0].url).toBe('/ajax/userreport/dungeonroute/xyz');
    });
});
