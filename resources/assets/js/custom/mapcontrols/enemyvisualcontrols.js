class EnemyVisualControls extends MapControl {
    constructor(map) {
        super(map);

        let self = this;

        this.map = map;

        this.mapControlOptions = {
            onAdd: function (leafletMap) {
                let template = Handlebars.templates['map_enemy_visuals_template'];

                let data = $.extend({}, getHandlebarsDefaultVariables(), {
                        enemy_visual_type: self.map.options.defaultEnemyVisualType
                    }
                );

                // Build the status bar from the template
                self.domElement = $(template(data));
                let $domElement = $(self.domElement);

                self.domElement = self.domElement[0];

                return self.domElement;
            }
        };
    }

    /**
     * Adds the Control to the current LeafletMap
     */
    addControl() {
        console.assert(this instanceof EnemyVisualControls, 'this is not EnemyVisualControls', this);

        // Code for the domElement
        L.Control.domElement = L.Control.extend(this.mapControlOptions);

        L.control.domElement = function (opts) {
            return new L.Control.domElement(opts);
        };

        this._mapControl = L.control.domElement({position: 'topright'}).addTo(this.map.leafletMap);

        // Add the leaflet draw control to the sidebar
        let container = this._mapControl.getContainer();
        let $targetContainer = $('#map_enemy_visuals_container');
        $targetContainer.append(container);

        // Now handled by dungeonmap refresh
        // refreshSelectPickers();
    }

    cleanup() {
        super.cleanup();

        console.assert(this instanceof EnemyVisualControls, 'this is not EnemyVisualControls', this);

        getState().unregister('enemydisplaytype:changed', this);
        $('#map_enemy_visuals_container').empty();
    }
}
