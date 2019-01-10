class AdminEnemy extends Enemy {

    constructor(map, layer) {
        super(map, layer);

        this.npc_id = 0;
        // Init to an empty value
        this.enemy_pack_id = -1;
        this.teeming = '';
        // Filled when we're currently drawing a patrol line
        this.currentPatrolPolyline = null;

        // From MDT enemy to enemy
        this.connected_enemy_id = 0;

        this.saving = false;
        this.deleting = false;
        this.setColors(c.map.admin.mapobject.colors);
        this.setSynced(false);

        this.enemyConnectionLayerGroup = null;
    }

    /**
     * Called when enemy selection for this enemy has changed (started/finished)
     * @param selectionEvent
     * @private
     */
    _enemySelectionChanged(selectionEvent) {
        console.assert(this instanceof AdminEnemy, this, 'this is not an AdminEnemy');

        // Redraw any changes as necessary
        this.redrawConnectionToEnemy();

        if (selectionEvent.data.finished) {
            // May save when nothing has changed, but that's okay
            this.save();

            // We're done with this event now (after finishing! otherwise we won't process the result)
            this.map.unregister('map:enemyselectionmodechanged', this);
        }
    }

    /**
     * Get the MDT enemy that is attached to this enemy. NOT the other way around.
     * @private
     */
    _getMDTEnemy() {
        console.assert(this instanceof AdminEnemy, this, 'this was not an AdminEnemy');
        console.assert(!this.is_mdt, this, 'this was an MDT Enemy, it should not be!');

        let result = null;
        if (this.mdt_id > 0) {
            let enemyMapObjectGroup = self.map.getMapObjectGroupByName('enemy');
            $.each(enemyMapObjectGroup.objects, function (i, mdtEnemy) {
                // Only MDT enemies, mdtEnemy.id is actually the clone index for MDT, combined with npc_id this gives us
                // a unique ID
                if (mdtEnemy.is_mdt && self.mdt_id === mdtEnemy.id && self.npc_id === mdtEnemy.npc_id) {
                    result = mdtEnemy;
                    return false;
                }
            });
        }

        return result;
    }

    /**
     * Triggered when an enemy was selected by the user when edit mode was enabled.
     * @param enemy The enemy that was selected (or de-selected). Will add/remove the enemy to the list to be redrawn.
     */
    enemySelected(enemy) {
        console.assert(this instanceof AdminEnemy, this, 'this is not an AdminEnemy');
        console.assert(enemy instanceof AdminEnemy, enemy, 'enemy is not an AdminEnemy');

        // let index = $.inArray(enemy.id, this.enemies);
        // // Already exists, user wants to deselect the enemy
        // let removed = index >= 0;
        //
        // // If the enemy was part of a pack..
        // if (enemy.enemy_pack_id > 0) {
        //     let enemyMapObjectGroup = this.map.getMapObjectGroupByName('enemy');
        //     for (let i = 0; i < enemyMapObjectGroup.objects.length; i++) {
        //         let enemyCandidate = enemyMapObjectGroup.objects[i];
        //         // If we should couple the enemy in addition to our own..
        //         if (enemyCandidate.enemy_pack_id === enemy.enemy_pack_id) {
        //             // Remove it too if we should
        //             if (removed) {
        //                 this._removeEnemy(enemyCandidate);
        //             }
        //             // Or add it too if we need
        //             else {
        //                 this._addEnemy(enemyCandidate);
        //             }
        //         }
        //     }
        // } else {
        //     if (removed) {
        //         this._removeEnemy(enemy);
        //     } else {
        //         this._addEnemy(enemy);
        //     }
        // }

        this.redrawConnectionToEnemy();
    }

    /**
     * Removes any existing UI connections to enemies.
     */
    removeExistingConnectionToEnemy() {
        console.assert(this instanceof AdminEnemy, this, 'this was not an AdminEnemy');

        // Remove previous layers if it's needed
        if (this.enemyConnectionLayerGroup !== null) {
            let enemyMapObjectGroup = this.map.getMapObjectGroupByName('enemy');
            enemyMapObjectGroup.layerGroup.removeLayer(this.enemyConnectionLayerGroup);
        }
    }

    /**
     * Redraw connections to the enemy
     */
    redrawConnectionToEnemy() {
        this.removeExistingConnectionToEnemy();

        // If this enemy is connected to an MDT enemy
        let mdtEnemy = this._getMDTEnemy();
        if (mdtEnemy !== null) {
            // Create & add new layer
            this.enemyConnectionLayerGroup = new L.LayerGroup();

            // Add the layer to the map
            let enemyMapObjectGroup = self.map.getMapObjectGroupByName('enemy');
            enemyMapObjectGroup.layerGroup.addLayer(this.enemyConnectionLayerGroup);

            // Draw a line to the MDT enemy
            let layer = L.polyline([
                mdtEnemy.layer.getLatLng(),
                self.layer.getLatLng()
            ], c.map.killzone.polylineOptions);

            // do not prevent clicking on anything else
            self.enemyConnectionLayerGroup.setZIndex(-1000);
            self.enemyConnectionLayerGroup.addLayer(layer);
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
            console.log(self);
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

                    // Register for changes to the selection event
                    self.map.register('map:enemyselectionmodechanged', self, self._enemySelectionChanged.bind(self));

                    // Start selecting enemies
                    self.map.startEnemySelection(mdtEnemySelection);
                } else if (enemySelection.getMapObject() === self) {
                    self.map.finishEnemySelection();
                }
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
            let template = handlebars.compile(customPopupHtml);

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
            self.layer.off('popupopen', popupOpenFn);
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

        $.ajax({
            type: 'POST',
            url: '/ajax/enemy',
            dataType: 'json',
            data: {
                id: self.id,
                enemy_pack_id: self.enemy_pack_id,
                npc_id: self.npc_id,
                floor_id: self.map.getCurrentFloor().id,
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
    }
}