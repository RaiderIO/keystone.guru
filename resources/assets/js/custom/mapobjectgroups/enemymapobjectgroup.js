class EnemyMapObjectGroup extends MapObjectGroup {
    constructor(manager, name, editable) {
        super(manager, name, editable);

        this.title = 'Hide/show enemies';
        this.fa_class = 'fa-users';

        this.manager.map.register('beguilingpreset:changed', this, function () {
            // @TODO remove all beguiling enemies
        });
    }

    _createObject(layer) {
        console.assert(this instanceof EnemyMapObjectGroup, 'this is not a EnemyMapObjectGroup', this);

        if (isMapAdmin) {
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
            let layer = new LeafletEnemyMarker();
            layer.setLatLng(L.latLng(remoteMapObject.lat, remoteMapObject.lng));

            let enemy = this.createNew(layer);
            enemy.id = remoteMapObject.id;
            enemy.enemy_pack_id = remoteMapObject.enemy_pack_id;
            enemy.floor_id = remoteMapObject.floor_id;
            enemy.teeming = remoteMapObject.teeming;
            enemy.faction = remoteMapObject.faction;
            enemy.enemy_forces_override = remoteMapObject.enemy_forces_override;
            enemy.raid_marker_name = remoteMapObject.raid_marker_name;
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

            // Beguiling NPC handling
            if (remoteMapObject.hasOwnProperty('beguiling_preset')) {
                enemy.beguiling_preset = remoteMapObject.beguiling_preset;
                // If it was set to a number
                if (remoteMapObject.beguiling_preset !== null) {
                    // Hide this enemy by default
                    enemy.setDefaultVisible(false);
                }
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
        super._fetchSuccess(response);
        // no super call required
        console.assert(this instanceof EnemyMapObjectGroup, 'this is not a EnemyMapObjectGroup', this);

        let enemies = [
            response.enemy.enemies,
            response.enemy.mdt_enemies
        ];

        // For each set of enemies..
        for (let i = 0; i < enemies.length; i++) {
            let enemySet = enemies[i];
            // Now draw the enemies on the map, if any
            for (let index in enemySet) {
                // Only if actually set
                if (enemySet.hasOwnProperty(index)) {
                    this._restoreObject(enemySet[index]);
                }
            }
        }
    }

    /**
     * Adds the beguiling enemy of an enemy pack
     * @param enemyPack EnemyPack
     * @param preset int
     * @param npcId int
     * @param location L.LatLng
     */
    createBeguilingEnemy(enemyPack, preset, npcId, location) {
        console.assert(this instanceof EnemyMapObjectGroup, 'this is not a EnemyMapObjectGroup', this);

        let npc = this.manager.map.getNpcById(npcId);

        console.log(npc);

        // Build an object that could've come straight from the server
        let remoteEnemy = {
            // Doesn't have a server ID
            id: -1,
            lat: location.lat,
            lng: location.lng,
            enemy_pack_id: enemyPack.id,
            floor_id: getState().getCurrentFloor().id,
            teeming: null,
            faction: 'any',
            enemy_forces_override: -1,
            raid_marker_name: null,
            beguiling_preset: preset,
            npc: npc
        };

        // Build and handle the enemy
        return this._restoreObject(remoteEnemy);
    }

    /**
     * Finds all beguiling enemies of a specific pack.
     * @param enemyPackId
     * @returns {Array}
     */
    getBeguilingEnemiesByEnemyPackId(enemyPackId) {
        let result = [];

        for (let i = 0; i < this.objects.length; i++) {
            let enemy = this.objects[i];
            if (enemy.isBeguiling() && enemy.enemy_pack_id === enemyPackId) {
                result.push(enemy);
            }
        }

        return result;
    }
}