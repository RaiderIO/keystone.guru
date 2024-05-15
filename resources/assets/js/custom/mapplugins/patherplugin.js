class PatherPlugin extends MapPlugin {
    constructor(map) {
        super(map);

        // Pather instance
        this.pather = null;
    }

    addToMap() {
        console.assert(this instanceof PatherPlugin, 'this is not an instance of PatherPlugin', this);
        let self = this;

        this.pather = new L.Pather();
        this.pather.on('created', function (patherEvent) {
            // Add the newly created polyline to our system
            let mapObjectGroup = self.map.mapObjectGroupManager.getByName('brushline');

            // Create a new brushline
            let points = [];

            // Convert the latlngs into something the polyline constructor understands
            let vertices = patherEvent.latLngs;
            for (let i = 0; i < vertices.length; i++) {
                let vertex = vertices[i];
                points.push([vertex.lat, vertex.lng]);
            }

            let layer = L.polyline(points);

            let object = mapObjectGroup.onNewLayerCreated(layer);
            object.save();

            // Remove it from Pather, we only use Pather for creating the actual layer
            self.pather.removePath(patherEvent.polyline);
        });
        this.map.leafletMap.addLayer(this.pather);
        this.pather.setMode(L.Pather.MODE.VIEW);
        // Set its options properly
        this.refresh();
        // Not enabled at this time
        this.toggle(false);
    }

    removeFromMap() {
        console.assert(this instanceof PatherPlugin, 'this is not an instance of PatherPlugin', this);

        // Pather for drawing lines
        if (this.pather !== null) {
            this.map.leafletMap.removeLayer(this.pather);
        }
    }

    toggle(enabled) {
        console.assert(this instanceof PatherPlugin, 'this is not an instance of PatherPlugin', this);

        // May be null when initializing
        if (this.pather !== null) {
            //  When enabled, add to the map
            if (enabled) {
                this.pather.setMode(L.Pather.MODE.CREATE);
                if (!(this.map.getMapState() instanceof PatherMapState)) {
                    this.map.setMapState(new PatherMapState(this.map));
                    this.map.signal('map:pathertoggled', {enabled: enabled});
                }
            } else {
                this.pather.setMode(L.Pather.MODE.VIEW);
                // Only disable it when we're actively in the pather map state
                if (this.map.getMapState() instanceof PatherMapState) {
                    this.map.setMapState(null);
                    this.map.signal('map:pathertoggled', {enabled: enabled});
                }
            }
        }
    }

    refresh() {
        console.assert(this instanceof PatherPlugin, 'this is not an instance of PatherPlugin', this);
        console.assert(this.pather instanceof L.Pather, 'this.pather is not a L.Pather', this.pather);

        this.pather.setOptions({
            strokeWidth: c.map.polyline.defaultWeight,
            smoothFactor: 5,
            pathColour: c.map.polyline.defaultColor()
        });
    }
}
