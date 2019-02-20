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

        this.saving = false;
        this.deleting = false;
        this.setColors(c.map.admin.mapobject.colors);
        this.setSynced(false);

        this.enemyConnectionLayerGroup = null;

        // When we're synced, connect to our connected enemy
        this.register(['shown', 'hidden'], this, function (hiddenEvent) {
            if (self.mdt_id > 0) {
                if (hiddenEvent.data.visible) {
                    self.redrawConnectionToEnemy();
                } else {
                    self.removeExistingConnectionToEnemy();
                }
            }
        });

        // Register for changes to the selection event
        this.map.register('map:enemyselectionmodechanged', this, this._enemySelectionModeChanged.bind(this));
    }

    /**
     * Called when enemy selection for this enemy has changed (started/finished)
     * @param selectionEvent
     * @private
     */
    _enemySelectionModeChanged(selectionEvent) {
        console.assert(this instanceof AdminEnemy, this, 'this is not an AdminEnemy');

        // Redraw any changes as necessary
        // this.redrawConnectionToEnemy();

        // Get whatever object is handling the enemy selection
        let enemySelection = this.map.getEnemySelection();

        let selectedMapObject = enemySelection.getMapObject();

        // We calculate this because tooltip binding is expensive for 100s of enemies on screen. Generally a MDT
        // enemy is close to the enemy we're selecting, so we only really need to disable tooltips for the enemies that
        // are close by. If they're far away, we don't really care if we get a tooltip for the odd time it happens
        // Advantage is that this dramatically speeds up the JS.
        // 100 = 10 distance
        let closeEnough = getDistanceSquared(selectedMapObject.layer.getLatLng(), this.layer.getLatLng()) < 100;
        // console.log(closeEnough);
        // Only if we were the enemy that initiated the selection
        if (selectionEvent.data.finished) {
            if (closeEnough) {
                // Attach tooltip again
                this.bindTooltip();
            }

            if (selectedMapObject === this) {
                // May save when nothing has changed, but that's okay
                let connectedEnemy = this.getConnectedEnemy();
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
            this.layer.unbindTooltip();
        }
    }

    /**
     * Get the MDT enemy that is attached to this enemy. NOT the other way around.
     */
    getConnectedEnemy() {
        console.assert(this instanceof AdminEnemy, this, 'this was not an AdminEnemy');
        let result = null;

        if (this._connectedEnemy === null) {
            let self = this;

            let enemyMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_ENEMY);
            // We're an enemy, we need to find an MDT enemy instead
            if (!this.is_mdt && this.mdt_id > 0) {
                $.each(enemyMapObjectGroup.objects, function (i, mdtEnemy) {
                    // Only MDT enemies, mdtEnemy.mdt_id is actually the clone index for MDT, combined with npc_id this gives us
                    // a unique ID
                    if (mdtEnemy.is_mdt && self.npc_id === mdtEnemy.npc_id && self.mdt_id === mdtEnemy.mdt_id) {
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
        console.assert(this instanceof AdminEnemy, this, 'this is not an AdminEnemy');
        this.mdt_id = -1;
        this._connectedEnemy = null;
    }

    /**
     * May only edit when we're not an MDT enemy
     * @returns {boolean}
     */
    isEditable() {
        console.assert(this instanceof AdminEnemy, this, 'this is not an AdminEnemy');
        return !this.is_mdt;
    }

    /**
     *
     * @returns {boolean}
     */
    isMismatched() {
        let connectedEnemy = this.getConnectedEnemy();

        return connectedEnemy.faction !== this.faction ||
            ((connectedEnemy.teeming === 'visible' && this.teeming !== 'visible') ||
                (connectedEnemy.teeming === 'hidden' && this.teeming !== 'hidden'));
    }

    /**
     * Triggered when an enemy was selected by the user when edit mode was enabled.
     * @param enemy The enemy that was selected (or de-selected). Will add/remove the enemy to the list to be redrawn.
     */
    enemySelected(enemy) {
        console.log('enemySelected', this);
        console.assert(this instanceof AdminEnemy, this, 'this is not an AdminEnemy');
        console.assert(enemy instanceof AdminEnemy, enemy, 'enemy is not an AdminEnemy');

        // Keep track of what we had
        this._previousConnectedEnemyId = this.enemy_id;

        // Unset any previously connected enemy; detach them from this MDT enemy, it no longer wants you (sorry :c)
        if (this._previousConnectedEnemyId > 0) {
            let enemyMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_ENEMY);
            let previousEnemy = enemyMapObjectGroup.findMapObjectById(this._previousConnectedEnemyId);
            previousEnemy.detachConnectedEnemy();
            // Remove its visual connection, probably better served using events but that'd add too much complexity for now
            previousEnemy.redrawConnectionToEnemy();
        }

        // We couple the enemy to ourselves (MDT enemy), not the other way around
        // This helps with drawing the lines
        enemy.mdt_id = this.mdt_id;
        this.enemy_id = enemy.id;

        // Redraw ourselves
        this.redrawConnectionToEnemy();

        // Fire an event to notify everyone an enemy has been selected for this
        this.signal('mdt_connected', {target: enemy});
        enemy.signal('mdt_connected', {target: this});

        // Finish the selection, we generally don't want to make changes multiple times. We can always restart the procedure
        this.map.finishEnemySelection();
    }

    /**
     * Removes any existing UI connections to enemies.
     */
    removeExistingConnectionToEnemy() {
        console.assert(this instanceof AdminEnemy, this, 'this was not an AdminEnemy');

        // Remove previous layers if it's needed
        if (this.enemyConnectionLayerGroup !== null) {
            let enemyMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_ENEMY);
            enemyMapObjectGroup.layerGroup.removeLayer(this.enemyConnectionLayerGroup);
        }
    }

    /**
     * Redraw connections to the enemy
     */
    redrawConnectionToEnemy() {
        console.assert(this instanceof AdminEnemy, this, 'this was not an AdminEnemy');

        this.removeExistingConnectionToEnemy();

        // If this enemy is connected to an MDT enemy
        let connectedEnemy = this.getConnectedEnemy();
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

    onLayerInit() {
        console.assert(this instanceof AdminEnemy, this, 'this was not an AdminEnemy');
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
            if (self.is_mdt && !self.map.deleteModeActive) {
                let enemySelection = self.map.getEnemySelection();
                // Can only interact with select mode if we're the one that is currently being selected
                if (enemySelection === null) {
                    let mdtEnemySelection = new MDTEnemySelection(self.map, self);
                    mdtEnemySelection.register('enemyselection:enemyselected', this, function (selectedEvent) {
                        self.enemySelected(selectedEvent.data.enemy);
                    });

                    // Start selecting enemies
                    self.map.startEnemySelection(mdtEnemySelection);
                }
                // User clicks the object again to cancel the procedure
                else if (enemySelection.getMapObject() === self) {
                    // Do not unregister enemyselectionmodechanged here; it may be changed externally as well
                    self.map.finishEnemySelection();
                }
            }
        });

        // When we're moved, keep drawing the connections anew
        self.layer.on('move', function () {
            self.redrawConnectionToEnemy();

            let connectedEnemy = self.getConnectedEnemy();
            if (connectedEnemy !== null) {
                connectedEnemy.redrawConnectionToEnemy();
            }
        });
    }

    /**
     * Since the ID may not be known at spawn time, this needs to be callable from when it is known (when it's synced to server).
     *
     * @param event
     * @private
     */
    _rebuildPopup(event) {
        console.assert(this instanceof AdminEnemy, this, 'this was not an AdminEnemy');

        if (!this.is_mdt) {
            let self = this;

            // Popup trigger function, needs to be outside the synced function to prevent multiple bindings
            // This also cannot be a private function since that'll apparently give different signatures as well.
            let popupOpenFn = function (event) {
                console.log('popupOpenFn');

                $('#enemy_edit_popup_teeming_' + self.id).val(self.teeming);
                $('#enemy_edit_popup_faction_' + self.id).val(self.faction);
                $('#enemy_edit_popup_enemy_forces_override_' + self.id).val(self.enemy_forces_override);
                $('#enemy_edit_popup_npc_' + self.id).val(self.npc_id);

                // Refresh all select pickers so they work again
                refreshSelectPickers();

                let $submitBtn = $('#enemy_edit_popup_submit_' + self.id);

                $submitBtn.unbind('click');
                $submitBtn.bind('click', function () {
                    self.teeming = $('#enemy_edit_popup_teeming_' + self.id).val();
                    self.faction = $('#enemy_edit_popup_faction_' + self.id).val();
                    self.enemy_forces_override = $('#enemy_edit_popup_enemy_forces_override_' + self.id).val();
                    self.npc_id = $('#enemy_edit_popup_npc_' + self.id).val();

                    self.edit();
                });
            };

            let customPopupHtml = $('#enemy_edit_popup_template').html();
            // Remove template so our
            let template = Handlebars.compile(customPopupHtml);

            let data = {id: self.id};

            // Build the status bar from the template
            customPopupHtml = template(data);

            let customOptions = {
                'maxWidth': '400',
                'minWidth': '300',
                'className': 'popupCustom'
            };

            self.layer.unbindPopup();
            self.layer.bindPopup(customPopupHtml, customOptions);

            // Have you tried turning it off and on again?
            self.layer.off('popupopen');
            self.layer.on('popupopen', popupOpenFn);
        }
    }

    onPopupInit() {
        console.assert(this instanceof AdminEnemy, this, 'this was not an AdminEnemy');
        // Don't actually init the popup here since we may not know the ID yet.
        // Called multiple times, so unreg first
        this.unregister('synced', this, this._rebuildPopup.bind(this));
        // When we're synced, construct the popup.  We don't know the ID before that so we cannot properly bind the popup.
        this.register('synced', this, this._rebuildPopup.bind(this));
    }

    edit() {
        console.assert(this instanceof AdminEnemy, this, 'this was not an AdminEnemy');
        this.save();
    }

    delete() {
        console.assert(this instanceof AdminEnemy, this, 'this was not an AdminEnemy');
        let self = this;
        $.ajax({
            type: 'POST',
            url: '/ajax/enemy',
            dataType: 'json',
            data: {
                _method: 'DELETE',
                id: self.id
            },
            beforeSend: function () {
                self.deleting = true;
            },
            success: function (json) {
                self.signal('object:deleted', {response: json});
            },
            complete: function () {
                self.deleting = false;
            },
            error: function () {
                self.layer.setStyle({
                    fillColor: c.map.admin.mapobject.colors.unsaved,
                    color: c.map.admin.mapobject.colors.unsavedBorder
                });
            }
        });
    }

    save() {
        console.assert(this instanceof AdminEnemy, this, 'this was not an AdminEnemy');
        let self = this;

        if (!this.is_mdt) {
            $.ajax({
                type: 'POST',
                url: '/ajax/enemy',
                dataType: 'json',
                data: {
                    id: self.id,
                    enemy_pack_id: self.enemy_pack_id,
                    npc_id: self.npc_id,
                    floor_id: self.map.getCurrentFloor().id,
                    mdt_id: self.mdt_id,
                    teeming: self.teeming,
                    faction: self.faction,
                    enemy_forces_override: self.enemy_forces_override,
                    lat: self.layer.getLatLng().lat,
                    lng: self.layer.getLatLng().lng
                },
                beforeSend: function () {
                    self.editing = true;
                    $('#enemy_edit_popup_submit_' + self.id).attr('disabled', 'disabled');
                },
                success: function (json) {
                    self.setSynced(true);
                    self.map.leafletMap.closePopup();
                    // We just received ID from creating the enemy
                    if (json.hasOwnProperty('id')) {
                        self.id = json.id;
                        // ID has changed, rebuild the popup
                        self._rebuildPopup();
                    }
                    // May be null if not set at all (yet)
                    if (json.hasOwnProperty('npc') && json.npc !== null) {
                        self.setNpc(json.npc);
                    }
                },
                complete: function () {
                    $('#enemy_edit_popup_submit_' + self.id).removeAttr('disabled');
                    self.editing = false;
                },
                error: function () {
                    // Even if we were synced, make sure user knows it's no longer / an error occurred
                    self.setSynced(false);
                }
            });
        } else {
            console.error('Cannot save an MDT enemy!');
        }
    }

    cleanup() {
        super.cleanup();

        // We're done with this event now (after finishing! otherwise we won't process the result)
        this.map.unregister('map:enemyselectionmodechanged', this);
    }
}