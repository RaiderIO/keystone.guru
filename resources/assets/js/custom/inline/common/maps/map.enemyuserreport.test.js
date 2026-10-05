// Follows the global-script recipe from map.favorite.test.js: stub the collaborators the class body
// touches at load time, then require the source.

globalThis.$ = globalThis.jQuery = require('jquery');

const {InlineCode} = require('../../inlinecode');
globalThis.InlineCode = InlineCode;

globalThis.SettingsTabMap = class SettingsTabMap {
};
globalThis.SettingsTabPull = class SettingsTabPull {
};

const {CommonMapsMap} = require('./map');

const REPORT_FORM_HTML = `
    <div id="enemy_report_collapse">
        <input id="enemy_report_category" value="enemy">
        <input id="enemy_report_enemy_id" value="-1">
        <input id="enemy_report_username" value="Someone else">
        <textarea id="enemy_report_message">Old message</textarea>
        <input type="checkbox" id="enemy_report_contact_ok" checked>
    </div>
`;

describe('CommonMapsMap enemy user report', () => {
    let hideSpy;
    let getOrCreateInstanceSpy;

    beforeEach(() => {
        hideSpy = vi.fn();
        getOrCreateInstanceSpy = vi.fn(() => ({hide: hideSpy}));
        globalThis.bootstrap = {Collapse: {getOrCreateInstance: getOrCreateInstanceSpy}};
    });

    afterEach(() => {
        vi.clearAllMocks();
        document.body.innerHTML = '';
    });

    /**
     * @returns {CommonMapsMap}
     */
    function buildMap() {
        return new CommonMapsMap('map', 'common/maps/map', {});
    }

    it('_resetEnemyUserReport_givenReportForm_resetsFormForEnemy', () => {
        // Arrange
        document.body.innerHTML = REPORT_FORM_HTML;

        // Act
        buildMap()._resetEnemyUserReport(123);

        // Assert
        expect($('#enemy_report_enemy_id').val()).toBe('123');
        expect($('#enemy_report_message').val()).toBe('');
        expect($('#enemy_report_contact_ok').is(':checked')).toBe(false);
        expect(getOrCreateInstanceSpy).toHaveBeenCalledWith(document.getElementById('enemy_report_collapse'), {toggle: false});
        expect(hideSpy).toHaveBeenCalledTimes(1);
    });

    it('_resetEnemyUserReport_givenNoReportForm_doesNothing', () => {
        // Arrange
        document.body.innerHTML = '<div id="enemy_details_modal_body"></div>';

        // Act
        buildMap()._resetEnemyUserReport(123);

        // Assert
        expect(getOrCreateInstanceSpy).not.toHaveBeenCalled();
    });

    it('_submitEnemyUserReport_givenReportForm_postsReportWithoutUsername', () => {
        // Arrange
        document.body.innerHTML = REPORT_FORM_HTML;
        $('#enemy_report_enemy_id').val('123');
        let ajaxSpy = vi.spyOn($, 'ajax').mockImplementation(() => {
        });

        // Act
        buildMap()._submitEnemyUserReport();

        // Assert
        expect(ajaxSpy).toHaveBeenCalledTimes(1);
        let options = ajaxSpy.mock.calls[0][0];
        expect(options.url).toBe('/ajax/userreport/enemy/123');
        expect(options.data).toEqual({category: 'enemy', message: 'Old message', contact_ok: 1});
        expect(options.data).not.toHaveProperty('username');
    });
});
