/**
 * Lets an admin click enemies to add them to (or remove them from) an enemy forces checkpoint.
 *
 * A checkpoint is deliberately allowed to span floors, and the admin mapping page never draws the facade,
 * so the only way to build a cross-floor checkpoint is to switch floors while this state is active.
 *
 * Two things make that work, and both are load-bearing:
 * - A floor change calls refreshLeafletMap(clearMapState = false), so the map state is kept.
 *   AdminDungeonMap must therefore FORWARD those arguments to its parent - it used to swallow them,
 *   which silently reinstated the default of true and killed the selection on every floor switch.
 * - Enemies are never cleaned up on a floor switch, only hidden (MapObjectGroup.clear() runs from
 *   reset(), which only the group's constructor calls), so their selectable flag and their
 *   `enemy:selected` registration survive.
 */
class EnemyForcesCheckpointEnemySelection extends EnemySelection {
    constructor(map, sourceMapObject) {
        super(map, sourceMapObject);

        this.snackbarId = null;
    }

    getName() {
        return 'EnemyForcesCheckpointEnemySelection';
    }

    disablesTooltips() {
        return true;
    }

    drawsEnemyEditBorder() {
        return true;
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
        console.assert(this instanceof EnemyForcesCheckpointEnemySelection, 'this is not an EnemyForcesCheckpointEnemySelection', this);
        console.assert(source instanceof EnemyForcesCheckpoint, 'source is not an EnemyForcesCheckpoint', source);
        console.assert(enemyCandidate instanceof Enemy, 'enemyCandidate is not an Enemy', enemyCandidate);

        return true;
    }

    /**
     * The way the icon looks when an enemy may be selected.
     * @protected
     */
    _getLayerIcon() {
        console.assert(this instanceof EnemyForcesCheckpointEnemySelection, 'this is not an EnemyForcesCheckpointEnemySelection', this);

        return LeafletKillZoneIconEditMode;
    }

    /**
     * Shows a snackbar saying which checkpoint is being edited and how much it currently holds. A
     * snackbar (not the checkpoint's own marker/tooltip) because the marker is floor-gated and
     * vanishes on a floor switch - exactly when a cross-floor checkpoint is being built.
     */
    start() {
        console.assert(this instanceof EnemyForcesCheckpointEnemySelection, 'this is not an EnemyForcesCheckpointEnemySelection', this);

        super.start();

        let self = this;

        let template = Handlebars.templates['map_controls_snackbar_enemy_forces_checkpoint_edit_template'];

        this.snackbarId = getState().addSnackbar(
            template($.extend({}, getHandlebarsDefaultVariables(), {
                done_label: lang.get('js.enemy_forces_checkpoint_snackbar_done_label'),
            })), {
                onDomAdded: function () {
                    self._refreshSnackbar();

                    $('#map_enemy_forces_checkpoint_edit_snackbar_done').on('click', function () {
                        self.map.setMapState(null);
                    });
                }
            }
        );

        getState().register('killzonesnumberstyle:changed', this, this._refreshSnackbar.bind(this));
        this.map.register('enemyforcescheckpoint:memberschanged', this, this._refreshSnackbar.bind(this));
    }

    stop() {
        console.assert(this instanceof EnemyForcesCheckpointEnemySelection, 'this is not an EnemyForcesCheckpointEnemySelection', this);

        super.stop();

        if (this.snackbarId !== null) {
            getState().removeSnackbar(this.snackbarId);
            this.snackbarId = null;
        }

        getState().unregister('killzonesnumberstyle:changed', this);
        this.map.unregister('enemyforcescheckpoint:memberschanged', this);
    }

    /**
     * Updates the snackbar with the checkpoint's current name and how much it holds.
     * @private
     */
    _refreshSnackbar() {
        console.assert(this instanceof EnemyForcesCheckpointEnemySelection, 'this is not an EnemyForcesCheckpointEnemySelection', this);

        /** @type {EnemyForcesCheckpoint} */
        let enemyForcesCheckpoint = this.getMapObject();
        let enemies = enemyForcesCheckpoint.getEnemies();
        let enemyForces = enemyForcesCheckpoint.getEnemyForces();

        let amount;
        if (getState().getKillZonesNumberStyle() === NUMBER_STYLE_ENEMY_FORCES) {
            amount = lang.get('js.enemy_forces_checkpoint_snackbar_enemy_forces', {enemyForces: enemyForces});
        } else {
            amount = lang.get('js.enemy_forces_checkpoint_snackbar_percentage', {
                percentage: getFormattedPercentage(enemyForces, this.map.enemyForcesManager.getEnemyForcesRequired()),
            });
        }

        $('#map_enemy_forces_checkpoint_edit_snackbar_name').text(
            lang.get('js.enemy_forces_checkpoint_snackbar_editing', {
                name: enemyForcesCheckpoint.name === null || enemyForcesCheckpoint.name === ''
                    ? lang.get('js.enemy_forces_checkpoint_unnamed_label')
                    : enemyForcesCheckpoint.name,
            })
        );
        $('#map_enemy_forces_checkpoint_edit_snackbar_summary').text(
            lang.get('js.enemy_forces_checkpoint_snackbar_summary', {
                enemies: enemies.length,
                amount: amount,
            })
        );
    }
}

// Guarded export for the test runner (Vitest). This is a no-op in the browser,
// where `module` is undefined, so it does not affect the concatenated bundle.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        EnemyForcesCheckpointEnemySelection,
    };
}
