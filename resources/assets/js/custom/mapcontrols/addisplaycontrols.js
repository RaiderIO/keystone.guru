class AdDisplayControls extends MapControl {
    constructor(map) {
        super(map);

        let self = this;

        this.map = map;

        this.mapControlOptions = {
            onAdd: function (leafletMap) {
                let source = '\n' +
                    '    <ins class="adsbygoogle"\n' +
                    '         style="display:inline-block;width:120px;height:240px"\n' +
                    '         data-ad-client="ca-pub-2985471802502246"\n' +
                    '         data-ad-slot="6343511996"></ins>';

                // Build the status bar from the template
                self.domElement = $(source);

                self.domElement = self.domElement[0];

                (window.adsbygoogle || []).push({});

                return self.domElement;
            }
        };
    }

    /**
     * Adds the Control to the current LeafletMap
     */
    addControl() {
        console.assert(this instanceof AdDisplayControls, 'this is not AdDisplayControls', this);

        // Code for the domElement
        L.Control.domElement = L.Control.extend(this.mapControlOptions);

        L.control.domElement = function (opts) {
            return new L.Control.domElement(opts);
        };

        this._mapControl = L.control.domElement({position: 'bottomright'}).addTo(this.map.leafletMap);
    }

    cleanup() {
        super.cleanup();

        console.assert(this instanceof AdDisplayControls, 'this is not AdDisplayControls', this);
    }
}
