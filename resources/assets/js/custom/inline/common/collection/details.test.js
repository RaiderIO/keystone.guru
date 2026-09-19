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
    seasonSelector:          'input[name="season_id"]',
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
                <fieldset>
                    <input type="radio" name="season_id" id="s_17" value="17">
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

    it('onSeasonChanged_givenFreeFormPicked_fetchesThePickerForNoSeasonAndShowsTheLoadingState', () => {
        // Arrange
        document.querySelector('#s_none').checked = true;

        // Act
        jQuery('#s_none').trigger('change');

        // Assert
        expect(fetchedUrls).toEqual(['http://localhost/collections/new?season_id=none']);
        expect(document.querySelector('#collection_dungeon_routes_loading').hidden).toBe(false);
        expect(document.querySelector('#slot_add').disabled).toBe(true);
    });

    it('onSeasonChanged_givenAnotherSeason_fetchesThePickerForThatSeason', () => {
        // Arrange
        document.querySelector('#s_17').checked = true;

        // Act
        jQuery('#s_17').trigger('change');

        // Assert
        expect(fetchedUrls).toEqual(['http://localhost/collections/new?season_id=17']);
    });
});
