/**
 * @property objects {KillZone[]}
 */
class KillZoneMapObjectGroup extends MapObjectGroup {
    constructor(manager, editable) {
        super(manager, MAP_OBJECT_GROUP_KILLZONE, editable);

        this.title = 'Hide/show killzone';
        this.fa_class = 'fa-bullseye';
    }

    /**
     * @inheritDoc
     **/
    _getRawObjects() {
        return getState().getMapContext().getKillZones();
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

    _onObjectDeleted(data) {
        super._onObjectDeleted(data);
        let mapObject = data.context;

        let toSave = [];

        $.each(this.objects, function (i, obj) {
            if (obj.getIndex() >= mapObject.getIndex()) {
                toSave.push(obj);
            }
            obj.setIndex(i + 1);
        });

        // If last pull is deleted, we don't need to change anything to pulls ahead of us (indices)
        if (toSave.length > 1) {
            this.massSave('*', null, toSave);
        }

        mapObject.unregister('killzone:enemyremoved', this);
        mapObject.unregister('killzone:enemyadded', this);
    }

    _createMapObject(layer, options = {}) {
        console.assert(this instanceof KillZoneMapObjectGroup, 'this is not an KillZoneMapObjectGroup', this);

        return new KillZone(this.manager.map, layer);
    }

    _createNewMapObject(layer, options) {
        let mapObject = super._createNewMapObject(layer, options);

        mapObject.register('killzone:enemyremoved', this, this._onKillZoneEnemyRemoved.bind(this));
        mapObject.register('killzone:enemyadded', this, this._onKillZoneEnemyAdded.bind(this));

        return mapObject;
    }

    _onKillZoneEnemyRemoved(killZoneEnemyRemovedEvent) {
        this.signal('killzone:enemyremoved', {
            killzone: killZoneEnemyRemovedEvent.context,
            enemy: killZoneEnemyRemovedEvent.data.enemy
        });
        this.signal('killzone:changed', {killzone: killZoneEnemyRemovedEvent.context});
    }

    _onKillZoneEnemyAdded(killZoneEnemyAddedEvent) {
        this.signal('killzone:enemyadded', {
            killzone: killZoneEnemyAddedEvent.context,
            enemy: killZoneEnemyAddedEvent.data.enemy
        });
        this.signal('killzone:changed', {killzone: killZoneEnemyAddedEvent.context});
    }

    _onObjectChanged(objectChangedEvent) {
        super._onObjectChanged(objectChangedEvent);

        this.signal('killzone:changed', {killzone: objectChangedEvent.context});
    }

    /**
     * Creates a whole new pull.
     * @param enemyIds array Any enemies that must be in the pull from the start
     * @returns {KillZone}
     */
    createNewPull(enemyIds = []) {
        console.assert(this instanceof KillZoneMapObjectGroup, 'this is not a KillZoneMapObjectGroup', this);
        // Construct an object equal to that received from the server
        let killZoneEnemies = [];
        for (let i = 0; i < enemyIds.length; i++) {
            killZoneEnemies.push({enemy_id: enemyIds[i]});
        }

        let killZone = this._loadMapObject({
            id: -1,
            color: c.map.killzone.polygonOptions.color(),
            floor_id: -1, // Only for the killzone location which is not set from a 'new pull'
            killzoneenemies: killZoneEnemies,
            lat: null,
            lng: null,
            index: this.objects.length + 1,
            // Bit of a hack, we don't want the synced event to be fired in this case, we only want it _after_ the ID has been
            // set by calling save() below. That will then trigger object:add and the killzone will have it's ID for the UI
            local: true
        });

        // Change the color as necessary
        if (getState().getMapContext().getPullGradientApplyAlways()) {
            this.applyPullGradient(true);
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
            this.massSave(['color'], saveOnComplete);
        }
    }

    /**
     * Saves all KillZones using the mass update endpoint.
     * @param fields {string|array}
     * @param onComplete {function|null} Called when massSave completed
     * @param killZones {array}
     */
    massSave(fields = '*', onComplete = null, killZones = []) {
        console.assert(this instanceof KillZoneMapObjectGroup, 'this is not a KillZoneMapObjectGroup', this);

        // All killzones if not supplied
        if (killZones.length === 0) {
            killZones = this.objects;
        }

        let killZonesData = [];
        for (let i = 0; i < killZones.length; i++) {
            let killZone = killZones[i];

            // Only those that can be saved
            if (killZone.id > 0) {
                killZonesData.push(killZone.getSaveData(fields));
            }
        }

        $.ajax({
            type: 'PUT',
            url: `/ajax/${getState().getMapContext().getPublicKey()}/${MAP_OBJECT_GROUP_KILLZONE}`,
            dataType: 'json',
            data: {
                killzones: killZonesData
            },
            success: function (json) {
                for (let i = 0; i < killZones.length; i++) {
                    killZones[i].setSynced(true);
                    killZones[i].onSaveSuccess(json);
                }
            },
            complete: function () {
                if (typeof onComplete === 'function') {
                    onComplete();
                }
            }
        });
    }

    /**
     * Checks if a specific enemy is killed by any kill zone.
     * @param enemyId {number}
     * @returns {boolean}
     */
    isEnemyKilled(enemyId) {
        let result = false;

        for (let i = 0; i < this.objects.length; i++) {
            if (this.objects[i].enemies.includes(enemyId)) {
                result = true;
                break;
            }
        }

        return result;
    }

    /**
     * Checks if the user has killed all unskippables, if not, returns false. True otherwise
     * @returns {boolean}
     */
    hasKilledAllUnskippables() {
        let result = true;

        let enemyMapObjectGroup = this.manager.getByName(MAP_OBJECT_GROUP_ENEMY);
        let mapContext = getState().getMapContext();

        for (let i = 0; i < enemyMapObjectGroup.objects.length; i++) {
            let enemy = enemyMapObjectGroup.objects[i];
            // If this enemy SHOULD have been killed by the user
            if (enemy.unskippable &&
                // If not teeming, OR if enemy is teeming AND we're teeming, or inverse that. THEN this enemy counts, otherwise it does not
                (enemy.teeming === null || (enemy.teeming === 'visible' && mapContext.getTeeming()) || (enemy.teeming === 'invisible' && !mapContext.getTeeming()))
            ) {
                // But if it's not..
                if (!this.isEnemyKilled(enemy.id)) {
                    // console.warn(`Has not killed enemy ${enemy.id}!`);
                    result = false;
                    break;
                }
            }
        }

        return result;
    }

    // _fetchSuccess(response) {
    //     // no super call, we're handling this by ourselves
    //     console.assert(this instanceof KillZoneMapObjectGroup, 'this is not a KillZoneMapObjectGroup', this);
    //
    //     if (!this.initialized) {
    //         let killZones = getState().getMapContext().getKillZones();
    //
    //         // Now draw the enemies on the map, if any
    //         for (let index in killZones) {
    //             // Only if actually set
    //             if (killZones.hasOwnProperty(index)) {
    //                 let killZone = killZones[index];
    //                 // Only restore enemies for the current floor
    //                 this._loadMapObject(killZone);
    //             }
    //         }
    //
    //         this.initialized = true;
    //
    //         this.signal('loadcomplete');
    //     } else {
    //         // Show any killzones that are on the new floor
    //         for (let index in this.objects) {
    //             if (this.objects.hasOwnProperty(index)) {
    //                 let killZone = this.objects[index];
    //                 // Re-set the enemy list
    //                 killZone.setEnemies([...killZone.enemies]);
    //
    //                 // Only display the kill zone's kill area if it's on our current floor
    //                 if (killZone.layer !== null && killZone.floor_id === getState().getCurrentFloor().id) {
    //                     this.setMapObjectVisibility(killZone, true);
    //                 }
    //             }
    //         }
    //
    //         this.setVisibility(true);
    //     }
    // }
}