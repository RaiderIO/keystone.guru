class AdDisplayControls extends MapControl {
    constructor(map) {
        super(map);

        let self = this;

        this.map = map;

        this.mapControlOptions = {
            onAdd: function (leafletMap) {
                let source = $('#map_ad_template').html();

                // Build the status bar from the template
                self.domElement = $(source);

                self.domElement = self.domElement[0];

                return self.domElement;
            }
        };
    }

    /**
     * Adds the Control to the current LeafletMap
     */
    addControl() {
        console.assert(this instanceof AdDisplayControls, this, 'this is not AdDisplayControls');

        // Code for the domElement
        L.Control.domElement = L.Control.extend(this.mapControlOptions);

        L.control.domElement = function (opts) {
            return new L.Control.domElement(opts);
        };

        this._mapControl = L.control.domElement({position: 'bottomright'}).addTo(this.map.leafletMap);
    }

    cleanup() {
        super.cleanup();

        console.assert(this instanceof AdDisplayControls, this, 'this is not AdDisplayControls');
    }
}
