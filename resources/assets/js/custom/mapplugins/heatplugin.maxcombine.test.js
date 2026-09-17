// The renderer folds every point that lands in the same screen space bucket into one. The stock implementation adds
// their values up, which is right for counts and wrong for distances - see HeatPlugin::setCombineMode.

global.MapPlugin = class MapPlugin {
    constructor(map) {
        this.map = map;
    }
};
global.MapContextDungeonExplore = class MapContextDungeonExplore {
};
global.COMBAT_LOG_EVENT_DATA_TYPE_PLAYER_POSITION = 'player_position';
global.COMBAT_LOG_EVENT_DATA_TYPE_ENEMY_POSITION = 'enemy_position';
global.COMBAT_LOG_EVENT_DATA_TYPE_ENEMY_FAILURE = 'enemy_failure';
global.HEAT_COMBINE_MODE_SUM = 'sum';
global.HEAT_COMBINE_MODE_MAX = 'max';
global.isMobile = () => true;

const HeatPlugin = require('./heatplugin');
const {heatLayerRedrawCombiningByMax} = HeatPlugin;

/**
 * A stand-in for the pieces of L.HeatLayer the redraw touches: bucket size comes from _heat._r (radius / 2 = 10 px
 * here), and container points are the raw latLng pairs so a test can place points in or out of one bucket.
 */
function createLayer(latLngs) {
    const heat = {
        _r: 20,
        maxValue: null,
        drawnData: null,
        max(value) {
            this.maxValue = value;
            return this;
        },
        data(data) {
            this.drawnData = data;
            return this;
        },
        draw() {
            return this;
        },
    };

    return {
        _latlngs: latLngs,
        _heat: heat,
        options: {minOpacity: 0.05},
        _map: {
            getSize: () => ({x: 800, y: 600, add: ([x, y]) => ({x: 800 + x, y: 600 + y})}),
            _getMapPanePos: () => ({x: 0, y: 0}),
            latLngToContainerPoint: ([x, y]) => ({x, y}),
        },
        _redraw: heatLayerRedrawCombiningByMax,
    };
}

describe('heatLayerRedrawCombiningByMax', () => {
    beforeEach(() => {
        global.L = {
            point: ([x, y]) => ({x, y}),
            Bounds: class {
                contains() {
                    return true;
                }
            },
        };
    });

    test('redraw_givenTwoPointsInOneBucket_keepsTheStrongestInsteadOfTheirSum', () => {
        const layer = createLayer([[100, 100, 60], [101, 101, 40]]);

        layer._redraw();

        expect(layer._heat.drawnData).toHaveLength(1);
        expect(layer._heat.drawnData[0][2]).toBe(60);
        expect(layer._heat.maxValue).toBe(60);
    });

    test('redraw_givenPointsInDifferentBuckets_keepsThemApart', () => {
        const layer = createLayer([[100, 100, 60], [400, 400, 40]]);

        layer._redraw();

        expect(layer._heat.drawnData).toHaveLength(2);
        expect(layer._heat.drawnData.map((entry) => entry[2]).sort((a, b) => a - b)).toEqual([40, 60]);
        expect(layer._heat.maxValue).toBe(60);
    });

    test('setCombineMode_givenMax_installsTheOverrideOnTheLayerAndRestoresItForSum', () => {
        const heatLayer = {redraw: () => {}};
        const plugin    = Object.create(HeatPlugin.prototype);
        plugin.heatLayer = heatLayer;
        plugin.combineMode = global.HEAT_COMBINE_MODE_SUM;

        plugin.setCombineMode(global.HEAT_COMBINE_MODE_MAX);
        expect(heatLayer._redraw).toBe(heatLayerRedrawCombiningByMax);

        plugin.setCombineMode(global.HEAT_COMBINE_MODE_SUM);
        expect(Object.prototype.hasOwnProperty.call(heatLayer, '_redraw')).toBe(false);
    });
});
