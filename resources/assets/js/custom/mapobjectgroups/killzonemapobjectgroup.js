class KillZoneMapObjectGroup extends MapObjectGroup {
    constructor(manager, name, editable) {
        super(manager, name, editable);

        let self = this;

        this.title = 'Hide/show killzone';
        this.fa_class = 'fa-bullseye';

        // this.manager.unregister('fetchsuccess', this);
        window.Echo.channel('route-edit')
            .listen('KillZoneChangedEvent', (e) => {
                self._restoreObject(e.killzone);
            });
    }

    _createObject(layer) {
        console.assert(this instanceof KillZoneMapObjectGroup, 'this is not an KillZoneMapObjectGroup');

        return new KillZone(this.manager.map, layer);
    }

    _restoreObject(remoteMapObject) {
        // Fetch the existing killzone if it exists
        let killzone = this.findMapObjectById(remoteMapObject.id);

        // Only create a new one if it's new for us
        if (killzone === null) {
            let layer = new LeafletKillZoneMarker();
            layer.setLatLng(L.latLng(remoteMapObject.lat, remoteMapObject.lng));

            /** @var KillZone killzone */
            killzone = this.createNew(layer);
        }

        // Now update the killzone to its new properties
        killzone.id = remoteMapObject.id;
        killzone.floor_id = remoteMapObject.floor_id;
        // Use default if not set
        if (remoteMapObject.color !== '') {
            killzone.color = remoteMapObject.color;
        }

        // Reconstruct the enemies we're coupled with in a format we expect
        if (typeof remoteMapObject.killzoneenemies !== 'undefined') {
            if (remoteMapObject.killzoneenemies.length > 1) {
                let enemies = [];
                for (let i = 0; i < remoteMapObject.killzoneenemies.length; i++) {
                    let enemy = remoteMapObject.killzoneenemies[i];
                    enemies.push(enemy.enemy_id);
                }
                // Restore the enemies, STILL NEED TO CALL SETENEMIES WHEN EVERYTHING'S DONE LOADING
                // Should be handled by the killzone itself
                killzone.enemies = enemies;

                // We just downloaded the kill zone, it's synced alright!
                killzone.setSynced(true);
            }
        }
    }

    _fetchSuccess(response) {
        super._fetchSuccess(response);
        // no super call required
        console.assert(this instanceof KillZoneMapObjectGroup, this, 'this is not a KillZoneMapObjectGroup');

        let killzones = response.killzone;

        // Now draw the patrols on the map
        for (let index in killzones) {
            if (killzones.hasOwnProperty(index)) {
                this._restoreObject(killzones[index]);
            }
        }
    }
}