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
            <select id="filter_mapping_version_id">
                <option value="10" selected>v1</option>
                <option value="11">v2</option>
            </select>
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
            mappingVersionId: 10,
            pageUrl: '/admin/tools/combatlog/route/enemy-failures',
            getEnemyFailuresUrl: '/ajax/admin/combatlogroute/enemy-failures',
            deleteUrl: '/ajax/admin/combatlogroute/enemy-failures',
            filterMappingVersionIdSelector: '#filter_mapping_version_id',
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

    test('clearButtonClick_givenDeleteSucceeded_reloadsThePage', () => {
        const instance = createInstance();
        const reload   = vi.spyOn(instance, '_reload').mockImplementation(() => {});
        // Resolve the DELETE immediately so .done() runs synchronously
        $.ajax.mockReturnValue({done: (callback) => { callback(); return {}; }});

        instance.activate();
        $('#clear_button').trigger('click');

        expect(reload).toHaveBeenCalledTimes(1);
    });

    test('activate_sendsMappingVersionIdInSearchRequest', () => {
        const instance = createInstance();

        instance.activate();

        const searchCall = $.ajax.mock.calls.find((call) => call[0].type === 'GET');
        expect(searchCall).toBeDefined();
        expect(String(searchCall[0].data.mapping_version_id)).toBe('10');
        expect(String(searchCall[0].data.dungeon_id)).toBe('123');
    });

    test('mappingVersionChange_navigatesToThePageForThatMappingVersion', () => {
        const instance = createInstance();
        const navigate = vi.spyOn(instance, '_navigateTo').mockImplementation(() => {});

        instance.activate();
        $('#filter_mapping_version_id').val('11').trigger('change');

        expect(navigate).toHaveBeenCalledWith('/admin/tools/combatlog/route/enemy-failures?dungeon_id=123&mapping_version_id=11');
    });
});
