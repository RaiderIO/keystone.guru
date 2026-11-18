/**
 * @typedef {Object} WalkGraphFloorResponse
 * @property {Number} run_count
 * @property {Number} min_runs
 * @property {Number} max_run_count
 * @property {Array<Array<Number>>} segments [runCount, fromLat, fromLng, toLat, toLng]
 */

/**
 * Draws where players walked on the current floor, built from combat logs. Holds no map objects: the floor's
 * segments are fetched the first time the group is shown on that floor, and drawn as one polyline per shading
 * band on a single canvas.
 */
class WalkGraphMapObjectGroup extends MapObjectGroup {
    constructor(manager) {
        super(manager, MAP_OBJECT_GROUP_WALK_GRAPH, false);

        this.fa_class = 'fa-shoe-prints';

        /** @type {Object.<Number, WalkGraphFloorResponse>} */
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

        // The base constructor sets the visibility before this group's own state exists
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
        $.ajax({
            type: 'GET',
            url: `/ajax/floor/${floorId}/walkgraph`,
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
     * @param response {WalkGraphFloorResponse}
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
     * @param response {WalkGraphFloorResponse}
     * @private
     */
    _draw(floorId, response) {
        // The map rebuilds the layer group on every refresh, so an unchanged one still holds this floor's drawing
        if (this._drawnLayerGroup === this.layerGroup && this._drawnFloorId === floorId) {
            return;
        }

        this.layerGroup.clearLayers();

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

        this._drawnLayerGroup = this.layerGroup;
        this._drawnFloorId = floorId;
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
