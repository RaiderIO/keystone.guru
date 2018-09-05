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

        this.id = 0;
        this.label = 'KillZone';
        this.saving = false;
        this.deleting = false;
        // List of IDs of selected enemies
        this.enemies = [];
        this.enemyConnectionsLayerGroup = null;

        this.setColors(c.map.killzone.colors);
        this.setSynced(false);
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

    setEnemies(enemies) {
        console.assert(this instanceof KillZone, this, 'this is not an KillZone');
        let self = this;

        let enemyMapObjectGroup = this.map.getMapObjectGroupByName('enemy');
        $.each(enemies, function (i, id) {
            let enemy = enemyMapObjectGroup.findMapObjectById(id);
            if (enemy !== null) {
                enemy.kill_zone_id = self.id;
            } else {
                console.warn('Unable to find enemy with id ' + id + ', this enemy was probably removed during a migration?');
            }
        });

        this.enemies = enemies;
    }

    /**
     * Starts select mode on this KillZone, if no other select mode was enabled already
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

            // Now killzoning something
            KillZoneSelectModeEnabled = true;
        }
    }

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
        if (index >= 0) {
            enemy.kill_zone_id = -1;
            // Remove it
            this.enemies.splice(index, 1);
        } else {
            enemy.kill_zone_id = this.id;
            // Add it
            this.enemies.push(enemy.id);
        }

        this.redrawConnectionsToEnemies();
    }

    /**
     * Throws away all current visible connections to enemies, and rebuilds the visuals.
     */
    redrawConnectionsToEnemies() {
        console.assert(this instanceof KillZone, this, 'this is not an KillZone');

        let self = this;

        // Remove previous layers if it's needed
        if (this.enemyConnectionsLayerGroup !== null) {
            this.map.leafletMap.removeLayer(this.enemyConnectionsLayerGroup);
        }

        // Create & add new layer
        this.enemyConnectionsLayerGroup = new L.LayerGroup();
        this.map.leafletMap.addLayer(this.enemyConnectionsLayerGroup);

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

        this.layer.on('click', function () {
            if (KillZoneSelectModeEnabled) {
                self.cancelSelectMode();
            } else {
                self.startSelectMode();
            }
        });

        // When moved, keep drawing the connections anew
        this.layer.on('move', function () {
            self.redrawConnectionsToEnemies();
        });


        // When we have all data, redraw the connections. Not sooner or otherwise we may not have the enemies back yet
        this.map.register('map:mapobjectgroupsfetchsuccess', this, function () {
            // The enemies data has been set, but not properly propagated to all enemies that they're attached to a killzone
            // Couldn't do that because enemies may not have been loaded at that point. Now we're sure the enemies have been
            // loaded so we can inject ourselves in the enemy
            self.setEnemies(self.enemies);
            self.redrawConnectionsToEnemies();
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