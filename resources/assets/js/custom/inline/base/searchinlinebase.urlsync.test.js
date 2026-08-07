// ---------------------------------------------------------------------------
// SearchInlineBase._updateUrl() keeps the address bar in sync with the search, and #3837 made it preserve the
// query params it doesn't own instead of dropping them. These tests pin the boundary of "doesn't own" on the base
// class itself, where all four search inlines share it - the heatmap sidebar's own behaviour is covered by
// custom/inline/common/maps/heatmapsearchsidebar.urlrestore.test.js.
//
// The interesting case is a param the caller injects through _search()'s `queryParameters` (npc_id on the enemy
// failures search): it is not a filter, yet it is ours - so it must not be resurrected from the URL once the
// search stops sending it.
// ---------------------------------------------------------------------------

globalThis.$ = globalThis.jQuery = require('jquery');

const {InlineCode} = require('../inlinecode');
globalThis.InlineCode = InlineCode;

const {getQueryParams} = require('../../util');
globalThis.getQueryParams = getQueryParams;

const {SearchFilter} = require('../common/search/filters/filter');
globalThis.SearchFilter = SearchFilter;
globalThis.SearchParams = require('../common/search/searchparams').SearchParams;

const {SearchInlineBase} = require('./searchinlinebase');

/**
 * A filter whose value the test drives directly, standing in for a DOM-backed one.
 */
class FixedValueFilter extends SearchFilter {
    constructor(value) {
        super(null, null, {});

        this.value = value;
    }

    getValue() {
        return this.value;
    }

    setValue(value) {
        this.value = value;
    }
}

/**
 * @param {String} queryString
 * @param {Object<string, SearchFilter>} filters
 * @returns {SearchInlineBase}
 */
function buildSearch(queryString, filters) {
    // jsdom doesn't allow assigning document.location.search - see custom/util.test.js for the same approach.
    window.history.replaceState({}, '', `/routes${queryString}`);

    const search = new SearchInlineBase({search: () => undefined}, 'search', 'base/search', {});
    search.filters = filters;

    return search;
}

/**
 * @returns {Object<string, string>}
 */
function writtenParams() {
    return Object.fromEntries(new URLSearchParams(window.location.search));
}

describe('SearchInlineBase - keeping the URL in sync with the search', () => {
    test('search_givenAParamNoFilterOwns_leavesItInTheUrl', () => {
        // Arrange
        const search = buildSearch('?title=abc&defaultZoom=1.4', {title: new FixedValueFilter('abc')});

        // Act
        search._search();

        // Assert
        expect(writtenParams()).toEqual({title: 'abc', defaultZoom: '1.4'});
    });

    test('search_givenAFiltersParamIsNoLongerSent_dropsItFromTheUrl', () => {
        // Arrange
        const title = new FixedValueFilter('abc');
        const search = buildSearch('?title=abc&expansion=midnight', {
            title: title,
            expansion: new FixedValueFilter('midnight'),
        });
        search._search();

        // Act - clearing a filter must clear it from the URL, not preserve it as someone else's param
        title.setValue('');
        search._search();

        // Assert
        expect(writtenParams()).toEqual({expansion: 'midnight'});
    });

    test('search_givenAnInjectedParamIsNoLongerSent_dropsItFromTheUrl', () => {
        // Arrange - mirrors CommonMapsCombatlogrouteenemyfailures, which only injects npc_id while an npc is selected
        const search = buildSearch('', {title: new FixedValueFilter('abc')});
        search._search({}, {npc_id: 123});
        expect(writtenParams().npc_id).toBe('123');

        // Act
        search._search({}, {});

        // Assert - nothing restores npc_id on F5, so leaving it behind would make the URL lie about the page state
        expect(writtenParams()).toEqual({title: 'abc'});
    });

    test('search_givenABlacklistedParam_keepsItOutOfTheUrl', () => {
        // Arrange
        const search = buildSearch('', {title: new FixedValueFilter('abc')});

        // Act
        search._search({}, {dungeonId: 42}, ['dungeonId']);

        // Assert
        expect(writtenParams()).toEqual({title: 'abc'});
    });

    test('search_givenAPreservedParamWithReservedCharacters_writesItBackEncoded', () => {
        // Arrange
        const search = buildSearch(`?title=abc&${encodeURIComponent('a&b')}=${encodeURIComponent('c&d')}`, {
            title: new FixedValueFilter('abc'),
        });

        // Act
        search._search();

        // Assert - re-emitting the key raw would split it into a different param set than it came in as
        expect(writtenParams()).toEqual({title: 'abc', 'a&b': 'c&d'});
    });
});
