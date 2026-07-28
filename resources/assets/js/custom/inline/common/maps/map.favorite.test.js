// #3705: (de-)favoriting a route via the header control gave no feedback. `_favorite()` now shows
// a success notification once the AJAX call resolves.
//
// Follows the global-script recipe from models/killzone.test.js / enemyselection.tooltips.test.js:
// stub the collaborators the class body touches at load time, then require the source. Only
// `InlineCode` is needed at load time (for `extends`); `SettingsTabMap`/`SettingsTabPull` are only
// `new`'d inside the constructor, so plain stub classes are enough - none of their real behaviour
// is exercised here.

globalThis.$ = globalThis.jQuery = require('jquery');

const {InlineCode} = require('../../inlinecode');
globalThis.InlineCode = InlineCode;

globalThis.SettingsTabMap = class SettingsTabMap {
};
globalThis.SettingsTabPull = class SettingsTabPull {
};

const {CommonMapsMap} = require('./map');

describe('CommonMapsMap._favorite', () => {
    let ajaxSpy;
    let publicKey;

    beforeEach(() => {
        publicKey = 'the_public_key';

        globalThis.getState = vi.fn(() => ({
            getMapContext: () => ({getPublicKey: () => publicKey}),
        }));

        globalThis.lang = {get: (key) => key};
        globalThis.showSuccessNotification = vi.fn();

        ajaxSpy = vi.spyOn($, 'ajax').mockImplementation((options) => {
            options.success({});
        });
    });

    afterEach(() => {
        vi.clearAllMocks();
    });

    /**
     * @returns {CommonMapsMap}
     */
    function buildMap() {
        return new CommonMapsMap('map', 'common/maps/map', {});
    }

    it('_favorite_givenTrue_postsAndShowsAddedNotification', () => {
        // Act
        buildMap()._favorite(true);

        // Assert
        expect(ajaxSpy).toHaveBeenCalledWith(expect.objectContaining({
            type: 'POST',
            url: `/ajax/${publicKey}/favorite`,
        }));
        expect(showSuccessNotification).toHaveBeenCalledWith('js.dungeonroute_favorite_added_success');
    });

    it('_favorite_givenFalse_deletesAndShowsRemovedNotification', () => {
        // Act
        buildMap()._favorite(false);

        // Assert
        expect(ajaxSpy).toHaveBeenCalledWith(expect.objectContaining({
            type: 'DELETE',
            url: `/ajax/${publicKey}/favorite`,
        }));
        expect(showSuccessNotification).toHaveBeenCalledWith('js.dungeonroute_favorite_removed_success');
    });
});
