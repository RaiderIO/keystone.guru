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

// $(function () {
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

// });

class KillZone extends MapObject {
    constructor(map, layer) {
        super(map, layer, {name: 'killzone', has_route_model_binding: true});

        let self = this;
        this.id = 0;
        this.floor_id = null;
        this.label = 'KillZone';
        this.color = '#000'; // This is just a default that should never be used anywhere
        this.description = '';
        this.index = 0;
        // May be changed based on the amount of enemies in our pull (see redrawConnectionsToEnemies())
        this.indexLabelDirection = 'center';
        // List of IDs of selected enemies
        this.enemies = [];
        this.spellIds = [];
        this.spells = [];
        // List of IDs of enemies that
        this.overpulledEnemies = [];
        // Temporary list of enemies when we received them from the server
        this.enemyConnectionsLayerGroup = null;
        // Layer that is shown to the user and that he/she can click on to make adjustments to this killzone. May be null
        this.enemiesLayer = null;
        this.overpulledEnemiesLayer = null;

        this.setSynced(false);

        this.register('object:changed', this, function (objectChangedEvent) {
            self.redrawConnectionsToEnemies();
        });

        this.map.register(['map:refresh'], this, function (shownHiddenEvent) {
            self.redrawConnectionsToEnemies();
        });

        // Disconnect any enemies from us if they were teeming, but the new state is not teeming
        getState().getMapContext().register('teeming:changed', this, function (teemingChangedEvent) {
            let teeming = teemingChangedEvent.data.teeming;

            // If we're visible for teeming, and we're now no longer teeming, remove ourselves from our current killzone
            let enemyMapObjectGroup = self.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_ENEMY);
            let hasRemovedEnemy = false;
            let currentEnemies = [...self.enemies];
            for (let i = 0; i < currentEnemies.length; i++) {
                let enemyId = currentEnemies[i];
                let enemy = enemyMapObjectGroup.findMapObjectById(enemyId);
                if (enemy !== null && enemy.teeming === 'visible' && !teeming) {
                    self._removeEnemy(enemy);
                    hasRemovedEnemy = true;
                }
            }

            // Commit changes if necessary
            if (hasRemovedEnemy) {
                self.save();
                self.redrawConnectionsToEnemies();
            }
        });

        // // External change (due to delete mode being started, for example)
        this.map.register('map:mapstatechanged', this, this._mapStateChanged.bind(this));

        getState().register('mapzoomlevel:changed', this, this._mapZoomLevelChanged.bind(this));
        getState().register('killzonesnumberstyle:changed', this, this._numberStyleChanged.bind(this));
        let killZoneMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_KILLZONE);
        killZoneMapObjectGroup.register('killzone:changed', this, this._onKillZoneChanged.bind(this));
    }

    /**
     * @inheritDoc
     */
    _getAttributes(force) {
        console.assert(this instanceof KillZone, 'this is not a KillZone', this);

        if (this._cachedAttributes !== null && !force) {
            return this._cachedAttributes;
        }

        let self = this;

        return this._cachedAttributes = super._getAttributes(force).concat([
            new Attribute({
                name: 'floor_id',
                type: 'int',
                edit: false, // Not directly changeable by user
            }),
            new Attribute({
                name: 'color',
                type: 'color',
                setter: this._setColor.bind(this),
                default: this._getColorDefault.bind(this)
            }),
            new Attribute({
                name: 'description',
                type: 'text',
                edit: false, // Not directly changeable by user
                default: null
            }),
            new Attribute({
                name: 'lat',
                type: 'float',
                edit: false,
                getter: function () {
                    return self.layer !== null ? self.layer.getLatLng().lat : null;
                },
                default: 0
            }),
            new Attribute({
                name: 'lng',
                type: 'float',
                edit: false,
                getter: function () {
                    return self.layer !== null ? self.layer.getLatLng().lng : null;
                },
                default: 0
            }),
            new Attribute({
                name: 'index',
                type: 'int',
                edit: false,
                setter: this.setIndex.bind(this),
                default: 1
            }),
            new Attribute({
                name: 'enemies',
                type: 'array',
                edit: false,
                default: [],
                setter: this._setEnemiesFromRemote.bind(this),
            }),
            new Attribute({
                name: 'spells',
                type: 'array',
                edit: false,
                default: [],
                setter: this._setSpellsFromRemote.bind(this),
                getter: function () {
                    return self.spellIds;
                }
            }),
        ]);
    }

    /**
     * @inheritDoc
     **/
    loadRemoteMapObject(remoteMapObject, parentAttribute = null) {
        super.loadRemoteMapObject(remoteMapObject, parentAttribute);

        // Hide the layer of the killzone
        this.setDefaultVisible(remoteMapObject.floor_id === getState().getCurrentFloor().id);
    }

    /**
     * Sets the color for the killzone.
     * @param color
     */
    _setColor(color) {
        console.assert(this instanceof KillZone, 'this was not a KillZone', this);

        this.color = color || this._getColorDefault();
    }

    /**
     *
     * @returns {string}
     * @protected
     */
    _getColorDefault() {
        return c.map.killzone.defaultColor();
    }

    /**
     *
     * @param remoteEnemies
     */
    _setEnemiesFromRemote(remoteEnemies) {
        console.assert(this instanceof KillZone, 'this is not a KillZone', this);

        // Reconstruct the enemies we're coupled with in a format we expect
        if (typeof remoteEnemies === 'undefined') {
            return;
        }

        // Check if the remote enemies differ in one shape or form of our current list
        let enemiesEqual = this.enemies.length === remoteEnemies.length;
        let enemies = [];
        for (let i = 0; i < remoteEnemies.length; i++) {
            let enemyId = remoteEnemies[i];
            enemies.push(enemyId);

            // If we haven't already signified the enemies are not equal
            // If we do not have an enemy at this index or if the enemy's ID at this index is not the same
            if (enemiesEqual && (!this.enemies.hasOwnProperty(i) || this.enemies[i] !== enemyId)) {
                enemiesEqual = false;
            }
        }

        // Do not unnecessarily call this function - it can be heavy
        if (!enemiesEqual) {
            this.setEnemies(enemies);
        }
    }

    /**
     *
     * @param {Object} remoteSpells
     * @private
     */
    _setSpellsFromRemote(remoteSpells) {
        console.assert(this instanceof KillZone, 'this is not a KillZone', this);

        if (typeof remoteSpells === 'undefined') {
            return;
        }

        let spellIds = [];

        this.spells = [];
        for (let i = 0; i < remoteSpells.length; i++) {
            let remoteSpell = remoteSpells[i];

            spellIds.push(remoteSpell.id);
        }

        this.setSpells(spellIds);
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
            enemyDetachedEvent.data.previous.id === this.id) {
            this._removeEnemy(enemyDetachedEvent.context);
            this.redrawConnectionsToEnemies();
        }
    }

    /**
     * Called whenever a prideful enemy has changed (moved its position, is deleted etc.)
     * @param objectChangedEvent {Object}
     * @private
     */
    _pridefulEnemyChanged(objectChangedEvent) {
        this.redrawConnectionsToEnemies();
    }

    /**
     * Called whenever the obsolete state of an enemy has changed
     * @param enemyObsoleteChangedEvent {Object}
     * @private
     */
    _enemyObsoleteChanged(enemyObsoleteChangedEvent) {
        this.redrawConnectionsToEnemies();

        this.signal('killzone:obsoleteenemychanged', {enemy: enemyObsoleteChangedEvent.context});
    }

    /**
     * @param enemyOverpulledChangedEvent {Object}
     * @private
     */
    _enemyOverpulledChanged(enemyOverpulledChangedEvent) {

        /** @type {Enemy} */
        let enemy = enemyOverpulledChangedEvent.context;

        if (enemy.getOverpulledKillZoneId() !== this.id) {
            this.removeOverpulledEnemy(enemy);
        }

        this.redrawConnectionsToEnemies();
    }

    /**
     *
     * @param enemy {Enemy}
     */
    removeOverpulledEnemy(enemy) {
        console.assert(this instanceof KillZone, 'this was not a KillZone', this);

        let index = $.inArray(enemy.id, this.overpulledEnemies);
        if (index !== -1) {
            // Remove it
            let deleted = this.overpulledEnemies.splice(index, 1);
            if (deleted.length === 1) {
                let enemyMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_ENEMY);
                let enemy = enemyMapObjectGroup.findMapObjectById(deleted[0]);
                enemy.unregister('overpulled:changed', this);

                this.signal('killzone:overpulledenemyremoved', {enemy: enemy});
            }
        }
    }

    /**
     * Removes an enemy from this killzone.
     * @param enemy {Enemy} The enemy object to remove.
     * @private
     */
    _removeEnemy(enemy) {
        console.assert(this instanceof KillZone, 'this was not a KillZone', this);

        // Deselect if necessary
        let externalChange = enemy.getKillZone() === null || enemy.getKillZone().id !== this.id;
        // console.warn(`KZ ${this.id} (${this.index}) removing enemy ${enemy.id} (${enemy.npc.name}) (external: ${externalChange})`);
        if (!externalChange) {
            enemy.setKillZone(null);
        }

        let index = $.inArray(enemy.id, this.enemies);
        if (index !== -1) {
            // Remove it
            let deleted = this.enemies.splice(index, 1);
            if (deleted.length === 1) {
                let enemyMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_ENEMY);
                /** @type {Enemy} */
                let enemy = enemyMapObjectGroup.findMapObjectById(deleted[0]);
                // This enemy left us, no longer interested in it
                enemy.unregister('obsolete:changed', this);
                enemy.unregister('killzone:detached', this);
                if (enemy.isPridefulNpc()) {
                    enemy.unregister('object:changed', this);
                }
            }
            this.signal('killzone:enemyremoved', {enemy: enemy});
        }

        // If the enemy we're removing from the pull is the real one
        if (!externalChange && enemy.isAwakenedNpc() && !enemy.isLinkedToLastBoss()) {
            // If we're detaching this awakened enemy from a pull, show the other
            let linkedAwakenedEnemy = enemy.getLinkedAwakenedEnemy();
            if (linkedAwakenedEnemy !== null) {
                /** @type {Enemy} */
                let enemyMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_ENEMY);

                let finalBoss = enemyMapObjectGroup.getFinalBoss();

                // Link it to the same pull as the final boss is part of
                if (finalBoss !== null && finalBoss.getKillZone() instanceof KillZone) {
                    // Add it to the target kill zone and save it; you cannot kill it twice
                    let finalBossKillZone = finalBoss.getKillZone();
                    finalBossKillZone._addEnemy(linkedAwakenedEnemy);
                    finalBossKillZone.save();
                }

                // Show the awakened enemy that's near the boss
                enemyMapObjectGroup.setMapObjectVisibility(linkedAwakenedEnemy, true);
            }
        }
    }

    /**
     *
     * @param enemy {Enemy}
     */
    addOverpulledEnemy(enemy) {
        console.assert(this instanceof KillZone, 'this was not a KillZone', this);

        if (!this.overpulledEnemies.includes(enemy.id)) {
            this.overpulledEnemies.push(enemy.id);

            enemy.register('overpulled:changed', this, this._enemyOverpulledChanged.bind(this));
            this.signal('killzone:overpulledenemyadded', {enemy: enemy});
        }
    }

    /**
     * Adds an enemy to this kill zone.
     * @param enemy {Enemy} The enemy object to add.
     * @private
     */
    _addEnemy(enemy) {
        console.assert(this instanceof KillZone, 'this was not a KillZone', this);
        // console.warn(`KZ ${this.id} (${this.index}) adding enemy ${enemy.id} (${enemy.npc.name})`);

        enemy.setKillZone(this);
        // Add it, but don't double add it
        if ($.inArray(enemy.id, this.enemies) === -1) {
            this.enemies.push(enemy.id);

            // We're interested in knowing when this enemy has detached itself (by assigning to another killzone, for example)
            enemy.register('killzone:detached', this, this._enemyDetached.bind(this));
            if (enemy.isPridefulNpc()) {
                enemy.register('object:changed', this, this._pridefulEnemyChanged.bind(this));
            }
            enemy.register('obsolete:changed', this, this._enemyObsoleteChanged.bind(this));
            this.signal('killzone:enemyadded', {enemy: enemy});
        }

        // If the enemy we're adding to the pull is the real one, not the one attached to a pack with the final boss
        if (enemy.isAwakenedNpc() && enemy.enemy_pack_id === null) {
            // If we're attaching this awakened enemy to a pull, deselect the other
            let linkedAwakenedEnemy = enemy.getLinkedAwakenedEnemy();
            if (linkedAwakenedEnemy !== null) {
                if (linkedAwakenedEnemy.getKillZone() instanceof KillZone) {
                    // Remove it from the target kill zone and save it; you cannot kill it twice
                    let linkedAwakenedEnemyKillZone = linkedAwakenedEnemy.getKillZone();
                    linkedAwakenedEnemyKillZone._removeEnemy(linkedAwakenedEnemy);
                    linkedAwakenedEnemyKillZone.save();
                }

                // Hide the awakened enemy that's near the boss
                let enemyMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_ENEMY);
                enemyMapObjectGroup.setMapObjectVisibility(linkedAwakenedEnemy, false);
            }
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
            /** @type Enemy */
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
        let ignorePackBuddies = enemySelectedEvent.data.ignorePackBuddies;
        console.assert(enemy instanceof Enemy, 'enemy is not an Enemy', enemy);
        console.assert(this instanceof KillZone, 'this is not a KillZone', this);

        // Only when we're saved
        if (this.id === 0) {
            console.warn('Not handling _enemySelected; killzone not (yet) saved!', this, enemy.id);
            return;
        }

        let index = $.inArray(enemy.id, this.enemies);
        // Already exists, user wants to deselect the enemy
        let removed = index >= 0;
        let isOverpulledEnemy = enemySelectedEvent.context instanceof SelectKillZoneEnemySelectionOverpull;

        // Keep track of the killzone it may have been attached to, we need to refresh it ourselves here since then
        // the actions only get done once. If the previous enemy was part of a pack of 10 enemies, which were part of
        // the same killzone, it would otherwise send 10 save messages (if this.save() was part of killzone:detached
        // logic. By removing that there and adding it here, we get one clean save message.
        let previousKillZone = enemy.getKillZone();

        // If the enemy was part of a pack..
        if (enemy.enemy_pack_id !== 0 && !ignorePackBuddies) {
            let packBuddies = enemy.getPackBuddies();
            packBuddies.push(enemy);
            // Add all enemies in the pack to this killzone as well
            for (let i = 0; i < packBuddies.length; i++) {
                let packBuddy = packBuddies[i];
                // If we should couple the enemy in addition to our own..
                if (packBuddy.enemy_pack_id === enemy.enemy_pack_id) {
                    // Remove it too if we should
                    this._addOrRemoveEnemy(packBuddy, removed, isOverpulledEnemy);
                }
            }
        } else {
            this._addOrRemoveEnemy(enemy, removed, isOverpulledEnemy);
        }

        this.redrawConnectionsToEnemies();

        // The previous killzone lost a member, we have to notify it and save it
        if (previousKillZone !== null && previousKillZone.id !== this.id) {
            previousKillZone.redrawConnectionsToEnemies();
        }
    }

    /**
     *
     * @param enemy
     * @param removed
     * @param isOverpulled
     * @private
     */
    _addOrRemoveEnemy(enemy, removed, isOverpulled) {
        if (removed) {
            if (isOverpulled) {
                this.removeOverpulledEnemy(enemy);
            } else {
                this._removeEnemy(enemy);
            }
        } else {
            if (isOverpulled) {
                this.addOverpulledEnemy(enemy);
            } else {
                this._addEnemy(enemy);
            }
        }
    }

    /**
     * Called when enemy selection for this killzone has changed (started/finished)
     * @param mapStateChangedEvent {Object}
     * @private
     */
    _mapStateChanged(mapStateChangedEvent) {
        console.assert(this instanceof KillZone, 'this is not a KillZone', this);

        let previousState = mapStateChangedEvent.data.previousMapState;
        let newState = mapStateChangedEvent.data.newMapState;
        if (previousState instanceof EnemySelection || newState instanceof EnemySelection) {
            // Redraw any changes as necessary (for example, user (de-)selected a killzone, must redraw to update selection visuals)
            this.redrawConnectionsToEnemies();

            // If live session, we should still do this - we want to know when we've selected an enemy to be overpulled
            if (this.map.options.edit || getState().getMapContext() instanceof MapContextLiveSession) {
                if (previousState instanceof EnemySelection && previousState.getMapObject().id === this.id) {
                    // Unreg if we were listening
                    previousState.unregister('enemyselection:enemyselected', this);
                }

                if (newState instanceof EnemySelection && newState.getMapObject().id === this.id) {
                    // Reg for changes to our killzone if necessary
                    newState.register('enemyselection:enemyselected', this, this._enemySelected.bind(this));
                }
            }
        }
    }

    /**
     *
     * @param mapZoomLevelChangedEvent {Object}
     * @private
     */
    _mapZoomLevelChanged(mapZoomLevelChangedEvent) {
        console.assert(this instanceof KillZone, 'this is not a KillZone', this);

        // // Only if we actually have a tooltip to refresh
        if (this.isVisible()) {
            let currZoomLevel = parseInt(mapZoomLevelChangedEvent.data.mapZoomLevel);
            let prevZoomLevel = parseInt(mapZoomLevelChangedEvent.data.previousMapZoomLevel);

            let currentFloorPercentageDisplayZoom = c.map.killzone.getCurrentFloorPercentageDisplayZoom();
            // Don't do any unnecessary redrawings, they are costly
            if (// Zoomed out
                (prevZoomLevel === currentFloorPercentageDisplayZoom && prevZoomLevel > currZoomLevel) ||
                // Zoomed in
                (currZoomLevel === currentFloorPercentageDisplayZoom && currZoomLevel > prevZoomLevel)
            ) {
                this.redrawConnectionsToEnemies();
            }
        }
    }

    /**
     *
     * @param numberStyleChangedEvent
     * @private
     */
    _numberStyleChanged(numberStyleChangedEvent) {
        console.assert(this instanceof KillZone, 'this is not a KillZone', this);

        // Only if we actually have a tooltip to refresh
        if (this.isVisible()) {
            this.redrawConnectionsToEnemies();
        }
    }

    /**
     *
     * @param killZoneChangedEvent
     * @private
     */
    _onKillZoneChanged(killZoneChangedEvent) {
        console.assert(this instanceof KillZone, 'this is not a KillZone', this);
        // Refresh percentages of killzone text should the need arise
        if (killZoneChangedEvent.data.killzone.index < this.index &&
            getState().getMapZoomLevel() >= c.map.killzone.getCurrentFloorPercentageDisplayZoom()) {
            this.bindTooltip();
        }
    }

    /**
     * Get the LatLngs of all enemies that are visible on the current floor.
     * @param enemyIds {Array}
     * @returns {[]}
     * @private
     */
    _getVisibleEntitiesLatLngs(enemyIds) {
        console.assert(this instanceof KillZone, 'this is not a KillZone', this);
        let self = this;

        let enemyMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_ENEMY);

        let latLngs = [];
        let otherFloorsWithEnemies = [];
        let currentFloorId = getState().getCurrentFloor().id;
        $.each(enemyIds, function (i, id) {
            /** @type {Enemy} */
            let enemy = enemyMapObjectGroup.findMapObjectById(id);

            if (enemy !== null) {
                if (!enemy.isObsolete()) {
                    if (enemy.floor_id === currentFloorId) {
                        let latLng = enemy.layer.getLatLng();
                        latLngs.push([latLng.lat, latLng.lng]);
                    }
                    // The enemy was not on this floor; add its floor to the 'add floor switch as part of pack' list
                    else if (!otherFloorsWithEnemies.includes(enemy.floor_id)) {
                        otherFloorsWithEnemies.push(enemy.floor_id);
                    }
                }
            } else {
                console.warn('Unable to find enemy with id ' + id + ' for KZ ' + self.id + ' on floor ' + self.floor_id + ', ' +
                    'cannot draw connection, this enemy was probably removed during a migration?');
            }
        });

        // Alpha shapes
        if (this.layer !== null && this.floor_id !== null) {
            // Killzone not on this floor, draw a line to the floor that it is
            if (currentFloorId !== this.floor_id && this.floor_id !== null) {
                otherFloorsWithEnemies.push(this.floor_id);
            }
            // Killzone on this floor, include the lat/lng in our bounds
            else {
                let selfLatLng = this.layer.getLatLng();
                latLngs.unshift([selfLatLng.lat, selfLatLng.lng]);
            }
        }

        // If there are other floors with enemies AND enemies on this floor..
        if (otherFloorsWithEnemies.length > 0 && latLngs.length > 0) {
            console.info(`Pull ${this.index} has enemies on other floors`, otherFloorsWithEnemies);
            let floorSwitchMapObjectGroup = self.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_DUNGEON_FLOOR_SWITCH_MARKER);
            if (_.size(floorSwitchMapObjectGroup.objects) > 0) {
                $.each(otherFloorsWithEnemies, function (i, floorId) {
                    // Build a list of eligible floor switchers to the floor ID we want (there may be multiple!)
                    // In the case of Waycrest, we want to select the closest floor switch marker, not the 1st index which
                    // may be really far away. We also only want floor switches that are on _our_ floor.
                    let floorSwitchMarkerCandidates = [];
                    $.each(floorSwitchMapObjectGroup.objects, function (j, floorSwitchMapObject) {
                        if (floorSwitchMapObject.floor_id === currentFloorId &&
                            floorSwitchMapObject.target_floor_id === floorId) {
                            floorSwitchMarkerCandidates.push(floorSwitchMapObject);
                        }
                    });

                    if( floorSwitchMarkerCandidates.length === 0 ) {
                        console.info(`Could not find a floor switch marker from floor_id ${currentFloorId} -> target_floor_id ${floorId}`);
                        return false;
                    }

                    // Calculate a rough center of our bounds
                    let ourCenterLatLng = latLngs.length === 1 ? latLngs[0] : getCenteroid(latLngs);
                    let closestFloorSwitchMarker = null;
                    let closestDistance = 999999999999999;

                    // console.log('ourCenterLatLng', ourCenterLatLng);
                    //
                    // console.log('floorSwitchMarkerCandidates', floorSwitchMarkerCandidates);
                    // Find the closest floor switch marker
                    $.each(floorSwitchMarkerCandidates, function (j, floorSwitchMapObject) {
                        let distance = floorSwitchMapObject.layer.getLatLng().distanceTo(ourCenterLatLng);
                        // console.log(closestDistance, distance);
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
        }

        // If finally we only have one enemy and that's it, add a dummy location so that the pull index will be shown on the layer
        if (latLngs.length === 1) {
            latLngs.push([
                latLngs[0][0] + 0.1,
                latLngs[0][1] + 0.1,
            ]);

            this.indexLabelDirection = 'right';
        } else {
            this.indexLabelDirection = 'center';
        }

        return latLngs;
    }

    /**
     * @inheritDoc
     */
    isEditable() {
        return false;
    }

    /**
     * @inheritDoc
     */
    isDeletable() {
        return false;
    }

    /**
     * @inheritDoc
     */
    isEditableByPopup() {
        return false;
    }

    /**
     * Checks if this kill zone has a kill area or not.
     * @returns {boolean}
     */
    hasKillArea() {
        return this.layer !== null;
    }

    /**
     * Checks if this kill zone should be visible or not.
     * @returns {boolean|boolean}
     */
    isKillAreaVisible() {
        return this.hasKillArea() && getState().getCurrentFloor().id === this.floor_id;
    }

    /**
     * Checks if this kill zone kills the last boss or not.
     * @return {boolean}
     */
    isLinkedToLastBoss() {
        let result = false;

        let enemyMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_ENEMY);
        for (let i = 0; i < this.enemies.length; i++) {
            let enemy = enemyMapObjectGroup.findMapObjectById(this.enemies[i]);
            if (enemy !== null && enemy.isLastBoss()) {
                result = true;
                break;
            }
        }

        return result;
    }

    /**
     * Get the floor IDs that this KillZone is spread across.
     * @returns {[]}
     */
    getFloorIds() {
        let result = [];

        let enemyMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_ENEMY);

        $.each(this.enemies, function (i, id) {
            /** @type Enemy */
            let enemy = enemyMapObjectGroup.findMapObjectById(id);

            if (enemy !== null && !result.includes(enemy.floor_id)) {
                result.push(enemy.floor_id);
            }
        });

        return result;
    }

    /**
     * Get the enemy forces that will be added if this enemy pack is killed.
     */
    getEnemyForces() {
        let result = 0;

        let enemyMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_ENEMY);

        // We must consider overpulled enemies part of our enemy forces as well - even though they may technically not be part of the current pull
        let allEnemies = this.enemies.concat(this.overpulledEnemies);
        for (let i = 0; i < allEnemies.length; i++) {
            /** @type {Enemy} */
            let enemy = enemyMapObjectGroup.findMapObjectById(allEnemies[i]);
            // Unless this enemy is obsolete - then we don't consider it anymore for this pull
            if (enemy !== null && !enemy.isObsolete()) {
                result += enemy.getEnemyForces();
            }
        }

        return result;
    }

    /**
     * Get the amount of shrouded stacks that you will have after this pack has been killed.
     * @returns {number}
     */
    getShroudedEnemyStacksCumulative() {
        // You always get one bonus for some reason
        let result = 1;

        let killZoneMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_KILLZONE);
        for (let key in killZoneMapObjectGroup.objects) {
            let killZone = killZoneMapObjectGroup.objects[key];
            if (killZone.getIndex() <= this.getIndex()) {
                result += killZone.getShroudedEnemyStacks();
            }
        }

        return result;
    }

    /**
     * Get the amount of enemy forces that will be killed after this pack has been killed.
     * @returns {number}
     */
    getEnemyForcesCumulative() {
        let result = 0;

        let killZoneMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_KILLZONE);
        for (let key in killZoneMapObjectGroup.objects) {
            let killZone = killZoneMapObjectGroup.objects[key];
            if (killZone.getIndex() <= this.getIndex()) {
                result += killZone.getEnemyForces();
            }
        }

        return result;
    }

    /**
     *
     * @param {Array} spellIds
     */
    setSpells(spellIds) {
        this.spellIds = [];
        this.spells = [];

        let mapContext = getState().getMapContext();
        for (let i = 0; i < spellIds.length; i++) {
            let spellId = parseInt(spellIds[i]);

            this.spellIds.push(spellId);
            this.spells.push(mapContext.getSpell(spellId));
        }

        this.signal('killzone:spellschanged');
    }

    /**
     * Bulk sets the enemies for this killzone.
     * @param enemies
     */
    setEnemies(enemies) {
        console.assert(this instanceof KillZone, 'this is not a KillZone', this);

        // .sort() adjusts the array in place, but we don't care for the order
        if (_.isEqual(this.enemies.sort(), enemies.sort())) {
            console.log(`Not executing set enemies - the current and received list of enemies is the same`, this.enemies, enemies);
            return;
        }

        let self = this;

        // Remove any enemies that we may have had
        let enemyMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_ENEMY);

        // Copy enemies array as we're making changes in it by removing enemies
        let currentEnemies = [...this.enemies];
        for (let i = 0; i < currentEnemies.length; i++) {
            let enemyId = currentEnemies[i];
            /** @type Enemy */
            let enemy = enemyMapObjectGroup.findMapObjectById(enemyId);
            if (enemy !== null) {
                self._removeEnemy(enemy);
            } else {
                console.warn('Remove: unable to find enemy with id ' + enemyId + ' for KZ ' + self.id + ' on floor ' + self.floor_id + ', ' +
                    'this enemy was probably removed during a migration?');
            }
        }

        for (let i = 0; i < enemies.length; i++) {
            let enemyId = enemies[i];
            /** @type Enemy */
            let enemy = enemyMapObjectGroup.findMapObjectById(enemyId);
            if (enemy !== null) {
                if (!enemy.shouldBeIgnored()) {
                    self._addEnemy(enemy);
                } else {
                    console.log(`Not adding enemy ${enemy.id} to killzone ${this.id}, enemy should be ignored!`);
                }
            } else {
                console.warn('Add: unable to find enemy with id ' + enemyId + ' for KZ ' + self.id + ' on floor ' + self.floor_id + ', ' +
                    'this enemy was probably removed during a migration?');
            }
        }

        this.redrawConnectionsToEnemies();
    }

    /**
     * Removes any existing UI connections to enemies.
     */
    removeExistingConnectionsToEnemies() {
        console.assert(this instanceof KillZone, 'this is not a KillZone', this);

        // Remove previous layers if it's needed
        if (this.enemyConnectionsLayerGroup !== null) {
            let killZoneMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_KILLZONE);
            // Remove layers we no longer need from the layer group
            if (this.enemiesLayer !== null) {
                this.enemyConnectionsLayerGroup.removeLayer(this.enemiesLayer);
                this.enemiesLayer = null;
            }
            if (this.overpulledEnemiesLayer !== null) {
                this.enemyConnectionsLayerGroup.removeLayer(this.overpulledEnemiesLayer);
                this.overpulledEnemiesLayer = null;
            }
            // And finally remove the layer group from the KZ layer group
            killZoneMapObjectGroup.layerGroup.removeLayer(this.enemyConnectionsLayerGroup);

            this.enemyConnectionsLayerGroup = null;
        }
    }

    /**
     * Throws away all current visible connections to enemies, and rebuilds the visuals.
     */
    redrawConnectionsToEnemies() {
        console.assert(this instanceof KillZone, 'this is not a KillZone', this);

        let self = this;

        this.removeExistingConnectionsToEnemies();

        // Create & add new layer
        this.enemyConnectionsLayerGroup = new L.LayerGroup();

        let killZoneMapObjectGroup = self.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_KILLZONE);
        killZoneMapObjectGroup.layerGroup.addLayer(this.enemyConnectionsLayerGroup);

        // Add connections from each enemy to our location
        let latLngs = this._getVisibleEntitiesLatLngs(this.enemies);

        let p = hull(latLngs, 100);
        let opts = $.extend({}, c.map.killzone.polygonOptions, {color: this.color, fillColor: this.color});

        // Only if we can actually make an offset
        if (latLngs.length > 1 && p.length > 1) {
            try {
                p = createOffsetPolygon(
                    p.map(point => ({lat: point[0], lng: point[1]})),
                    c.map.killzone.margin,
                    c.map.killzone.arcSegments(p.length)
                );
            } catch (error) {
                // May be thrown if 'vertices overlap'
                console.warn(`Vertices overlap!`, p);
            }

            if (this.map.getMapState() instanceof EnemySelection && this.map.getMapState().getMapObject().id === this.id) {
                opts = $.extend(opts, c.map.killzone.polygonOptionsSelected);
                // Change the pulse color to be dark or light depending on the KZ color
                opts.pulseColor = isColorDark(this.color) ? opts.pulseColorLight : opts.pulseColorDark;
                this.enemiesLayer = L.polyline.antPath(p, opts);
                this.enemiesLayer._map = this.map;
            } else {
                this.enemiesLayer = L.polygon(p, opts);
            }


            // do not prevent clicking on anything else
            this.enemyConnectionsLayerGroup.setZIndex(-1000);

            this.enemyConnectionsLayerGroup.addLayer(this.enemiesLayer);

            this.bindTooltip();

            // Only add popup to the killzone
            this.enemiesLayer.on('click', function () {
                // We're now selecting this killzone
                let currentMapState = self.map.getMapState();
                let newMapState = currentMapState;

                if (!(currentMapState instanceof EditMapState) &&
                    !(currentMapState instanceof DeleteMapState) &&
                    !(currentMapState instanceof RaidMarkerSelectMapState)) {
                    // If we're already being selected..
                    if (currentMapState instanceof EnemySelection && currentMapState.getMapObject().id === self.id) {
                        newMapState = null;
                    } else if (getState().getMapContext() instanceof MapContextLiveSession) {
                        newMapState = new SelectKillZoneEnemySelectionOverpull(self.map, self);
                    } else if (self.map.options.edit) {
                        newMapState = new EditKillZoneEnemySelection(self.map, self);
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

        // Add connections from each enemy to our location
        let overpulledEnemyLatLngs = this._getVisibleEntitiesLatLngs(this.overpulledEnemies);

        if (overpulledEnemyLatLngs.length > 0) {
            this.centeroid = this.getLayerCenteroid();
            this.overpulledEnemiesLayer = new L.LayerGroup();

            for (let index in overpulledEnemyLatLngs) {
                if (overpulledEnemyLatLngs.hasOwnProperty(index)) {
                    let overpulledEnemyLatLng = overpulledEnemyLatLngs[index];

                    this.overpulledEnemiesLayer.addLayer(
                        L.polyline([
                            [this.centeroid.lat, this.centeroid.lng],
                            overpulledEnemyLatLng
                        ], opts)
                    );
                }
            }

            // do not prevent clicking on anything else
            this.enemyConnectionsLayerGroup.setZIndex(-1000);

            this.enemyConnectionsLayerGroup.addLayer(this.overpulledEnemiesLayer);
        }
    }

    /**
     * Get a latlng object describing the centeroid of the enemies layer.
     * @returns {L.latLng}
     */
    getLayerCenteroid() {
        return getCenteroid(this._getVisibleEntitiesLatLngs(this.enemies));
    }

    /**
     * The index of this KillZone.
     * @returns {number}
     */
    getIndex() {
        return this.index;
    }

    /**
     * Sets the index of this pull.
     * @param index
     */
    setIndex(index) {
        this.index = index;

        // this.bindTooltip();
    }

    /**
     *
     * @returns {boolean}
     */
    isVisible() {
        // Visible is not tied to having a layer here; we are visible if we're on the same floor
        return this._getVisibleEntitiesLatLngs(this.enemies).length > 0;
    }

    /**
     * Get the amount of shrouded stacks in this pull
     */
    getShroudedEnemyStacks() {
        let result = 0;

        let enemyMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_ENEMY);
        for (let i = 0; i < this.enemies.length; i++) {
            let enemyId = this.enemies[i];
            /** @type {Enemy} */
            let enemy = enemyMapObjectGroup.findMapObjectById(enemyId);
            if (enemy.isShrouded()) {
                result += 1;
            } else if (enemy.isShroudedZulGamux()) {
                result += 3;
            }
        }

        return result;
    }

    /**
     *
     * @returns {boolean}
     */
    isVisibleOnScreen() {
        let result = false;
        if (this.isVisible() && this.enemiesLayer !== null) {
            result = this.map.leafletMap.getBounds().contains(this.getLayerCenteroid())
        }
        return result;
    }

    bindTooltip() {
        super.bindTooltip();

        if (!this.map.options.noUI && this.enemiesLayer !== null) {
            this.enemiesLayer.unbindTooltip();

            // Only when NOT currently editing the layer
            if (!(this.map.getMapState() instanceof EnemySelection && this.map.getMapState().getMapObject().id === this.id)) {
                let tooltipText = this.index + '';
                let state = getState();

                // For speedruns, stop here and don't add anything else
                if (!state.getMapContext().isDungeonSpeedrunEnabled() &&
                    state.getMapZoomLevel() >= c.map.killzone.getCurrentFloorPercentageDisplayZoom()) {
                    if (state.getKillZonesNumberStyle() === NUMBER_STYLE_PERCENTAGE) {
                        let enemyForcesCumulativePercent = getFormattedPercentage(this.getEnemyForcesCumulative(), this.map.enemyForcesManager.getEnemyForcesRequired());
                        tooltipText += ` - ${enemyForcesCumulativePercent}%`;
                    } else if (state.getKillZonesNumberStyle() === NUMBER_STYLE_ENEMY_FORCES) {
                        tooltipText += ` - ${this.getEnemyForcesCumulative()}/${this.map.enemyForcesManager.getEnemyForcesRequired()}`;
                    }
                }

                try {
                    let spellTemplate = Handlebars.templates['map_killzone_tooltip'];

                    let data = $.extend({}, getHandlebarsDefaultVariables(), {
                        tooltipText: tooltipText,
                        pull_color: this.color,
                        spells: this.spells,
                    });

                    this.enemiesLayer.bindTooltip(spellTemplate(data), {
                        direction: this.indexLabelDirection,
                        className: 'leaflet-tooltip-killzone-index',
                        permanent: true
                    });
                } catch (error) {
                    console.warn('Too fast adding new pulls - couldn\'t add tooltip to killzone because previous was not actually added to map yet!');
                }
            }
        }
    }

    // To be overridden by any implementing classes
    onLayerInit() {
        console.assert(this instanceof KillZone, 'this is not a KillZone', this);
        console.assert(this.layer instanceof L.Layer, 'this.layer is not an L.Layer', this);
        super.onLayerInit();

        let self = this;

        // When we're moved, keep drawing the connections anew
        this.layer.on('move', function () {
            self.redrawConnectionsToEnemies();
        });


        this.map.register('killzone:selectionchanged', this, this.redrawConnectionsToEnemies.bind(this));
        // When we have all data, redraw the connections. Not sooner or otherwise we may not have the enemies back yet
        this.map.register('map:mapobjectgroupsloaded', this, function () {
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
                    // Also triggered when multiple floors and the enemies we're attached to are not loaded
                    // console.warn('Unable to find enemy with id ' + id + ' for KZ ' + self.id + ' on floor ' + self.floor_id + ', ' +
                    //     'this enemy was probably removed during a migration?');
                }
            });
        });

        // When we're synced, construct the popup.  We don't know the ID before that so we cannot properly bind the popup.
        this.register('object:changed', this, function (event) {
            // Restore the connections to our enemies
            self.redrawConnectionsToEnemies();
        });
    }

    localDelete(massDelete = false) {
        // Detach from all enemies upon deletion
        this._detachFromEnemies();

        super.localDelete(massDelete);
    }

    /**
     * @inheritDoc
     */
    onSaveSuccess(json, massSave = false) {
        super.onSaveSuccess(json);

        this.redrawConnectionsToEnemies();

        this.signal('killzone:changed', {enemy_forces: json.enemy_forces, mass_save: massSave});
    }

    /**
     * @inheritDoc
     **/
    onDeleteSuccess(json, massDelete = false) {
        super.onDeleteSuccess(json);

        this.signal('killzone:changed', {enemy_forces: json.enemy_forces, mass_delete: massDelete});
    }

    cleanup() {
        let self = this;
        let state = getState();


        state.getMapContext().unregister('teeming:changed', this);
        let killZoneMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_KILLZONE);
        killZoneMapObjectGroup.unregister('killzone:changed', this);
        state.unregister('mapzoomlevel:changed', this);
        state.unregister('killzonesnumberstyle:changed', this);
        this.unregister('object:deleted', this);
        this.unregister('object:changed', this);
        this.map.unregister('map:refresh', this);
        this.map.unregister('map:mapstatechanged', this);
        this.map.unregister('killzone:selectionchanged', this);
        this.map.unregister('map:mapobjectgroupsloaded', this);

        let enemyMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_ENEMY);
        $.each(enemyMapObjectGroup.objects, function (i, enemy) {
            enemy.setSelectable(false);
            enemy.unregister('enemy:selected', self);
            enemy.unregister('killzone:detached', self);
        });

        this.removeExistingConnectionsToEnemies();

        super.cleanup();
    }

    toString() {
        return `Pull ${this.index}`;
    }
}
