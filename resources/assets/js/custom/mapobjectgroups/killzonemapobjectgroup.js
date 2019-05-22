class KillZoneMapObjectGroup extends MapObjectGroup {
    constructor(manager, name, editable) {
        super(manager, name, editable);

        this.title = 'Hide/show killzone';
        this.fa_class = 'fa-bullseye';

        // this.manager.unregister('fetchsuccess', this);
        window.Echo.channel('route-edit')
            .listen('MapObjectEvent', (e) => {
                console.log(e);
            });
    }

    _createObject(layer) {
        console.assert(this instanceof KillZoneMapObjectGroup, 'this is not an KillZoneMapObjectGroup');

        return new KillZone(this.manager.map, layer);
    }

    _restoreObject(localMapObject, remoteMapObject) {
        localMapObject.id = remoteMapObject.id;
        localMapObject.floor_id = remoteMapObject.floor_id;
        // Use default if not set
        if (remoteMapObject.color !== '') {
            localMapObject.color = remoteMapObject.color;
        }

        // Reconstruct the enemies we're coupled with in a format we expect
        if (remoteMapObject.killzoneenemies !== null) {
            if (remoteMapObject.killzoneenemies.length <= 1) {
                return false;
            }
            let enemies = [];
            for (let i = 0; i < remoteMapObject.killzoneenemies.length; i++) {
                let enemy = remoteMapObject.killzoneenemies[i];
                enemies.push(enemy.enemy_id);
            }
            // Restore the enemies, STILL NEED TO CALL SETENEMIES WHEN EVERYTHING'S DONE LOADING
            // Should be handled by the killzone itself
            localMapObject.enemies = enemies;
        }

        // We just downloaded the kill zone, it's synced alright!
        localMapObject.setSynced(true);
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

                this._restoreObject(killzone, remoteKillZone);
            }
        }
    }
}