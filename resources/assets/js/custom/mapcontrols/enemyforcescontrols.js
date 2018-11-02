class EnemyForcesControls extends MapControl {
    constructor(map) {
        super(map);
        console.assert(this instanceof EnemyForcesControls, this, 'this is not EnemyForcesControls');

        let self = this;

        this.map = map;
        // Just the initial enemy forces upon page load.
        this._setEnemyForces(dungeonRouteEnemyForces); // Defined in map.blade.php

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
                    self._setEnemyForces(self.enemyForces + data.context.enemy_forces);
                });
                enemy.register('killzone:detached', self, function (data) {
                    self._setEnemyForces(self.enemyForces - data.context.enemy_forces);
                });
            });
        });

        let killzoneMapObjectGroup = self.map.getMapObjectGroupByName('killzone');
        killzoneMapObjectGroup.register('object:add', this, function (addEvent) {
            addEvent.data.object.register('killzone:synced', self, self._killzoneSynced.bind(self));
        });
    }

    _killzoneSynced(syncedEvent) {
        console.assert(this instanceof EnemyForcesControls, this, 'this is not EnemyForcesControls');

        if (typeof syncedEvent.data.enemy_forces !== 'undefined') {
            this._setEnemyForces(syncedEvent.data.enemy_forces);
        }
    }

    /**
     * Sets the enemy forces to a specific value.
     * @param value
     * @private
     */
    _setEnemyForces(value) {
        console.assert(this instanceof EnemyForcesControls, this, 'this is not EnemyForcesControls');

        this.enemyForces = value;
        // @TODO This is a bit of a dirty solution for solving an issue where being in edit mode and switching a floor the enemy_forces counter is reset to 0.
        dungeonRouteEnemyForces = value;
        this.refreshUI();
    }

    /**
     * Refreshes the UI to reflect the current enemy forces state
     */
    refreshUI() {
        console.assert(this instanceof EnemyForcesControls, this, 'this is not EnemyForcesControls');

        let enemyForcesRequired = this.map.getEnemyForcesRequired();
        let enemyForcesPercent = enemyForcesRequired === 0 ? 0 : ((this.enemyForces / enemyForcesRequired) * 100);
        let $enemyForces = $('#map_enemy_forces');
        let $numbers = $('#map_enemy_forces_numbers');

        $numbers.removeClass('map_enemy_forces_too_much_warning');
        $numbers.removeClass('map_enemy_forces_ok');
        if (this.enemyForces >= enemyForcesRequired) {
            // When editing the route..
            if (this.map.edit) {
                if (enemyForcesPercent > 110) {
                    $enemyForces.attr('title', 'Warning: your route kills too much enemy forces.');
                    $numbers.addClass('map_enemy_forces_too_much_warning');
                    $('#map_enemy_forces_success').hide();
                    $('#map_enemy_forces_warning').show();
                } else if (enemyForcesPercent >= 100) {
                    $enemyForces.attr('title', '');
                    $numbers.addClass('map_enemy_forces_ok');
                    $('#map_enemy_forces_success').show();
                    $('#map_enemy_forces_warning').hide();
                }
            }
            // Only when viewing a route with less than 100% enemy forces
            else {
                if (enemyForcesPercent < 100) {
                    $enemyForces.attr('title', 'Warning: this route does not kill enough enemy forces!');
                    $numbers.addClass('map_enemy_forces_too_little_warning');
                    $('#map_enemy_forces_success').hide();
                    $('#map_enemy_forces_warning').show();
                } else if (enemyForcesPercent >= 100) {
                    $enemyForces.attr('title', '');
                    $numbers.addClass('map_enemy_forces_ok');
                    $('#map_enemy_forces_success').show();
                    $('#map_enemy_forces_warning').hide();
                } else if (enemyForcesPercent > 110) {
                    $enemyForces.attr('title', 'Warning: this route kills too much enemy forces.');
                    $numbers.addClass('map_enemy_forces_too_much_warning');
                    $('#map_enemy_forces_success').hide();
                    $('#map_enemy_forces_warning').show();
                }
            }
        }

        $('#map_enemy_forces_count').html(this.enemyForces);
        $('#map_enemy_forces_percent').html(enemyForcesPercent.toFixed(2));

        refreshTooltips();
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

        this._mapControl = L.control.statusbar({position: 'bottomhorizontalcenter'}).addTo(this.map.leafletMap);

        // Add the leaflet draw control to the sidebar
        let container = this._mapControl.getContainer();
        let $targetContainer = $('#edit_route_enemy_forces_container');
        $targetContainer.append(container);

        // Show the default values
        this.refreshUI();
    }

    cleanup() {
        super.cleanup();

        console.assert(this instanceof EnemyForcesControls, this, 'this is not EnemyForcesControls');
        let self = this;

        // Unreg from map
        this.map.unregister('map:mapobjectgroupsfetchsuccess', this);
        // Unreg killzones
        let killzoneMapObjectGroup = this.map.getMapObjectGroupByName('killzone');
        killzoneMapObjectGroup.unregister('object:add', this);

        // Unreg enemies
        let enemyMapObjectGroup = this.map.getMapObjectGroupByName('enemy');
        $.each(enemyMapObjectGroup.objects, function (i, enemy) {
            // Unreg
            enemy.unregister('killzone:attached', self);
            enemy.unregister('killzone:detached', self);
        });
    }

}
