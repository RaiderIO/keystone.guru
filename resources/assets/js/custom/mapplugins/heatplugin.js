class HeatPlugin extends MapPlugin {
    constructor(map) {
        super(map);

        let self = this;

        this.hidden = false;
        // The map applies its persisted settings (state.setDungeonMap) before the plugins are
        // constructed, so the initial heatmapshowontop:changed signal fires before this plugin can
        // listen for it. Seed the value directly from the map options so the very first addToMap()
        // renders in the correct pane; runtime toggles are handled by the signal below.
        this.showOnTop = map.options?.defaultHeatmapShowOnTop ?? false;
        this.heatLayer = null;
        this.draw = false;
        /** The weight of every grid cell holding data, per floor - used for the tooltips */
        this.weightByFloorIdGrid = [];
        /** Grid size of the floors whose data came on a grid of their own, keyed by floor id - see setRawLatLngsPerFloor() */
        this.gridSizeByFloorId = [];
        /** The raw latLngs per floor */
        this.rawLatLngsByFloorId = [];
        this.dataType = COMBAT_LOG_EVENT_DATA_TYPE_PLAYER_POSITION;
        this.runCount = 0;
        this.gridSizeX = 300;
        this.gridSizeY = 200;
        /**
         * The radius where we consider points around us when calculating weights for points that don't exist in the heatmap.
         * We build a full grid of weights for each floor, so we don't have to do that on the fly.
         **/
        this.weightCacheRadius = [];
        this.weightCacheRadius[COMBAT_LOG_EVENT_DATA_TYPE_PLAYER_POSITION] = 5;
        this.weightCacheRadius[COMBAT_LOG_EVENT_DATA_TYPE_ENEMY_POSITION] = 2;
        this.weightCacheRadius[COMBAT_LOG_EVENT_DATA_TYPE_ENEMY_FAILURE] = 2;

        /** The max weight that we have in the heatmap per floor, used for %-age calculations in the tooltip */
        this.weightMaxByFloorId = [];
        /** The tooltip's 100% for the floors whose data came with a weight max of its own, keyed by floor id */
        this.tooltipWeightMaxByFloorId = [];
        this.mouseTooltip = null;
        this.mouseTooltipEnabled = true;
        /** How the renderer folds together cells that land in the same screen space bucket - see setCombineMode() */
        this.combineMode = HEAT_COMBINE_MODE_SUM;

        let state = getState();

        state.register('floorid:changed', this, function (floorIdChangedEvent) {
            self._applyLatLngsForFloor(floorIdChangedEvent.data.floorId);
        });
        state.register('heatmapshowtooltips:changed', this, function (heatmapShowTooltipsChangedEvent) {
            self.mouseTooltipEnabled = heatmapShowTooltipsChangedEvent.data.visible;
        });

        state.register('heatmapshowontop:changed', this, function (event) {
            self.showOnTop = event.data.onTop;
            self._applyShowOnTop();
        });

        if (!isMobile()) {
            let fnRef = this._onLeafletMapMouseMove.bind(this);
            this.map.register('map:refresh', this, function () {
                self.map.leafletMap.off('mousemove', fnRef).on('mousemove', fnRef);
            });
        }
    }

    /**
     * @param floorId {Number}
     * @returns {{x: Number, y: Number}}
     * @private
     */
    _getGridSize(floorId) {
        return this.gridSizeByFloorId[floorId] ?? {x: this.gridSizeX, y: this.gridSizeY};
    }

    _getGridPositionForLatLng(latLng, floorId) {
        const gridSize = this._getGridSize(floorId);

        return {
            x: Math.floor((latLng.lat / MAP_MAX_LAT) * gridSize.x),
            y: Math.floor((latLng.lng / MAP_MAX_LNG) * gridSize.y)
        }
    }

    /**
     * The weight of a cell, or for a cell without data the distance weighted average of the cells around it.
     * Computed on demand: precomputing it for every cell of every floor stalls the page on a continent's many floors.
     */
    _getWeightAt(floorId, x, y, radius) {
        const grid = this.weightByFloorIdGrid[floorId];

        if (!grid) return null; // No data for this floor

        // If the exact weight exists, return it
        if (grid[x]?.[y] !== undefined) {
            return grid[x][y];
        }

        const gridSize = this._getGridSize(floorId);
        const aspectRatio = gridSize.y / gridSize.x; // Adjust vertical distance scaling
        let weightedSum = 0;
        let weightTotal = 0;

        for (let dx = -radius; dx <= radius; dx++) {
            for (let dy = -Math.round(radius / aspectRatio); dy <= Math.round(radius / aspectRatio); dy++) {
                const nx = x + dx;
                const ny = y + dy;

                if (nx < 0 || nx >= gridSize.x || ny < 0 || ny >= gridSize.y) continue; // Out of bounds
                if (grid[nx]?.[ny] === undefined) continue; // No weight assigned

                // Adjust distance calculation
                const adjustedDx = dx;
                const adjustedDy = dy * aspectRatio; // Scale vertical distance
                const distance = Math.sqrt(adjustedDx * adjustedDx + adjustedDy * adjustedDy);

                if (distance === 0) continue; // Skip the original point

                const weight = grid[nx][ny];
                const influence = 1 / distance; // Closer points have more influence

                weightedSum += weight * influence;
                weightTotal += influence;
            }
        }

        return weightTotal > 0 ? Math.floor(weightedSum / weightTotal) : 0;
    }


    _onLeafletMapMouseMove(event) {
        console.assert(this instanceof HeatPlugin, 'this is not an instance of HeatPlugin', this);

        let weight = 0;
        let latLng = event.latlng;
        // Can be undefined on mobile
        if (this.mouseTooltipEnabled) {
            let floorId = getState().getCurrentFloor().id;
            let gridPosition = this._getGridPositionForLatLng(latLng, floorId);
            weight = this._getWeightAt(floorId, gridPosition.x, gridPosition.y, this.weightCacheRadius[this.dataType]);

            if (weight !== null && weight > 0) {
                if (!this.mouseTooltip) {
                    // Create a new tooltip if it doesn't exist
                    this.mouseTooltip = L.tooltip({
                        permanent: true,
                        direction: "top",
                        offset: L.point(0, -10), // Adjust position slightly
                        className: "heatmap-tooltip", // Optional: Custom styling
                    }).setLatLng(latLng);

                    this.map.leafletMap.addLayer(this.mouseTooltip);
                }

                // Update tooltip position and content
                let max = this.tooltipWeightMaxByFloorId[floorId] ?? this.weightMax;
                // This somehow doesn't work right, weightMax produces a better result
                // let max = this.dataType === COMBAT_LOG_EVENT_DATA_TYPE_PLAYER_POSITION ? this.weightMax : this.runCount;
                let percent = Math.round(weight / max * 100);
                this.mouseTooltip.setLatLng(latLng).setContent(`${percent}% - ${weight}`);
            }
        }

        if (this.mouseTooltip && (!this.mouseTooltipEnabled || weight <= 0)) {
            // Remove tooltip if no weight/not enabled
            this.map.leafletMap.removeLayer(this.mouseTooltip);
            this.mouseTooltip = null;
        }
    }


    _applyLatLngsForFloor(floorId, force = false) {
        console.assert(this instanceof HeatPlugin, 'this is not an instance of HeatPlugin', this);

        if (this.hidden && !force) {
            console.log(`Not showing LatLngs - Heatmap was hidden by user`);
            return;
        }

        if (!this.isEnabled()) {
            console.log(`Not showing LatLngs - Heatmap was disabled`);
            return;
        }

        // The heat layer may not exist yet when its addition was deferred until the map container
        // gained a non-zero size. The data is retained in rawLatLngsByFloorId and re-applied once
        // the layer is created (see _deferAddHeatLayer).
        if (this.heatLayer === null) {
            return;
        }

        let result = [];
        if (this.rawLatLngsByFloorId.hasOwnProperty(floorId)) {
            result = this.rawLatLngsByFloorId[floorId];
        }
        this.heatLayer.setLatLngs(result);
    }

    isEnabled() {
        console.assert(this instanceof HeatPlugin, 'this is not an instance of HeatPlugin', this);

        return getState().getMapContext() instanceof MapContextDungeonExplore;
    }

    addToMap() {
        console.assert(this instanceof HeatPlugin, 'this is not an instance of HeatPlugin', this);

        if (!this.isEnabled()) {
            return;
        }

        this.heatLayer = L.heatLayer([], $.extend({}, c.map.heatmapSettings, {
            pane: this.showOnTop ? 'tooltipPane' : 'overlayPane',
        }));

        this._applyCombineMode();

        this.heatLayer.addTo(this.map.leafletMap);
        // The map defers plugin loading until it has a non-zero size, so floor data may have already
        // arrived (and been stored but not rendered while heatLayer was still null); (re)apply it now.
        this._applyLatLngsForFloor(getState().getCurrentFloor().id);
        // let self = this;
        // Debug function that adds latLngs to your mouse location as you move around
        // this.map.leafletMap.on({
        //     movestart: function () {
        //         self.draw = false;
        //     },
        //     moveend: function () {
        //         self.draw = true;
        //     },
        //     mousemove: function (e) {
        //         if (self.draw) {
        //             self.heatLayer.addLatLng(e.latlng);
        //         }
        //     }
        // });
    }

    /**
     * Moves the heat layer's canvas between the overlay pane (behind enemies) and the tooltip pane
     * (on top of enemies) to reflect the current showOnTop setting.
     *
     * leaflet-heat only parents the canvas in its onAdd handler, and its setOptions() never
     * re-parents an already-added canvas - it just updates radius/blur/gradient and redraws. So to
     * flip the render order at runtime we update the layer option (so a later remove/re-add lands in
     * the right pane) and move the existing canvas element into the target pane ourselves.
     */
    _applyShowOnTop() {
        console.assert(this instanceof HeatPlugin, 'this is not an instance of HeatPlugin', this);

        if (this.heatLayer === null) {
            return;
        }

        let pane = this.showOnTop ? 'tooltipPane' : 'overlayPane';
        this.heatLayer.setOptions({pane: pane});

        if (this.heatLayer._canvas !== undefined && this.heatLayer._canvas !== null) {
            this.map.leafletMap.getPane(pane).appendChild(this.heatLayer._canvas);
        }
    }

    setOptions(options) {
        console.assert(this instanceof HeatPlugin, 'this is not an instance of HeatPlugin', this);

        if (this.heatLayer === null) {
            return;
        }

        this.heatLayer.setOptions(options);
    }

    removeFromMap() {
        console.assert(this instanceof HeatPlugin, 'this is not an instance of HeatPlugin', this);

        if (!this.isEnabled()) {
            return;
        }

        if (this.heatLayer !== null) {
            this.map.leafletMap.removeLayer(this.heatLayer);
        }
    }

    toggle(enabled) {
        console.assert(this instanceof HeatPlugin, 'this is not an instance of HeatPlugin', this);

        this.hidden = !enabled;

        this._applyLatLngsForFloor(enabled ? getState().getCurrentFloor().id : -1, true);
    }

    /**
     * A floor's data may carry its own grid_size_x/grid_size_y and weight_max when it was aggregated on a grid of its
     * own (a continent's facade); every other floor uses the grid size and weight max given here.
     *
     * @param rawLatLngsPerFloor {Object}
     * @param dataType {String}
     * @param runCount {Number}
     * @param weightMax {Number|null} Null takes the highest weight in rawLatLngsPerFloor
     * @param gridSizeX {Number}
     * @param gridSizeY {Number}
     */
    setRawLatLngsPerFloor(rawLatLngsPerFloor, dataType, runCount, weightMax, gridSizeX, gridSizeY) {
        console.assert(this instanceof HeatPlugin, 'this is not an instance of HeatPlugin', this);

        this.dataType = dataType;
        this.runCount = runCount;
        this.gridSizeX = gridSizeX;
        this.gridSizeY = gridSizeY;

        // Construct an easily referenced array that splits up the latLngs per floor
        this.rawLatLngsByFloorId = [];
        this.weightMaxByFloorId = [];
        this.weightByFloorIdGrid = [];
        this.gridSizeByFloorId = [];
        this.tooltipWeightMaxByFloorId = [];
        for (let index in rawLatLngsPerFloor) {
            let rawLatLngsOnFloor = rawLatLngsPerFloor[index];
            let floorId = rawLatLngsOnFloor.floor_id;

            if (rawLatLngsOnFloor.grid_size_x !== undefined && rawLatLngsOnFloor.grid_size_y !== undefined) {
                this.gridSizeByFloorId[floorId] = {x: rawLatLngsOnFloor.grid_size_x, y: rawLatLngsOnFloor.grid_size_y};
            }
            if (rawLatLngsOnFloor.weight_max !== undefined) {
                this.tooltipWeightMaxByFloorId[floorId] = rawLatLngsOnFloor.weight_max;
            }

            let latLngs = rawLatLngsOnFloor.lat_lngs;
            let floorLatLngs = new Array(latLngs.length);
            let grid = [];
            let floorWeightMax = 0;
            for (let latLngIndex = 0; latLngIndex < latLngs.length; latLngIndex++) {
                let latLng = latLngs[latLngIndex];
                floorLatLngs[latLngIndex] = [latLng.lat, latLng.lng, latLng.weight];

                let gridCoordinates = this._getGridPositionForLatLng(latLng, floorId);

                grid[gridCoordinates.x] ??= [];
                grid[gridCoordinates.x][gridCoordinates.y] = latLng.weight;

                if (floorWeightMax < latLng.weight) {
                    floorWeightMax = latLng.weight;
                }
            }

            this.rawLatLngsByFloorId[floorId] = floorLatLngs;
            this.weightByFloorIdGrid[floorId] = grid;
            this.weightMaxByFloorId[floorId] = floorWeightMax;
        }

        this.weightMax = weightMax ?? this.weightMaxByFloorId.reduce((max, floorWeightMax) => Math.max(max, floorWeightMax), 0);

        this._applyLatLngsForFloor(getState().getCurrentFloor().id);
    }

    /**
     * Weights that are a distance rather than a count need HEAT_COMBINE_MODE_MAX: the renderer folds every point
     * that lands in the same screen space bucket into one, and adding two distances up produces a value that is
     * neither of them - a cluster of moderate cells then outshines a single far outlier, and the tooltip, which
     * reports the real cell value, disagrees with the colour under the cursor. Adding counts up is correct, so
     * HEAT_COMBINE_MODE_SUM stays the default.
     *
     * @param mode {String}
     */
    setCombineMode(mode) {
        console.assert(this instanceof HeatPlugin, 'this is not an instance of HeatPlugin', this);

        this.combineMode = mode;
        this._applyCombineMode();

        if (this.heatLayer !== null) {
            this.heatLayer.redraw();
        }
    }

    /**
     * The override is assigned to the layer instance rather than to L.HeatLayer: the class only exists once the
     * vendor bundle has run, which is not guaranteed at the time this file is evaluated.
     * @private
     */
    _applyCombineMode() {
        if (this.heatLayer === null) {
            return;
        }

        if (this.combineMode === HEAT_COMBINE_MODE_MAX) {
            this.heatLayer._redraw = heatLayerRedrawCombiningByMax;
        } else {
            delete this.heatLayer._redraw;
        }
    }
}

/**
 * L.HeatLayer::_redraw, with the bucket merge keeping the strongest value instead of adding the values up. The rest
 * of the geometry - bucket size, the two cell offset, the bounds check - matches the vendored implementation, which
 * this must stay in step with.
 */
function heatLayerRedrawCombiningByMax() {
    if (!this._map) {
        return;
    }

    const radius   = this._heat._r;
    const size     = this._map.getSize();
    const bounds   = new L.Bounds(L.point([-radius, -radius]), size.add([radius, radius]));
    const cellSize = radius / 2;
    const panePos  = this._map._getMapPanePos();
    const offsetX  = panePos.x % cellSize;
    const offsetY  = panePos.y % cellSize;

    const grid = [];
    let max    = 1;

    for (let i = 0; i < this._latlngs.length; i++) {
        const latLng = this._latlngs[i];
        const point  = this._map.latLngToContainerPoint(latLng);
        const x      = Math.floor((point.x - offsetX) / cellSize) + 2;
        const y      = Math.floor((point.y - offsetY) / cellSize) + 2;
        const value  = latLng.alt !== undefined ? latLng.alt : (latLng[2] !== undefined ? +latLng[2] : 1);

        grid[y] = grid[y] || [];
        const cell = grid[y][x];

        if (cell === undefined) {
            const newCell = [point.x, point.y, value];
            newCell.p     = point;
            grid[y][x]    = newCell;
        } else if (value > cell[2]) {
            cell[0] = point.x;
            cell[1] = point.y;
            cell[2] = value;
            cell.p  = point;
        }

        if (grid[y][x][2] > max) {
            max = grid[y][x][2];
        }
    }

    const data = [];
    for (let y = 0; y < grid.length; y++) {
        if (!grid[y]) {
            continue;
        }

        for (let x = 0; x < grid[y].length; x++) {
            const cell = grid[y][x];
            if (cell && bounds.contains(cell.p)) {
                data.push([Math.round(cell[0]), Math.round(cell[1]), Math.min(cell[2], max)]);
            }
        }
    }

    this._heat.max(max);
    this._heat.data(data).draw(this.options.minOpacity);
    this._frame = null;
}

// Guarded export for the test runner (Vitest). This is a no-op in the browser,
// where `module` is undefined, so it does not affect the concatenated bundle.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = HeatPlugin;
    module.exports.heatLayerRedrawCombiningByMax = heatLayerRedrawCombiningByMax;
}
