class AdminEnemy extends Enemy {

    constructor(map, layer) {
        super(map, layer);

        let self = this;

        this.npc_id = 0;
        // Init to an empty value
        this.enemy_pack_id = -1;
        // Filled when we're currently drawing a patrol line
        this.currentPatrolPolyline = null;

        // Used by MDT enemy to keep track of the previously connected enemy.
        // When the connected enemy changed, we need to update both the old (remove connection) and new enemy (create new connection)
        // This ID helps keep track of the old enemy.
        this._previousConnectedEnemyId = -1;
        // Cached connected enemy
        this._connectedEnemy = null;
        // Whatever enemy we're connected with if we're an MDT enemy
        this.enemy_id = -1;

        this.setSynced(false);

        this.enemyConnectionLayerGroup = null;

        // When we're synced, connect to our connected enemy
        this.register(['shown', 'hidden'], this, function (hiddenEvent) {
            if (self.mdt_id > 0) {
                if (hiddenEvent.data.visible && getState().getMdtMappingModeEnabled()) {
                    self.redrawConnectionToMDTEnemy();
                } else {
                    self.removeExistingConnectionToEnemy();
                }
            }
        });

        // When successfull, re-set our NPC
        this.register('save:success', this, function (saveSuccessEvent) {
            let json = saveSuccessEvent.data.json;
            // May be null if not set at all (yet)
            if (json.hasOwnProperty('npc') && json.npc !== null) {
                self.setNpc(json.npc);
            }
        });

        // Register for changes to the selection event
        this.map.register('map:mapstatechanged', this, this._mapStateChangedEvent.bind(this));
    }

    /**
     * Called when enemy selection for this enemy has changed (started/finished)
     * @param mapStateChangedEvent
     * @private
     */
    _mapStateChangedEvent(mapStateChangedEvent) {
        console.assert(this instanceof AdminEnemy, 'this is not an AdminEnemy', this);

        // Redraw any changes as necessary
        // this.redrawConnectionToMDTEnemy();

        // Get whatever object is handling the enemy selection
        let enemySelection = mapStateChangedEvent.data.newMapState === null ?
            mapStateChangedEvent.data.previousMapState :
            mapStateChangedEvent.data.newMapState;

        // Only if we WERE ever selecting enemies
        if (enemySelection instanceof EnemySelection && this.layer !== null) {
            let selectedMapObject = enemySelection.getMapObject();

            // We calculate this because tooltip binding is expensive for 100s of enemies on screen. Generally a MDT
            // enemy is close to the enemy we're selecting, so we only really need to disable tooltips for the enemies that
            // are close by. If they're far away, we don't really care if we get a tooltip for the odd time it happens
            // Advantage is that this dramatically speeds up the JS.
            // 100 = 10 distance
            let closeEnough = getLatLngDistanceSquared(selectedMapObject.layer.getLatLng(), this.layer.getLatLng()) < 100;
            //
            if (!(mapStateChangedEvent.data.newMapState instanceof EnemySelection)) {
                if (closeEnough) {
                    // Attach tooltip again
                    this.bindTooltip();
                }

                if (selectedMapObject === this) {
                    // May save when nothing has changed, but that's okay
                    let connectedEnemy = this.getConnectedMDTEnemy();
                    if (connectedEnemy !== null) {
                        // Save them, not us
                        connectedEnemy.save();
                    }

                    if (this._previousConnectedEnemyId > 0) {
                        let enemyMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_ENEMY);
                        let previousEnemy = enemyMapObjectGroup.findMapObjectById(this._previousConnectedEnemyId);
                        // Must be found..
                        if (previousEnemy !== null) {
                            previousEnemy.save();
                            previousEnemy.bindTooltip();
                        } else {
                            console.error('Unable to find previous enemy', this._previousConnectedEnemyId);
                        }
                    }

                    // Reset it for the next time
                    this._previousConnectedEnemyId = -1;
                }
            } else if (closeEnough) {
                // Remove tooltip whilst actively coupling. It gets in the way
                this.unbindTooltip();
            }
        }
    }

    /**
     * Get the MDT enemy that is attached to this enemy. NOT the other way around.
     * @return {Enemy}
     */
    getConnectedMDTEnemy() {
        console.assert(this instanceof AdminEnemy, 'this was not an AdminEnemy', this);
        let result = null;

        if (this._connectedEnemy === null) {
            let self = this;

            let enemyMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_ENEMY);
            // We're an enemy, we need to find an MDT enemy instead
            if (!this.is_mdt && this.mdt_id > 0) {
                $.each(enemyMapObjectGroup.objects, function (i, mdtEnemy) {
                    // Only MDT enemies, mdtEnemy.mdt_id is actually the clone index for MDT, combined with npc_id this gives us
                    // a unique ID
                    if (mdtEnemy.is_mdt && self.getMdtNpcId() === mdtEnemy.npc_id && self.mdt_id === mdtEnemy.mdt_id) {
                        result = mdtEnemy;
                        return false;
                    }
                });
                if (result === null) {
                    console.error('Unable to find MDT enemy when this enemy is coupled to one!', self);
                }
            }
            // We're an MDT enemy and we're looking for our enemy
            else if (this.is_mdt && this.enemy_id > 0) {
                $.each(enemyMapObjectGroup.objects, function (i, enemy) {
                    // Only normal enemies, the MDT enemy has a direct ID link to the enemy
                    if (!enemy.is_mdt && self.enemy_id === enemy.id) {
                        result = enemy;
                        return false;
                    }
                });
                if (result === null) {
                    console.error('Unable to find enemy when this MDT enemy is coupled to one!', self);
                }
            }
        } else {
            result = this._connectedEnemy;
        }

        return result;
    }

    /**
     * Detaches the connected MDT enemy from this enemy.
     */
    detachConnectedEnemy() {
        console.assert(this instanceof AdminEnemy, 'this is not an AdminEnemy', this);
        this.mdt_id = -1;
        this._connectedEnemy = null;
    }

    /**
     * Set this enemy to be selectable whenever the user wants to select enemies.
     * @param value boolean True or false
     */
    setSelectable(value) {
        super.setSelectable(value);

        if (this.visual !== null) {
            // Refresh the icon
            this.visual.refresh();
        }
    }

    /**
     * May only edit when we're not an MDT enemy
     * @returns {boolean}
     */
    isEditable() {
        console.assert(this instanceof AdminEnemy, 'this is not an AdminEnemy', this);
        return !this.is_mdt;
    }

    /**
     * Unlike normal enemies, admin enemies may be deleted.
     * @returns {boolean}
     */
    isDeletable() {
        return !this.is_mdt
    }

    /**
     *
     * @returns {boolean}
     */
    isMismatched() {
        let connectedEnemy = this.getConnectedMDTEnemy();

        return connectedEnemy.faction !== this.faction ||
            ((connectedEnemy.teeming === 'visible' && this.teeming !== 'visible') ||
                (connectedEnemy.teeming === 'hidden' && this.teeming !== 'hidden'));
    }

    /**
     * Triggered when an enemy was selected by the user when edit mode was enabled.
     * @param enemy The enemy that was selected (or de-selected). Will add/remove the enemy to the list to be redrawn.
     */
    enemySelected(enemy) {
        console.assert(this instanceof AdminEnemy, 'this is not an AdminEnemy', this);
        console.assert(enemy instanceof AdminEnemy, 'enemy is not an AdminEnemy', enemy);

        // Keep track of what we had
        this._previousConnectedEnemyId = this.enemy_id;

        // Unset any previously connected enemy; detach them from this MDT enemy, it no longer wants you (sorry :c)
        if (this._previousConnectedEnemyId > 0) {
            let enemyMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_ENEMY);
            let previousEnemy = enemyMapObjectGroup.findMapObjectById(this._previousConnectedEnemyId);
            previousEnemy.detachConnectedEnemy();
            // Remove its visual connection, probably better served using events but that'd add too much complexity for now
            previousEnemy.redrawConnectionToMDTEnemy();
        }

        // We couple the enemy to ourselves (MDT enemy), not the other way around
        // This helps with drawing the lines
        enemy.mdt_id = this.mdt_id;
        this.enemy_id = enemy.id;
        // Couple this as well (one way) so that the visual knows the npc ids don't match
        this.mdt_npc_id = enemy.mdt_npc_id;

        // Fire an event to notify everyone an enemy has been selected for this
        this.signal('mdt_connected', {target: enemy});
        enemy.signal('mdt_connected', {target: this});

        // Redraw ourselves
        this.redrawConnectionToMDTEnemy();
        this.visual.refresh();

        // Finish the selection, we generally don't want to make changes multiple times. We can always restart the procedure
        this.map.setMapState(null);
    }

    /**
     * Removes any existing UI connections to enemies.
     */
    removeExistingConnectionToEnemy() {
        console.assert(this instanceof AdminEnemy, 'this was not an AdminEnemy', this);

        // Remove previous layers if it's needed
        if (this.enemyConnectionLayerGroup !== null) {
            let enemyMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_ENEMY);
            enemyMapObjectGroup.layerGroup.removeLayer(this.enemyConnectionLayerGroup);
        }
    }

    /**
     * Redraw connections to the enemy
     */
    redrawConnectionToMDTEnemy() {
        console.assert(this instanceof AdminEnemy, 'this was not an AdminEnemy', this);

        this.removeExistingConnectionToEnemy();

        // If this enemy is connected to an MDT enemy
        let connectedEnemy = this.getConnectedMDTEnemy();
        let enemyMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_ENEMY);

        // Only when we should..
        if (connectedEnemy !== null) {
            if (enemyMapObjectGroup.isMapObjectVisible(this) &&
                enemyMapObjectGroup.isMapObjectVisible(connectedEnemy)) {
                // Create & add new layer
                this.enemyConnectionLayerGroup = new L.LayerGroup();

                // Add the layer to the map
                let enemyMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_ENEMY);
                enemyMapObjectGroup.layerGroup.addLayer(this.enemyConnectionLayerGroup);

                // Different options for different things
                let options = c.map.adminenemy.mdtPolylineOptions;
                if (this.isMismatched()) {
                    options = c.map.adminenemy.mdtPolylineMismatchOptions;
                }

                // Draw a line to the MDT enemy
                let layer = L.polyline([
                    connectedEnemy.layer.getLatLng(),
                    this.layer.getLatLng()
                ], options);

                // do not prevent clicking on anything else
                this.enemyConnectionLayerGroup.setZIndex(-1000);
                this.enemyConnectionLayerGroup.addLayer(layer);
            }
        }
    }

    /**
     * Called when the layer is initialized.
     */
    onLayerInit() {
        console.assert(this instanceof AdminEnemy, 'this was not an AdminEnemy', this);
        super.onLayerInit();

        let self = this;
        self.map.leafletMap.on('contextmenu', function () {
            if (self.currentPatrolPolyline !== null) {
                self.map.leafletMap.addLayer(self.currentPatrolPolyline);
                self.currentPatrolPolyline.disable();
            }
        });

        // Show a permanent tooltip for the pack's name
        self.layer.on('click', function () {
            // When deleting, we shouldn't have these interactions
            // Only when we're an MDT enemy!
            if (self.is_mdt && !(self.map.getMapState() instanceof DeleteMapState)) {
                let currentMapState = self.map.getMapState();
                // Can only interact with select mode if we're the one that is currently being selected
                if (currentMapState === null) {
                    let mdtEnemySelection = new MDTEnemySelection(self.map, self);
                    mdtEnemySelection.register('enemyselection:enemyselected', this, function (selectedEvent) {
                        self.enemySelected(selectedEvent.data.enemy);
                    });

                    // Start selecting enemies
                    self.map.setMapState(mdtEnemySelection);
                }
                // User clicks the object again to cancel the procedure
                else if (currentMapState.getMapObject() === self) {
                    // Do not unregister enemyselectionmodechanged here; it may be changed externally as well
                    self.map.setMapState(null);
                }
            }
        });

        // When we're moved, keep drawing the connections anew
        self.layer.on('move', function () {
            self.redrawConnectionToMDTEnemy();

            let connectedEnemy = self.getConnectedMDTEnemy();
            if (connectedEnemy !== null) {
                connectedEnemy.redrawConnectionToMDTEnemy();
            }
        });
    }

    bindTooltip() {
        console.assert(this instanceof AdminEnemy, 'this is not an AdminEnemy', this);
        let template = Handlebars.templates['map_enemy_tooltip_template'];

        let data = {};
        // Determine what to show for enemy forces based on override or not
        let enemy_forces = this.npc === null ? -1 : this.npc.enemy_forces;

        // Admin maps have 0 enemy forces
        if (this.enemy_forces_override >= 0 || enemy_forces >= 1) {
            // @TODO This HTML probably needs to go somewhere else
            if (this.enemy_forces_override >= 0) {
                enemy_forces = '<s>' + enemy_forces + '</s> ' +
                    '<span style="color: orange;">' + this.enemy_forces_override + '</span> ' + this._getPercentageString(this.enemy_forces_override);
            } else if (enemy_forces >= 1) {
                enemy_forces += ' ' + this._getPercentageString(enemy_forces);
            }
        } else if (enemy_forces === -1) {
            enemy_forces = 'unknown';
        }

        data = $.extend({}, getHandlebarsDefaultVariables(), {
            npc_name: this.npc === null ? lang.get('messages.no_npc_found_label') : this.npc.name,
            enemy_forces: enemy_forces,
            base_health: this.npc === null ? '-' : this.npc.base_health,
            teeming: (this.teeming === 'visible' ? 'yes' : (this.teeming === 'hidden' ? 'hidden' : 'no')),
            is_teeming: this.teeming === 'visible',
            id: this.id,
            size: c.map.enemy.calculateSize(
                this.npc === null ? this.map.options.npcsMinHealth : this.npc.base_health,
                this.map.options.npcsMinHealth,
                this.map.options.npcsMaxHealth
            ),
            faction: this.faction,
            seasonal_type: this.seasonal_type,
            seasonal_index: this.seasonal_index,
            npc_id: this.npc_id,
            npc_id_type: typeof this.npc_id,
            is_mdt: this.is_mdt,
            mdt_id: this.mdt_id,
            mdt_npc_id: this.mdt_npc_id,
            enemy_id: this.enemy_id,
            attached_to_pack: this.enemy_pack_id >= 0 ? 'true (' + this.enemy_pack_id + ')' : 'false',
            visual: this.visual !== null ? this.visual.getName() : 'undefined'
        });

        // Remove any previous tooltip
        if (this.layer !== null) {
            this.unbindTooltip();
            this.layer.bindTooltip(template(data), {
                direction: 'top'
            });
        }
    }

    cleanup() {
        super.cleanup();

        // We're done with this event now (after finishing! otherwise we won't process the result)
        this.map.unregister('map:mapstatechanged', this);
        this.unregister('save:success', this);
    }
}
