global.L = require('leaflet');
global.window.L = global.L;

const {MapTileLayer} = require('./maptilelayer');

/**
 * jsdom has no 3D transform support, so Leaflet would position everything with top/left. Forcing
 * any3d makes L.DomUtil.setPosition take the translate3d path it takes in a real browser.
 */
function addTileWith3d(layer) {
    const originalAny3d = L.Browser.any3d;
    L.Browser.any3d = true;

    try {
        const container = document.createElement('div');
        const coords = L.point(2, 1);
        coords.z = 3;

        layer._tiles = {};
        layer._level = {origin: L.point(100, 50)};
        layer._wrapCoords = (c) => c;
        layer.createTile = (c, done) => document.createElement('img');
        layer._addTile(coords, container);

        return layer._tiles[layer._tileCoordsToKey(coords)].el;
    } finally {
        L.Browser.any3d = originalAny3d;
    }
}

describe('MapTileLayer._addTile', () => {
    it('addTile_givenLeafletTileLayer_positionsTileWithTranslate3d', () => {
        const layer = L.tileLayer('{z}/{x}_{y}.webp', {tileSize: L.point(384, 256)});

        const tile = addTileWith3d(layer);

        expect(tile.style.transform).toBe('translate3d(668px,206px,0)');
    });

    it('addTile_given3dBrowser_positionsTileWithTranslate2d', () => {
        const layer = new MapTileLayer('{z}/{x}_{y}.webp', {tileSize: L.point(384, 256)});

        const tile = addTileWith3d(layer);

        expect(tile.style.transform).toBe('translate(668px,206px)');
        expect(L.DomUtil.getPosition(tile)).toEqual(L.point(668, 206));
    });
});
