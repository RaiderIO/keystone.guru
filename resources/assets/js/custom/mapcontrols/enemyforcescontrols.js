class EnemyForcesControls extends MapControl {
    constructor(map) {
        super(map);
        console.assert(this instanceof EnemyForcesControls, 'this is not EnemyForcesControls', this);

        let self = this;

        this.loaded = false;
        this.lastFooterMessage = null;
        this.map = map;
        // Just the initial enemy forces upon page load.
        this._setEnemyForces(getState().getMapContext().getEnemyForces()); // Defined in map.blade.php

        this.mapControlOptions = {
            onAdd: function (leafletMap) {
                let template = Handlebars.templates['map_enemy_forces_template_view'];

                let data = $.extend({}, getHandlebarsDefaultVariables(), {
                    enemy_forces_total: self.map.getEnemyForcesRequired()
                });

                // Build the status bar from the template
                self.statusbar = $(template(data));

                self.statusbar = self.statusbar[0];

                return self.statusbar;
            }
        };

        // Listen for when all enemies are loaded
        this.map.register('map:mapobjectgroupsloaded', this, function () {
            let enemyMapObjectGroup = self.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_ENEMY);

            // For each enemy we've loaded
            $.each(enemyMapObjectGroup.objects, function (i, enemy) {
                // Local changes will update the counter
                enemy.register('killzone:attached', self, function (data) {
                    // This is also triggered
                    if (self.map.getMapState() instanceof EnemySelection) {
                        self._setEnemyForces(self.enemyForces + data.context.getEnemyForces());
                    }
                });
                enemy.register('killzone:detached', self, function (data) {
                    if (self.map.getMapState() instanceof EnemySelection) {
                        self._setEnemyForces(self.enemyForces - data.context.getEnemyForces());
                    }
                });
            });
        });

        let killzoneMapObjectGroup = self.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_KILLZONE);
        killzoneMapObjectGroup.register('object:add', this, function (addEvent) {
            addEvent.data.object.register('killzone:changed', self, self._onKillZoneChanged.bind(self));
        });

        // Update the total count when teeming was changed
        getState().getMapContext().register('teeming:changed', this, function () {
            self.refreshUI();
        });

        this.loaded = true;
    }

    _onKillZoneChanged(objectChangedEvent) {
        console.assert(this instanceof EnemyForcesControls, 'this is not EnemyForcesControls', this);

        if (typeof objectChangedEvent.data.enemy_forces !== 'undefined') {
            this._setEnemyForces(objectChangedEvent.data.enemy_forces);
        }
    }

    /**
     * Sets the enemy forces to a specific value.
     * @param value
     * @private
     */
    _setEnemyForces(value) {
        console.assert(this instanceof EnemyForcesControls, 'this is not EnemyForcesControls', this);

        let oldEnemyForces = this.enemyForces;

        this.enemyForces = value;
        // Write the enemy forces into the state so we can remember it when switching floors and this control is re-created
        getState().getMapContext().setEnemyForces(value);
        this.refreshUI();

        // Don't trigger this when loading in the route and the value actually changed
        if (this.loaded && this.enemyForces !== oldEnemyForces) {
            let $enemyForces = $('#map_enemy_forces');
            // Show a short flash of green using the flash class
            $enemyForces.addClass('update');

            $enemyForces.addClass('flash');
            setTimeout(function () {
                $enemyForces.removeClass('flash');
            }, 1000);
        }
    }

    /**
     * Refreshes the UI to reflect the current enemy forces state
     */
    refreshUI() {
        console.assert(this instanceof EnemyForcesControls, 'this is not EnemyForcesControls', this);

        let enemyForcesRequired = this.map.getEnemyForcesRequired();
        let enemyForcesPercent = enemyForcesRequired === 0 ? 0 : ((this.enemyForces / enemyForcesRequired) * 100);
        let $enemyForces = $('#map_enemy_forces');
        let $numbers = $('#map_enemy_forces_numbers');

        $numbers.removeClass('map_enemy_forces_too_much_warning');
        $numbers.removeClass('map_enemy_forces_ok');
        if (this.enemyForces >= enemyForcesRequired) {
            // When editing the route..
            if (this.map.options.edit) {
                if (enemyForcesPercent > 110) {
                    $enemyForces.attr('title', 'Warning: your route has too much enemy forces.');
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
                if (enemyForcesPercent > 110) {
                    $enemyForces.attr('title', 'Warning: this route has too much enemy forces.');
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
        } else if (enemyForcesPercent < 100) {
            $enemyForces.attr('title', 'Warning: this route does not have enough enemy forces!');
            $numbers.addClass('map_enemy_forces_too_little_warning');
            $('#map_enemy_forces_success').hide();
            $('#map_enemy_forces_warning').show();
        }

        $('#map_enemy_forces_count').html(this.enemyForces);
        $('#map_enemy_forces_count_total').html(enemyForcesRequired);
        $('#map_enemy_forces_percent').html(enemyForcesPercent.toFixed(2));

        refreshTooltips($enemyForces);
    }

    /**
     * Adds the Control to the current LeafletMap
     */
    addControl() {
        console.assert(this instanceof EnemyForcesControls, 'this is not EnemyForcesControls', this);

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

        // Fix for Edge prioritizing float: left; from leaflet-control, leading to the div having 1 pixel width rather
        // than the full width. Removing the leaflet-control class fixes this.
        let $enemyForces = $('#map_enemy_forces');
        $enemyForces.removeClass('leaflet-control');

        // Show the default values
        this.refreshUI();
    }

    cleanup() {
        super.cleanup();

        console.assert(this instanceof EnemyForcesControls, 'this is not EnemyForcesControls', this);
        let self = this;

        getState().getMapContext().unregister('teeming:changed', this);
        // Unreg from map
        this.map.unregister('map:mapobjectgroupsloaded', this);
        // Unreg killzones
        let killzoneMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_KILLZONE);
        killzoneMapObjectGroup.unregister('object:add', this);

        // Unreg enemies
        let enemyMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_ENEMY);
        $.each(enemyMapObjectGroup.objects, function (i, enemy) {
            // Unreg
            enemy.unregister('killzone:attached', self);
            enemy.unregister('killzone:detached', self);
        });
    }

}
