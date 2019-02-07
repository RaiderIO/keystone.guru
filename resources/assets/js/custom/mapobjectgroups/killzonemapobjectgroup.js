class KillZoneMapObjectGroup extends MapObjectGroup {
    constructor(map, name, editable) {
        super(map, name, editable);

        this.title = 'Hide/show killzone';
        this.fa_class = 'fa-bullseye';
    }

    _createObject(layer) {
        console.assert(this instanceof KillZoneMapObjectGroup, 'this is not an KillZoneMapObjectGroup');

        return new KillZone(this.map, layer);
    }


    fetchFromServer(floor) {
        // no super call required
        console.assert(this instanceof KillZoneMapObjectGroup, this, 'this is not a KillZoneMapObjectGroup');

        let self = this;

        // No network traffic if this is enabled!
        if (!this.map.isTryModeEnabled()) {
            $.ajax({
                type: 'GET',
                url: '/ajax/killzones',
                dataType: 'json',
                data: {
                    dungeonroute: this.map.getDungeonRoute().publicKey,
                    floor_id: floor.id
                },
                success: function (json) {
                    // Now draw the patrols on the map
                    for (let index in json) {
                        if (json.hasOwnProperty(index)) {
                            let remoteKillZone = json[index];

                            let layer = new LeafletKillZoneMarker();
                            layer.setLatLng(L.latLng(remoteKillZone.lat, remoteKillZone.lng));

                            /** @var KillZone killzone */
                            let killzone = self.createNew(layer);
                            killzone.id = remoteKillZone.id;
                            killzone.floor_id = remoteKillZone.floor_id;
                            // Use default if not set
                            if (remoteKillZone.color !== '') {
                                killzone.color = remoteKillZone.color;
                            }

                            // Reconstruct the enemies we're coupled with in a format we expect
                            if (remoteKillZone.killzoneenemies !== null) {
                                let enemies = [];
                                for (let i = 0; i < remoteKillZone.killzoneenemies.length; i++) {
                                    let enemy = remoteKillZone.killzoneenemies[i];
                                    enemies.push(enemy.enemy_id);
                                }
                                // Restore the enemies, STILL NEED TO CALL SETENEMIES WHEN EVERYTHING'S DONE LOADING
                                // Should be handled by the killzone itself
                                killzone.enemies = enemies;
                            }
                            // We just downloaded the kill zone, it's synced alright!
                            killzone.setSynced(true);
                        }
                    }

                    self.signal('fetchsuccess');
                }
            });
        } else {
            self.signal('fetchsuccess');
        }
    }
}