/**
 * @typedef {Object} EnemyPathAppearance
 * @property outerDiameter {Number} Diameter including the border, in CSS pixels.
 * @property borderWidth {Number}
 * @property borderColor {String}
 * @property innerMargin {Number} Ring between the border and the image through which outerBackgroundColor shows.
 * @property outerBackgroundColor {String|null}
 * @property opacity {Number} 0 to 1.
 * @property sprite {EnemyCanvasSprite}
 */

/**
 * One enemy drawn on the shared enemy L.Canvas: the outer circle and its fill, the pre-rendered
 * image layer from the sprite cache and the border. A CircleMarker underneath, so Leaflet's canvas
 * renderer projects, culls and hit-tests it (`_containsPoint`) like any other circle.
 */
let EnemyPath = L.CircleMarker.extend({
    options: {
        stroke: false,
        fill: false,
        interactive: true,
        bubblingMouseEvents: true,
    },

    /**
     * @param latLng {L.LatLng}
     * @param options {Object}
     * @param options.spriteCache {EnemyCanvasSpriteCache}
     */
    initialize: function (latLng, options) {
        L.CircleMarker.prototype.initialize.call(this, latLng, options);

        /** @type {EnemyPathAppearance|null} */
        this._appearance = null;
        /** @type {function(L.Point, Number)|null} */
        this._onProjected = null;
    },

    /**
     * @param appearance {EnemyPathAppearance}
     */
    setAppearance: function (appearance) {
        this._appearance = appearance;

        let radius = appearance.outerDiameter / 2;
        if (radius !== this._radius) {
            this.setRadius(radius);
        } else {
            this.redraw();
        }
    },

    /**
     * @param callback {function(L.Point, Number)|null} Receives the layer point and radius each time the path is projected.
     */
    setProjectedCallback: function (callback) {
        this._onProjected = callback;
    },

    _project: function () {
        L.CircleMarker.prototype._project.call(this);

        if (this._onProjected !== null) {
            this._onProjected(this._point, this._radius);
        }
    },

    _updatePath: function () {
        let renderer = this._renderer;
        if (!renderer._drawing || this._empty() || this._appearance === null) {
            return;
        }

        let appearance = this._appearance;
        let ctx = renderer._ctx;
        let x = this._point.x;
        let y = this._point.y;
        let radius = appearance.outerDiameter / 2;
        let paddingRadius = radius - appearance.borderWidth;

        ctx.globalAlpha = appearance.opacity;

        if (appearance.outerBackgroundColor !== null && paddingRadius > 0) {
            ctx.beginPath();
            ctx.arc(x, y, paddingRadius, 0, Math.PI * 2);
            ctx.fillStyle = appearance.outerBackgroundColor;
            ctx.fill();
        }

        let spriteDiameter = EnemyCanvasSpriteCache.quantiseSize(paddingRadius * 2 - appearance.innerMargin * 2);
        let sprite = this.options.spriteCache.get(appearance.sprite, spriteDiameter);
        if (sprite !== null) {
            ctx.drawImage(sprite, x - spriteDiameter / 2, y - spriteDiameter / 2, spriteDiameter, spriteDiameter);
        } else if (appearance.sprite.backgroundColors[0] !== null) {
            ctx.beginPath();
            ctx.arc(x, y, spriteDiameter / 2, 0, Math.PI * 2);
            ctx.fillStyle = appearance.sprite.backgroundColors[0];
            ctx.fill();
        }

        if (appearance.borderWidth > 0) {
            ctx.beginPath();
            ctx.arc(x, y, radius - appearance.borderWidth / 2, 0, Math.PI * 2);
            ctx.lineWidth = appearance.borderWidth;
            ctx.strokeStyle = appearance.borderColor;
            ctx.stroke();
        }

        ctx.globalAlpha = 1;
    },
});

/**
 * Holds enemy markers exactly like an L.LayerGroup - so hasLayer(), visibility and every existing
 * caller keep working on the markers - but puts each marker's EnemyPath on the map instead of the
 * marker itself, so no enemy DOM is ever created.
 */
let EnemyCanvasLayerGroup = L.LayerGroup.extend({
    /**
     * @param layers {L.Layer[]}
     * @param options {Object}
     * @param options.resolvePath {function(L.Marker): EnemyPath}
     */
    initialize: function (layers, options) {
        this._resolvePath = options.resolvePath;
        L.LayerGroup.prototype.initialize.call(this, layers, options);
    },

    addLayer: function (layer) {
        let id = this.getLayerId(layer);
        this._layers[id] = layer;

        if (this._map) {
            this._map.addLayer(this._getRenderedLayer(layer));
        }

        return this;
    },

    removeLayer: function (layer) {
        let id = layer in this._layers ? layer : this.getLayerId(layer);

        if (this._map && this._layers[id]) {
            this._map.removeLayer(this._getRenderedLayer(this._layers[id]));
        }

        delete this._layers[id];

        return this;
    },

    onAdd: function (map) {
        this.eachLayer(function (layer) {
            map.addLayer(this._getRenderedLayer(layer));
        }, this);
    },

    onRemove: function (map) {
        this.eachLayer(function (layer) {
            map.removeLayer(this._getRenderedLayer(layer));
        }, this);
    },

    /**
     * @param layer {L.Layer}
     * @returns {L.Layer}
     * @private
     */
    _getRenderedLayer: function (layer) {
        return layer instanceof L.Marker ? this._resolvePath(layer) : layer;
    },
});
