class KillZoneMapObjectGroup extends MapObjectGroup {
    constructor(manager, editable) {
        super(manager, MAP_OBJECT_GROUP_KILLZONE, '', editable);

        let self = this;

        this.title = 'Hide/show killzone';
        this.fa_class = 'fa-bullseye';

        if (this.manager.map.options.echo) {
            window.Echo.join(this.manager.map.options.appType + '-route-edit.' + getState().getDungeonRoute().publicKey)
                .listen('.killzone-changed', (e) => {
                    // Always restore killzones; we can view them from the sidebar
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

    /**
     * We override this function because we do not want to destroy all killzones upon refresh. Instead, we want to hide
     * everything and show that which needs to be shown.
     * @param beforeRefreshEvent
     * @private
     */
    _onBeforeRefresh(beforeRefreshEvent) {
        console.assert(this instanceof KillZoneMapObjectGroup, 'this is not an KillZoneMapObjectGroup', this);

        // Remove any layers that were added before
        this._removeObjectsFromLayer.call(this);

        if (this.layerGroup !== null) {
            console.warn('Removing layer group from map');
            // Remove ourselves from the map prior to refreshing
            this.manager.map.leafletMap.removeLayer(this.layerGroup);
        }

        // Prevent writing our empty state back to the killzone list upon initial load
        if (this.initialized) {
            // Write the killzones we know back in the state so we can restore them later on
            // getState().updateKillZones(this.objects);
        }
    }

    /**
     * May be overridden by implementing classes
     * @param fetchEvent
     * @private
     */
    _onFetchSuccess(fetchEvent) {
        // No assert here, we manage the killzones ourselves and they are persistent across refreshes
        this._fetchSuccess(fetchEvent.data.response);
    }

    _onObjectDeleted(data) {
        super._onObjectDeleted(data);

        $.each(this.objects, function (i, obj) {
            obj.setIndex(i + 1);
        });
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

        let layer = null;
        // Only if it was set, and if it was on this floor
        if (remoteMapObject.lat !== null && remoteMapObject.lng !== null) {
            layer = new LeafletKillZoneMarker();
            layer.setLatLng(L.latLng(remoteMapObject.lat, remoteMapObject.lng));
        }

        // Only create a new one if it's new for us
        if (killzone === null) {
            /** @var KillZone killzone */
            killzone = this.createNew(layer);
        } else {
            // Update the killzone layer with that of the remote
            killzone.layer = layer;
        }

        // Now update the killzone to its new properties
        killzone.id = remoteMapObject.id;
        killzone.floor_id = remoteMapObject.floor_id;
        killzone.setIndex(this.objects.length);
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

            killzone.setEnemies(enemies);
        }

        // Hide the layer of the killzone
        killzone.setDefaultVisible(remoteMapObject.floor_id === getState().getCurrentFloor().id);

        // We just downloaded the kill zone, it's synced alright!
        if (!remoteMapObject.local) {
            killzone.setSynced(true);
        }

        // Show echo notification or not
        this._showReceivedFromEcho(killzone, username);

        return killzone;
    }

    /**
     * Creates a whole new pull.
     * @param enemyIds array Any enemies that must be in the pull from the start
     * @returns {KillZone}
     */
    createNewPull(enemyIds = []) {
        // Construct an object equal to that received from the server
        let killzoneEnemies = [];
        for (let i = 0; i < enemyIds.length; i++) {
            killzoneEnemies.push({enemy_id: enemyIds[i]});
        }

        let killZone = this._restoreObject({
            id: -1,
            color: c.map.killzone.polygonOptions.color(),
            floor_id: -1, // Only for the killzone location which is not set from a 'new pull'
            killzoneenemies: killzoneEnemies,
            lat: null,
            lng: null,
            // Bit of a hack, we don't want the synced event to be fired in this case, we only want it _after_ the ID has been
            // set by calling save() below. That will then trigger object:add and the killzone will have it's ID for the UI
            local: true
        });
        killZone.save();

        this.signal('killzone:new', {newKillZone: killZone});
        return killZone;
    }

    _fetchSuccess(response) {
        // no super call, we're handling this by ourselves
        console.assert(this instanceof KillZoneMapObjectGroup, 'this is not a KillZoneMapObjectGroup', this);

        if (!this.initialized) {
            let killZones = getState().getKillZones();

            // Now draw the enemies on the map, if any
            for (let index in killZones) {
                // Only if actually set
                if (killZones.hasOwnProperty(index)) {
                    let killZone = killZones[index];
                    // Only restore enemies for the current floor
                    this._restoreObject(killZone);
                }
            }

            this.initialized = true;

            this.signal('restorecomplete');
        } else {
            // Show any killzones that are on the new floor
            for (let index in this.objects) {
                if (this.objects.hasOwnProperty(index)) {
                    let killZone = this.objects[index];
                    // Re-set the enemy list
                    killZone.setEnemies([...killZone.enemies]);

                    // Only display the kill zone's kill area if it's on our current floor
                    if( killZone.layer !== null && killZone.floor_id === getState().getCurrentFloor().id ) {
                        this.setMapObjectVisibility(killZone, true);
                    }
                }
            }

            this.setVisibility(true);
        }
    }
}