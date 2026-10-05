// `killzonessidebar.js` references its collaborators as bare globals in the browser bundle, so
// they are stubbed on `globalThis` before the class body is evaluated. The map is a stub too.
const {InlineCode}    = require('../../inlinecode');
globalThis.InlineCode = InlineCode;

class MapState {
}

globalThis.KillZone                             = class {};
globalThis.SelectKillZoneEnemySelectionOverpull = class extends MapState {};
globalThis.EditKillZoneEnemySelection           = class extends MapState {};
globalThis.ViewKillZoneEnemySelection           = class extends MapState {};
globalThis.MapContextLiveSession                = class {};
globalThis.Sidebar                              = class {};
globalThis.PullWorkBench                        = class {};

const {CommonMapsKillzonessidebar} = require('./killzonessidebar');

describe('CommonMapsKillzonessidebar._confirmDeleteAllPulls', () => {
    let previousShowConfirmYesCancel;
    let previousLang;

    beforeEach(() => {
        previousShowConfirmYesCancel    = globalThis.showConfirmYesCancel;
        previousLang                    = globalThis.lang;
        globalThis.showConfirmYesCancel = vi.fn();
        globalThis.lang                 = {get: (key) => key};
    });

    afterEach(() => {
        globalThis.showConfirmYesCancel = previousShowConfirmYesCancel;
        globalThis.lang                 = previousLang;
    });

    test('_confirmDeleteAllPulls_givenConfirmed_deletesEveryPullBehindADangerButton', () => {
        // Arrange
        const killZoneMapObjectGroup = {deleteAll: vi.fn()};
        const context                = Object.assign(Object.create(CommonMapsKillzonessidebar.prototype), {
            map:                   {mapObjectGroupManager: {getKillZoneMapObjectGroup: () => killZoneMapObjectGroup}},
            _rebuildFloorSwitches: vi.fn(),
        });

        // Act
        CommonMapsKillzonessidebar.prototype._confirmDeleteAllPulls.call(context);
        globalThis.showConfirmYesCancel.mock.calls[0][1]();

        // Assert
        expect(globalThis.showConfirmYesCancel).toHaveBeenCalledExactlyOnceWith('js.killzone_sidebar_delete_all_pulls_confirm_label', expect.any(Function), null, {
            yesLabel:    'js.killzone_sidebar_delete_all_pulls_confirm_yes',
            yesClass:    'btn btn-danger me-1',
            cancelClass: 'btn btn-secondary',
        });
        expect(killZoneMapObjectGroup.deleteAll).toHaveBeenCalledTimes(1);
        expect(context._rebuildFloorSwitches).toHaveBeenCalledTimes(1);
    });
});
