globalThis.$ = globalThis.jQuery = require('jquery');

const {InlineCode} = require('../../inlinecode');
globalThis.InlineCode = InlineCode;

const {getQueryParams} = require('../../../util');
globalThis.getQueryParams = getQueryParams;

const {SearchFilter} = require('./filters/filter');
globalThis.SearchFilter = SearchFilter;

const {SearchFilterPassThrough} = require('./filters/filterpassthrough');
globalThis.SearchFilterPassThrough = SearchFilterPassThrough;

const {SearchFilterInput} = require('./filters/filterinput');
globalThis.SearchFilterInput = SearchFilterInput;

const {SearchFilterInputChange} = require('./filters/filterinputchange');
globalThis.SearchFilterInputChange = SearchFilterInputChange;

globalThis.HEAT_COMBINE_MODE_MAX = 'max';

const {SearchParams} = require('./searchparams');
globalThis.SearchParams = SearchParams;

const {SearchHandler} = require('./searchhandler');
globalThis.SearchHandler = SearchHandler;

const {SearchInlineBase} = require('../../base/searchinlinebase');
globalThis.SearchInlineBase = SearchInlineBase;

const {CommonMapsCombatlogrouteenemyresolutions} = require('./searchhandlercombatlogrouteenemyresolutions');

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
        `;

        dungeonMapStub = {
            pluginHeat: {
                toggle: vi.fn(),
                setCombineMode: vi.fn(),
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

        vi.spyOn($, 'ajax').mockReturnValue({done: () => ({})});
        globalThis.getQueryParams = getQueryParams;
    });

    function createInstance() {
        return new CommonMapsCombatlogrouteenemyresolutions('combatlogrouteenemyresolutions', 'common/maps/combatlogrouteenemyresolutions', {
            dungeonId: 123,
            mappingVersionId: 10,
            pageUrl: '/admin/tools/combatlog/route/enemy-resolutions',
            getEnemyResolutionsUrl: '/ajax/admin/combatlogroute/enemy-resolutions',
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
});
