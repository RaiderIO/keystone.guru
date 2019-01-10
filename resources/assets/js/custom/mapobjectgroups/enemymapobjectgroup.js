class EnemyMapObjectGroup extends MapObjectGroup {
    constructor(map, name, classname, editable) {
        super(map, name, editable);

        this.classname = classname;
        this.title = 'Hide/show enemies';
        this.fa_class = 'fa-users';
    }

    _createObject(layer) {
        console.assert(this instanceof EnemyMapObjectGroup, 'this is not an EnemyMapObjectGroup');

        switch (this.classname) {
            case "AdminEnemy":
                return new AdminEnemy(this.map, layer);
            default:
                return new Enemy(this.map, layer);
        }
    }

    fetchFromServer(floor, callback) {
        // no super call required
        console.assert(this instanceof EnemyMapObjectGroup, this, 'this is not a EnemyMapObjectGroup');

        let self = this;

        $.ajax({
            type: 'GET',
            url: '/ajax/enemies',
            dataType: 'json',
            data: {
                dungeonroute: this.map.getDungeonRoute().publicKey,
                floor_id: floor.id,
                // Admin = show mdt enemies, otherwise don't
                show_mdt_enemies: this.classname === 'AdminEnemy' ? 1 : 0
            },
            success: function (json) {
                let enemies = [
                    json.enemies,
                    json.mdt_enemies
                ];
                // For each set of enemies..
                for (let i = 0; i < enemies.length; i++) {
                    let enemySet = enemies[i];
                    // Now draw the enemies on the map, if any
                    for (let index in enemySet) {
                        // Only if actually set
                        if (enemySet.hasOwnProperty(index)) {
                            let remoteEnemy = enemySet[index];

                            let faction = self.map.getDungeonRoute().faction;

                            if (remoteEnemy.faction !== 'any' && faction !== 'any' && faction !== remoteEnemy.faction) {
                                console.log('Skipping enemy that does not belong to the requested faction ', remoteEnemy, faction);
                                continue;
                            }

                            // If the map isn't teeming, but the enemy is teeming..
                            if (!self.map.teeming && remoteEnemy.teeming === 'visible') {
                                console.log('Skipping teeming enemy ' + remoteEnemy.id);
                                continue;
                            }
                            // If the map is teeming, but the enemy shouldn't be there for teeming maps..
                            else if (self.map.teeming && remoteEnemy.teeming === 'invisible') {
                                console.log('Skipping teeming-filtered enemy ' + remoteEnemy.id);
                                continue;
                            }

                            let layer = new LeafletEnemyMarker();
                            layer.setLatLng(L.latLng(remoteEnemy.lat, remoteEnemy.lng));

                            let enemy = self.createNew(layer);
                            enemy.id = remoteEnemy.id;
                            enemy.enemy_pack_id = remoteEnemy.enemy_pack_id;
                            enemy.floor_id = remoteEnemy.floor_id;
                            enemy.teeming = remoteEnemy.teeming;
                            enemy.faction = remoteEnemy.faction;
                            enemy.enemy_forces_override = remoteEnemy.enemy_forces_override;
                            enemy.raid_marker_name = remoteEnemy.raid_marker_name;
                            enemy.infested_yes_votes = remoteEnemy.infested_yes_votes;
                            enemy.infested_no_votes = remoteEnemy.infested_no_votes;
                            enemy.infested_user_vote = remoteEnemy.infested_user_vote;
                            enemy.is_infested = remoteEnemy.is_infested;
                            // Exception for MDT enemies
                            enemy.is_mdt = remoteEnemy.hasOwnProperty('is_mdt') ? remoteEnemy.is_mdt : false;

                            // After synced when the visual has been built, can't do that before here since some funky
                            // things would go wrong with the visuals
                            if(remoteEnemy.hasOwnProperty('is_mdt')) {
                                // Hide MDT objects initially
                                self.setMapObjectVisibility(enemy, false);
                            }

                            enemy.setNpc(remoteEnemy.npc);
                            // If actually set..
                            if (remoteEnemy.hasOwnProperty('raid_marker_name') && remoteEnemy.raid_marker_name !== null) {
                                enemy.setRaidMarkerName(remoteEnemy.raid_marker_name);
                            }

                            // We just downloaded the enemy pack, it's synced alright!
                            enemy.setSynced(true);
                        }
                    }
                }
                callback();
            }
        });
    }
}