/**
 * @typedef {Object} EnemyPathAppearance
 * @property outerDiameter {Number} Diameter including the border, in CSS pixels.
 * @property borderWidth {Number}
 * @property borderColor {String}
 * @property innerMargin {Number} Ring between the border and the image through which outerBackgroundColor shows.
 * @property outerBackgroundColor {String|null}
 * @property opacity {Number} 0 to 1.
 * @property sprite {EnemyCanvasSprite}
 * @property [badges] {Array.<{box: EnemyCanvasBoxStyle, left: Number, top: Number}>} Positioned from the top
 *           left corner of the enemy's outer circle, drawn on top of everything else.
 * @property [selection] {EnemyCanvasBoxStyle|null} The halo around a selectable enemy, centred on it.
 */

/**
 * One enemy drawn on the shared enemy L.Canvas: the aggressiveness ring, the pre-rendered image
 * layer from the sprite cache (with the state border and text), the border, the selection halo and
 * badges. None of them overlap except the badges, so the enemy's opacity reads as one layer's. A
 * CircleMarker underneath, so Leaflet's canvas renderer projects, culls and hit-tests it
 * (`_containsPoint`) like any other circle.
 */
let EnemyPath = L.CircleMarker.extend({
    options: {
        stroke: false,
        fill: false,
        interactive: false,
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

        let radius = EnemyPath.getDrawnRadius(appearance);
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
            let appearance = this._appearance;
            this._onProjected(this._point, appearance === null ? this._radius : appearance.outerDiameter / 2);
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

        let spriteDiameter = EnemyCanvasSpriteCache.quantiseSize(paddingRadius * 2 - appearance.innerMargin * 2);

        if (appearance.outerBackgroundColor !== null && paddingRadius > spriteDiameter / 2) {
            ctx.beginPath();
            ctx.arc(x, y, paddingRadius, 0, Math.PI * 2);
            ctx.arc(x, y, spriteDiameter / 2, 0, Math.PI * 2, true);
            ctx.fillStyle = appearance.outerBackgroundColor;
            ctx.fill();
        }

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

        let selection = appearance.selection ?? null;
        if (selection !== null && selection.borderWidth > 0 && selection.borderColor !== null) {
            let halfWidth = selection.width / 2 - selection.borderWidth / 2;
            let halfHeight = selection.height / 2 - selection.borderWidth / 2;
            EnemyCanvasSpriteCache.roundedRect(
                ctx, x - halfWidth, y - halfHeight, halfWidth * 2, halfHeight * 2,
                Math.max(0, Math.min(selection.borderRadius, halfWidth, halfHeight))
            );
            ctx.lineWidth = selection.borderWidth;
            ctx.strokeStyle = selection.borderColor;
            ctx.setLineDash(selection.borderDashed ?
                EnemyCanvasSpriteCache.getDashPattern((halfWidth + halfHeight) * 4, selection.borderWidth) : []);
            ctx.stroke();
            ctx.setLineDash([]);
        }

        let badges = appearance.badges ?? [];
        let left = x - radius;
        let top = y - radius;
        for (let i = 0; i < badges.length; i++) {
            let badge = badges[i];
            let badgeCanvas = this.options.spriteCache.getBadge(badge.box);
            if (badgeCanvas !== null) {
                ctx.drawImage(badgeCanvas, left + badge.left, top + badge.top, badge.box.width, badge.box.height);
            }
        }

        ctx.globalAlpha = 1;
    },
});

/**
 * Leaflet only redraws and culls the part of the canvas inside a path's radius, so badges that hang
 * over the enemy's edge and the selection halo around it must fall inside it too.
 * @param appearance {EnemyPathAppearance}
 * @returns {Number} The radius Leaflet projects, redraws and culls the path with.
 */
EnemyPath.getDrawnRadius = function (appearance) {
    let radius = appearance.outerDiameter / 2;
    let reach = radius;

    let selection = appearance.selection ?? null;
    if (selection !== null) {
        reach = Math.max(reach, Math.hypot(selection.width, selection.height) / 2);
    }

    let badges = appearance.badges ?? [];
    for (let i = 0; i < badges.length; i++) {
        let badge = badges[i];
        let corners = [
            [badge.left, badge.top],
            [badge.left + badge.box.width, badge.top + badge.box.height],
            [badge.left, badge.top + badge.box.height],
            [badge.left + badge.box.width, badge.top],
        ];
        for (let j = 0; j < corners.length; j++) {
            reach = Math.max(reach, Math.hypot(corners[j][0] - radius, corners[j][1] - radius));
        }
    }

    return reach;
};

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

if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        EnemyPath,
        EnemyCanvasLayerGroup,
    };
}
