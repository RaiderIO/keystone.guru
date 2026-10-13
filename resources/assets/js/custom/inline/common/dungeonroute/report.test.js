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

/**
 * The report form of common/modal/userreport/dungeonroute.blade.php inside its modal, for a signed-in user.
 * @param {string} publicKey The route's public key, or 'auto' when the modal serves every route card on the page
 */
function buildReportModal(publicKey) {
    document.body.innerHTML = `
        <a id="report_link" href="#" data-bs-toggle="modal" data-bs-target="#userreport_dungeonroute_modal" data-publickey="xyz789"></a>
        <a id="report_link_without_key" href="#" data-bs-toggle="modal" data-bs-target="#userreport_dungeonroute_modal"></a>
        <div id="userreport_dungeonroute_modal">
            <div class="report_route_${publicKey}">
                <input type="hidden" class="form-control dungeonroute_report_category" value="enemy">
                <input type="hidden" class="form-control dungeonroute_report_enemy_id" value="-1">
                <textarea class="form-control dungeonroute_report_message">Broken route</textarea>
                <input type="checkbox" class="form-check-input dungeonroute_report_contact_ok" value="1">
                <button class="btn btn-info dungeonroute_report_submit">Submit</button>
                <button class="btn btn-info dungeonroute_report_saving disabled" style="display: none;"></button>
            </div>
        </div>
    `;
}

/**
 * @param {string} publicKey
 * @returns {CommonDungeonrouteReport}
 */
function activateReport(publicKey) {
    const report = new CommonDungeonrouteReport('report', 'common/dungeonroute/report', {
        selectorRoot: `.report_route_${publicKey}`,
        publicKey: publicKey,
    });
    report.activate();

    return report;
}

/**
 * Opens the modal the way Bootstrap does, naming the link that opened it.
 * @param {string} linkSelector
 */
function openModalFrom(linkSelector) {
    $('#userreport_dungeonroute_modal').trigger($.Event('show.bs.modal', {relatedTarget: document.querySelector(linkSelector)}));
}

describe('CommonDungeonrouteReport.activate', () => {
    let ajaxSpy;
    let modal;

    beforeEach(() => {
        ajaxSpy = vi.spyOn($, 'ajax').mockImplementation(() => {
        });
        modal = {hide: vi.fn()};
        globalThis.bootstrap = {Modal: {getOrCreateInstance: vi.fn(() => modal)}};
        globalThis.showSuccessNotification = vi.fn();
    });

    afterEach(() => {
        document.body.innerHTML = '';
    });

    it('activate_givenSubmitClicked_postsTheReportForTheRoute', () => {
        // Arrange
        buildReportModal('abc');
        activateReport('abc');

        // Act
        $('.dungeonroute_report_submit').trigger('click');

        // Assert
        expect(ajaxSpy).toHaveBeenCalledTimes(1);
        expect(ajaxSpy.mock.calls[0][0]).toMatchObject({
            type: 'POST',
            url: '/ajax/userreport/dungeonroute/abc',
            data: {category: 'enemy', message: 'Broken route', contact_ok: 0},
        });
    });

    it('activate_givenContactOkTicked_sendsContactOkOne', () => {
        // Arrange
        buildReportModal('abc');
        activateReport('abc');
        document.querySelector('.dungeonroute_report_contact_ok').checked = true;

        // Act
        $('.dungeonroute_report_submit').trigger('click');

        // Assert
        expect(ajaxSpy.mock.calls[0][0].data.contact_ok).toBe(1);
    });

    it('activate_givenAutoPublicKey_reportsTheRouteOfTheLinkThatOpenedTheModal', () => {
        // Arrange
        buildReportModal('auto');
        activateReport('auto');

        // Act
        openModalFrom('#report_link');
        $('.dungeonroute_report_submit').trigger('click');

        // Assert
        expect(ajaxSpy.mock.calls[0][0].url).toBe('/ajax/userreport/dungeonroute/xyz789');
    });

    it('activate_givenAutoPublicKeyAndALinkWithoutOne_logsAnError', () => {
        // Arrange
        buildReportModal('auto');
        activateReport('auto');
        const errorSpy = vi.spyOn(console, 'error').mockImplementation(() => {
        });

        // Act
        openModalFrom('#report_link_without_key');

        // Assert
        expect(errorSpy).toHaveBeenCalledExactlyOnceWith('Unable to find public key of dungeonroute on relatedTarget!');
    });

    it('activate_givenAFixedPublicKey_ignoresTheLinkThatOpenedTheModal', () => {
        // Arrange
        buildReportModal('abc');
        activateReport('abc');

        // Act
        openModalFrom('#report_link');
        $('.dungeonroute_report_submit').trigger('click');

        // Assert
        expect(ajaxSpy.mock.calls[0][0].url).toBe('/ajax/userreport/dungeonroute/abc');
    });

    it('_submitDungeonRouteUserReport_givenRequestInFlight_swapsTheSubmitButtonForTheSavingIndicator', () => {
        // Arrange
        buildReportModal('abc');
        activateReport('abc');
        $('.dungeonroute_report_submit').trigger('click');

        // Act
        ajaxSpy.mock.calls[0][0].beforeSend();

        // Assert
        expect($('.dungeonroute_report_submit').css('display')).toBe('none');
        expect($('.dungeonroute_report_saving').css('display')).not.toBe('none');
    });

    it('_submitDungeonRouteUserReport_givenRequestCompletes_restoresTheSubmitButton', () => {
        // Arrange
        buildReportModal('abc');
        activateReport('abc');
        $('.dungeonroute_report_submit').trigger('click');
        ajaxSpy.mock.calls[0][0].beforeSend();

        // Act
        ajaxSpy.mock.calls[0][0].complete();

        // Assert
        expect($('.dungeonroute_report_submit').css('display')).not.toBe('none');
        expect($('.dungeonroute_report_saving').css('display')).toBe('none');
    });

    it('_submitDungeonRouteUserReport_givenSuccess_closesTheModalAndConfirms', () => {
        // Arrange
        buildReportModal('abc');
        activateReport('abc');
        $('.dungeonroute_report_submit').trigger('click');

        // Act
        ajaxSpy.mock.calls[0][0].success({});

        // Assert
        expect(bootstrap.Modal.getOrCreateInstance).toHaveBeenCalledExactlyOnceWith(document.getElementById('userreport_dungeonroute_modal'));
        expect(modal.hide).toHaveBeenCalledTimes(1);
        expect(showSuccessNotification).toHaveBeenCalledExactlyOnceWith('js.dungeonroute_report_enemy_success');
    });
});
