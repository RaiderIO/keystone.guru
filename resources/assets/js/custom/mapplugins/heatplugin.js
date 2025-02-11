class HeatPlugin extends MapPlugin {
    constructor(map) {
        super(map);

        let self = this;

        this.hidden = false;
        this.heatLayer = null;
        this.draw = false;
        /** A grid of weights for each coordinate for each floor - used for the tooltips */
        this.weightByFloorIdGrid = [];
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

        /** The max weight that we have in the heatmap per floor, used for %-age calculations in the tooltip */
        this.weightMaxByFloorId = [];
        this.mouseTooltip = null;
        this.mouseTooltipEnabled = true;

        let state = getState();

        state.register('floorid:changed', this, function (floorIdChangedEvent) {
            self._applyLatLngsForFloor(floorIdChangedEvent.data.floorId);
        });
        state.register('heatmapshowtooltips:changed', this, function (heatmapShowTooltipsChangedEvent) {
            self.mouseTooltipEnabled = heatmapShowTooltipsChangedEvent.data.visible;
        });

        let fnRef = this._onLeafletMapMouseMove.bind(this);
        this.map.register('map:refresh', this, function () {
            self.map.leafletMap.off('mousemove', fnRef).on('mousemove', fnRef);
        });


    }

    _getGridPositionForLatLng(latLng) {
        return {
            x: Math.floor((latLng.lat / MAP_MAX_LAT) * this.gridSizeX),
            y: Math.floor((latLng.lng / MAP_MAX_LNG) * this.gridSizeY)
        }
    }

    _getWeightAt(floorId, x, y, radius) {
        const grid = this.weightByFloorIdGrid[floorId];

        if (!grid) return null; // No data for this floor

        // If the exact weight exists, return it
        if (grid[x]?.[y] !== undefined) {
            return grid[x][y];
        }

        const aspectRatio = this.gridSizeY / this.gridSizeX; // Adjust vertical distance scaling
        let weightedSum = 0;
        let weightTotal = 0;

        for (let dx = -radius; dx <= radius; dx++) {
            for (let dy = -Math.round(radius / aspectRatio); dy <= Math.round(radius / aspectRatio); dy++) {
                const nx = x + dx;
                const ny = y + dy;

                if (nx < 0 || nx >= this.gridSizeX || ny < 0 || ny >= this.gridSizeY) continue; // Out of bounds
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
        if (this.mouseTooltipEnabled) {
            let latLng = event.latlng;
            let gridPosition = this._getGridPositionForLatLng(latLng);
            weight = this._getWeightAt(getState().getCurrentFloor().id, gridPosition.x, gridPosition.y, this.weightCacheRadius[this.dataType]);

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
                let max = this.weightMax;
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

        this.heatLayer = L.heatLayer([], $.extend({}, c.map.heatmapSettings));

        this.heatLayer.addTo(this.map.leafletMap);
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

    setOptions(options) {
        console.assert(this instanceof HeatPlugin, 'this is not an instance of HeatPlugin', this);

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
     * @param rawLatLngsPerFloor {Object}
     * @param dataType {String}
     * @param runCount {Number}
     * @param weightMax {Number}
     * @param gridSizeX {Number}
     * @param gridSizeY {Number}
     */
    setRawLatLngsPerFloor(rawLatLngsPerFloor, dataType, runCount, weightMax, gridSizeX, gridSizeY) {
        console.assert(this instanceof HeatPlugin, 'this is not an instance of HeatPlugin', this);

        this.dataType = dataType;
        this.runCount = runCount;
        this.gridSizeX = gridSizeX;
        this.gridSizeY = gridSizeY;
        this.weightMax = weightMax ?? Math.max(this.weightMaxByFloorId);

        // Construct an easily referenced array that splits up the latLngs per floor
        this.rawLatLngsByFloorId = [];
        this.weightMaxByFloorId = [];
        let weightByFloorIdGridCache = [];
        for (let index in rawLatLngsPerFloor) {
            let rawLatLngsOnFloor = rawLatLngsPerFloor[index];

            this.rawLatLngsByFloorId[rawLatLngsOnFloor.floor_id] = [];
            this.weightByFloorIdGrid[rawLatLngsOnFloor.floor_id] = [];
            this.weightMaxByFloorId[rawLatLngsOnFloor.floor_id] = 0;
            for (let latLngIndex in rawLatLngsOnFloor.lat_lngs) {
                this.rawLatLngsByFloorId[rawLatLngsOnFloor.floor_id].push([
                    rawLatLngsOnFloor.lat_lngs[latLngIndex].lat,
                    rawLatLngsOnFloor.lat_lngs[latLngIndex].lng,
                    rawLatLngsOnFloor.lat_lngs[latLngIndex].weight,
                ]);

                let gridCoordinates = this._getGridPositionForLatLng(rawLatLngsOnFloor.lat_lngs[latLngIndex]);

                this.weightByFloorIdGrid[rawLatLngsOnFloor.floor_id][gridCoordinates.x] ??= [];
                this.weightByFloorIdGrid[rawLatLngsOnFloor.floor_id][gridCoordinates.x][gridCoordinates.y] = rawLatLngsOnFloor.lat_lngs[latLngIndex].weight;

                if (this.weightMaxByFloorId[rawLatLngsOnFloor.floor_id] < rawLatLngsOnFloor.lat_lngs[latLngIndex].weight) {
                    this.weightMaxByFloorId[rawLatLngsOnFloor.floor_id] = rawLatLngsOnFloor.lat_lngs[latLngIndex].weight;
                }
            }

            weightByFloorIdGridCache[rawLatLngsOnFloor.floor_id] = [];
            // Precompute missing weights for the entire grid
            for (let x = 0; x < this.gridSizeX; x++) {
                weightByFloorIdGridCache[rawLatLngsOnFloor.floor_id][x] ??= [];
                for (let y = 0; y < this.gridSizeY; y++) {
                    weightByFloorIdGridCache[rawLatLngsOnFloor.floor_id][x][y] = this._getWeightAt(rawLatLngsOnFloor.floor_id, x, y, this.weightCacheRadius[[this.dataType]]); // Use radius=5 (adjust as needed)
                }
            }

            this.weightByFloorIdGrid[rawLatLngsOnFloor.floor_id] = weightByFloorIdGridCache[rawLatLngsOnFloor.floor_id];
        }

        this._applyLatLngsForFloor(getState().getCurrentFloor().id);
    }

    clear() {
        console.assert(this instanceof HeatPlugin, 'this is not an instance of HeatPlugin', this);

        this.setLatLngs([]);
    }
}
