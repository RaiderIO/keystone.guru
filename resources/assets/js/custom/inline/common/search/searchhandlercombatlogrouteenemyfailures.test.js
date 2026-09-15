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
            leafletMap: {},
        };
        stateStub = {
            getDungeonMap: () => dungeonMapStub,
            getCurrentFloor: () => ({id: 5}),
            register: vi.fn(),
        };
        globalThis.getState = () => stateStub;

        // Minimal Leaflet: a layer group that remembers what was added, and layers that remember their options
        layerGroupStub = {
            layers: [],
            addTo: function () { return this; },
            clearLayers: function () { this.layers = []; },
        };
        const layerFactory = (type) => (latLngs, options) => {
            const layer = {type, latLngs, options, bindPopup: function () { return this; }, addTo: function (group) { group.layers.push(this); return this; }};
            return layer;
        };
        globalThis.L = {
            layerGroup: () => layerGroupStub,
            polygon: layerFactory('polygon'),
            circleMarker: layerFactory('circleMarker'),
        };

        vi.spyOn($, 'ajax').mockReturnValue({done: () => ({})});
    });

    let stateStub;
    let layerGroupStub;

    function createInstance() {
        return new CommonMapsCombatlogrouteenemyfailures('combatlogrouteenemyfailures', 'common/maps/combatlogrouteenemyfailures', {
            dungeonId: 123,
            mappingVersionId: 10,
            pageUrl: '/admin/tools/combatlog/route/enemy-failures',
            getEnemyFailuresUrl: '/ajax/admin/combatlogroute/enemy-failures',
            clustersUrl: '/ajax/admin/combatlogroute/enemy-failures/clusters',
            showClustersSelector: '#show_clusters',
            verdicts: {npc_not_mapped: {label: 'NPC not mapped', color: '#e74c3c'}},
            clusterPopupTexts: {failures: ':count failures', nearestEnemy: ':id', nearestNone: 'none', inRange: ':count', seen: ':first :last', filterNpc: 'Filter'},
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

    const cluster = (overrides = {}) => Object.assign({
        npc_id: 1, npc_name: 'Npc', floor_id: 5, count: 8, route_count: 4, avg_failures_per_route: 2,
        centroid: {lat: -10, lng: 20, floor_id: 5}, hull: [[-9, 19], [-11, 19], [-10, 21]],
        first_seen: '2026-08-01T00:00:00+00:00', last_seen: '2026-08-02T00:00:00+00:00',
        nearest_enemy_id: null, nearest_enemy_distance: null, nearest_enemy_floor_id: null, nearest_enemy_pack_group: null,
        enemies_within_range: 0, verdict: 'npc_not_mapped', low_volume: false, suggestion: 'Add it',
    }, overrides);

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

    test('search_alsoFetchesClustersWithTheSameFilters', () => {
        const instance = createInstance();
        $('#filter_npc_id').append('<option value="42" selected>x</option>');

        instance.activate();

        const clusterCall = $.ajax.mock.calls.find((call) => call[0].url === '/ajax/admin/combatlogroute/enemy-failures/clusters');
        expect(clusterCall).toBeDefined();
        expect(clusterCall[0].data).toEqual({dungeon_id: 123, mapping_version_id: 10, npc_id: [42]});
    });

    test('redrawClusters_drawsOnlyCurrentFloorClustersAsMarkerAndHull', () => {
        const instance = createInstance();
        document.body.insertAdjacentHTML('beforeend', '<input type="checkbox" id="show_clusters" checked>');
        instance.activate();

        instance._clusters = [cluster({floor_id: 5}), cluster({floor_id: 6}), cluster({floor_id: 5, hull: []})];
        instance._redrawClusters();

        // floor 5 with hull: polygon + marker; floor 5 without hull: marker only; floor 6: nothing
        expect(layerGroupStub.layers.map((layer) => layer.type)).toEqual(['polygon', 'circleMarker', 'circleMarker']);
        expect(layerGroupStub.layers[1].options.color).toBe('#e74c3c');
        expect(layerGroupStub.layers[1].latLngs).toEqual([-10, 20]);
    });

    test('redrawClusters_givenCheckboxUnchecked_drawsNothing', () => {
        const instance = createInstance();
        document.body.insertAdjacentHTML('beforeend', '<input type="checkbox" id="show_clusters">');
        instance.activate();

        instance._clusters = [cluster()];
        instance._redrawClusters();

        expect(layerGroupStub.layers).toEqual([]);
    });

    test('fetchClusters_givenStaleResponseArrivingLate_ignoresIt', () => {
        const instance = createInstance();
        document.body.insertAdjacentHTML('beforeend', '<input type="checkbox" id="show_clusters" checked>');
        instance.activate();

        // Two cluster requests in flight; resolve the SECOND first, then the first (stale) one
        const pending = [];
        $.ajax.mockImplementation((options) => ({done: (callback) => { if (options.url.endsWith('/clusters')) pending.push(callback); return {}; }}));
        instance._fetchClusters([1]);
        instance._fetchClusters([2]);
        pending[1]({data: [cluster({npc_id: 2})]});
        pending[0]({data: [cluster({npc_id: 1})]});

        expect(instance._clusters.map((c) => c.npc_id)).toEqual([2]);
    });

    test('activate_registersForFloorChangesToRedraw', () => {
        const instance = createInstance();

        instance.activate();

        expect(stateStub.register).toHaveBeenCalledWith('floorid:changed', instance, expect.any(Function));
    });

    test('mappingVersionChange_navigatesToThePageForThatMappingVersion', () => {
        const instance = createInstance();
        const navigate = vi.spyOn(instance, '_navigateTo').mockImplementation(() => {});

        instance.activate();
        $('#filter_mapping_version_id').val('11').trigger('change');

        expect(navigate).toHaveBeenCalledWith('/admin/tools/combatlog/route/enemy-failures?dungeon_id=123&mapping_version_id=11');
    });
});
