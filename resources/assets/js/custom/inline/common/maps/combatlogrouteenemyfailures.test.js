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

const {SearchFilter} = require('../search/filters/filter');
globalThis.SearchFilter = SearchFilter;

const {SearchFilterPassThrough} = require('../search/filters/filterpassthrough');
globalThis.SearchFilterPassThrough = SearchFilterPassThrough;

const {SearchParams} = require('../search/searchparams');
globalThis.SearchParams = SearchParams;

const {SearchHandler} = require('../search/searchhandler');
globalThis.SearchHandler = SearchHandler;

const {SearchInlineBase} = require('../../base/searchinlinebase');
globalThis.SearchInlineBase = SearchInlineBase;

const {SearchHandlerCombatLogRouteEnemyFailures} = require('../search/searchhandlercombatlogrouteenemyfailures');
globalThis.SearchHandlerCombatLogRouteEnemyFailures = SearchHandlerCombatLogRouteEnemyFailures;

const {CommonMapsCombatlogrouteenemyfailures} = require('./combatlogrouteenemyfailures');

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

        vi.spyOn($, 'ajax').mockReturnValue({done: () => ({}), abort: () => {}});
        // Every search writes its filters into the address bar, which jsdom keeps for the whole file
        window.history.replaceState({}, '', '/');
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
        document.body.insertAdjacentHTML('beforeend', '<input type="checkbox" id="show_clusters" checked>');
        let currentFloor = {id: 5};
        stateStub.getCurrentFloor = () => currentFloor;

        instance.activate();
        instance._clusters = [cluster({floor_id: 5, hull: []}), cluster({floor_id: 6, hull: [], centroid: {lat: -30, lng: 40, floor_id: 6}})];
        instance._redrawClusters();
        const floorChangedRegistrations = stateStub.register.mock.calls.filter((call) => call[0] === 'floorid:changed');
        currentFloor = {id: 6};
        floorChangedRegistrations.forEach((call) => call[2]());

        expect(floorChangedRegistrations.map((call) => call[1])).toEqual([instance]);
        expect(layerGroupStub.layers.map((layer) => layer.latLngs)).toEqual([[-30, 40]]);
    });

    test('search_givenNoNpcSelected_fetchesClustersWithoutAnNpcFilter', () => {
        const instance = createInstance();

        instance.activate();

        const clusterCall = $.ajax.mock.calls.find((call) => call[0].url === '/ajax/admin/combatlogroute/enemy-failures/clusters');
        expect(clusterCall[0].data).toEqual({dungeon_id: 123, mapping_version_id: 10});
    });

    test('clusterPopupSelectNpcClick_selectsOnlyThatNpcAndSearchesForIt', () => {
        const instance = createInstance();
        $('#filter_npc_id').append('<option value="41" selected>x</option><option value="42">y</option>');
        instance.activate();
        $.ajax.mockClear();
        $('body').append(instance._getClusterPopupHtml(cluster({npc_id: 42})));

        $('.js-enemy-failure-cluster-select-npc').trigger('click');

        expect($('#filter_npc_id').val()).toEqual(['42']);
        const clusterCall = $.ajax.mock.calls.find((call) => call[0].url === '/ajax/admin/combatlogroute/enemy-failures/clusters');
        expect(clusterCall?.[0].data.npc_id).toEqual([42]);
    });

    test('getClusterPopupHtml_escapesServerTextAndNamesTheNearestEnemy', () => {
        const instance = createInstance();

        const html = instance._getClusterPopupHtml(cluster({npc_name: '<b>x</b>', suggestion: '<img src=x>', nearest_enemy_id: 77}));
        const htmlWithoutNearest = instance._getClusterPopupHtml(cluster());

        expect(html).toContain('&lt;b&gt;x&lt;/b&gt; (1)');
        expect(html).toContain('&lt;img src=x&gt;');
        expect(html).not.toContain('<img');
        expect(html).toContain('<div>77</div>');
        expect(htmlWithoutNearest).toContain('<div>none</div>');
    });

    test('mappingVersionChange_navigatesToThePageForThatMappingVersion', () => {
        const instance = createInstance();
        const navigate = vi.spyOn(instance, '_navigateTo').mockImplementation(() => {});

        instance.activate();
        $('#filter_mapping_version_id').val('11').trigger('change');

        expect(navigate).toHaveBeenCalledWith('/admin/tools/combatlog/route/enemy-failures?dungeon_id=123&mapping_version_id=11');
    });
});
