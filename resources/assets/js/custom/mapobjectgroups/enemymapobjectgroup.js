class EnemyMapObjectGroup extends MapObjectGroup {
    constructor(manager, editable) {
        super(manager, MAP_OBJECT_GROUP_ENEMY, '', editable);

        this.title = 'Hide/show enemies';
        this.fa_class = 'fa-users';
    }

    /**
     * @inheritDoc
     */
    _createLayer(remoteMapObject) {
        let layer = null;

        // Only create a visual if we should display this enemy
        if (remoteMapObject.floor_id === getState().getCurrentFloor().id) {
            layer = new LeafletEnemyMarker();
            layer.setLatLng(L.latLng(remoteMapObject.lat, remoteMapObject.lng));
        }

        return layer;
    }

    /**
     * @inheritDoc
     */
    _createMapObject(layer, options = {}) {
        console.assert(this instanceof EnemyMapObjectGroup, 'this is not a EnemyMapObjectGroup', this);

        if (getState().isMapAdmin()) {
            return new AdminEnemy(this.manager.map, layer);
        } else {
            return new Enemy(this.manager.map, layer);
        }
    }

    /**
     *
     * @param remoteMapObject {Object}
     * @param mapObject {Enemy|MapObject}
     * @param options {Object}
     * @returns {MapObject}
     * @protected
     */
    _updateMapObject(remoteMapObject, mapObject, options = {}) {
        super._updateMapObject(remoteMapObject, mapObject, options);

        // Refresh visual if not created new, AFTER setting the NPC again etc.
        mapObject.visual.refresh();

        return mapObject;
    }

    _fetchSuccess(response) {
        // no super call, we're handling this by ourselves
        console.assert(this instanceof EnemyMapObjectGroup, 'this is not a EnemyMapObjectGroup', this);

        // Only generate the enemies once
        // if (getState().getEnemies().length === 0) {
        // The enemies are no longer returned from the response; get it from the getState() instead
        let enemySets = [
            getState().getRawEnemies(),
            getState().getMdtEnemies(),
        ];

        // For each set of enemies..
        for (let i = 0; i < enemySets.length; i++) {
            let enemySet = enemySets[i];
            // Now draw the enemies on the map, if any
            for (let index in enemySet) {
                // Only if actually set
                if (enemySet.hasOwnProperty(index)) {
                    // Only restore enemies for the current floor
                    this._restoreObject(enemySet[index]);
                }
            }
        }

        // Couple awakened enemies to each other
        for (let i = 0; i < this.objects.length; i++) {
            let enemy = this.objects[i];

            // Check only those Awakened mobs that are not part of the final boss pack
            if (enemy.npc !== null && enemy.isAwakenedNpc() && enemy.enemy_pack_id === -1) {
                for (let j = 0; j < this.objects.length; j++) {
                    let enemyCandidate = this.objects[j];

                    // Don't check ourselves, only match those enemies with the same npc id and seasonal_index that are part of the final boss pack
                    if (enemyCandidate.id !== enemy.id && enemyCandidate.npc !== null &&
                        enemyCandidate.isAwakenedNpc() && enemyCandidate.npc.id === enemy.npc.id &&
                        enemyCandidate.seasonal_index === enemy.seasonal_index &&
                        enemyCandidate.enemy_pack_id !== -1) {

                        enemy.setLinkedAwakenedEnemy(enemyCandidate);
                        break;
                    }
                }
            }
        }

        // Set the enemies back to our state
        getState().setEnemies(this.objects);
        // } else {
        //     // Update the visibility of the existing enemies
        //     for (let i = 0; i < this.objects.length; i++) {
        //         let enemy = this.objects[i];
        //         this.setMapObjectVisibility(enemy, enemy.shouldBeVisible());
        //     }
        // }

        this.signal('restorecomplete');
    }

    /**
     * Helper function to fetch the final boss of this dungeon.
     *
     * @return {Enemy|null}
     */
    getFinalBoss() {
        let finalBoss = null;
        for (let i = 0; i < this.objects.length; i++) {
            let enemy = this.objects[i];
            if (enemy.npc !== null && enemy.npc.classification_id === 4) {
                finalBoss = enemy;
                break;
            }
        }

        return finalBoss;
    }
}