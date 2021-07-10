class EnemyForcesControls extends MapControl {
    constructor(map) {
        super(map);
        console.assert(this instanceof EnemyForcesControls, 'this is not EnemyForcesControls', this);

        let self = this;

        this.loaded = false;
        this.map = map;

        this.map.enemyForcesManager.register('enemyforces:changed', this, this._onEnemyForcesChanged.bind(this));

        // Just the initial enemy forces upon page load.
        this._onEnemyForcesChanged(this.map.enemyForcesManager.getEnemyForces());

        this.mapControlOptions = {
            onAdd: function (leafletMap) {
                let template = Handlebars.templates['map_enemy_forces_template_view'];

                let data = $.extend({}, getHandlebarsDefaultVariables(), {
                    enemy_forces_total: self.map.enemyForcesManager.getEnemyForcesRequired()
                });

                // Build the status bar from the template
                self.statusbar = $(template(data));

                self.statusbar = self.statusbar[0];

                return self.statusbar;
            }
        };

        // Update the total count when teeming was changed
        getState().getMapContext().register('teeming:changed', this, function () {
            self.refreshUI();
        });

        this.loaded = true;
    }

    /**
     * Sets the enemy forces to a specific value.
     * @param enemyForcesChangedEvent {Object}
     * @private
     */
    _onEnemyForcesChanged(enemyForcesChangedEvent) {
        console.assert(this instanceof EnemyForcesControls, 'this is not EnemyForcesControls', this);

        this.refreshUI();

        // Don't trigger this when loading in the route and the value actually changed
        if (this.loaded) {
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

        let currentEnemyForces = this.map.enemyForcesManager.getEnemyForces();
        let enemyForcesRequired = this.map.enemyForcesManager.getEnemyForcesRequired();
        let enemyForcesPercent = enemyForcesRequired === 0 ? 0 : ((currentEnemyForces / enemyForcesRequired) * 100);
        let $enemyForces = $('#map_enemy_forces');
        let $numbers = $('#map_enemy_forces_numbers');

        $numbers.removeClass('map_enemy_forces_too_much_warning');
        $numbers.removeClass('map_enemy_forces_ok');
        $numbers.removeAttr('title');

        let killZoneMapObjectGroup = getState().getDungeonMap().mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_KILLZONE);
        if (!killZoneMapObjectGroup.hasKilledAllUnskippables()) {
            $numbers.attr('title', lang.get('messages.enemy_forces_not_all_unskippables_killed_label'))
                .addClass('map_enemy_forces_too_little_warning');

            $('#map_enemy_forces_success').hide();
            $('#map_enemy_forces_warning').show();
        } else if (currentEnemyForces >= enemyForcesRequired) {
            // When editing the route..
            if (this.map.options.edit) {
                if (enemyForcesPercent > 110) {
                    $numbers.attr('title', lang.get('messages.enemy_forces_too_much_label'))
                        .addClass('map_enemy_forces_too_much_warning');
                    $('#map_enemy_forces_success').hide();
                    $('#map_enemy_forces_warning').show();
                } else if (enemyForcesPercent >= 100) {
                    $numbers.attr('title', '')
                        .addClass('map_enemy_forces_ok');
                    $('#map_enemy_forces_success').show();
                    $('#map_enemy_forces_warning').hide();
                }
            }
            // Only when viewing a route with less than 100% enemy forces
            else {
                if (enemyForcesPercent > 110) {
                    $numbers.attr('title', lang.get('messages.enemy_forces_too_much_label'))
                        .addClass('map_enemy_forces_too_much_warning');
                    $('#map_enemy_forces_success').hide();
                    $('#map_enemy_forces_warning').show();
                } else if (enemyForcesPercent >= 100) {
                    $numbers.attr('title', '')
                        .addClass('map_enemy_forces_ok');
                    $('#map_enemy_forces_success').show();
                    $('#map_enemy_forces_warning').hide();
                }
            }
        } else if (enemyForcesPercent < 100) {
            $numbers.attr('title', lang.get('messages.enemy_forces_too_little_label'));
            $numbers.addClass('map_enemy_forces_too_little_warning');
            $('#map_enemy_forces_success').hide();
            $('#map_enemy_forces_warning').show();
        }

        $('#map_enemy_forces_count').html(currentEnemyForces);
        $('#map_enemy_forces_count_total').html(enemyForcesRequired);
        $('#map_enemy_forces_percent').html(Math.round(enemyForcesPercent * 10) / 10);
        $('#map_enemy_forces_override_warning').toggle(this.map.enemyForcesManager.getEnemyForcesOverride() !== null);

        $numbers.refreshTooltips();
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
        console.assert(this instanceof EnemyForcesControls, 'this is not EnemyForcesControls', this);
        super.cleanup();

        this.map.enemyForcesManager.unregister('enemyforces:changed', this);
        getState().getMapContext().unregister('teeming:changed', this);
    }

}
