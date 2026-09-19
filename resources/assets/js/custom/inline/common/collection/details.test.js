// ---------------------------------------------------------------------------
// `details.js` is concatenated into a bundle in the browser and references its collaborators as bare globals, so
// `InlineCode` must be on `globalThis` before the class body is evaluated (same pattern as orderedselect.test.js).
// ---------------------------------------------------------------------------

const jQuery = require('jquery');

const {InlineCode}    = require('../../inlinecode');
globalThis.InlineCode = InlineCode;

const {CommonCollectionDetails} = require('./details');

const OPTIONS = {
    dungeonRoutesSelector:   '#collection_dungeon_routes',
    totalSelector:           '#collection_dungeon_routes_total',
    loadingSelector:         '#collection_dungeon_routes_loading',
    errorSelector:           '#collection_dungeon_routes_error',
    gameVersionSelector:     '.collection_game_version',
    seasonContainerSelector: '.collection_season',
    max:                     24,
    countText:               ':count / :max',
    formUrl:                 'http://localhost/collections/new',
    seasonNone:              'none',
};

describe('CommonCollectionDetails', () => {
    let previousJquery;
    let previousFetch;
    let fetchedUrls;

    beforeEach(() => {
        previousJquery  = globalThis.$;
        previousFetch   = globalThis.fetch;
        globalThis.$    = jQuery;
        fetchedUrls     = [];
        globalThis.fetch = (url) => {
            fetchedUrls.push(url);
            return new Promise(() => {});
        };

        document.body.innerHTML = `
            <form>
                <input type="radio" class="collection_game_version" name="game_version_id" id="gv_1" value="1" checked>
                <input type="radio" class="collection_game_version" name="game_version_id" id="gv_5" value="5">
                <fieldset class="collection_season" data-game-version-id="1">
                    <input type="radio" name="season_id" id="s_18" value="18" checked>
                    <input type="radio" name="season_id" id="s_none" value="">
                </fieldset>
                <div id="collection_dungeon_routes_loading" hidden></div>
                <div id="collection_dungeon_routes_error" hidden></div>
                <div id="collection_dungeon_routes">
                    <span id="collection_dungeon_routes_total"></span>
                    <ol id="slot_list">
                        <li class="ordered_select_item"><input type="hidden" name="dungeon_routes[]" value="3"></li>
                    </ol>
                    <select id="slot_add"></select>
                </div>
            </form>`;

        new CommonCollectionDetails('details', 'common/collection/details', OPTIONS).activate();
    });

    afterEach(() => {
        globalThis.$     = previousJquery;
        globalThis.fetch = previousFetch;
        document.body.innerHTML = '';
    });

    it('activate_givenRoutesInTheSlots_countsThemForTheWholeCollection', () => {
        // Arrange - done in beforeEach

        // Act - done in beforeEach

        // Assert
        expect(document.querySelector('#collection_dungeon_routes_total').textContent).toBe('1 / 24');
    });

    it('onOrderedSelectChanged_givenAnAddedRoute_updatesTheTotal', () => {
        // Arrange
        jQuery('#slot_list').append('<li class="ordered_select_item"><input type="hidden" name="dungeon_routes[]" value="4"></li>');

        // Act
        jQuery('#slot_list').trigger('orderedselect:changed');

        // Assert
        expect(document.querySelector('#collection_dungeon_routes_total').textContent).toBe('2 / 24');
    });

    it('onKindChanged_givenFreeFormPicked_fetchesThePickerForNoSeasonAndShowsTheLoadingState', () => {
        // Arrange
        document.querySelector('#s_none').checked = true;

        // Act
        jQuery('#s_none').trigger('change');

        // Assert
        expect(fetchedUrls).toEqual(['http://localhost/collections/new?game_version_id=1&season_id=none']);
        expect(document.querySelector('#collection_dungeon_routes_loading').hidden).toBe(false);
        expect(document.querySelector('#slot_add').disabled).toBe(true);
    });

    it('onKindChanged_givenAGameVersionWithoutSeasons_hidesTheSeasonsAndFetchesWithoutASeason', () => {
        // Arrange
        document.querySelector('#gv_5').checked = true;

        // Act
        jQuery('#gv_5').trigger('change');

        // Assert
        expect(document.querySelector('.collection_season').hidden).toBe(true);
        expect(document.querySelector('#s_18').disabled).toBe(true);
        expect(fetchedUrls).toEqual(['http://localhost/collections/new?game_version_id=5']);
    });
});
