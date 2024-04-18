class HeatPlugin extends MapPlugin {
    constructor(map) {
        super(map);

        this.heatLayer = null;
        this.draw = false;
    }

    isEnabled() {
        return getState().getMapContext() instanceof MapContextDungeonExplore;
    }

    addToMap() {
        if (!this.isEnabled()) {
            return;
        }

        let self = this;

        this.heatLayer = L.heatLayer([]);

        this.heatLayer.addTo(this.map.leafletMap);
        this.map.leafletMap.on({
            movestart: function () {
                self.draw = false;
            },
            moveend: function () {
                self.draw = true;
            },
            mousemove: function (e) {
                if (self.draw) {
                    self.heatLayer.addLatLng(e.latlng);
                }
            }
        })
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
}
