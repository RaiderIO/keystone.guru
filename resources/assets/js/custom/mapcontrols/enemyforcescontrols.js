class EnemyForcesControls extends MapControl {
    constructor(map) {
        super(map);
        console.assert(this instanceof EnemyForcesControls, this, 'this is not EnemyForcesControls');
        console.assert(map instanceof DungeonMap, map, 'map is not DungeonMap');

        let self = this;

        this.map = map;
        this.currentEnemyForces = 0;
        this.mapControlOptions = {
            onAdd: function (leafletMap) {
                let source = $("#map_enemy_forces_template").html();
                let template = handlebars.compile(source);

                let data = {
                    enemy_forces_total: self.map.getEnemyForcesRequired()
                };

                // Build the status bar from the template
                self.statusbar = $(template(data));

                self.statusbar = self.statusbar[0];

                return self.statusbar;
            }
        };

        // Listen for when all enemies are loaded
        this.map.register('map:mapobjectgroupsfetchsuccess', this, function () {
            console.log('mapobjectgroupsfetchsuccess!');
            let enemyMapObjectGroup = self.map.getMapObjectGroupByName('enemy');

            // For each enemy we've loaded
            $.each(enemyMapObjectGroup.objects, function (i, enemy) {
                enemy.register('killzone:attached', self, function (data) {
                    self.currentEnemyForces += data.context.enemy_forces;

                    self.refreshUI();
                });
                enemy.register('killzone:detached', self, function (data) {
                    self.currentEnemyForces -= data.context.enemy_forces;

                    self.refreshUI();
                });
            });
        });
    }

    refreshUI() {
        console.assert(this instanceof EnemyForcesControls, this, 'this is not EnemyForcesControls');

        $('#map_enemy_forces_count').html(this.currentEnemyForces);
        $('#map_enemy_forces_percent').html(((this.currentEnemyForces / this.map.getEnemyForcesRequired()) * 100).toFixed(2));
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
