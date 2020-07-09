class EnemyMapObjectGroup extends MapObjectGroup {
    constructor(manager, editable) {
        super(manager, MAP_OBJECT_GROUP_ENEMY, '', editable);

        this.title = 'Hide/show enemies';
        this.fa_class = 'fa-users';
    }

    _createObject(layer) {
        console.assert(this instanceof EnemyMapObjectGroup, 'this is not a EnemyMapObjectGroup', this);

        if (getState().isMapAdmin()) {
            return new AdminEnemy(this.manager.map, layer);
        } else {
            return new Enemy(this.manager.map, layer);
        }
    }

    _restoreObject(remoteMapObject, username = null) {
        console.assert(this instanceof EnemyMapObjectGroup, 'this is not a EnemyMapObjectGroup', this);

        // Only create a visual if we should display this enemy
        let layer = null;
        if (remoteMapObject.floor_id === getState().getCurrentFloor().id) {
            layer = new LeafletEnemyMarker();
            layer.setLatLng(L.latLng(remoteMapObject.lat, remoteMapObject.lng));
        }

        let enemy = this.createNew(layer);
        enemy.loadRemoteMapObject(remoteMapObject);

        if (remoteMapObject.hasOwnProperty('is_mdt')) {
            // Exception for MDT enemies
            enemy.is_mdt = remoteMapObject.is_mdt;
            // Whatever enemy this MDT enemy is linked to
            enemy.enemy_id = remoteMapObject.enemy_id;
            // Hide this enemy by default
            enemy.setDefaultVisible(false);
        }

        // When in admin mode, show all enemies
        if (!getState().isMapAdmin()) {
            // Hide this enemy by default
            enemy.setDefaultVisible(enemy.shouldBeVisible());
        }

        // Do this last
        enemy.setNpc(remoteMapObject.npc);

        // We just downloaded the enemy, it's synced alright!
        enemy.setSynced(true);

        return enemy;
    }

    _fetchSuccess(response) {
        // no super call, we're handling this by ourselves
        console.assert(this instanceof EnemyMapObjectGroup, 'this is not a EnemyMapObjectGroup', this);

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
                    let enemy = enemySet[index];
                    // Only restore enemies for the current floor
                    this._restoreObject(enemy);
                }
            }
        }

        // Set the enemies back to our state
        getState().setEnemies(this.objects);

        this.signal('restorecomplete');
    }
}