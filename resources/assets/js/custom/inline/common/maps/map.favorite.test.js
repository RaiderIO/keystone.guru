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

describe('CommonMapsMap._setupFavorite', () => {
    let ajaxSpy;

    beforeEach(() => {
        globalThis.getState = vi.fn(() => ({
            getMapContext: () => ({getPublicKey: () => 'the_public_key'}),
        }));
        globalThis.lang = {get: (key) => key};
        globalThis.showSuccessNotification = vi.fn();
        ajaxSpy = vi.spyOn($, 'ajax').mockImplementation(() => {
        });
    });

    afterEach(() => {
        vi.clearAllMocks();
        document.body.innerHTML = '';
    });

    /**
     * Mirrors the favorite star in common/maps/controls/header.blade.php.
     * @param {boolean} favorited
     */
    function renderStars(favorited) {
        document.body.innerHTML = `
            <i id="route_favorited" class="fas fa-star favorite_star favorited" style="display: ${favorited ? 'inherit' : 'none'}"></i>
            <i id="route_not_favorited" class="far fa-star favorite_star" style="display: ${favorited ? 'none' : 'inherit'}"></i>
            <input type="hidden" id="favorite" name="favorite" value="${favorited ? '1' : '0'}">`;
    }

    it.each([
        [false, 'POST', '1', '#route_favorited', '#route_not_favorited'],
        [true, 'DELETE', '0', '#route_not_favorited', '#route_favorited'],
    ])('_setupFavorite_givenStarClickedWhileFavorited%s_togglesTheFavorite', (favorited, expectedType, expectedValue, shownStar, hiddenStar) => {
        // Arrange
        renderStars(favorited);
        new CommonMapsMap('map', 'common/maps/map', {})._setupFavorite();

        // Act
        $(favorited ? '#route_favorited' : '#route_not_favorited').trigger('click');

        // Assert
        expect(ajaxSpy).toHaveBeenCalledTimes(1);
        expect(ajaxSpy.mock.calls[0][0]).toMatchObject({type: expectedType, url: '/ajax/the_public_key/favorite'});
        expect($('#favorite').val()).toBe(expectedValue);
        expect($(shownStar).css('display')).not.toBe('none');
        expect($(hiddenStar).css('display')).toBe('none');
    });
});
