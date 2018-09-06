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

let LeafletKillZoneMarker = L.Marker.extend({
    options: {
        icon: LeafletKillZoneIcon
    }
});

let KillZoneSelectModeEnabled = false;

class KillZone extends MapObject {
    constructor(map, layer) {
        super(map, layer);

        let self = this;
        this.id = 0;
        this.label = 'KillZone';
        this.saving = false;
        this.deleting = false;
        // List of IDs of selected enemies
        this.enemies = [];
        this.enemyConnectionsLayerGroup = null;

        this.setColors(c.map.killzone.colors);
        this.setSynced(false);

        // We gotta remove the connections manually since they're self managed here.
        this.map.register('map:refresh', this, function () {
            console.log('map refreshed!');
            self.removeExistingConnectionsToEnemies();
        });
    }

    /**
     * Removes an enemy from this killzone.
     * @param enemy Object The enemy object to remove.
     * @private
     */
    _removeEnemy(enemy) {
        enemy.setKillZone(-1);
        let index = $.inArray(enemy.id, this.enemies);
        if (index !== -1) {
            // Remove it
            this.enemies.splice(index, 1);
        }
    }

    /**
     * Adds an enemy to this killzone.
     * @param enemy Object The enemy object to add.
     * @private
     */
    _addEnemy(enemy) {
        enemy.setKillZone(this.id);
        // Add it, but don't double add it
        if ($.inArray(enemy.id, this.enemies) === -1) {
            this.enemies.push(enemy.id);
        }
    }

    getContextMenuItems() {
        console.assert(this instanceof KillZone, this, 'this was not a KillZone');
        // Merge existing context menu items with the admin ones
        return super.getContextMenuItems().concat([{
            text: '<i class="fas fa-save"></i> ' + (this.saving ? "Saving.." : "Save"),
            disabled: this.synced || this.saving,
            callback: (this.save).bind(this)
        }, '-', {
            text: '<i class="fas fa-trash"></i> ' + (this.deleting ? "Deleting.." : "Delete"),
            disabled: !this.synced || this.deleting,
            callback: (this.delete).bind(this)
        }]);
    }

    edit() {
        console.assert(this instanceof KillZone, this, 'this was not a KillZone');
        this.save();
    }

    delete() {
        let self = this;
        console.assert(this instanceof KillZone, this, 'this was not a KillZone');
        $.ajax({
            type: 'POST',
            url: '/ajax/killzone',
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
                self.setSynced(false);
            }
        });
    }

    save() {
        let self = this;
        console.assert(this instanceof KillZone, this, 'this was not a KillZone');
        $.ajax({
            type: 'POST',
            url: '/ajax/killzone',
            dataType: 'json',
            data: {
                id: self.id,
                dungeonroute: dungeonRoutePublicKey, // defined in map.blade.php
                floor_id: self.map.getCurrentFloor().id,
                lat: self.layer.getLatLng().lat,
                lng: self.layer.getLatLng().lng,
                enemies: self.enemies
            },
            beforeSend: function () {
                self.saving = true;
            },
            success: function (json) {
                self.id = json.id;

                self.setSynced(true);
            },
            complete: function () {
                self.saving = false;
            },
            error: function () {
                // Even if we were synced, make sure user knows it's no longer / an error occurred
                self.setSynced(false);
            }
        });
    }

    /**
     * Bulk sets the enemies for this killzone.
     * @param enemies
     */
    setEnemies(enemies) {
        console.assert(this instanceof KillZone, this, 'this is not an KillZone');
        let self = this;

        let enemyMapObjectGroup = this.map.getMapObjectGroupByName('enemy');
        $.each(enemies, function (i, id) {
            let enemy = enemyMapObjectGroup.findMapObjectById(id);
            if (enemy !== null) {
                enemy.setKillZone(self.id);
            } else {
                console.warn('Unable to find enemy with id ' + id + ', this enemy was probably removed during a migration?');
            }
        });

        this.enemies = enemies;
        this.redrawConnectionsToEnemies();
    }

    /**
     * Starts select mode on this KillZone, if no other select mode was enabled already.
     */
    startSelectMode() {
        if (!KillZoneSelectModeEnabled) {
            console.assert(this instanceof KillZone, this, 'this is not an KillZone');
            let self = this;

            let enemyMapObjectGroup = this.map.getMapObjectGroupByName('enemy');
            $.each(enemyMapObjectGroup.objects, function (i, enemy) {
                // We cannot kill an enemy twice, but can deselect once we have selected it
                if (enemy.kill_zone_id <= 0 || enemy.kill_zone_id === self.id) {
                    enemy.setKillZoneSelectable(!enemy.isKillZoneSelectable());
                }

                enemy.register('killzone:selected', self, function (data) {
                    self.enemySelected(data.context);
                })
            });

            // Cannot start editing things while we're doing this.
            $('.leaflet-draw-edit-edit').addClass('leaflet-disabled');
            $('.leaflet-draw-edit-remove').addClass('leaflet-disabled');

            // Now killzoning something
            KillZoneSelectModeEnabled = true;
        }
    }

    /**
     * Stops select mode of this KillZone.
     */
    cancelSelectMode() {
        if (KillZoneSelectModeEnabled) {
            console.assert(this instanceof KillZone, this, 'this is not an KillZone');
            KillZoneSelectModeEnabled = false;

            let self = this;

            // Revert all things we did to enemies
            let enemyMapObjectGroup = this.map.getMapObjectGroupByName('enemy');
            $.each(enemyMapObjectGroup.objects, function (i, enemy) {
                enemy.setKillZoneSelectable(false);
                enemy.unregister('killzone:selected', self);
            });

            // Ok we're clear, may edit again (there's always something to edit because this KillZone exists)
            $('.leaflet-draw-edit-edit').removeClass('leaflet-disabled');
            $('.leaflet-draw-edit-remove').removeClass('leaflet-disabled');

            this.save();
        }
    }

    /**
     * Triggered when an enemy was selected by the user when edit mode was enabled.
     * @param enemy The enemy that was selected (or de-selected). Will add/remove the enemy to the list to be redrawn.
     */
    enemySelected(enemy) {
        console.assert(enemy instanceof Enemy, enemy, 'enemy is not an Enemy');
        console.assert(this instanceof KillZone, this, 'this is not an KillZone');

        let index = $.inArray(enemy.id, this.enemies);
        // Already exists, user wants to deselect the enemy
        let removed = index >= 0;
        if (removed) {
            this._removeEnemy(enemy);
        } else {
            this._addEnemy(enemy);
        }

        // If the enemy was part of a pack..
        if (enemy.enemy_pack_id > 0) {
            let enemyMapObjectGroup = this.map.getMapObjectGroupByName('enemy');
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
        }

        this.redrawConnectionsToEnemies();
    }

    /**
     * Removes any existing UI connections to enemies.
     */
    removeExistingConnectionsToEnemies() {
        console.assert(this instanceof KillZone, this, 'this is not an KillZone');

        // Remove previous layers if it's needed
        if (this.enemyConnectionsLayerGroup !== null) {
            console.log('removing existing connections!');
            let killZoneMapObjectGroup = this.map.getMapObjectGroupByName('killzone');
            killZoneMapObjectGroup.layerGroup.removeLayer(this.enemyConnectionsLayerGroup);
        }
    }

    /**
     * Throws away all current visible connections to enemies, and rebuilds the visuals.
     */
    redrawConnectionsToEnemies() {
        console.assert(this instanceof KillZone, this, 'this is not an KillZone');
        console.log('redrawing connections!');

        let self = this;

        let killZoneMapObjectGroup = self.map.getMapObjectGroupByName('killzone');

        this.removeExistingConnectionsToEnemies();

        // Create & add new layer
        this.enemyConnectionsLayerGroup = new L.LayerGroup();
        killZoneMapObjectGroup.layerGroup.addLayer(this.enemyConnectionsLayerGroup);

        // Add connections from each enemy to our location
        let enemyMapObjectGroup = self.map.getMapObjectGroupByName('enemy');
        $.each(this.enemies, function (i, id) {
            let enemy = enemyMapObjectGroup.findMapObjectById(id);

            if (enemy !== null) {
                let layer = L.polyline([
                    enemy.layer.getLatLng(),
                    self.layer.getLatLng()
                ], c.map.killzone.polylineOptions);

                self.enemyConnectionsLayerGroup.addLayer(layer);
            } else {
                console.warn('Unable to find enemy with id ' + id + ', cannot draw connection, this enemy was probably removed during a migration?');
            }
        });
    }

    // To be overridden by any implementing classes
    onLayerInit() {
        console.assert(this instanceof KillZone, this, 'this is not an KillZone');
        super.onLayerInit();

        let self = this;

        if (this.map.edit) {
            this.layer.on('click', function () {
                if (KillZoneSelectModeEnabled) {
                    self.cancelSelectMode();
                } else {
                    self.startSelectMode();
                }
            });
        }

        // When we're moved, keep drawing the connections anew
        this.layer.on('move', function () {
            self.redrawConnectionsToEnemies();
        });


        // When we have all data, redraw the connections. Not sooner or otherwise we may not have the enemies back yet
        this.map.register('map:mapobjectgroupsfetchsuccess', this, function () {
            // The enemies data has been set, but not properly propagated to all enemies that they're attached to a killzone
            // Couldn't do that because enemies may not have been loaded at that point. Now we're sure the enemies have been
            // loaded so we can inject ourselves in the enemy
            self.setEnemies(self.enemies);
        });

        // When we're synced, construct the popup.  We don't know the ID before that so we cannot properly bind the popup.
        this.register('synced', this, function (event) {
            // Restore the connections to our enemies

            // let customPopupHtml = $("#killzone_edit_popup_template").html();
            // // Remove template so our
            // let template = handlebars.compile(customPopupHtml);
            //
            // let data = {id: self.id};
            //
            // // Build the status bar from the template
            // customPopupHtml = template(data);
            //
            // let customOptions = {
            //     'maxWidth': '400',
            //     'minWidth': '300',
            //     'className': 'popupCustom'
            // };
            // self.layer.bindPopup(customPopupHtml, customOptions);
            // self.layer.on('popupopen', function (event) {
            //     $("#killzone_edit_popup_color_" + self.id).val(self.killzoneColor);
            //
            //     $("#killzone_edit_popup_submit_" + self.id).bind('click', function () {
            //         self.setKillZoneColor($("#killzone_edit_popup_color_" + self.id).val());
            //
            //         self.edit();
            //     });
            // });
        });
    }
}