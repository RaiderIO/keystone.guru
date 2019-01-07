class AdminEnemy extends Enemy {

    constructor(map, layer) {
        super(map, layer);

        this.npc_id = 0;
        // Init to an empty value
        this.enemy_pack_id = -1;
        this.teeming = '';
        // Filled when we're currently drawing a patrol line
        this.currentPatrolPolyline = null;

        this.mdtEnemyMappable = false;

        this.saving = false;
        this.deleting = false;
        this.setColors(c.map.admin.mapobject.colors);
        this.setSynced(false);
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
    }

    // /**
    //  * Starts select mode on this KillZone, if no other select mode was enabled already.
    //  */
    // startMDTEnemyMappingMode() {
    //     console.assert(this instanceof KillZone, this, 'this is not an KillZone');
    //     let self = this;
    //     if (!this.map.isMDTEnemyMappingModeEnabled()) {
    //         this.layer.setIcon(LeafletMDTEnemyMarkerSelected);
    //
    //         let enemyMapObjectGroup = this.map.getMapObjectGroupByName('enemy');
    //         $.each(enemyMapObjectGroup.objects, function (i, enemy) {
    //             // MDT enemies are mapped based on NPC id and a specific MDT_ID
    //             if (!enemy.is_mdt && (enemy.mdt_id === self.mdt_id || enemy.mdt_id === -1) && enemy.npc_id === self.npc_id) {
    //                 enemy.setKillZoneSelectable(!enemy.isKillZoneSelectable());
    //             }
    //
    //             enemy.register('killzone:selected', self, function (data) {
    //                 self.enemySelected(data.context);
    //             })
    //         });
    //
    //         // Cannot start editing things while we're doing this.
    //         // @TODO https://stackoverflow.com/questions/40414970/disable-leaflet-draw-delete-button
    //         $('.leaflet-draw-edit-edit').addClass('leaflet-disabled');
    //         $('.leaflet-draw-edit-remove').addClass('leaflet-disabled');
    //
    //         // Now killzoning something
    //         this.map.setSelectModeKillZone(this);
    //
    //         this.redrawConnectionsToEnemies();
    //     }
    // }
    //
    // /**
    //  * Stops select mode of this KillZone.
    //  */
    // cancelMDTEnemyMappingMode(externalChange = false) {
    //     console.assert(this instanceof KillZone, this, 'this is not an KillZone');
    //     if (this.map.isKillZoneSelectModeEnabled() || externalChange) {
    //         if (!externalChange) {
    //             this.map.setSelectModeKillZone(null);
    //         }
    //
    //         this.layer.setIcon(LeafletKillZoneIcon);
    //
    //         let self = this;
    //
    //         // Revert all things we did to enemies
    //         let enemyMapObjectGroup = this.map.getMapObjectGroupByName('enemy');
    //         $.each(enemyMapObjectGroup.objects, function (i, enemy) {
    //             enemy.setKillZoneSelectable(false);
    //             enemy.unregister('killzone:selected', self);
    //         });
    //
    //         // Ok we're clear, may edit again (there's always something to edit because this KillZone exists)
    //         $('.leaflet-draw-edit-edit').removeClass('leaflet-disabled');
    //         $('.leaflet-draw-edit-remove').removeClass('leaflet-disabled');
    //
    //         this.redrawConnectionsToEnemies();
    //         this.save();
    //     }
    // }
    //
    // /**
    //  * Triggered when an enemy was selected by the user when edit mode was enabled.
    //  * @param enemy The enemy that was selected (or de-selected). Will add/remove the enemy to the list to be redrawn.
    //  */
    // enemySelected(enemy) {
    //     console.assert(enemy instanceof Enemy, enemy, 'enemy is not an Enemy');
    //     console.assert(this instanceof KillZone, this, 'this is not an KillZone');
    //
    //     let index = $.inArray(enemy.id, this.enemies);
    //     // Already exists, user wants to deselect the enemy
    //     let removed = index >= 0;
    //
    //     // If the enemy was part of a pack..
    //     if (enemy.enemy_pack_id > 0) {
    //         let enemyMapObjectGroup = this.map.getMapObjectGroupByName('enemy');
    //         for (let i = 0; i < enemyMapObjectGroup.objects.length; i++) {
    //             let enemyCandidate = enemyMapObjectGroup.objects[i];
    //             // If we should couple the enemy in addition to our own..
    //             if (enemyCandidate.enemy_pack_id === enemy.enemy_pack_id) {
    //                 // Remove it too if we should
    //                 if (removed) {
    //                     this._removeEnemy(enemyCandidate);
    //                 }
    //                 // Or add it too if we need
    //                 else {
    //                     this._addEnemy(enemyCandidate);
    //                 }
    //             }
    //         }
    //     } else {
    //         if (removed) {
    //             this._removeEnemy(enemy);
    //         } else {
    //             this._addEnemy(enemy);
    //         }
    //     }
    //
    //     this.redrawConnectionsToEnemies();
    // }
    //
    // /**
    //  * Removes any existing UI connections to enemies.
    //  */
    // removeExistingConnectionsToEnemies() {
    //     console.assert(this instanceof KillZone, this, 'this is not an KillZone');
    //
    //     // Remove previous layers if it's needed
    //     if (this.enemyConnectionsLayerGroup !== null) {
    //         let killZoneMapObjectGroup = this.map.getMapObjectGroupByName('killzone');
    //         killZoneMapObjectGroup.layerGroup.removeLayer(this.enemyConnectionsLayerGroup);
    //     }
    // }
    //
    // /**
    //  * Throws away all current visible connections to enemies, and rebuilds the visuals.
    //  */
    // redrawConnectionsToEnemies() {
    //     console.assert(this instanceof KillZone, this, 'this is not an KillZone');
    //
    //     let self = this;
    //
    //     let killZoneMapObjectGroup = self.map.getMapObjectGroupByName('killzone');
    //
    //     this.removeExistingConnectionsToEnemies();
    //
    //     // Create & add new layer
    //     this.enemyConnectionsLayerGroup = new L.LayerGroup();
    //     killZoneMapObjectGroup.layerGroup.addLayer(this.enemyConnectionsLayerGroup);
    //
    //     // Add connections from each enemy to our location
    //     let enemyMapObjectGroup = self.map.getMapObjectGroupByName('enemy');
    //     let latLngs = [];
    //     $.each(this.enemies, function (i, id) {
    //         let enemy = enemyMapObjectGroup.findMapObjectById(id);
    //
    //         if (enemy !== null) {
    //             let latLng = enemy.layer.getLatLng();
    //             latLngs.push([latLng.lat, latLng.lng]);
    //
    //             // Draw lines to self if killzone mode is enabled
    //             if (self.map.currentSelectModeKillZone === self) {
    //                 let layer = L.polyline([
    //                     latLng,
    //                     self.layer.getLatLng()
    //                 ], c.map.killzone.polylineOptions);
    //                 // do not prevent clicking on anything else
    //                 self.enemyConnectionsLayerGroup.setZIndex(-1000);
    //
    //                 self.enemyConnectionsLayerGroup.addLayer(layer);
    //             }
    //         } else {
    //             console.warn('Unable to find enemy with id ' + id + ' for KZ ' + self.id + 'on floor ' + self.floor_id + ', ' +
    //                 'cannot draw connection, this enemy was probably removed during a migration?');
    //         }
    //     });
    // }

    /**
     * Checks if this enemy is possibly selectable by a kill zone.
     * @returns {*}
     */
    isMDTEnemyMappable() {
        return this.mdtEnemyMappable;
    }

    /**
     * Set this enemy to be selectable whenever an MDT wants to connect to this enemy.
     * @param value
     */
    setMDTEnemyMappable(value) {
        console.assert(this instanceof Enemy, this, 'this is not an Enemy');
        this.mdtEnemyMappable = value;
        // Refresh the icon
        this.visual.refresh();
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