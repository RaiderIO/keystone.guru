class HeatPlugin extends MapPlugin {
    constructor(map) {
        super(map);

        let self = this;

        this.heatLayer = null;
        this.draw = false;
        this.rawLatLngs = [];
        this.rawLatLngsByFloorId = [];

        getState().register('floorid:changed', this, function (floorIdChangedEvent) {
            console.log(floorIdChangedEvent);
            self._applyLatLngsForFloor(floorIdChangedEvent.data.floorId);
        });
    }

    _applyLatLngsForFloor(floorId) {
        let result = [];
        if (this.rawLatLngsByFloorId.hasOwnProperty(floorId)) {
            result = this.rawLatLngsByFloorId[floorId];
        } else {
            for (let index in this.rawLatLngs) {
                let rawLatLng = this.rawLatLngs[index];
                if (rawLatLng.floor_id === floorId) {
                    result.push([rawLatLng.lat, rawLatLng.lng, 3]);
                }
            }
            this.rawLatLngsByFloorId[floorId] = result;
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
     * @param rawLatLngs {Object}
     */
    setRawLatLngs(rawLatLngs) {
        this.rawLatLngs = rawLatLngs;
        this.rawLatLngsByFloorId = [];

        this._applyLatLngsForFloor(getState().getMapContext().getFloorId());
    }

    clear() {
        this.setLatLngs([]);
    }
}
