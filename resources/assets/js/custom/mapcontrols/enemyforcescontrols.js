class EnemyForcesControls extends MapControl {
    constructor(map) {
        super(map);
        console.assert(this instanceof EnemyForcesControls, this, 'this is not EnemyForcesControls');
        console.assert(map instanceof DungeonMap, map, 'map is not DungeonMap');

        let self = this;

        this.map = map;

        this.mapControlOptions = {
            onAdd: function (leafletMap) {
                let source = $("#map_enemy_forces_template").html();
                let template = handlebars.compile(source);

                let data = {
                    enemy_forces_total: self.map.dungeonData.enemy_forces_required
                };

                // Build the status bar from the template
                self.statusbar = $(template(data));

                self.statusbar = self.statusbar[0];

                return self.statusbar;
            }
        };
    }

    /**
     * Adds the Control to the current LeafletMap
     */
    addControl() {
        console.assert(this instanceof EnemyForcesControls, this, 'this is not EnemyForcesControls');

        // Code for the statusbar
        L.Control.Statusbar = L.Control.extend(this.mapControlOptions);

        L.control.statusbar = function (opts) {
            return new L.Control.Statusbar(opts);
        };

        this._mapControl = L.control.statusbar({position: 'topright'}).addTo(this.map.leafletMap);
    }
}
