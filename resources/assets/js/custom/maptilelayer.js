/**
 * The dungeon map's tile layer: an L.TileLayer that positions its tiles with a 2D translate() instead
 * of Leaflet's translate3d.
 *
 * A translate3d gives every tile a compositor layer of its own, and Chrome antialiases each layer's
 * edges separately. Leaflet's `mix-blend-mode: plus-lighter` on tiles only sums those half-covered
 * edge pixels back to full coverage at some device pixel ratios, so at browser zooms such as 90% or
 * 110% every tile edge shows as a seam. Positioned in 2D, all tiles of a zoom level raster into their
 * tile container's single layer and meet without seams. Leaflet 2 positions its tiles the same way.
 */
let MapTileLayer = L.TileLayer.extend({
    _addTile: function (coords, container) {
        L.TileLayer.prototype._addTile.call(this, coords, container);

        let tile = this._tiles[this._tileCoordsToKey(coords)];
        if (L.Browser.any3d && tile) {
            let position = L.DomUtil.getPosition(tile.el);
            tile.el.style.transform = `translate(${position.x}px,${position.y}px)`;
        }
    },
});

if (typeof module !== 'undefined' && module.exports) {
    module.exports = {MapTileLayer};
}
