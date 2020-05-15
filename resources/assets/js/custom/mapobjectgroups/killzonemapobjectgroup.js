class KillZoneMapObjectGroup extends MapObjectGroup {
    constructor(manager, editable) {
        super(manager, MAP_OBJECT_GROUP_KILLZONE, editable);

        let self = this;

        this.title = 'Hide/show killzone';
        this.fa_class = 'fa-bullseye';

        if (this.manager.map.options.echo) {
            window.Echo.join('route-edit.' + getState().getDungeonRoute().publicKey)
                .listen('.killzone-changed', (e) => {
                    self._restoreObject(e.killzone, e.user);
                })
                .listen('.killzone-deleted', (e) => {
                    let mapObject = self.findMapObjectById(e.id);
                    if (mapObject !== null) {
                        mapObject.localDelete();
                        self._showDeletedFromEcho(mapObject, e.user);
                    }
                });
        }
    }

    _createObject(layer) {
        console.assert(this instanceof KillZoneMapObjectGroup, 'this is not an KillZoneMapObjectGroup', this);

        return new KillZone(this.manager.map, layer);
    }

    /**
     *
     * @param remoteMapObject
     * @param username
     * @returns {KillZone}
     * @private
     */
    _restoreObject(remoteMapObject, username = null) {
        console.assert(this instanceof KillZoneMapObjectGroup, 'this is not an KillZoneMapObjectGroup', this);
        // Fetch the existing killzone if it exists
        let killzone = this.findMapObjectById(remoteMapObject.id);

        // Only create a new one if it's new for us
        if (killzone === null) {
            let layer = null;
            // Only if it was set, and if it was on this floor
            if (remoteMapObject.lat !== null && remoteMapObject.lng !== null &&
                remoteMapObject.floor_id === getState().getCurrentFloor().id) {
                layer = new LeafletKillZoneMarker();
                layer.setLatLng(L.latLng(remoteMapObject.lat, remoteMapObject.lng));
            }

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
            let enemies = [];
            for (let i = 0; i < remoteMapObject.killzoneenemies.length; i++) {
                let enemy = remoteMapObject.killzoneenemies[i];
                enemies.push(enemy.enemy_id);
            }
            // Restore the enemies, STILL NEED TO CALL SETENEMIES WHEN EVERYTHING'S DONE LOADING
            // Should be handled by the killzone itself
            killzone.setEnemies(enemies);
        }

        // We just downloaded the kill zone, it's synced alright!
        if (!remoteMapObject.local) {
            killzone.setSynced(true);
        }

        // Show echo notification or not
        this._showReceivedFromEcho(killzone, username);

        return killzone;
    }

    _fetchSuccess(response) {
        super._fetchSuccess(response);
        // no super call required
        console.assert(this instanceof KillZoneMapObjectGroup, 'this is not a KillZoneMapObjectGroup', this);

        let killzones = response.killzone;

        // Now draw the killzones on the map
        for (let index in killzones) {
            if (killzones.hasOwnProperty(index)) {
                this._restoreObject(killzones[index]);
            }
        }
    }

    /**
     * Creates a whole new pull.
     * @returns {KillZone}
     */
    createNewPull() {
        let killZone = this._restoreObject({
            id: -1,
            color: c.map.killzone.polygonOptions.color,
            floor_id: -1, // Only for the killzone location which is not set from a 'new pull'
            killzoneenemies: [],
            lat: null,
            lng: null,
            // Bit of a hack, we don't want the synced event to be fired in this case, we only want it _after_ the ID has been
            // set by calling save() below. That will then trigger object:add and the killzone will have it's ID for the UI
            local: true
        });
        killZone.save();
        return killZone;
    }
}