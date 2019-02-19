class EnemyMapObjectGroup extends MapObjectGroup {
    constructor(manager, name, editable) {
        super(manager, name, editable);

        this.title = 'Hide/show enemies';
        this.fa_class = 'fa-users';
    }

    _createObject(layer) {
        console.assert(this instanceof EnemyMapObjectGroup, 'this is not an EnemyMapObjectGroup');

        if (isAdmin) {
            return new AdminEnemy(this.manager.map, layer);
        } else {
            return new Enemy(this.manager.map, layer);
        }
    }

    _fetchSuccess(response) {
        super._fetchSuccess(response);
        // no super call required
        console.assert(this instanceof EnemyMapObjectGroup, this, 'this is not a EnemyMapObjectGroup');

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
                    let remoteEnemy = enemySet[index];

                    let faction = this.manager.map.getDungeonRoute().faction;

                    if (remoteEnemy.faction !== 'any' && faction !== 'any' && faction !== remoteEnemy.faction) {
                        console.log('Skipping enemy that does not belong to the requested faction ', remoteEnemy, faction);
                        continue;
                    }

                    // If the map isn't teeming, but the enemy is teeming..
                    if (!this.manager.map.teeming && remoteEnemy.teeming === 'visible') {
                        console.log('Skipping teeming enemy ' + remoteEnemy.id);
                        continue;
                    }
                    // If the map is teeming, but the enemy shouldn't be there for teeming maps..
                    else if (this.manager.map.teeming && remoteEnemy.teeming === 'invisible') {
                        console.log('Skipping teeming-filtered enemy ' + remoteEnemy.id);
                        continue;
                    }

                    let layer = new LeafletEnemyMarker();
                    layer.setLatLng(L.latLng(remoteEnemy.lat, remoteEnemy.lng));

                    let enemy = this.createNew(layer);
                    enemy.id = remoteEnemy.id;
                    enemy.enemy_pack_id = remoteEnemy.enemy_pack_id;
                    enemy.floor_id = remoteEnemy.floor_id;
                    enemy.teeming = remoteEnemy.teeming;
                    enemy.faction = remoteEnemy.faction;
                    enemy.enemy_forces_override = remoteEnemy.enemy_forces_override;
                    enemy.raid_marker_name = remoteEnemy.raid_marker_name;
                    // MDT id is always set
                    enemy.mdt_id = remoteEnemy.mdt_id;
                    enemy.is_mdt = false;

                    if (remoteEnemy.hasOwnProperty('is_mdt')) {
                        // Exception for MDT enemies
                        enemy.is_mdt = remoteEnemy.is_mdt;
                        // Whatever enemy this MDT enemy is linked to
                        enemy.enemy_id = remoteEnemy.enemy_id;
                        // Hide this enemy by default
                        enemy.setDefaultVisible(false);
                    }
                    // If actually set..
                    if (remoteEnemy.hasOwnProperty('raid_marker_name') && remoteEnemy.raid_marker_name !== null) {
                        enemy.setRaidMarkerName(remoteEnemy.raid_marker_name);
                    }

                    // Do this last
                    enemy.setNpc(remoteEnemy.npc);

                    // We just downloaded the enemy, it's synced alright!
                    enemy.setSynced(true);
                }
            }
        }
    }
}