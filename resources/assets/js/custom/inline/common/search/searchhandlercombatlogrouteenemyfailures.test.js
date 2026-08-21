// ---------------------------------------------------------------------------
// Regression suite for #4214: the "Clear failures" button on the admin combat log route enemy
// failures sidebar sent a DELETE request with no body, but the delete endpoint's form request
// requires dungeon_id - so the click always 422'd. The sibling GET search request already sent
// dungeon_id fine, via a SearchFilterPassThrough filter wired into _search(), but the clear
// button's DELETE call bypassed that entirely.
// ---------------------------------------------------------------------------

globalThis.$ = globalThis.jQuery = require('jquery');

const {InlineCode} = require('../../inlinecode');
globalThis.InlineCode = InlineCode;

const {getQueryParams} = require('../../../util');
globalThis.getQueryParams = getQueryParams;

const {SearchFilter} = require('./filters/filter');
globalThis.SearchFilter = SearchFilter;

const {SearchFilterPassThrough} = require('./filters/filterpassthrough');
globalThis.SearchFilterPassThrough = SearchFilterPassThrough;

const {SearchParams} = require('./searchparams');
globalThis.SearchParams = SearchParams;

const {SearchHandler} = require('./searchhandler');
globalThis.SearchHandler = SearchHandler;

const {SearchInlineBase} = require('../../base/searchinlinebase');
globalThis.SearchInlineBase = SearchInlineBase;

const {CommonMapsCombatlogrouteenemyfailures} = require('./searchhandlercombatlogrouteenemyfailures');

describe('CommonMapsCombatlogrouteenemyfailures', () => {
    let dungeonMapStub;

    beforeEach(() => {
        document.body.innerHTML = `
            <select id="filter_npc_id" multiple></select>
            <button id="clear_button"></button>
            <div id="routes_container"></div>
            <div id="routes_list"></div>
        `;

        dungeonMapStub = {
            pluginHeat: {
                toggle: vi.fn(),
                setRawLatLngsPerFloor: vi.fn(),
            },
        };
        globalThis.getState = () => ({getDungeonMap: () => dungeonMapStub});

        vi.spyOn($, 'ajax').mockReturnValue({done: () => ({})});
    });

    function createInstance() {
        return new CommonMapsCombatlogrouteenemyfailures('combatlogrouteenemyfailures', 'common/maps/combatlogrouteenemyfailures', {
            dungeonId: 123,
            getEnemyFailuresUrl: '/ajax/admin/combatlogroute/enemy-failures',
            deleteUrl: '/ajax/admin/combatlogroute/enemy-failures',
            filterNpcIdSelector: '#filter_npc_id',
            clearButtonSelector: '#clear_button',
            routesContainerSelector: '#routes_container',
            routesListSelector: '#routes_list',
            noMatchingRoutesText: 'No matching routes',
            dependencies: [],
        });
    }

    test('clearButtonClick_sendsDungeonIdInDeleteRequest', () => {
        const instance = createInstance();

        instance.activate();
        $.ajax.mockClear();

        $('#clear_button').trigger('click');

        expect($.ajax).toHaveBeenCalledTimes(1);
        const ajaxCall = $.ajax.mock.calls[0][0];
        expect(ajaxCall.type).toBe('DELETE');
        expect(ajaxCall.url).toBe('/ajax/admin/combatlogroute/enemy-failures');
        expect(ajaxCall.data).toEqual({dungeon_id: 123});
    });
});
