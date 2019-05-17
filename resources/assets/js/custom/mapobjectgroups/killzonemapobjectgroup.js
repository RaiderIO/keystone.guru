class KillZoneMapObjectGroup extends MapObjectGroup {
    constructor(manager, name, editable) {
        super(manager, name, editable);

        this.title = 'Hide/show killzone';
        this.fa_class = 'fa-bullseye';

        // this.manager.unregister('fetchsuccess', this);
    }

    _createObject(layer) {
        console.assert(this instanceof KillZoneMapObjectGroup, 'this is not an KillZoneMapObjectGroup');

        return new KillZone(this.manager.map, layer);
    }

    _fetchSuccess(response) {
        super._fetchSuccess(response);
        // no super call required
        console.assert(this instanceof KillZoneMapObjectGroup, this, 'this is not a KillZoneMapObjectGroup');

        let killzones = response.killzone;

        // Now draw the patrols on the map
        for (let index in killzones) {
            if (killzones.hasOwnProperty(index)) {
                let remoteKillZone = killzones[index];

                let layer = new LeafletKillZoneMarker();
                layer.setLatLng(L.latLng(remoteKillZone.lat, remoteKillZone.lng));

                /** @var KillZone killzone */
                let killzone = this.createNew(layer);
                killzone.id = remoteKillZone.id;
                killzone.floor_id = remoteKillZone.floor_id;
                // Use default if not set
                if (remoteKillZone.color !== '') {
                    killzone.color = remoteKillZone.color;
                }

                // Reconstruct the enemies we're coupled with in a format we expect
                if (remoteKillZone.killzoneenemies !== null) {
                    if (remoteKillZone.killzoneenemies.length <= 1) {
                        continue;
                    }
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
    }
}