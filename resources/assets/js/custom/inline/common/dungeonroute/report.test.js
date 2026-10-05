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
});
