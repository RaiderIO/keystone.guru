/**
 * @property objects {KillZone[]}
 */
class KillZoneMapObjectGroup extends MapObjectGroup {
    constructor(manager, editable) {
        super(manager, MAP_OBJECT_GROUP_KILLZONE, '', editable);

        this.title = 'Hide/show killzone';
        this.fa_class = 'fa-bullseye';
    }

    /**
     * @inheritDoc
     */
    _createLayer(remoteMapObject) {
        let layer = null;
        if (remoteMapObject.lat !== null && remoteMapObject.lng !== null) {
            layer = (new LeafletKillZoneMarker()).setLatLng(L.latLng(remoteMapObject.lat, remoteMapObject.lng));
        }
        return layer;
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

        this.saveAll();
    }

    _createMapObject(layer, options = {}) {
        console.assert(this instanceof KillZoneMapObjectGroup, 'this is not an KillZoneMapObjectGroup', this);

        return new KillZone(this.manager.map, layer);
    }

    /**
     * Creates a whole new pull.
     * @param enemyIds array Any enemies that must be in the pull from the start
     * @returns {KillZone}
     */
    createNewPull(enemyIds = []) {
        console.assert(this instanceof KillZoneMapObjectGroup, 'this is not a KillZoneMapObjectGroup', this);
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
            index: this.objects.length + 1,
            // Bit of a hack, we don't want the synced event to be fired in this case, we only want it _after_ the ID has been
            // set by calling save() below. That will then trigger object:add and the killzone will have it's ID for the UI
            local: true
        });

        // Change the color as necessary
        if (getState().getPullGradientApplyAlways()) {
            this.applyPullGradient();
        }

        killZone.save();

        this.signal('killzone:new', {newKillZone: killZone});
        return killZone;
    }

    /**
     * Applies the pull gradient to killzones
     * @param save {boolean}
     * @param saveOnComplete {function|null}
     */
    applyPullGradient(save = false, saveOnComplete = null) {
        console.assert(this instanceof KillZoneMapObjectGroup, 'this is not a KillZoneMapObjectGroup', this);

        let count = this.objects.length;
        let handlers = getState().getPullGradientHandlers();
        for (let i = 0; i < count; i++) {
            for (let killZoneIndex in this.objects) {
                if (this.objects.hasOwnProperty(killZoneIndex)) {
                    let killZone = this.objects[killZoneIndex];
                    if (killZone.getIndex() === (i + 1)) {
                        // Prevent division by 0
                        killZone.color = pickHexFromHandlers(handlers, count === 1 ? 50 : (i / count) * 100);
                        break;
                    }
                }
            }
        }

        if (save) {
            this.saveAll(['color'], saveOnComplete);
        }
    }

    /**
     * Saves all KillZones using the mass update endpoint.
     * @param fields {string|array}
     * @param onComplete {function|null} Called when saveAll completed
     */
    saveAll(fields = '*', onComplete = null) {
        console.assert(this instanceof KillZoneMapObjectGroup, 'this is not a KillZoneMapObjectGroup', this);
        let self = this;

        let killZonesData = [];
        for (let i = 0; i < this.objects.length; i++) {
            let killZone = this.objects[i];

            killZonesData.push(killZone.getSaveData(fields));
        }

        $.ajax({
            type: 'PUT',
            url: `/ajax/${getState().getDungeonRoute().publicKey}/${MAP_OBJECT_GROUP_KILLZONE}`,
            dataType: 'json',
            data: {
                killzones: killZonesData
            },
            success: function (json) {
                for (let i = 0; i < self.objects.length; i++) {
                    self.objects[i].setSynced(true);
                    self.objects[i].onSaveSuccess(json);
                }
            },
            complete: function () {
                if (typeof onComplete === 'function') {
                    onComplete();
                }
            }
        });
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
                    if (killZone.layer !== null && killZone.floor_id === getState().getCurrentFloor().id) {
                        this.setMapObjectVisibility(killZone, true);
                    }
                }
            }

            this.setVisibility(true);
        }
    }
}