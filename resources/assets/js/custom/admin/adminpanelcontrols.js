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

        this.map.leafletMap.on('mousemove', function(mouseMoveEvent){
            $('#admin_panel_mouse_coordinates').html(
                mouseMoveEvent.latlng.lat + ', ' + mouseMoveEvent.latlng.lng
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
