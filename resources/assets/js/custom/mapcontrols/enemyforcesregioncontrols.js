/**
 * While an admin is assigning enemies to an enemy forces region, this panel says which region is
 * being edited and how much it currently holds.
 *
 * It exists because the region's own marker (and the lines it draws to its members) is floor-gated by
 * MapObject.shouldBeVisible(), so it disappears the moment the admin switches floors - which is
 * exactly when a cross-floor region is being built. Without this panel the admin would be clicking
 * enemies blind, with nothing on screen saying selection mode is even still active.
 *
 * All of its state is read from the map state on every refresh: DungeonMap._addMapControls()
 * re-instantiates every control on each map refresh (i.e. on every floor switch), so a control may
 * not hold state of its own.
 */
class EnemyForcesRegionControls extends MapControl {
    constructor(map) {
        super(map);
        console.assert(this instanceof EnemyForcesRegionControls, 'this is not EnemyForcesRegionControls', this);

        let self = this;

        this.map = map;
        this.panel = null;

        this.map.register('map:mapstatechanged', this, function () {
            self.refreshUI();
        });
        getState().register('mapnumberstyle:changed', this, function () {
            self.refreshUI();
        });
        this.map.register('enemyforcesregion:memberschanged', this, function () {
            self.refreshUI();
        });

        this.mapControlOptions = {
            onAdd: function () {
                let template = Handlebars.templates['map_enemy_forces_region_edit_panel'];

                self.panel = $(template({
                    done: lang.get('js.enemy_forces_region_edit_panel_done_label'),
                }))[0];

                $(self.panel).find('.map_enemy_forces_region_edit_panel_done').on('click', function () {
                    self.map.setMapState(null);
                });

                self.refreshUI();

                return self.panel;
            },
        };
    }

    /**
     * Shows the panel with the region currently being edited, or hides it when no region is.
     */
    refreshUI() {
        console.assert(this instanceof EnemyForcesRegionControls, 'this is not EnemyForcesRegionControls', this);

        if (this.panel === null) {
            return;
        }

        let mapState = this.map.getMapState();
        let isEditing = mapState instanceof EnemyForcesRegionEnemySelection;

        $(this.panel).toggleClass('d-none', !isEditing);

        if (!isEditing) {
            return;
        }

        /** @type {EnemyForcesRegion} */
        let enemyForcesRegion = mapState.getMapObject();
        let enemies = enemyForcesRegion.getEnemies();
        let enemyForces = enemyForcesRegion.getEnemyForces();

        let amount;
        if (getState().getMapNumberStyle() === NUMBER_STYLE_ENEMY_FORCES) {
            amount = lang.get('js.enemy_forces_region_edit_panel_enemy_forces', {enemyForces: enemyForces});
        } else {
            amount = lang.get('js.enemy_forces_region_edit_panel_percentage', {
                percentage: getFormattedPercentage(enemyForces, this.map.enemyForcesManager.getEnemyForcesRequired()),
            });
        }

        $(this.panel).find('.map_enemy_forces_region_edit_panel_name').text(
            lang.get('js.enemy_forces_region_edit_panel_editing', {
                name: enemyForcesRegion.name === null || enemyForcesRegion.name === ''
                    ? lang.get('js.enemy_forces_region_unnamed_label')
                    : enemyForcesRegion.name,
            })
        );
        $(this.panel).find('.map_enemy_forces_region_edit_panel_summary').text(
            lang.get('js.enemy_forces_region_edit_panel_summary', {
                enemies: enemies.length,
                amount: amount,
            })
        );
    }

    /**
     * Adds the Control to the current LeafletMap.
     */
    addControl() {
        console.assert(this instanceof EnemyForcesRegionControls, 'this is not EnemyForcesRegionControls', this);

        L.Control.EnemyForcesRegionPanel = L.Control.extend(this.mapControlOptions);

        L.control.enemyForcesRegionPanel = function (opts) {
            return new L.Control.EnemyForcesRegionPanel(opts);
        };

        this._mapControl = L.control.enemyForcesRegionPanel({position: 'verticalcenterright'}).addTo(this.map.leafletMap);

        this.refreshUI();
    }

    cleanup() {
        console.assert(this instanceof EnemyForcesRegionControls, 'this is not EnemyForcesRegionControls', this);
        super.cleanup();

        this.panel = null;

        this.map.unregister('map:mapstatechanged', this);
        this.map.unregister('enemyforcesregion:memberschanged', this);
        getState().unregister('mapnumberstyle:changed', this);
    }
}
