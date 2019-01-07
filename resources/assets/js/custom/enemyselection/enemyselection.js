class EnemySelection extends Signalable {
    constructor(map) {
        super(map);
        this.label = 'DungeonFloorSwitchMarker';

        this.sourceMapObject = null;
    }

    /**
     * Filter function which should be overriden in implementing classes.
     * @param source MapObject
     * @param enemyCandidate Enemy
     * @returns {boolean}
     * @private
     */
    _filter(source, enemyCandidate){
        return true;
    }

    _getLayerIcon(){
        return null;
    }

    /**
     * Sets the source of the map object that causes the enemy selection.
     * @param sourceMapObject MapObject
     */
    setSourceMapObject(sourceMapObject){
        console.assert(sourceMapObject instanceof MapObject, this, 'sourceMapObject is not a MapObject');

        this.sourceMapObject = sourceMapObject;
    }

    /**
     * Starts select mode on this KillZone, if no other select mode was enabled already.
     */
    startSelectMode() {
        console.assert(this instanceof EnemySelection, this, 'this is not an EnemySelection');
        let self = this;
        if (!this.map.isKillZoneSelectModeEnabled()) {
            this.sourceMapObject.layer.setIcon(this._getLayerIcon());

            let enemyMapObjectGroup = this.map.getMapObjectGroupByName('enemy');
            $.each(enemyMapObjectGroup.objects, function (i, enemy) {
                // We cannot kill an enemy twice, but can deselect once we have selected it
                if (self._filter(this.sourceMapObject, enemy)) {
                    enemy.setKillZoneSelectable(!enemy.isKillZoneSelectable());
                }

                enemy.register('killzone:selected', self, function (data) {
                    self.enemySelected(data.context);
                })
            });

            // Cannot start editing things while we're doing this.
            // @TODO https://stackoverflow.com/questions/40414970/disable-leaflet-draw-delete-button
            $('.leaflet-draw-edit-edit').addClass('leaflet-disabled');
            $('.leaflet-draw-edit-remove').addClass('leaflet-disabled');

            // Now killzoning something
            this.map.setSelectModeKillZone(this);

            this.redrawConnectionsToEnemies();
        }
    }

    /**
     * Stops select mode of this KillZone.
     */
    cancelSelectMode(externalChange = false) {
        console.assert(this instanceof KillZone, this, 'this is not an KillZone');
        if (this.map.isKillZoneSelectModeEnabled() || externalChange) {
            if (!externalChange) {
                this.map.setSelectModeKillZone(null);
            }

            this.layer.setIcon(LeafletKillZoneIcon);

            let self = this;

            // Revert all things we did to enemies
            let enemyMapObjectGroup = this.map.getMapObjectGroupByName('enemy');
            $.each(enemyMapObjectGroup.objects, function (i, enemy) {
                enemy.setKillZoneSelectable(false);
                enemy.unregister('killzone:selected', self);
            });

            // Ok we're clear, may edit again (there's always something to edit because this KillZone exists)
            $('.leaflet-draw-edit-edit').removeClass('leaflet-disabled');
            $('.leaflet-draw-edit-remove').removeClass('leaflet-disabled');

            this.redrawConnectionsToEnemies();
            this.save();
        }
    }

    /**
     * Triggered when an enemy was selected by the user when edit mode was enabled.
     * @param enemy The enemy that was selected (or de-selected). Will add/remove the enemy to the list to be redrawn.
     */
    enemySelected(enemy) {
        console.assert(enemy instanceof Enemy, enemy, 'enemy is not an Enemy');
        console.assert(this instanceof KillZone, this, 'this is not an KillZone');

        let index = $.inArray(enemy.id, this.enemies);
        // Already exists, user wants to deselect the enemy
        let removed = index >= 0;

        // If the enemy was part of a pack..
        if (enemy.enemy_pack_id > 0) {
            let enemyMapObjectGroup = this.map.getMapObjectGroupByName('enemy');
            for (let i = 0; i < enemyMapObjectGroup.objects.length; i++) {
                let enemyCandidate = enemyMapObjectGroup.objects[i];
                // If we should couple the enemy in addition to our own..
                if (enemyCandidate.enemy_pack_id === enemy.enemy_pack_id) {
                    // Remove it too if we should
                    if (removed) {
                        this._removeEnemy(enemyCandidate);
                    }
                    // Or add it too if we need
                    else {
                        this._addEnemy(enemyCandidate);
                    }
                }
            }
        } else {
            if (removed) {
                this._removeEnemy(enemy);
            } else {
                this._addEnemy(enemy);
            }
        }

        this.redrawConnectionsToEnemies();
    }
}