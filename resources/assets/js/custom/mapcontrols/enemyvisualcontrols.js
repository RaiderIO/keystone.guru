class EnemyVisualControls extends MapControl {
    constructor(map) {
        super(map);

        let self = this;

        this.map = map;

        this.mapControlOptions = {
            onAdd: function (leafletMap) {
                let source = $('#map_enemy_visuals_template').html();
                let template = handlebars.compile(source);

                let data = {};

                // Build the status bar from the template
                self.domElement = $(template(data));
                self.domElement.bind('change', self._enemyVisualChanged.bind(self));

                self.domElement = self.domElement[0];

                return self.domElement;
            }
        };
    }

    /**
     * Called whenever the user wants to change enemy visuals.
     * @param changedEvent
     * @private
     */
    _enemyVisualChanged(changedEvent) {
        console.assert(this instanceof EnemyVisualControls, this, 'this is not EnemyVisualControls');

        let enemyMapObjectGroup = this.map.getMapObjectGroupByName('enemy');
        $.each(enemyMapObjectGroup.objects, function (i, enemy) {
            console.assert(enemy instanceof Enemy, this, 'enemy is not an Enemy');
            enemy.visual.setMainVisual($('#map_enemy_visuals_dropdown').val());
        });
    }

    /**
     * Adds the Control to the current LeafletMap
     */
    addControl() {
        console.assert(this instanceof EnemyVisualControls, this, 'this is not EnemyVisualControls');

        // Code for the domElement
        L.Control.domElement = L.Control.extend(this.mapControlOptions);

        L.control.domElement = function (opts) {
            return new L.Control.domElement(opts);
        };

        this._mapControl = L.control.domElement({position: 'topright'}).addTo(this.map.leafletMap);
    }

    cleanup() {
        super.cleanup();

        console.assert(this instanceof EnemyVisualControls, this, 'this is not EnemyVisualControls');

        this.map.unregister('map:mapobjectgroupsfetchsuccess', this);
    }
}
