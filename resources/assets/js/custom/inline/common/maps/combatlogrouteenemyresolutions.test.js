globalThis.$ = globalThis.jQuery = require('jquery');

const {InlineCode} = require('../../inlinecode');
globalThis.InlineCode = InlineCode;

const {getQueryParams} = require('../../../util');
globalThis.getQueryParams = getQueryParams;

const {SearchFilter} = require('../search/filters/filter');
globalThis.SearchFilter = SearchFilter;

const {SearchFilterPassThrough} = require('../search/filters/filterpassthrough');
globalThis.SearchFilterPassThrough = SearchFilterPassThrough;

const {SearchFilterInput} = require('../search/filters/filterinput');
globalThis.SearchFilterInput = SearchFilterInput;

const {SearchFilterInputChange} = require('../search/filters/filterinputchange');
globalThis.SearchFilterInputChange = SearchFilterInputChange;

globalThis.HEAT_COMBINE_MODE_MAX = 'max';

const {SearchParams} = require('../search/searchparams');
globalThis.SearchParams = SearchParams;

const {SearchHandler} = require('../search/searchhandler');
globalThis.SearchHandler = SearchHandler;

const {SearchInlineBase} = require('../../base/searchinlinebase');
globalThis.SearchInlineBase = SearchInlineBase;

const {SearchHandlerCombatLogRouteEnemyResolutions} = require('../search/searchhandlercombatlogrouteenemyresolutions');
globalThis.SearchHandlerCombatLogRouteEnemyResolutions = SearchHandlerCombatLogRouteEnemyResolutions;

const {CommonMapsCombatlogrouteenemyresolutions} = require('./combatlogrouteenemyresolutions');

const Lang = require('lang.js');

const MESSAGES = {
    'en.js': {
        enemy_resolution_group_verdict_displaced: 'Mapped in the wrong place',
        enemy_resolution_group_verdict_converged: 'Runs to the group before logged',
        enemy_resolution_group_verdict_scatter:   'Scattered',
        enemy_resolution_group_pack:              'Pack :group (id :id)',
        enemy_resolution_group_enemy:             'Enemy :id',
        enemy_resolution_group_resolutions:       ':count long matches in :routes routes (:share% of routes)',
        enemy_resolution_group_displacement:      'Engaged :distance yd from where it is mapped',
        enemy_resolution_group_consistency:       'Direction consistency :consistency, shape ratio :ratio',
        enemy_resolution_group_seen:              'Seen :first - :last',
        enemy_resolution_group_low_volume:        'Few routes - treat with caution',
    },
};

describe('CommonMapsCombatlogrouteenemyresolutions', () => {
    let dungeonMapStub;
    let stateStub;

    beforeEach(() => {
        document.body.innerHTML = `
            <select id="filter_mapping_version_id">
                <option value="10" selected>v1</option>
                <option value="11">v2</option>
            </select>
            <select id="filter_npc_id" multiple></select>
            <select id="filter_metric">
                <option value="average" selected>Average</option>
                <option value="max">Max</option>
            </select>
            <input id="filter_min_distance" type="number">
            <button id="clear_button"></button>
            <div id="summary"></div>
            <div id="routes_container"></div>
            <div id="routes_list"></div>
            <input type="checkbox" id="show_lines" checked>
            <input type="checkbox" id="show_groups" checked>
        `;
        globalThis.lang = new Lang({messages: MESSAGES, locale: 'en'});

        dungeonMapStub = {
            pluginHeat: {
                toggle: vi.fn(),
                setCombineMode: vi.fn(),
                setRawLatLngsPerFloor: vi.fn(),
            },
            leafletMap: {},
        };
        currentFloor = {id: 5};
        stateStub = {
            getDungeonMap: () => dungeonMapStub,
            getCurrentFloor: () => currentFloor,
            register: vi.fn(),
        };
        globalThis.getState = () => stateStub;

        // Minimal Leaflet: a layer group that remembers what was added, and lines that remember their options
        layerGroupStub = {
            layers: [],
            addTo: function () { return this; },
            clearLayers: function () { this.layers = []; },
        };
        globalThis.L = {
            layerGroup: () => layerGroupStub,
            polyline: (latLngs, options) => ({
                latLngs,
                options,
                popup: null,
                bindPopup: function (html) { this.popup = html; return this; },
                addTo: function (group) { group.layers.push(this); return this; },
            }),
            circleMarker: (latLng, options) => ({
                latLng,
                options,
                popup: null,
                bindPopup: function (html) { this.popup = html; return this; },
                addTo: function (group) { group.layers.push(this); return this; },
            }),
        };

        vi.spyOn($, 'ajax').mockReturnValue({done: () => ({})});
        globalThis.getQueryParams = getQueryParams;
    });

    let currentFloor;
    let layerGroupStub;

    const line = (overrides = {}) => Object.assign({
        floor_id: 5, lat: -10, lng: 20, enemy_lat: -12, enemy_lng: 22,
        distance: 80, weighted_distance: 80, npc_id: 42, npc_name: 'Npc',
        enemy_id: 7, source: null,
        dungeon_route_public_key: null, dungeon_route_url: null,
    }, overrides);

    const group = (overrides = {}) => Object.assign({
        floor_id: 5, enemy_pack_id: 30136, enemy_pack_group: 64, enemy_ids: [1, 2, 3], npc_names: 'Npc',
        count: 400, route_count: 300, route_share: 0.87,
        engaged_centroid: {lat: -50, lng: 60}, mapped_centroid: {lat: -52, lng: 63},
        displacement: 44.2, direction_consistency: 0.91, shape_ratio: 0.94,
        first_seen: '2026-09-18T09:08:05+00:00', last_seen: '2026-09-22T15:20:02+00:00',
        verdict: 'displaced', low_volume: false, suggestion: 'Move it',
    }, overrides);

    function createInstance() {
        return new CommonMapsCombatlogrouteenemyresolutions('combatlogrouteenemyresolutions', 'common/maps/combatlogrouteenemyresolutions', {
            dungeonId: 123,
            mappingVersionId: 10,
            pageUrl: '/admin/tools/combatlog/route/enemy-resolutions',
            getEnemyResolutionsUrl: '/ajax/admin/combatlogroute/enemy-resolutions',
            linesUrl: '/ajax/admin/combatlogroute/enemy-resolutions/lines',
            showLinesSelector: '#show_lines',
            groupsUrl: '/ajax/admin/combatlogroute/enemy-resolutions/groups',
            showGroupsSelector: '#show_groups',
            verdictColors: {displaced: '#e74c3c', converged: '#3498db', scatter: '#95a5a6'},
            linePopupTexts: {
                npc: ':name (:id)',
                distance: 'Resolved to enemy :enemy, :distance yd away',
                weighted: 'Kill priority had the matcher judge it as :weighted yd',
                route: 'Open route :key',
                importedRoute: 'Recorded on :source, route :id there',
                noRoute: 'No route recorded for this match',
            },
            deleteUrl: '/ajax/admin/combatlogroute/enemy-resolutions',
            filterMappingVersionIdSelector: '#filter_mapping_version_id',
            filterNpcIdSelector: '#filter_npc_id',
            filterMetricSelector: '#filter_metric',
            filterMinDistanceSelector: '#filter_min_distance',
            clearButtonSelector: '#clear_button',
            summarySelector: '#summary',
            routesContainerSelector: '#routes_container',
            routesListSelector: '#routes_list',
            summaryText: 'Drawing :drawn of :total matches, worst cell :max yd',
            dependencies: [],
        });
    }

    test('activate_enablesTheHeatLayer', () => {
        const instance = createInstance();

        instance.activate();

        expect(dungeonMapStub.pluginHeat.toggle).toHaveBeenCalledWith(true);
    });

    /**
     * A cell's weight is a distance, so the renderer must keep the strongest of the cells it folds together rather
     * than adding them up - two adjacent moderate cells are not one severe one.
     */
    test('activate_putsTheHeatLayerInMaxCombineMode', () => {
        const instance = createInstance();

        instance.activate();

        expect(dungeonMapStub.pluginHeat.setCombineMode).toHaveBeenCalledWith('max');
    });

    /**
     * Registered filters are what SearchInlineBase restores from the query string, so a shared link to a hot spot
     * comes back with the same weighting and cut-off rather than silently resetting to the defaults.
     */
    test('filters_areRegisteredSoTheySurviveTheUrlRoundTrip', () => {
        const instance = createInstance();

        $('#filter_metric').val('max');
        $('#filter_min_distance').val('75');

        expect(Object.keys(instance.filters)).toEqual(expect.arrayContaining(['metric', 'min_distance']));
        expect(instance.filters.metric.getValue()).toBe('max');
        expect(instance.filters.min_distance.getValue()).toBe('75');

        instance.filters.metric.setValue('average');
        expect($('#filter_metric').val()).toBe('average');
    });

    /**
     * Registering a control as a filter is what restores it from the URL, but SearchFilterInput binds nothing on its
     * own - without a change binding the map would sit there showing the old weighting after the control moved.
     */
    test('metricChange_searchesAgain', () => {
        // The filter binds this._search at construction time, so the spy has to be in place before that
        const search   = vi.spyOn(CommonMapsCombatlogrouteenemyresolutions.prototype, '_search').mockImplementation(() => {});
        const instance = createInstance();
        instance.activate();
        search.mockClear();

        $('#filter_metric').val('max').trigger('change');

        expect(search).toHaveBeenCalledTimes(1);
        search.mockRestore();
    });

    test('minDistanceChange_searchesAgain', () => {
        const search   = vi.spyOn(CommonMapsCombatlogrouteenemyresolutions.prototype, '_search').mockImplementation(() => {});
        const instance = createInstance();
        instance.activate();
        search.mockClear();

        $('#filter_min_distance').val('75').trigger('change');

        expect(search).toHaveBeenCalledTimes(1);
        search.mockRestore();
    });

    test('activate_givenNpcIdsInTheUrl_restoresTheSelection', () => {
        const search = vi.spyOn(CommonMapsCombatlogrouteenemyresolutions.prototype, '_search').mockImplementation(() => {});
        globalThis.getQueryParams = () => ({npc_id: '123,456'});
        $('#filter_npc_id').append('<option value="123"></option><option value="456"></option><option value="789"></option>');

        createInstance().activate();

        expect($('#filter_npc_id').val()).toEqual(['123', '456']);
        search.mockRestore();
    });

    test('activate_givenTomSelectOnTheNpcControl_restoresThroughItSilently', () => {
        const search   = vi.spyOn(CommonMapsCombatlogrouteenemyresolutions.prototype, '_search').mockImplementation(() => {});
        const setValue = vi.fn();
        globalThis.getQueryParams = () => ({npc_id: '123,456'});
        $('#filter_npc_id')[0].tomselect = {setValue};

        createInstance().activate();

        expect(setValue).toHaveBeenCalledWith(['123', '456'], true);
        search.mockRestore();
    });

    test('clearButtonClick_sendsDungeonIdInDeleteRequest', () => {
        const instance = createInstance();

        instance.activate();
        $.ajax.mockClear();

        $('#clear_button').trigger('click');

        expect($.ajax).toHaveBeenCalledTimes(1);
        const ajaxCall = $.ajax.mock.calls[0][0];
        expect(ajaxCall.type).toBe('DELETE');
        expect(ajaxCall.url).toBe('/ajax/admin/combatlogroute/enemy-resolutions');
        expect(ajaxCall.data).toEqual({dungeon_id: 123});
    });

    test('loaderFn_givenHeatmapResponse_feedsTheLayerAndFillsTheSummary', () => {
        const instance = createInstance();
        instance.activate();

        instance.searchHandler.options.loaderFn(false, JSON.stringify({
            data: [{floor_id: 5, lat_lngs: [{lat: -10, lng: 20, weight: 48.5, count: 4}]}],
            weight_max: 48.5,
            resolution_count: 9,
            drawn_count: 4,
            grid_size_x: 300,
            grid_size_y: 200,
            dungeon_routes: [{public_key: 'abc', title: 'A route', url: '/route/abc'}],
        }));

        expect(dungeonMapStub.pluginHeat.setRawLatLngsPerFloor).toHaveBeenCalledWith(
            [{floor_id: 5, lat_lngs: [{lat: -10, lng: 20, weight: 48.5, count: 4}]}], null, null, 48.5, 300, 200
        );
        expect($('#summary').text()).toBe('Drawing 4 of 9 matches, worst cell 48.5 yd');
        expect($('#routes_list a').length).toBe(1);
        // jsdom has no layout, so :visible never matches - the inline style show() sets is what can be asserted
        expect($('#routes_container').css('display')).not.toBe('none');
    });

    test('search_alsoFetchesLinesWithTheSameFilters', () => {
        const instance = createInstance();
        $('#filter_npc_id').append('<option value="42" selected>x</option>');
        $('#filter_min_distance').val('75');

        instance.activate();

        const linesCall = $.ajax.mock.calls.find((call) => call[0].url === '/ajax/admin/combatlogroute/enemy-resolutions/lines');
        expect(linesCall).toBeDefined();
        expect(linesCall[0].data).toEqual({dungeon_id: 123, mapping_version_id: 10, npc_id: [42], min_distance: '75'});
    });

    test('redrawLines_drawsOnlyCurrentFloorLinesFromEngagementToEnemy', () => {
        const instance = createInstance();
        instance.activate();

        instance._lines = [line({floor_id: 5}), line({floor_id: 6}), line({floor_id: 5, lat: -30, lng: 40})];
        instance._redrawLines();

        expect(layerGroupStub.layers.length).toBe(2);
        expect(layerGroupStub.layers[0].latLngs).toEqual([[-10, 20], [-12, 22]]);
        expect(layerGroupStub.layers[1].latLngs).toEqual([[-30, 40], [-12, 22]]);
    });

    /**
     * The layer group survives a floor switch, so without clearing it by hand the previous floor's lines would stay
     * on the map on top of the new floor's.
     */
    test('floorChange_clearsTheLayerAndRedrawsForTheNewFloor', () => {
        const instance = createInstance();
        instance.activate();

        instance._lines = [line({floor_id: 5}), line({floor_id: 6, lat: -30, lng: 40})];
        instance._redrawLines();
        expect(layerGroupStub.layers.length).toBe(1);
        expect(layerGroupStub.layers[0].latLngs).toEqual([[-10, 20], [-12, 22]]);

        const floorChanged = stateStub.register.mock.calls.find((call) => call[0] === 'floorid:changed')[2];
        currentFloor = {id: 6};
        floorChanged();

        expect(layerGroupStub.layers.length).toBe(1);
        expect(layerGroupStub.layers[0].latLngs).toEqual([[-30, 40], [-12, 22]]);
    });

    test('redrawLines_givenCheckboxUnchecked_drawsNothing', () => {
        const instance = createInstance();
        instance.activate();
        $('#show_lines').prop('checked', false);

        instance._lines = [line()];
        instance._redrawLines();

        expect(layerGroupStub.layers).toEqual([]);
    });

    /**
     * Overlapping requests: only the latest may paint, so a slower earlier response is discarded when it returns.
     */
    test('fetchLines_givenStaleResponseArrivingLate_ignoresIt', () => {
        const instance = createInstance();
        instance.activate();

        // Two line requests in flight; resolve the SECOND first, then the first (stale) one
        const pending = [];
        $.ajax.mockImplementation((options) => ({done: (callback) => { if (options.url.endsWith('/lines')) pending.push(callback); return {}; }}));
        instance._fetchLines([1]);
        instance._fetchLines([2]);
        pending[1]({data: [line({npc_id: 2})]});
        pending[0]({data: [line({npc_id: 1})]});

        expect(instance._lines.map((entry) => entry.npc_id)).toEqual([2]);
    });

    test('getLineColor_givenSeverity_goesFromYellowThroughOrangeToRed', () => {
        const instance = createInstance();

        expect(instance._getLineColor(1)).toBe('#e74c3c');
        expect(instance._getLineColor(0.8)).toBe('#e74c3c');
        expect(instance._getLineColor(0.5)).toBe('#e67e22');
        expect(instance._getLineColor(0.1)).toBe('#f1c40f');
    });

    test('redrawLines_colorsEachLineByHowItComparesToTheWorstOne', () => {
        const instance = createInstance();
        instance.activate();

        instance._lines = [line({weighted_distance: 100}), line({weighted_distance: 20})];
        instance._redrawLines();

        expect(layerGroupStub.layers[0].options.color).toBe('#e74c3c');
        expect(layerGroupStub.layers[1].options.color).toBe('#f1c40f');
    });

    test('getLinePopupHtml_givenLocallyRecordedMatch_linksTheRoute', () => {
        const instance = createInstance();

        const html = instance._getLinePopupHtml(line({
            dungeon_route_public_key: 'abc123',
            dungeon_route_url: '/route/abc123',
        }));

        expect(html).toContain('href="/route/abc123"');
        expect(html).toContain('Open route abc123');
        expect(html).not.toContain('No route recorded');
    });

    /**
     * An imported row's dungeon_route_id belongs to the deployment it came from, so there is nothing to link to here -
     * its source and public key are what identify the route where it lives.
     */
    test('getLinePopupHtml_givenImportedMatch_namesTheSourceInsteadOfLinking', () => {
        const instance = createInstance();

        // The shape the endpoint really produces for an imported row: no local key, no local link, a remote id
        const html = instance._getLinePopupHtml(line({
            dungeon_route_id: 4242,
            dungeon_route_public_key: null,
            dungeon_route_url: null,
            source: 'production',
        }));

        expect(html).toContain('Recorded on production, route 4242 there');
        expect(html).not.toContain('<a href');
    });

    test('getLinePopupHtml_givenMatchWithoutARoute_saysSo', () => {
        const instance = createInstance();

        const html = instance._getLinePopupHtml(line());

        expect(html).toContain('No route recorded for this match');
    });

    /**
     * The recorded distance pair is what says kill priority moved this match, and it says it about the match as it
     * happened rather than about the enemy as it stands today.
     */
    test('getLinePopupHtml_givenWeightedDistanceDifferingFromTheRealOne_showsWhatTheMatcherJudged', () => {
        const instance = createInstance();

        const weighted = instance._getLinePopupHtml(line({distance: 80, weighted_distance: 40}));
        const unweighted = instance._getLinePopupHtml(line({distance: 80, weighted_distance: 80}));

        expect(weighted).toContain('40');
        expect(weighted).toContain('judge');
        expect(unweighted).not.toContain('judge');
    });

    test('redrawLines_bindsThePopupToEachLine', () => {
        const instance = createInstance();
        instance.activate();

        instance._lines = [line({npc_name: 'Deadly Thing'})];
        instance._redrawLines();

        expect(layerGroupStub.layers[0].popup).toContain('Deadly Thing (42)');
    });

    test('search_alsoFetchesGroupsWithTheSameFilters', () => {
        const instance = createInstance();
        $('#filter_npc_id').append('<option value="42" selected>x</option>');
        $('#filter_min_distance').val('75');

        instance.activate();

        const groupsCall = $.ajax.mock.calls.find((call) => call[0].url === '/ajax/admin/combatlogroute/enemy-resolutions/groups');
        expect(groupsCall).toBeDefined();
        expect(groupsCall[0].data).toEqual({dungeon_id: 123, mapping_version_id: 10, npc_id: [42], min_distance: '75'});
    });

    test('redrawGroups_drawsOnlyCurrentFloorGroupsAsAnArrowFromMappedToEngaged', () => {
        const instance = createInstance();
        instance.activate();

        instance._groups = [group({floor_id: 5}), group({floor_id: 6})];
        instance._redrawGroups();

        // A line and a marker at either end, for the one group on this floor
        expect(layerGroupStub.layers.length).toBe(3);
        expect(layerGroupStub.layers[0].latLngs).toEqual([[-52, 63], [-50, 60]]);
        expect(layerGroupStub.layers[0].options.color).toBe('#e74c3c');
        expect(layerGroupStub.layers[1].latLng).toEqual([-52, 63]);
        expect(layerGroupStub.layers[2].latLng).toEqual([-50, 60]);
    });

    test('redrawGroups_givenLowVolumeGroup_fadesIt', () => {
        const instance = createInstance();
        instance.activate();

        instance._groups = [group({low_volume: true}), group({low_volume: false})];
        instance._redrawGroups();

        expect(layerGroupStub.layers[0].options.opacity).toBeLessThan(layerGroupStub.layers[3].options.opacity);
    });

    test('redrawGroups_givenCheckboxUnchecked_drawsNothing', () => {
        const instance = createInstance();
        instance.activate();
        $('#show_groups').prop('checked', false);

        instance._groups = [group()];
        instance._redrawGroups();

        expect(layerGroupStub.layers).toEqual([]);
    });

    test('fetchGroups_givenStaleResponseArrivingLate_ignoresIt', () => {
        const instance = createInstance();
        instance.activate();

        const pending = [];
        $.ajax.mockImplementation((options) => ({done: (callback) => { if (options.url.endsWith('/groups')) pending.push(callback); return {}; }}));
        instance._fetchGroups([1]);
        instance._fetchGroups([2]);
        pending[1]({data: [group({enemy_pack_id: 2})]});
        pending[0]({data: [group({enemy_pack_id: 1})]});

        expect(instance._groups.map((entry) => entry.enemy_pack_id)).toEqual([2]);
    });

    test('getGroupPopupHtml_givenPackGroup_namesThePackVerdictAndSuggestion', () => {
        const instance = createInstance();

        const html = instance._getGroupPopupHtml(group());

        expect(html).toContain('Pack 64 (id 30136)');
        expect(html).toContain('Mapped in the wrong place');
        expect(html).toContain('400 long matches in 300 routes (87% of routes)');
        expect(html).toContain('Engaged 44 yd from where it is mapped');
        expect(html).toContain('Move it');
        expect(html).not.toContain('Few routes');
    });

    test('getGroupPopupHtml_givenPacklessLowVolumeGroup_namesTheEnemyAndWarns', () => {
        const instance = createInstance();

        const html = instance._getGroupPopupHtml(group({enemy_pack_id: null, enemy_pack_group: null, enemy_ids: [77], low_volume: true, shape_ratio: null}));

        expect(html).toContain('Enemy 77');
        expect(html).toContain('shape ratio -');
        expect(html).toContain('Few routes - treat with caution');
    });

    test('getGroupPopupHtml_escapesServerText', () => {
        const instance = createInstance();

        const html = instance._getGroupPopupHtml(group({npc_names: '<b>x</b>', suggestion: '<img src=x>'}));

        expect(html).not.toContain('<b>x</b>');
        expect(html).not.toContain('<img');
    });
});
