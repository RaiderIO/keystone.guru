$(function () {
    L.Draw.KillZone = L.Draw.Marker.extend({
        statics: {
            TYPE: 'killzone'
        },
        options: {
            icon: LeafletKillZoneIcon
        },
        initialize: function (map, options) {
            // Save the type so super can fire, need to do this as cannot do this.TYPE :(
            this.type = L.Draw.KillZone.TYPE;
            L.Draw.Feature.prototype.initialize.call(this, map, options);
        }
    });
});

let LeafletKillZoneIcon = L.divIcon({
    html: '<i class="fas fa-bullseye"></i>',
    iconSize: [30, 30],
    className: 'marker_div_icon_font_awesome marker_div_icon_killzone'
});

let LeafletKillZoneIconEditMode = L.divIcon({
    html: '<i class="fas fa-bullseye"></i>',
    iconSize: [30, 30],
    className: 'marker_div_icon_font_awesome marker_div_icon_killzone killzone_icon_big leaflet-edit-marker-selected'
});

let LeafletKillZoneMarker = L.Marker.extend({
    options: {
        icon: LeafletKillZoneIcon
    }
});

class KillZone extends MapObject {
    constructor(map, layer) {
        super(map, layer);

        let self = this;
        this.id = 0;
        this.label = 'KillZone';
        this.color = c.map.killzone.polygonOptions.color();
        // List of IDs of selected enemies
        this.enemies = [];
        // Temporary list of enemies when we received them from the server
        this.remoteEnemies = [];
        this.enemyConnectionsLayerGroup = null;

        this.setColors(c.map.killzone.colors);
        this.setSynced(false);

        // // We gotta remove the connections manually since they're self managed here.
        // this.map.register('map:beforerefresh', this, function () {
        //     // In case someone switched dungeons prior to finishing the kill zone edit
        //     self.map.setSelectModeKillZone(null);
        //     self.removeExistingConnectionsToEnemies();
        // });
        //
        // // External change (due to delete mode being started, for example)
        this.map.register('map:mapstatechanged', this, this._mapStateChanged.bind(this));
    }

    /**
     * An enemy that was added to us has now detached itself.
     * @param enemyDetachedEvent
     * @private
     */
    _enemyDetached(enemyDetachedEvent) {
        console.assert(this instanceof KillZone, 'this was not a KillZone', this);

        // Only remove it when it concerns us
        if (enemyDetachedEvent.data.previous === null ||
            enemyDetachedEvent.data.previous.id === this.id
        ) {
            this._removeEnemy(enemyDetachedEvent.context);
            this.redrawConnectionsToEnemies();
        }
    }

    /**
     * Removes an enemy from this killzone.
     * @param enemy Object The enemy object to remove.
     * @private
     */
    _removeEnemy(enemy) {
        console.assert(this instanceof KillZone, 'this was not a KillZone', this);

        // Deselect if necessary
        if (enemy.getKillZone() !== null && enemy.getKillZone().id === this.id) {
            enemy.setKillZone(null);
        }

        let index = $.inArray(enemy.id, this.enemies);
        if (index !== -1) {
            // Remove it
            let deleted = this.enemies.splice(index, 1);
            if (deleted.length === 1) {
                let enemyMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_ENEMY);
                // This enemy left us, no longer interested in it
                enemyMapObjectGroup.findMapObjectById(deleted[0]).unregister('killzone:detached', this);
            }
            this.signal('killzone:enemyremoved', this, {enemy: enemy});
        }
    }

    /**
     * Adds an enemy to this killzone.
     * @param enemy Object The enemy object to add.
     * @private
     */
    _addEnemy(enemy) {
        console.assert(this instanceof KillZone, 'this was not a KillZone', this);

        enemy.setKillZone(this);
        // Add it, but don't double add it
        if ($.inArray(enemy.id, this.enemies) === -1) {
            this.enemies.push(enemy.id);

            // We're interested in knowing when this enemy has detached itself (by assigning to another killzone, for example)
            enemy.register('killzone:detached', this, this._enemyDetached.bind(this));
            this.signal('killzone:enemyadded', this, {enemy: enemy});
        }
    }

    /**
     * Detaches this killzone from all its enemies.
     * @private
     */
    _detachFromEnemies() {
        console.assert(this instanceof KillZone, 'this was not a KillZone', this);

        this.removeExistingConnectionsToEnemies();

        let enemyMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_ENEMY);
        // Copy enemies array as we're making changes in it by removing enemies
        let currentEnemies = [...this.enemies];
        for (let i = 0; i < currentEnemies.length; i++) {
            let enemyId = currentEnemies[i];
            let enemy = enemyMapObjectGroup.findMapObjectById(enemyId);
            // When found, actually detach it
            if (enemy !== null) {
                enemy.setKillZone(null);
            }
        }
    }

    /**
     * Triggered when an enemy was selected by the user when edit mode was enabled.
     * @param enemySelectedEvent {object} The event that was triggered when an enemy was selected (or de-selected).
     * Will add/remove the enemy to the list to be redrawn.
     */
    _enemySelected(enemySelectedEvent) {
        let enemy = enemySelectedEvent.data.enemy;
        console.assert(enemy instanceof Enemy, 'enemy is not an Enemy', enemy);
        console.assert(this instanceof KillZone, 'this is not an KillZone', this);

        let index = $.inArray(enemy.id, this.enemies);
        // Already exists, user wants to deselect the enemy
        let removed = index >= 0;

        // Keep track of the killzone it may have been attached to, we need to refresh it ourselves here since then
        // the actions only get done once. If the previous enemy was part of a pack of 10 enemies, which were part of
        // the same killzone, it would otherwise send 10 save messages (if this.save() was part of killzone:detached
        // logic. By removing that there and adding it here, we get one clean save message.
        let previousKillZone = enemy.getKillZone();

        // If the enemy was part of a pack..
        if (enemy.enemy_pack_id > 0) {
            let enemyMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_ENEMY);
            for (let i = 0; i < enemyMapObjectGroup.objects.length; i++) {
                let enemyCandidate = enemyMapObjectGroup.objects[i];
                // If we should couple the enemy in addition to our own..
                if (enemyCandidate.enemy_pack_id === enemy.enemy_pack_id) {
                    // Remove it too if we should
                    if (removed) {
                        this._removeEnemy(enemyCandidate);
                    }
                    // Or add it too if we need
                    else {
                        this._addEnemy(enemyCandidate);
                    }
                }
            }
        } else {
            if (removed) {
                this._removeEnemy(enemy);
            } else {
                this._addEnemy(enemy);
            }
        }

        this.redrawConnectionsToEnemies();
        this.save();

        // The previous killzone lost a member, we have to notify it and save it
        if (previousKillZone !== null && previousKillZone.id !== this.id) {
            previousKillZone.redrawConnectionsToEnemies();
            previousKillZone.save();
        }
    }

    /**
     * Called when enemy selection for this killzone has changed (started/finished)
     * @param mapStateChangedEvent
     * @private
     */
    _mapStateChanged(mapStateChangedEvent) {
        console.assert(this instanceof KillZone, 'this is not a KillZone', this);

        let previousState = mapStateChangedEvent.data.previousMapState;
        let newState = mapStateChangedEvent.data.newMapState;
        if (previousState instanceof EnemySelection || newState instanceof EnemySelection) {
            // Unreg always
            // if( previousState instanceof EnemySelection ) {
            //     console.log('Unregging');
            //     previousState.unregister('enemyselection:enemyselected', this);
            // }
            //
            // // Reg if necessary
            // if( newState instanceof EnemySelection ) {
            //     console.log('Regging');
            //     newState.register('enemyselection:enemyselected', this, this._enemySelected.bind(this));
            // }

            // Redraw any changes as necessary (for example, user (de-)selected a killzone, must redraw to update selection visuals)
            this.redrawConnectionsToEnemies();

            if (previousState instanceof EnemySelection && previousState.getMapObject().id === this.id) {
                // May save when nothing has changed, but that's okay
                this.save();
            }
        }
    }

    /**
     * Get the LatLngs of all enemies that are visible on the current floor.
     * @returns {[]}
     * @private
     */
    _getVisibleEntitiesLatLngs() {
        console.assert(this instanceof KillZone, 'this is not a KillZone', this);
        let self = this;

        let enemyMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_ENEMY);

        let latLngs = [];
        let otherFloorsWithEnemies = [];
        $.each(this.enemies, function (i, id) {
            let enemy = enemyMapObjectGroup.findMapObjectById(id);

            if (enemy !== null) {
                if (enemy.layer !== null) {
                    let latLng = enemy.layer.getLatLng();
                    latLngs.push([latLng.lat, latLng.lng]);

                    // Draw lines to self if killzone mode is enabled
                    if (self.map.currentSelectModeKillZone === self) {
                        let layer = L.polyline([
                            latLng,
                            self.layer.getLatLng()
                        ], c.map.killzone.polylineOptions);
                        // do not prevent clicking on anything else
                        self.enemyConnectionsLayerGroup.setZIndex(-1000);

                        self.enemyConnectionsLayerGroup.addLayer(layer);
                    }
                }
                // The enemy was not on this floor; add its floor to the 'add floor switch as part of pack' list
                else if (!otherFloorsWithEnemies.includes(enemy.floor_id)) {
                    otherFloorsWithEnemies.push(enemy.floor_id);
                }
            } else {
                console.warn('Unable to find enemy with id ' + id + ' for KZ ' + self.id + 'on floor ' + self.floor_id + ', ' +
                    'cannot draw connection, this enemy was probably removed during a migration?');
            }
        });


        // Alpha shapes
        if (this.isKillZoneVisible()) {
            let selfLatLng = this.layer.getLatLng();
            latLngs.unshift([selfLatLng.lat, selfLatLng.lng]);
        }

        // If there are other floors with enemies..
        if (otherFloorsWithEnemies.length > 0) {
            let floorSwitchMapObjectGroup = self.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_DUNGEON_FLOOR_SWITCH_MARKER);
            $.each(otherFloorsWithEnemies, function (i, floorId) {
                // Build a list of eligible floor switchers to the floor ID we want (there may be multiple!)
                // In the case of Waycrest, we want to select the closest floor switch marker, not the 1st index which
                // may be really far away
                let floorSwitchMarkerCandidates = [];
                $.each(floorSwitchMapObjectGroup.objects, function (j, floorSwitchMapObject) {
                    if (floorSwitchMapObject.target_floor_id === floorId) {
                        floorSwitchMarkerCandidates.push(floorSwitchMapObject);
                    }
                });

                console.assert(floorSwitchMarkerCandidates.length > 0, 'floorSwitchMarkerCandidates.length is <= 0', self);

                // Calculate a rough center of our bounds
                let ourCenterLatLng = self._getLayerCenteroid(latLngs);
                let closestFloorSwitchMarker = null;
                let closestDistance = 9999999;

                // Find the closest floor switch marker
                $.each(floorSwitchMarkerCandidates, function (j, floorSwitchMapObject) {
                    let distance = floorSwitchMapObject.layer.getLatLng().distanceTo(ourCenterLatLng);
                    if (closestDistance > distance) {
                        closestDistance = distance;
                        closestFloorSwitchMarker = floorSwitchMapObject;
                    }
                });
                console.assert(closestFloorSwitchMarker instanceof DungeonFloorSwitchMarker,
                    'closestFloorSwitchMarker is not a DungeonFloorSwitchMarker', closestFloorSwitchMarker);

                // Add its location to the list!
                let latLng = closestFloorSwitchMarker.layer.getLatLng();
                latLngs.push([latLng.lat, latLng.lng]);
            });
        }

        return latLngs;
    }

    /**
     * Get the center LatLng of this killzone's layer
     * @param arr
     * @see https://stackoverflow.com/questions/22796520/finding-the-center-of-leaflet-polygon
     * @return {object}
     */
    _getLayerCenteroid(arr) {
        let reduce = arr.reduce(function (x, y) {
            return [x[0] + y[0] / arr.length, x[1] + y[1] / arr.length]
        }, [0, 0]);

        return L.latLng(reduce[0], reduce[1]);
    }

    /**
     * Checks if this killzone should be visible or not.
     * @returns {boolean|boolean}
     */
    isKillZoneVisible() {
        return this.layer !== null && getState().getCurrentFloor().id === this.floor_id;
    }

    /**
     * Get the enemy forces that will be added if this enemy pack is killed.
     */
    getEnemyForces() {
        let result = 0;

        let enemyMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_ENEMY);
        for (let i = 0; i < this.enemies.length; i++) {
            let enemyId = this.enemies[i];
            let enemy = enemyMapObjectGroup.findMapObjectById(enemyId);
            if (enemy !== null && enemy.npc !== null) {
                result += enemy.npc.enemy_forces;
            }
        }

        return result;
    }

    /**
     * Bulk sets the enemies for this killzone.
     * @param enemies
     */
    setEnemies(enemies) {
        console.assert(this instanceof KillZone, 'this is not an KillZone', this);
        let self = this;

        // Remove any enemies that we may have had
        let enemyMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_ENEMY);

        // Copy enemies array as we're making changes in it by removing enemies
        let currentEnemies = [...this.enemies];
        $.each(currentEnemies, function (i, id) {
            let enemy = enemyMapObjectGroup.findMapObjectById(id);
            if (enemy !== null) {
                self._removeEnemy(enemy);
            } else {
                console.warn('Remove: unable to find enemy with id ' + id + ' for KZ ' + self.id + ' on floor ' + self.floor_id + ', ' +
                    'this enemy was probably removed during a migration?');
            }
        });

        $.each(enemies, function (i, id) {
            let enemy = enemyMapObjectGroup.findMapObjectById(id);
            if (enemy !== null) {
                self._addEnemy(enemy);
            } else {
                console.warn('Add: unable to find enemy with id ' + id + ' for KZ ' + self.id + ' on floor ' + self.floor_id + ', ' +
                    'this enemy was probably removed during a migration?');
            }
        });

        this.redrawConnectionsToEnemies();
    }

    /**
     * Removes any existing UI connections to enemies.
     */
    removeExistingConnectionsToEnemies() {
        console.assert(this instanceof KillZone, 'this is not an KillZone', this);

        // Remove previous layers if it's needed
        if (this.enemyConnectionsLayerGroup !== null) {
            let killZoneMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_KILLZONE);
            killZoneMapObjectGroup.layerGroup.removeLayer(this.enemyConnectionsLayerGroup);
        }
    }

    /**
     * Throws away all current visible connections to enemies, and rebuilds the visuals.
     */
    redrawConnectionsToEnemies() {
        console.assert(this instanceof KillZone, 'this is not an KillZone', this);

        let self = this;

        this.removeExistingConnectionsToEnemies();

        // Create & add new layer
        this.enemyConnectionsLayerGroup = new L.LayerGroup();

        let killZoneMapObjectGroup = self.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_KILLZONE);
        killZoneMapObjectGroup.layerGroup.addLayer(this.enemyConnectionsLayerGroup);

        // Add connections from each enemy to our location
        let enemyMapObjectGroup = self.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_ENEMY);
        let latLngs = this._getVisibleEntitiesLatLngs();

        let p = hull(latLngs, 100);

        // Only if we can actually make an offset
        if (latLngs.length > 1 && p.length > 1) {
            let offset = new Offset();
            p = offset.data(p).arcSegments(c.map.killzone.arcSegments(p.length)).margin(c.map.killzone.margin);

            let opts = $.extend({}, c.map.killzone.polygonOptions, {color: this.color, fillColor: this.color});

            let layer;
            if (this.map.getMapState() instanceof EnemySelection && this.map.getMapState().getMapObject().id === this.id) {
                opts = $.extend(opts, c.map.killzone.polygonOptionsSelected);
                // Change the pulse color to be dark or light depending on the KZ color
                opts.pulseColor = isColorDark(this.color) ? opts.pulseColorLight : opts.pulseColorDark;
                layer = L.polyline.antPath(p, opts);
            } else {
                layer = L.polygon(p, opts);
            }


            // do not prevent clicking on anything else
            this.enemyConnectionsLayerGroup.setZIndex(-1000);

            this.enemyConnectionsLayerGroup.addLayer(layer);

            // Only add popup to the killzone
            if (this.isEditable()) {
                layer.on('click', function () {
                    // We're now selecting this killzone
                    let currentMapState = self.map.getMapState();
                    let newMapState = currentMapState;
                    if (!(currentMapState instanceof EditMapState) && !(currentMapState instanceof DeleteMapState)) {
                        // If we're already being selected..
                        if (currentMapState instanceof EnemySelection && currentMapState.getMapObject().id === self.id) {
                            newMapState = null;
                        } else if (self.map.options.edit) {
                            newMapState = new KillZoneEnemySelection(self.map, self);
                        } else {
                            newMapState = new ViewKillZoneEnemySelection(self.map, self);
                        }
                    }

                    // Only if there would be a change
                    if (newMapState !== currentMapState) {
                        // Set to null or not
                        self.map.setMapState(newMapState);
                    }
                });
            }
        }
    }

    /**
     * Get a
     * @returns {object}
     */
    getLayerCenteroid() {
        return this._getLayerCenteroid(this._getVisibleEntitiesLatLngs());
    }

    // To be overridden by any implementing classes
    onLayerInit() {
        console.assert(this instanceof KillZone, 'this is not a KillZone', this);
        console.assert(this.layer instanceof L.Layer, 'this.layer is not an L.Layer', this);
        super.onLayerInit();

        let self = this;

        if (this.map.options.edit) {
            this.layer.on('click', function (clickEvent) {
                let mapState = self.map.getMapState();
                // Can only assign
                if (mapState === null) {

                }
            });
        }

        // When we're moved, keep drawing the connections anew
        this.layer.on('move', function () {
            self.redrawConnectionsToEnemies();
        });


        this.map.register('killzone:selectionchanged', this, this.redrawConnectionsToEnemies.bind(this));
        // When we have all data, redraw the connections. Not sooner or otherwise we may not have the enemies back yet
        this.map.register('map:mapobjectgroupsfetchsuccess', this, function () {
            // Hide the killzone layer when in preview mode
            if (self.map.options.noUI) {
                let killZoneMapObjectGroup = self.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_KILLZONE);
                killZoneMapObjectGroup.setMapObjectVisibility(self, false);
            }
        });

        this.register('object:deleted', this, function () {
            let enemyMapObjectGroup = self.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_ENEMY);
            $.each(self.enemies, function (i, id) {
                let enemy = enemyMapObjectGroup.findMapObjectById(id);
                if (enemy !== null) {
                    // Detach ourselves
                    enemy.setKillZone(null);
                } else {
                    console.warn('Unable to find enemy with id ' + id + ' for KZ ' + self.id + ' on floor ' + self.floor_id + ', ' +
                        'this enemy was probably removed during a migration?');
                }
            });
        });

        // When we're synced, construct the popup.  We don't know the ID before that so we cannot properly bind the popup.
        this.register('synced', this, function (event) {
            // Restore the connections to our enemies
            self.redrawConnectionsToEnemies();
        });
    }

    edit() {
        console.assert(this instanceof KillZone, 'this was not a KillZone', this);
        this.save();
        this.redrawConnectionsToEnemies();
        this.signal('killzone:changed');
    }

    delete() {
        let self = this;
        console.assert(this instanceof KillZone, 'this was not a KillZone', this);

        $.ajax({
            type: 'POST',
            url: '/ajax/' + getState().getDungeonRoute().publicKey + '/killzone/' + self.id,
            dataType: 'json',
            data: {
                _method: 'DELETE'
            },
            success: function (json) {
                // Detach from all enemies upon deletion
                self._detachFromEnemies();
                self.localDelete();
                self.signal('killzone:synced', {enemy_forces: json.enemy_forces});
            },
            error: function (xhr, textStatus, errorThrown) {
                // Even if we were synced, make sure user knows it's no longer / an error occurred
                self.setSynced(false);

                defaultAjaxErrorFn(xhr, textStatus, errorThrown);
            }
        });
    }

    save() {
        let self = this;
        console.assert(this instanceof KillZone, 'this was not a KillZone', this);

        $.ajax({
            type: 'POST',
            url: '/ajax/' + getState().getDungeonRoute().publicKey + '/killzone',
            dataType: 'json',
            data: {
                id: self.id,
                floor_id: getState().getCurrentFloor().id,
                color: self.color,
                lat: self.layer !== null ? self.layer.getLatLng().lat : null,
                lng: self.layer !== null ? self.layer.getLatLng().lng : null,
                enemies: self.enemies
            },
            success: function (json) {
                self.id = json.id;

                self.setSynced(true);
                self.signal('killzone:synced', {enemy_forces: json.enemy_forces});
            },
            error: function (xhr, textStatus, errorThrown) {
                // Even if we were synced, make sure user knows it's no longer / an error occurred
                self.setSynced(false);

                defaultAjaxErrorFn(xhr, textStatus, errorThrown);
            }
        });
    }

    cleanup() {
        let self = this;

        // this.unregister('synced', this); // Not needed as super.cleanup() does this
        this.map.unregister('map:mapstatechanged', this);
        this.map.unregister('killzone:selectionchanged', this);
        this.map.unregister('map:mapobjectgroupsfetchsuccess', this);
        this.map.unregister('map:beforerefresh', this);

        let enemyMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_ENEMY);
        $.each(enemyMapObjectGroup.objects, function (i, enemy) {
            enemy.setSelectable(false);
            enemy.unregister('enemy:selected', self);
            enemy.unregister('killzone:detached', self);
        });

        this.removeExistingConnectionsToEnemies();

        super.cleanup();
    }
}