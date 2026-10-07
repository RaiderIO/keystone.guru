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
 * CircleMarker underneath, so Leaflet's canvas renderer projects and culls it like any other circle,
 * and hit-tests it on what is drawn (`_containsPoint`). Mouse events carry the enemy's own position
 * and do not bubble to the map, as they do on an L.Marker, so listeners and popups cannot tell it
 * from a DOM enemy.
 */
let EnemyPath = L.CircleMarker.extend({
    options: {
        stroke: false,
        fill: false,
        interactive: true,
        bubblingMouseEvents: false,
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

    /**
     * The map gives a path with a radius over 10px the mouse position, and an L.Marker its own.
     * @inheritDoc
     */
    fire: function (type, data, propagate) {
        if (this._map && data && data.originalEvent && data.latlng) {
            data.latlng = this.getLatLng();
            data.layerPoint = this._map.latLngToLayerPoint(data.latlng);
            data.containerPoint = this._map.layerPointToContainerPoint(data.layerPoint);
        }

        return L.CircleMarker.prototype.fire.call(this, type, data, propagate);
    },

    /**
     * The drawn circle and the badges hanging over its edge, as the DOM icon's children catch the
     * mouse too. Not the selection halo, nor the rest of the radius Leaflet culls with.
     * @param point {L.Point} A layer point.
     * @returns {Boolean}
     */
    _containsPoint: function (point) {
        let appearance = this._appearance;
        if (appearance === null || !this._point) {
            return false;
        }

        let radius = appearance.outerDiameter / 2;
        let dx = point.x - this._point.x;
        let dy = point.y - this._point.y;
        if (dx * dx + dy * dy <= radius * radius) {
            return true;
        }

        let badges = appearance.badges ?? [];
        for (let i = 0; i < badges.length; i++) {
            let badge = badges[i];
            let left = badge.left - radius;
            let top = badge.top - radius;
            if (dx >= left && dx <= left + badge.box.width && dy >= top && dy <= top + badge.box.height) {
                return true;
            }
        }

        return false;
    },

    /**
     * The top of the enemy, where EnemyVisual anchors the DOM icon's tooltip.
     * @returns {L.Point}
     */
    _getTooltipAnchor: function () {
        return L.point(0, this._appearance === null ? 0 : -this._appearance.outerDiameter / 2);
    },

    /**
     * The top of the enemy, where EnemyVisual anchors the DOM icon's popup.
     * @returns {L.Point}
     */
    _getPopupAnchor: function () {
        return this._getTooltipAnchor();
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
 * The enemy canvas sits above the overlay pane's pulls and patrols, and Leaflet's canvas renderer
 * never passes a mouse event on to what lies underneath it. So the canvas only takes pointer events
 * while the mouse is over an enemy; everywhere else they fall through to the layers below.
 */
let EnemyCanvasRenderer = L.Canvas.extend({
    onAdd: function () {
        L.Canvas.prototype.onAdd.call(this);

        this._capturesPointer = null;
        this._setCapturesPointer(false);
        this._invalidateContainerRect();
        L.DomEvent.on(this._map.getContainer(), 'mousemove', this._onMapContainerMouseMove, this);
        L.DomEvent.on(this._map.getContainer(), 'mouseenter', this._invalidateContainerRect, this);
        L.DomEvent.on(window, 'scroll resize', this._invalidateContainerRect, this);
    },

    onRemove: function () {
        L.DomEvent.off(this._map.getContainer(), 'mousemove', this._onMapContainerMouseMove, this);
        L.DomEvent.off(this._map.getContainer(), 'mouseenter', this._invalidateContainerRect, this);
        L.DomEvent.off(window, 'scroll resize', this._invalidateContainerRect, this);

        L.Canvas.prototype.onRemove.call(this);
    },

    getEvents: function () {
        let events = L.Canvas.prototype.getEvents.call(this);
        events.resize = this._invalidateContainerRect;

        return events;
    },

    /**
     * Map#mouseEventToLayerPoint() reads the container's layout on every call, and the mousemove
     * listeners that run ahead of it leave the layout dirty: a forced layout per mouse move.
     * @param event {MouseEvent}
     * @returns {L.Point}
     */
    mouseEventToLayerPoint: function (event) {
        if (this._containerRect === null) {
            let container = this._map.getContainer();
            let rect = container.getBoundingClientRect();
            this._containerRect = {
                left: rect.left,
                top: rect.top,
                scaleX: rect.width / container.offsetWidth || 1,
                scaleY: rect.height / container.offsetHeight || 1,
                clientLeft: container.clientLeft,
                clientTop: container.clientTop,
            };
        }

        let rect = this._containerRect;

        return this._map.containerPointToLayerPoint(L.point(
            (event.clientX - rect.left) / rect.scaleX - rect.clientLeft,
            (event.clientY - rect.top) / rect.scaleY - rect.clientTop
        ));
    },

    /**
     * @param point {L.Point} A layer point.
     * @returns {L.Path|null} The topmost interactive path at the point, as Leaflet's own hit test picks it.
     */
    getLayerAt: function (point) {
        let result = null;

        for (let order = this._drawFirst; order; order = order.next) {
            let layer = order.layer;
            if (layer.options.interactive && layer._containsPoint(point)) {
                result = layer;
            }
        }

        return result;
    },

    /**
     * @param event {MouseEvent}
     * @private
     */
    _onMapContainerMouseMove: function (event) {
        let capturesPointer = this.getLayerAt(this.mouseEventToLayerPoint(event)) !== null;

        // Leaflet's own hover check is throttled, and the canvas gets no further mouse event once it
        // stops taking them, so the enemy just left would keep its tooltip and the pointer cursor.
        if (!capturesPointer && this._capturesPointer && event.target === this._container) {
            this._handleMouseOut(event);
        }

        this._setCapturesPointer(capturesPointer);
    },

    /**
     * @private
     */
    _invalidateContainerRect: function () {
        this._containerRect = null;
    },

    /**
     * @param capturesPointer {Boolean}
     * @private
     */
    _setCapturesPointer: function (capturesPointer) {
        if (this._capturesPointer !== capturesPointer) {
            this._capturesPointer = capturesPointer;
            this._container.style.pointerEvents = capturesPointer ? 'auto' : 'none';
        }
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

if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        EnemyPath,
        EnemyCanvasRenderer,
        EnemyCanvasLayerGroup,
    };
}
