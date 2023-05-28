class AdminPanelControls extends MapControl {
    constructor(map) {
        super(map);

        let self = this;

        this.map = map;

        this.mapControlOptions = {
            onAdd: function (leafletMap) {
                let template = Handlebars.templates['map_admin_panel_template'];

                // Build the status bar from the template
                self.domElement = $(template());

                self.domElement = self.domElement[0];

                return self.domElement;
            }
        };

        this.map.leafletMap.on('mousemove', function (mouseMoveEvent) {
            let lat = _.round(mouseMoveEvent.latlng.lat, 3);
            let lng = _.round(mouseMoveEvent.latlng.lng, 3);

            let mdtX = _.round(lng * 2.185, 3);
            let mdtY = _.round(lat * 2.185, 3);

            // Ingame coordinates
            let floor = getState().getCurrentFloor()
            let ingameMapSizeX = floor.ingame_max_x - floor.ingame_min_x;
            let ingameMapSizeY = floor.ingame_max_y - floor.ingame_min_y;

            let mapSizeLat = -256;
            let mapSizeLng = 384;

            let invertedLat = mapSizeLat - lat;
            let invertedLng = mapSizeLng - lng;

            let factorLat = (invertedLat / mapSizeLat);
            let factorLng = (invertedLng / mapSizeLng);

            let ingameX = _.round((ingameMapSizeX * factorLng) + floor.ingame_min_x, 3);
            let ingameY = _.round((ingameMapSizeY * factorLat) + floor.ingame_min_y, 3);

            $('#admin_panel_mouse_coordinates').html(
                `<span style="font-size: 16px">
                 lat/lng: ${lat}/${lng}<br>
                 MDT x/y: ${mdtX}/${mdtY}<br>
                 x/y: ${ingameX}/${ingameY}
                </span>`
            );
        });
    }

    /**
     * Adds the Control to the current LeafletMap
     */
    addControl() {
        console.assert(this instanceof AdminPanelControls, 'this is not AdminPanelControls', this);

        // Code for the domElement
        L.Control.domElement = L.Control.extend(this.mapControlOptions);

        L.control.domElement = function (opts) {
            return new L.Control.domElement(opts);
        };

        this._mapControl = L.control.domElement({position: 'bottomright'}).addTo(this.map.leafletMap);
    }

    cleanup() {
        super.cleanup();

        console.assert(this instanceof AdminPanelControls, 'this is not AdminPanelControls', this);
    }
}
