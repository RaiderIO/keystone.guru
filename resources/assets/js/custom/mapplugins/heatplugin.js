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
        this.sizeX = 300;
        this.sizeY = 200;
        /**
         * The radius where we consider points around us when calculating weights for points that don't exist in the heatmap.
         * We build a full grid of weights for each floor, so we don't have to do that on the fly.
         **/
        this.weightCacheRadius = 5;

        /** The max weight that we have in the heatmap per floor, used for %-age calculations in the tooltip */
        this.weightMaxByFloorId = [];
        this.mouseTooltip = null;

        getState().register('floorid:changed', this, function (floorIdChangedEvent) {
            self._applyLatLngsForFloor(floorIdChangedEvent.data.floorId);
        });

        let fnRef = this._onLeafletMapMouseMove.bind(this);
        this.map.register('map:refresh', this, function () {
            self.map.leafletMap.off('mousemove', fnRef).on('mousemove', fnRef);
        });

    }

    _getGridPositionForLatLng(latLng) {
        return {
            x: Math.floor((latLng.lat / MAP_MAX_LAT) * this.sizeX),
            y: Math.floor((latLng.lng / MAP_MAX_LNG) * this.sizeY)
        }
    }

    _getWeightAt(floorId, x, y, radius) {
        const grid = this.weightByFloorIdGrid[floorId];

        if (!grid) return null; // No data for this floor

        // If the exact weight exists, return it
        if (grid[x]?.[y] !== undefined) {
            return grid[x][y];
        }

        const aspectRatio = this.sizeY / this.sizeX; // Adjust vertical distance scaling
        let weightedSum = 0;
        let weightTotal = 0;

        for (let dx = -radius; dx <= radius; dx++) {
            for (let dy = -Math.round(radius / aspectRatio); dy <= Math.round(radius / aspectRatio); dy++) {
                const nx = x + dx;
                const ny = y + dy;

                if (nx < 0 || nx >= this.sizeX || ny < 0 || ny >= this.sizeY) continue; // Out of bounds
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

        let latLng = event.latlng;
        let gridPosition = this._getGridPositionForLatLng(latLng);
        let weight = this._getWeightAt(getState().getCurrentFloor().id, gridPosition.x, gridPosition.y, 9);

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
            let percent = Math.round(weight / this.weightMaxByFloorId[getState().getCurrentFloor().id] * 100);
            this.mouseTooltip.setLatLng(latLng).setContent(`${percent}% - ${weight}`);
        } else if (this.mouseTooltip) {
            // Remove tooltip if no weight
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
     * @param sizeX {Number|null}
     * @param sizeY {Number|null}
     */
    setRawLatLngsPerFloor(rawLatLngsPerFloor, sizeX, sizeY) {
        console.assert(this instanceof HeatPlugin, 'this is not an instance of HeatPlugin', this);

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
            for (let x = 0; x < this.sizeX; x++) {
                weightByFloorIdGridCache[rawLatLngsOnFloor.floor_id][x] ??= [];
                for (let y = 0; y < this.sizeY; y++) {
                    weightByFloorIdGridCache[rawLatLngsOnFloor.floor_id][x][y] = this._getWeightAt(rawLatLngsOnFloor.floor_id, x, y, this.weightCacheRadius); // Use radius=5 (adjust as needed)
                }
            }

            this.weightByFloorIdGrid[rawLatLngsOnFloor.floor_id] = weightByFloorIdGridCache[rawLatLngsOnFloor.floor_id];
        }

        this._applyLatLngsForFloor(getState().getCurrentFloor().id);

        this.sizeX = sizeX ?? this.sizeX;
        this.sizeY = sizeY ?? this.sizeY;
    }

    clear() {
        console.assert(this instanceof HeatPlugin, 'this is not an instance of HeatPlugin', this);

        this.setLatLngs([]);
    }
}
