class HeatPlugin extends MapPlugin {
    constructor(map) {
        super(map);

        let self = this;

        this.heatLayer = null;
        this.draw = false;
        this.rawLatLngs = [];
        this.rawLatLngsByFloorId = [];

        getState().register('floorid:changed', this, function (floorIdChangedEvent) {
            self._applyLatLngsForFloor(floorIdChangedEvent.data.floorId);
        });
    }

    _applyLatLngsForFloor(floorId) {
        let result = [];
        if (this.rawLatLngsByFloorId.hasOwnProperty(floorId)) {
            result = this.rawLatLngsByFloorId[floorId];
        }
        this.heatLayer.setLatLngs(result);
    }

    isEnabled() {
        return getState().getMapContext() instanceof MapContextDungeonExplore;
    }

    addToMap() {
        if (!this.isEnabled()) {
            return;
        }

        this.heatLayer = L.heatLayer([]);

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

    removeFromMap() {
        if (!this.isEnabled()) {
            return;
        }

        if (this.heatLayer !== null) {
            this.map.leafletMap.removeLayer(this.heatLayer);
        }
    }

    toggle(enabled) {

    }

    /**
     * @param rawLatLngsPerFloor {Object}
     */
    setRawLatLngsPerFloor(rawLatLngsPerFloor) {
        // Construct an easily referenced array that splits up the latLngs per floor
        this.rawLatLngsByFloorId = [];
        for (let index in rawLatLngsPerFloor) {
            let rawLatLngsOnFloor = rawLatLngsPerFloor[index];

            this.rawLatLngsByFloorId[rawLatLngsOnFloor.floor_id] = [];
            for (let latLngIndex in rawLatLngsOnFloor.lat_lngs) {
                this.rawLatLngsByFloorId[rawLatLngsOnFloor.floor_id].push([
                    rawLatLngsOnFloor.lat_lngs[latLngIndex].lat,
                    rawLatLngsOnFloor.lat_lngs[latLngIndex].lng,
                    rawLatLngsOnFloor.lat_lngs[latLngIndex].weight,
                ])
            }
        }

        this._applyLatLngsForFloor(getState().getMapContext().getFloorId());
    }

    clear() {
        this.setLatLngs([]);
    }
}
