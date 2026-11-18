// ---------------------------------------------------------------------------
// The walk graph group fetches a floor's segments once, the first time it is shown on that floor, and draws them
// as one canvas polyline per shading band.
// ---------------------------------------------------------------------------

const {Signalable} = require('../signalable');
globalThis.Signalable = Signalable;

const {MapObjectGroup} = require('./mapobjectgroup');
globalThis.MapObjectGroup = MapObjectGroup;

const {WalkGraphMapObjectGroup} = require('./walkgraphmapobjectgroup');

globalThis.MAP_OBJECT_GROUP_WALK_GRAPH = 'walkgraph';
globalThis.LEAFLET_PANE_OVERLAY = 'overlayPane';
globalThis.c = {
    map: {
        walkgraph: {
            color: '#00e5ff',
            bands: [
                {weight: 1, opacity: 0.2},
                {weight: 2, opacity: 0.6},
                {weight: 3, opacity: 1},
            ],
        },
    },
};

describe('WalkGraphMapObjectGroup', () => {
    let currentFloorId;
    let ajaxCalls;

    beforeEach(() => {
        vi.useFakeTimers();
        currentFloorId = 10;
        ajaxCalls = [];

        globalThis.getState = () => ({getCurrentFloor: () => ({id: currentFloorId})});
        globalThis.$ = {ajax: vi.fn((options) => ajaxCalls.push(options))};
        globalThis.L = {
            canvas: vi.fn((options) => ({type: 'canvas', options})),
            polyline: vi.fn((latLngs, options) => ({type: 'polyline', latLngs, options})),
        };
        globalThis.showInfoNotification = vi.fn();
        globalThis.lang = {get: vi.fn((key) => key)};
    });

    /**
     * A loaded group whose layer group records what is drawn. Object.create skips the constructor, which wants a
     * real map and manager.
     *
     * @returns {WalkGraphMapObjectGroup}
     */
    function buildLoadedGroup() {
        const group = Object.create(WalkGraphMapObjectGroup.prototype);
        group._signals = [];
        group._cleanedUp = false;
        group._responsesByFloorId = {};
        group._requestedFloorIds = {};
        group._drawnLayerGroup = null;
        group._drawnFloorId = null;
        group._canvasRenderer = null;
        group._initialized = true;
        group._visible = false;
        group.objects = {};
        group.manager = {map: {leafletMap: {hasLayer: () => false}}};
        group.layerGroup = buildLayerGroup();

        return group;
    }

    function buildLayerGroup() {
        return {
            layers: [],
            addLayer(layer) {
                this.layers.push(layer);
            },
            clearLayers() {
                this.layers = [];
            },
        };
    }

    afterEach(() => {
        vi.useRealTimers();
    });

    /**
     * Shows the group and lets its deferred fetch run.
     *
     * @param group {WalkGraphMapObjectGroup}
     */
    function show(group) {
        group.setVisibility(true);
        vi.runAllTimers();
    }

    function respond(ajaxCall, response) {
        ajaxCall.success(response);
    }

    it('setVisibility_givenFloorShownForTheFirstTime_fetchesItsSegments', () => {
        // Arrange
        const group = buildLoadedGroup();

        // Act
        show(group);

        // Assert
        expect(ajaxCalls).toHaveLength(1);
        expect(ajaxCalls[0].url).toBe('/ajax/floor/10/walkgraph');
    });

    it('setVisibility_givenHidden_fetchesNothing', () => {
        // Arrange
        const group = buildLoadedGroup();

        // Act
        group.setVisibility(false);

        // Assert
        expect(ajaxCalls).toHaveLength(0);
    });

    it('setVisibility_givenHiddenAgainBeforeTheFetchRuns_fetchesNothing', () => {
        // Arrange - page load shows every group once, then applies the user's hidden groups
        const group = buildLoadedGroup();
        group.setVisibility(true);

        // Act
        group.setVisibility(false);
        vi.runAllTimers();

        // Assert
        expect(ajaxCalls).toHaveLength(0);
    });

    it('setVisibility_givenFloorAlreadyFetched_doesNotFetchItAgain', () => {
        // Arrange
        const group = buildLoadedGroup();
        show(group);
        respond(ajaxCalls[0], {run_count: 20, min_runs: 15, max_run_count: 1, segments: []});
        group.layerGroup = buildLayerGroup();

        // Act
        show(group);

        // Assert
        expect(ajaxCalls).toHaveLength(1);
    });

    it('setVisibility_givenAnotherFloor_fetchesThatFloor', () => {
        // Arrange
        const group = buildLoadedGroup();
        show(group);
        respond(ajaxCalls[0], {run_count: 20, min_runs: 15, max_run_count: 1, segments: []});
        currentFloorId = 11;

        // Act
        show(group);

        // Assert
        expect(ajaxCalls).toHaveLength(2);
        expect(ajaxCalls[1].url).toBe('/ajax/floor/11/walkgraph');
    });

    it('fetchSuccess_givenSegments_drawsOneCanvasPolylinePerBandShadedByRunCount', () => {
        // Arrange - 1 run falls in the faintest band, 50 runs (the maximum) in the strongest
        const group = buildLoadedGroup();
        show(group);

        // Act
        respond(ajaxCalls[0], {
            run_count: 50,
            min_runs: 15,
            max_run_count: 50,
            segments: [
                [1, -10, 20, -11, 21],
                [50, -30, 40, -31, 41],
                [50, -50, 60, -51, 61],
            ],
        });

        // Assert
        expect(group.layerGroup.layers).toHaveLength(2);
        const [faintest, strongest] = group.layerGroup.layers;
        expect(faintest.latLngs).toEqual([[[-10, 20], [-11, 21]]]);
        expect(faintest.options).toMatchObject({weight: 1, opacity: 0.2, color: '#00e5ff', interactive: false});
        expect(strongest.latLngs).toEqual([[[-30, 40], [-31, 41]], [[-50, 60], [-51, 61]]]);
        expect(strongest.options).toMatchObject({weight: 3, opacity: 1});
        expect(faintest.options.renderer).toBe(strongest.options.renderer);
        expect(L.canvas).toHaveBeenCalledTimes(1);
    });

    it('fetchSuccess_givenNoSegments_drawsNothing', () => {
        // Arrange
        const group = buildLoadedGroup();
        show(group);

        // Act
        respond(ajaxCalls[0], {run_count: 0, min_runs: 15, max_run_count: 0, segments: []});

        // Assert
        expect(group.layerGroup.layers).toHaveLength(0);
        expect(showInfoNotification).not.toHaveBeenCalled();
    });

    it('fetchSuccess_givenFloorChangedWhileLoading_doesNotDrawTheOldFloor', () => {
        // Arrange
        const group = buildLoadedGroup();
        show(group);
        currentFloorId = 11;

        // Act
        respond(ajaxCalls[0], {run_count: 20, min_runs: 15, max_run_count: 1, segments: [[1, -10, 20, -11, 21]]});

        // Assert
        expect(group.layerGroup.layers).toHaveLength(0);
    });

    it('fetchSuccess_givenFewerRunsThanNeeded_saysTheGraphIsIncomplete', () => {
        // Arrange
        const group = buildLoadedGroup();
        show(group);

        // Act
        respond(ajaxCalls[0], {run_count: 4, min_runs: 15, max_run_count: 1, segments: [[1, -10, 20, -11, 21]]});

        // Assert
        expect(lang.get).toHaveBeenCalledWith('js.walkgraph_incomplete', {runCount: 4, minRuns: 15});
        expect(showInfoNotification).toHaveBeenCalledTimes(1);
    });

    it('fetchSuccess_givenEnoughRuns_saysNothing', () => {
        // Arrange
        const group = buildLoadedGroup();
        show(group);

        // Act
        respond(ajaxCalls[0], {run_count: 15, min_runs: 15, max_run_count: 1, segments: [[1, -10, 20, -11, 21]]});

        // Assert
        expect(group.layerGroup.layers).toHaveLength(1);
        expect(showInfoNotification).not.toHaveBeenCalled();
    });

    it('fetchError_givenFailedRequest_fetchesAgainNextTime', () => {
        // Arrange
        const group = buildLoadedGroup();
        show(group);
        ajaxCalls[0].error();

        // Act
        show(group);

        // Assert
        expect(ajaxCalls).toHaveLength(2);
    });

    it('onBeforeRefresh_givenDrawnFloor_clearsItAndRedrawsIntoTheNextLayerGroup', () => {
        // Arrange - a floor change: the old layer group is emptied, the map builds a new one
        const group = buildLoadedGroup();
        show(group);
        respond(ajaxCalls[0], {run_count: 20, min_runs: 15, max_run_count: 1, segments: [[1, -10, 20, -11, 21]]});
        const oldLayerGroup = group.layerGroup;

        // Act
        group._onBeforeRefresh();
        group.layerGroup = buildLayerGroup();
        show(group);

        // Assert
        expect(oldLayerGroup.layers).toHaveLength(0);
        expect(group.layerGroup.layers).toHaveLength(1);
        expect(ajaxCalls).toHaveLength(1);
    });
});
