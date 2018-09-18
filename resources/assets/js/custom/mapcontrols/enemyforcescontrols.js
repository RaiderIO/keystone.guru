class EnemyForcesControls extends MapControl {
    constructor(map) {
        super(map);
        console.assert(this instanceof EnemyForcesControls, this, 'this is not EnemyForcesControls');
        console.assert(map instanceof DungeonMap, map, 'map is not DungeonMap');

        let self = this;

        this.map = map;
        // Just the initial enemy forces upon page load.
        this.enemyForces = dungeonRouteEnemyForces; // Defined in map.blade.php
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
            let enemyMapObjectGroup = self.map.getMapObjectGroupByName('enemy');

            // For each enemy we've loaded
            $.each(enemyMapObjectGroup.objects, function (i, enemy) {
                // Local changes will update the counter
                enemy.register('killzone:attached', self, function (data) {
                    console.log('attached to killzone!');
                    self.enemyForces += data.context.enemy_forces;

                    self.refreshUI();
                });
                enemy.register('killzone:detached', self, function (data) {
                    self.enemyForces -= data.context.enemy_forces;

                    self.refreshUI();
                });
                // Remote changes will be the authority when it comes to forces
                enemy.register('killzone:synced', self, function (data) {
                    self.enemyForces = data.enemy_forces;

                    self.refreshUI();
                });
            });
        });
    }

    /**
     * Refreshes the UI to reflect the current enemy forces state
     */
    refreshUI() {
        console.assert(this instanceof EnemyForcesControls, this, 'this is not EnemyForcesControls');

        let enemyForcesRequired = this.map.getEnemyForcesRequired();
        let enemyForcesPercent = enemyForcesRequired === 0 ? 0 : ((this.enemyForces / enemyForcesRequired) * 100);
        let $numbers = $('#map_enemy_forces_numbers');
        if (this.enemyForces > enemyForcesRequired) {
            if (enemyForcesPercent > 110) {
                $numbers.addClass('map_enemy_forces_too_much');
            } else {
                $numbers.addClass('map_enemy_forces_too_much_warning');
            }
        } else {
            $numbers.removeClass('map_enemy_forces_too_much');
            $numbers.removeClass('map_enemy_forces_too_much_warning');
        }

        $('#map_enemy_forces_count').html(this.enemyForces);
        $('#map_enemy_forces_percent').html(enemyForcesPercent.toFixed(2));
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

        // Show the default values
        this.refreshUI();
    }
}
