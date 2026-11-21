/**
 * @typedef {Object} WalkGraphFloorResponse
 * @property {Number} run_count
 * @property {Number} min_runs
 * @property {Number} max_run_count
 * @property {Array<Array<Number>>} segments [runCount, fromLat, fromLng, toLat, toLng]
 */

/**
 * @typedef {Object} WalkGraphFloorAreaResponse
 * @property {Number} run_count
 * @property {Number} min_runs
 * @property {Array<Array<Array<Number>>>} rings Closed rings of [lat, lng]; a ring winding against the one around it is a hole in it
 */

/**
 * Draws where players walked on the current floor, built from combat logs. Holds no map objects: the floor's walk graph
 * is fetched the first time the group is shown on that floor, and drawn on a single canvas in one of two styles:
 * every walked segment as one polyline per shading band, or the outline of the walkable area as one polygon.
 */
class WalkGraphMapObjectGroup extends MapObjectGroup {
    /**
     * @param manager {MapObjectGroupManager}
     * @param style {String} WALK_GRAPH_LAYER_STYLE_SEGMENTS or WALK_GRAPH_LAYER_STYLE_AREA
     */
    constructor(manager, style) {
        super(manager, MAP_OBJECT_GROUP_WALK_GRAPH, false);

        this.fa_class = 'fa-shoe-prints';

        /** @type {String} */
        this._style = style;

        /** @type {Object.<Number, WalkGraphFloorResponse|WalkGraphFloorAreaResponse>} */
        this._responsesByFloorId = {};

        /** @type {Object.<Number, Boolean>} */
        this._requestedFloorIds = {};

        /** @type {L.LayerGroup|null} The layer group the current floor was last drawn into */
        this._drawnLayerGroup = null;

        /** @type {Number|null} */
        this._drawnFloorId = null;

        /** @type {L.Canvas|null} */
        this._canvasRenderer = null;
    }

    /**
     * @inheritDoc
     */
    _getMapPane() {
        return LEAFLET_PANE_OVERLAY;
    }

    /**
     * @inheritDoc
     */
    _getRawObjects() {
        return [];
    }

    /**
     * @inheritDoc
     */
    isCanvasRendered() {
        return true;
    }

    /**
     * @inheritDoc
     */
    setVisibility(visible) {
        super.setVisibility(visible);

        // Also called from the base constructor, when this group's own fields are still unset
        if (visible && this._initialized) {
            this._showCurrentFloor();
        }
    }

    /**
     * @inheritDoc
     */
    _onBeforeRefresh() {
        super._onBeforeRefresh();

        // The map keeps the previous layer group on the map when it rebuilds them
        this.layerGroup.clearLayers();
        this._drawnLayerGroup = null;
        this._drawnFloorId = null;
    }

    /**
     * Draws the current floor, fetching its segments first when this floor was never shown.
     * @private
     */
    _showCurrentFloor() {
        let floorId = getState().getCurrentFloor().id;

        if (this._responsesByFloorId.hasOwnProperty(floorId)) {
            this._draw(floorId, this._responsesByFloorId[floorId]);
        } else {
            // On page load every group is shown once before the user's hidden groups are applied
            setTimeout(() => {
                if (this._visible && !this._requestedFloorIds.hasOwnProperty(floorId)) {
                    this._requestedFloorIds[floorId] = true;
                    this._fetch(floorId);
                }
            }, 0);
        }
    }

    /**
     * @param floorId {Number}
     * @private
     */
    _fetch(floorId) {
        let isAreaStyle = this._style === WALK_GRAPH_LAYER_STYLE_AREA;

        $.ajax({
            type: 'GET',
            url: isAreaStyle ? `/ajax/floor/${floorId}/walkgraph/area` : `/ajax/floor/${floorId}/walkgraph`,
            // A facade floor's area is placed through the floor unions of the mapping version on display
            data: isAreaStyle ? {mapping_version_id: getState().getMapContext().getMappingVersion().id} : {},
            dataType: 'json',
            success: (response) => {
                this._responsesByFloorId[floorId] = response;

                this._notifyWhenIncomplete(response);

                // The user may have moved on to another floor, or hidden the group, while this was loading
                if (this._visible && getState().getCurrentFloor().id === floorId) {
                    this._draw(floorId, response);
                }
            },
            error: () => {
                // Allow another attempt the next time the floor is shown
                delete this._requestedFloorIds[floorId];
            },
        });
    }

    /**
     * @param response {WalkGraphFloorResponse|WalkGraphFloorAreaResponse}
     * @private
     */
    _notifyWhenIncomplete(response) {
        if (response.run_count > 0 && response.run_count < response.min_runs) {
            showInfoNotification(lang.get('js.walkgraph_incomplete', {
                runCount: response.run_count,
                minRuns: response.min_runs,
            }));
        }
    }

    /**
     * @param floorId {Number}
     * @param response {WalkGraphFloorResponse|WalkGraphFloorAreaResponse}
     * @private
     */
    _draw(floorId, response) {
        // The map rebuilds the layer group on every refresh, so an unchanged one still holds this floor's drawing
        if (this._drawnLayerGroup === this.layerGroup && this._drawnFloorId === floorId) {
            return;
        }

        this.layerGroup.clearLayers();

        if (this._style === WALK_GRAPH_LAYER_STYLE_AREA) {
            this._drawArea(response);
        } else {
            this._drawSegments(response);
        }

        this._drawnLayerGroup = this.layerGroup;
        this._drawnFloorId = floorId;
    }

    /**
     * @param response {WalkGraphFloorAreaResponse}
     * @private
     */
    _drawArea(response) {
        if (response.rings.length === 0) {
            return;
        }

        let area = c.map.walkgraph.area;
        // One polygon for all rings: holes wind against their outline, and the overlapping areas of a facade's floors
        // wind the same way, so a non-zero fill cuts the holes without cutting one floor out of another
        this.layerGroup.addLayer(L.polygon(response.rings, {
            renderer: this._getCanvasRenderer(),
            color: c.map.walkgraph.color,
            weight: area.weight,
            opacity: area.opacity,
            fillColor: c.map.walkgraph.color,
            fillOpacity: area.fillOpacity,
            fillRule: 'nonzero',
            interactive: false,
        }));
    }

    /**
     * @param response {WalkGraphFloorResponse}
     * @private
     */
    _drawSegments(response) {
        let bands = c.map.walkgraph.bands;
        let segmentsByBand = this._getSegmentsByBand(response, bands.length);
        for (let i = 0; i < bands.length; i++) {
            if (segmentsByBand[i].length === 0) {
                continue;
            }

            this.layerGroup.addLayer(L.polyline(segmentsByBand[i], {
                renderer: this._getCanvasRenderer(),
                color: c.map.walkgraph.color,
                weight: bands[i].weight,
                opacity: bands[i].opacity,
                interactive: false,
            }));
        }
    }

    /**
     * Splits the segments into bands by how many runs walked them, on a log scale: a few corridors carry most runs,
     * and a linear scale would leave everything else in the faintest band.
     *
     * @param response {WalkGraphFloorResponse}
     * @param bandCount {Number}
     * @returns {Array<Array<Array<Array<Number>>>>} Per band, the [[fromLat, fromLng], [toLat, toLng]] of its segments
     * @private
     */
    _getSegmentsByBand(response, bandCount) {
        let segmentsByBand = [];
        for (let i = 0; i < bandCount; i++) {
            segmentsByBand.push([]);
        }

        let maxRunCountLog = Math.log1p(Math.max(1, response.max_run_count));
        for (let segment of response.segments) {
            let [runCount, fromLat, fromLng, toLat, toLng] = segment;
            let band = Math.min(bandCount - 1, Math.floor((Math.log1p(runCount) / maxRunCountLog) * bandCount));

            segmentsByBand[band].push([[fromLat, fromLng], [toLat, toLng]]);
        }

        return segmentsByBand;
    }

    /**
     * @returns {L.Canvas}
     * @private
     */
    _getCanvasRenderer() {
        if (this._canvasRenderer === null) {
            this._canvasRenderer = L.canvas({pane: this._getMapPane()});
        }

        return this._canvasRenderer;
    }
}

// Guarded export for the test runner (Vitest). This is a no-op in the browser,
// where `module` is undefined, so it does not affect the concatenated bundle.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {WalkGraphMapObjectGroup};
}
