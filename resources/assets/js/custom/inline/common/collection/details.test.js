// ---------------------------------------------------------------------------
// `details.js` is concatenated into a bundle in the browser and references its collaborators as bare globals, so
// `InlineCode` must be on `globalThis` before the class body is evaluated (same pattern as orderedselect.test.js).
// ---------------------------------------------------------------------------

const jQuery = require('jquery');

const {InlineCode}    = require('../../inlinecode');
globalThis.InlineCode = InlineCode;

const {CommonCollectionDetails} = require('./details');

const OPTIONS = {
    routesSelector:  '#collection_routes',
    loadingSelector: '#collection_routes_loading',
    errorSelector:   '#collection_routes_error',
    seasonSelector:  'input[name="season_id"]',
    formUrl:         'http://localhost/collections/new',
    seasonNone:      'none',
};

describe('CommonCollectionDetails', () => {
    let previousJquery;
    let previousFetch;
    let previousInlineManager;
    let previousRefreshSelectPickers;
    let fetchedUrls;
    let resolveFetch;

    beforeEach(() => {
        previousJquery              = globalThis.$;
        previousFetch               = globalThis.fetch;
        previousInlineManager       = globalThis._inlineManager;
        previousRefreshSelectPickers = globalThis.refreshSelectPickers;
        globalThis.$                = jQuery;
        fetchedUrls                 = [];
        globalThis.fetch            = (url) => {
            fetchedUrls.push(url);

            return new Promise((resolve) => {
                resolveFetch = (html) => resolve({ok: true, text: () => Promise.resolve(html)});
            });
        };
        globalThis.refreshSelectPickers = vi.fn();

        document.body.innerHTML = `
            <section id="collection_routes">
                <div id="collection_routes_loading" hidden></div>
                <div id="collection_routes_error" hidden></div>
                <select id="slot_add"></select>
            </section>
            <form>
                <fieldset>
                    <input type="radio" name="season_id" id="s_17" value="17">
                    <input type="radio" name="season_id" id="s_18" value="18" checked>
                    <input type="radio" name="season_id" id="s_none" value="">
                </fieldset>
            </form>`;

        new CommonCollectionDetails('details', 'common/collection/details', OPTIONS).activate();
    });

    afterEach(() => {
        globalThis.$                    = previousJquery;
        globalThis.fetch                = previousFetch;
        globalThis._inlineManager       = previousInlineManager;
        globalThis.refreshSelectPickers = previousRefreshSelectPickers;
        document.body.innerHTML = '';
    });

    it('onSeasonChanged_givenFreeFormPicked_fetchesTheSectionForNoSeasonAndShowsTheLoadingState', () => {
        // Arrange
        document.querySelector('#s_none').checked = true;

        // Act
        jQuery('#s_none').trigger('change');

        // Assert
        expect(fetchedUrls).toEqual(['http://localhost/collections/new?season_id=none']);
        expect(document.querySelector('#collection_routes_loading').hidden).toBe(false);
        expect(document.querySelector('#slot_add').disabled).toBe(true);
    });

    it('onSeasonChanged_givenAnotherSeason_fetchesTheSectionForThatSeason', () => {
        // Arrange
        document.querySelector('#s_17').checked = true;

        // Act
        jQuery('#s_17').trigger('change');

        // Assert
        expect(fetchedUrls).toEqual(['http://localhost/collections/new?season_id=17']);
    });

    it('onSeasonChanged_givenTheSectionComesBack_activatesEveryControlInItUnderTheIdItHad', async () => {
        // Arrange
        const initialised = [];
        const activated   = [];
        globalThis._inlineManager = {
            init:     (id, path, options) => initialised.push({id, path, options}),
            activate: (id) => activated.push(id),
        };
        document.querySelector('#s_17').checked = true;
        jQuery('#s_17').trigger('change');

        // Act
        resolveFetch(`
            <section id="collection_routes" data-inline-id="collection_routes_inline"
                     data-inline-path="common/collection/routes" data-inline-options='{"max":24}'>
                <div id="collection_routes_loading" hidden></div>
                <div id="collection_routes_error" hidden></div>
                <div class="ordered_select" data-inline-id="dungeon_routes_9_inline"
                     data-inline-path="common/forms/orderedselect" data-inline-options='{"max":24}'></div>
                <div id="collection_route_picker" data-inline-id="collection_route_picker"
                     data-inline-path="common/dungeonroute/picker" data-inline-options='{"max":24}'></div>
            </section>`);
        await new Promise(process.nextTick);

        // Assert
        expect(initialised.map((call) => call.id)).toEqual([
            'dungeon_routes_9_inline',
            'collection_route_picker',
            'collection_routes_inline',
        ]);
        expect(initialised[0].path).toBe('common/forms/orderedselect');
        expect(initialised[0].options).toEqual({max: 24});
        // The section's own controller looks the others up, so it activates once they exist
        expect(activated).toEqual([
            'dungeon_routes_9_inline',
            'collection_route_picker',
            'collection_routes_inline',
        ]);
        expect(globalThis.refreshSelectPickers).toHaveBeenCalled();
        expect(document.querySelector('#collection_routes_loading').hidden).toBe(true);
    });

    it('onSeasonChanged_givenTheFetchFails_saysSoAndLeavesTheSectionInPlace', async () => {
        // Arrange
        globalThis.fetch = () => Promise.resolve({ok: false, status: 500});
        document.querySelector('#s_17').checked = true;

        // Act
        jQuery('#s_17').trigger('change');
        await new Promise(process.nextTick);

        // Assert
        expect(document.querySelector('#collection_routes_error').hidden).toBe(false);
        expect(document.querySelector('#collection_routes_loading').hidden).toBe(true);
        expect(document.querySelector('#collection_routes')).not.toBeNull();
    });
});
