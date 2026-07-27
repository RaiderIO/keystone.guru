/**
 * Lets an admin click enemies to add them to (or remove them from) an enemy forces region.
 *
 * A region is deliberately allowed to span floors, and the admin mapping page never draws the facade,
 * so the only way to build a cross-floor region is to switch floors while this state is active.
 *
 * Two things make that work, and both are load-bearing:
 * - A floor change calls refreshLeafletMap(clearMapState = false), so the map state is kept.
 *   AdminDungeonMap must therefore FORWARD those arguments to its parent - it used to swallow them,
 *   which silently reinstated the default of true and killed the selection on every floor switch.
 * - Enemies are never cleaned up on a floor switch, only hidden (MapObjectGroup.clear() runs from
 *   reset(), which only the group's constructor calls), so their selectable flag and their
 *   `enemy:selected` registration survive.
 */
class EnemyForcesRegionEnemySelection extends EnemySelection {
    constructor(map, sourceMapObject) {
        super(map, sourceMapObject);

        this.snackbarId = null;
    }

    getName() {
        return 'EnemyForcesRegionEnemySelection';
    }

    shouldRebuildEnemyVisuals() {
        return true;
    }

    /**
     * Filters an enemy if it should be selected or not.
     * @param source {MapObject}
     * @param enemyCandidate {Enemy}
     * @returns {boolean}
     * @protected
     */
    _filter(source, enemyCandidate) {
        console.assert(this instanceof EnemyForcesRegionEnemySelection, 'this is not an EnemyForcesRegionEnemySelection', this);
        console.assert(source instanceof EnemyForcesRegion, 'source is not an EnemyForcesRegion', source);
        console.assert(enemyCandidate instanceof Enemy, 'enemyCandidate is not an Enemy', enemyCandidate);

        return true;
    }

    /**
     * The way the icon looks when an enemy may be selected.
     * @protected
     */
    _getLayerIcon() {
        console.assert(this instanceof EnemyForcesRegionEnemySelection, 'this is not an EnemyForcesRegionEnemySelection', this);

        return LeafletKillZoneIconEditMode;
    }

    /**
     * Shows a snackbar saying which region is being edited and how much it currently holds. A
     * snackbar (not the region's own marker/tooltip) because the marker is floor-gated and
     * vanishes on a floor switch - exactly when a cross-floor region is being built.
     */
    start() {
        console.assert(this instanceof EnemyForcesRegionEnemySelection, 'this is not an EnemyForcesRegionEnemySelection', this);

        super.start();

        let self = this;

        let template = Handlebars.templates['map_controls_snackbar_enemy_forces_region_edit_template'];

        this.snackbarId = getState().addSnackbar(
            template($.extend({}, getHandlebarsDefaultVariables(), {
                done_label: lang.get('js.enemy_forces_region_snackbar_done_label'),
            })), {
                onDomAdded: function () {
                    self._refreshSnackbar();

                    $('#map_enemy_forces_region_edit_snackbar_done').on('click', function () {
                        self.map.setMapState(null);
                    });
                }
            }
        );

        getState().register('mapnumberstyle:changed', this, this._refreshSnackbar.bind(this));
        this.map.register('enemyforcesregion:memberschanged', this, this._refreshSnackbar.bind(this));
    }

    stop() {
        console.assert(this instanceof EnemyForcesRegionEnemySelection, 'this is not an EnemyForcesRegionEnemySelection', this);

        super.stop();

        if (this.snackbarId !== null) {
            getState().removeSnackbar(this.snackbarId);
            this.snackbarId = null;
        }

        getState().unregister('mapnumberstyle:changed', this);
        this.map.unregister('enemyforcesregion:memberschanged', this);
    }

    /**
     * Updates the snackbar with the region's current name and how much it holds.
     * @private
     */
    _refreshSnackbar() {
        console.assert(this instanceof EnemyForcesRegionEnemySelection, 'this is not an EnemyForcesRegionEnemySelection', this);

        /** @type {EnemyForcesRegion} */
        let enemyForcesRegion = this.getMapObject();
        let enemies = enemyForcesRegion.getEnemies();
        let enemyForces = enemyForcesRegion.getEnemyForces();

        let amount;
        if (getState().getMapNumberStyle() === NUMBER_STYLE_ENEMY_FORCES) {
            amount = lang.get('js.enemy_forces_region_snackbar_enemy_forces', {enemyForces: enemyForces});
        } else {
            amount = lang.get('js.enemy_forces_region_snackbar_percentage', {
                percentage: getFormattedPercentage(enemyForces, this.map.enemyForcesManager.getEnemyForcesRequired()),
            });
        }

        $('#map_enemy_forces_region_edit_snackbar_name').text(
            lang.get('js.enemy_forces_region_snackbar_editing', {
                name: enemyForcesRegion.name === null || enemyForcesRegion.name === ''
                    ? lang.get('js.enemy_forces_region_unnamed_label')
                    : enemyForcesRegion.name,
            })
        );
        $('#map_enemy_forces_region_edit_snackbar_summary').text(
            lang.get('js.enemy_forces_region_snackbar_summary', {
                enemies: enemies.length,
                amount: amount,
            })
        );
    }
}
