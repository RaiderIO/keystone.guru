class EnemyMapObjectGroup extends MapObjectGroup {
    constructor(manager, editable) {
        super(manager, MAP_OBJECT_GROUP_ENEMY, '', editable);

        this.title = 'Hide/show enemies';
        this.fa_class = 'fa-users';

        getState().register('seasonalindex:changed', this, this._seasonalIndexChanged.bind(this));
    }

    /**
     * Triggered when the seasonal index was changed.
     * @param seasonalIndexChangedEvent
     * @private
     */
    _seasonalIndexChanged(seasonalIndexChangedEvent) {
        console.assert(this instanceof MapObjectGroup, 'this is not a MapObjectGroup', this);

        for (let i = 0; i < this.objects.length; i++) {
            let enemy = this.objects[i];
            if (enemy.seasonal_index !== null) {
                this.setMapObjectVisibility(enemy, enemy.seasonal_index === seasonalIndexChangedEvent.data.seasonalIndex);
            }
        }
    }

    _createObject(layer) {
        console.assert(this instanceof EnemyMapObjectGroup, 'this is not a EnemyMapObjectGroup', this);

        if (getState().isMapAdmin()) {
            return new AdminEnemy(this.manager.map, layer);
        } else {
            return new Enemy(this.manager.map, layer);
        }
    }

    _restoreObject(remoteMapObject) {
        console.assert(this instanceof EnemyMapObjectGroup, 'this is not a EnemyMapObjectGroup', this);

        let result = null;

        // Check teeming, faction status
        if (this._isObjectVisible(remoteMapObject)) {
            // Only create a visual if we should display this enemy
            let layer = null;
            if (remoteMapObject.floor_id === getState().getCurrentFloor().id) {
                layer = new LeafletEnemyMarker();
                layer.setLatLng(L.latLng(remoteMapObject.lat, remoteMapObject.lng));
            }

            let enemy = this.createNew(layer);
            enemy.id = remoteMapObject.id;
            enemy.enemy_pack_id = remoteMapObject.enemy_pack_id;
            enemy.floor_id = remoteMapObject.floor_id;
            enemy.teeming = remoteMapObject.teeming;
            enemy.faction = remoteMapObject.faction;
            enemy.enemy_forces_override = remoteMapObject.enemy_forces_override;
            enemy.seasonal_index = remoteMapObject.seasonal_index;
            enemy.raid_marker_name = remoteMapObject.raid_marker_name;
            enemy.dangerous = remoteMapObject.dangerous === 1;
            // MDT id is always set
            enemy.mdt_id = remoteMapObject.mdt_id;
            enemy.is_mdt = false;

            if (remoteMapObject.hasOwnProperty('is_mdt')) {
                // Exception for MDT enemies
                enemy.is_mdt = remoteMapObject.is_mdt;
                // Whatever enemy this MDT enemy is linked to
                enemy.enemy_id = remoteMapObject.enemy_id;
                // Hide this enemy by default
                enemy.setDefaultVisible(false);
            }

            if (enemy.seasonal_index !== null && getState().getSeasonalIndex() !== enemy.seasonal_index) {
                // Hide this enemy by default
                enemy.setDefaultVisible(false);
            }

            // If actually set..
            if (remoteMapObject.hasOwnProperty('raid_marker_name') && remoteMapObject.raid_marker_name !== null) {
                enemy.setRaidMarkerName(remoteMapObject.raid_marker_name);
            }

            // Do this last
            enemy.setNpc(remoteMapObject.npc);

            // We just downloaded the enemy, it's synced alright!
            enemy.setSynced(true);

            result = enemy;
        }

        return result;
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