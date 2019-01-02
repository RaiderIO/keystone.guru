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

let LeafletKillZoneIconSelected = L.divIcon({
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
        this.saving = false;
        this.deleting = false;
        // List of IDs of selected enemies
        this.enemies = [];
        this.enemyConnectionsLayerGroup = null;

        this.setColors(c.map.killzone.colors);
        this.setSynced(false);

        // We gotta remove the connections manually since they're self managed here.
        this.map.register('map:beforerefresh', this, function () {
            // In case someone switched dungeons prior to finishing the kill zone edit
            self.map.setSelectModeKillZone(null);
            self.removeExistingConnectionsToEnemies();
        });

        // External change (due to delete mode being started, for example)
        this.map.register('map:killzoneselectmodechanged', this, function (event) {
            let killzone = event.data.killzone;
            let previousKillzone = event.data.previousKillzone;
            // Only if the toolbar is active, not when we just de-selected ourselves
            if (killzone === null && previousKillzone === self && self.map.toolbarActive) {
                self.cancelSelectMode(true);
            }
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

    /**
     * Detaches this killzone from all its enemies.
     * @private
     */
    _detachFromEnemies() {
        console.assert(this instanceof KillZone, this, 'this was not a KillZone');

        this.removeExistingConnectionsToEnemies();

        for (let i = 0; i < this.enemies.length; i++) {
            let enemyId = this.enemies[i];
            // Find the enemy
            let enemyMapObjectGroup = this.map.getMapObjectGroupByName('enemy');
            let enemy = enemyMapObjectGroup.findMapObjectById(enemyId);
            // When found, actually detach it
            if (enemy !== null) {
                enemy.setKillZone(0);
            }
        }
    }

    edit() {
        console.assert(this instanceof KillZone, this, 'this was not a KillZone');
        this.save();
    }

    delete() {
        let self = this;
        console.assert(this instanceof KillZone, this, 'this was not a KillZone');

        let successFn = function (json) {
            // Detach from all enemies upon deletion
            self._detachFromEnemies();
            self.removeExistingConnectionsToEnemies();
            self.signal('object:deleted', {response: json});
            self.signal('killzone:synced', {enemy_forces: json.enemy_forces});
        };

        // No network traffic if this is enabled!
        if (!this.map.isTryModeEnabled()) {
            $.ajax({
                type: 'POST',
                url: '/ajax/dungeonroute/' + this.map.getDungeonRoute().publicKey + '/killzone/' + self.id,
                dataType: 'json',
                data: {
                    _method: 'DELETE'
                },
                beforeSend: function () {
                    self.deleting = true;
                },
                success: successFn,
                complete: function () {
                    self.deleting = false;
                },
                error: function () {
                    self.setSynced(false);
                }
            });
        } else {
            successFn();
        }
    }

    save() {
        let self = this;
        console.assert(this instanceof KillZone, this, 'this was not a KillZone');

        let successFn = function (json) {
            self.id = json.id;

            self.setSynced(true);
            self.signal('killzone:synced', {enemy_forces: json.enemy_forces});
        };

        // No network traffic if this is enabled!
        if (!this.map.isTryModeEnabled()) {
            $.ajax({
                type: 'POST',
                url: '/ajax/dungeonroute/' + this.map.getDungeonRoute().publicKey + '/killzone',
                dataType: 'json',
                data: {
                    id: self.id,
                    floor_id: self.map.getCurrentFloor().id,
                    lat: self.layer.getLatLng().lat,
                    lng: self.layer.getLatLng().lng,
                    enemies: self.enemies
                },
                beforeSend: function () {
                    self.saving = true;
                },
                success: successFn,
                complete: function () {
                    self.saving = false;
                },
                error: function (xhr) {
                    // Even if we were synced, make sure user knows it's no longer / an error occurred
                    self.setSynced(false);
                    defaultAjaxErrorFn(xhr);
                }
            });
        } else {
            // We have to supply an ID to keep everything working properly
            successFn({id: parseInt((Math.random() * 10000000))})
        }
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
                console.warn('Unable to find enemy with id ' + id + ' for KZ ' + self.id + ' on floor ' + self.floor_id + ', ' +
                    'this enemy was probably removed during a migration?');
            }
        });

        this.enemies = enemies;
        this.redrawConnectionsToEnemies();
    }

    /**
     * Starts select mode on this KillZone, if no other select mode was enabled already.
     */
    startSelectMode() {
        console.assert(this instanceof KillZone, this, 'this is not an KillZone');
        let self = this;
        if (!this.map.isKillZoneSelectModeEnabled()) {
            this.layer.setIcon(LeafletKillZoneIconSelected);

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
            // @TODO https://stackoverflow.com/questions/40414970/disable-leaflet-draw-delete-button
            $('.leaflet-draw-edit-edit').addClass('leaflet-disabled');
            $('.leaflet-draw-edit-remove').addClass('leaflet-disabled');

            // Now killzoning something
            this.map.setSelectModeKillZone(this);
        }
    }

    /**
     * Stops select mode of this KillZone.
     */
    cancelSelectMode(externalChange = false) {
        console.assert(this instanceof KillZone, this, 'this is not an KillZone');
        if (this.map.isKillZoneSelectModeEnabled() || externalChange) {
            if (!externalChange) {
                this.map.setSelectModeKillZone(null);
            }

            this.layer.setIcon(LeafletKillZoneIcon);

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
        } else {
            if (removed) {
                this._removeEnemy(enemy);
            } else {
                this._addEnemy(enemy);
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
            let killZoneMapObjectGroup = this.map.getMapObjectGroupByName('killzone');
            killZoneMapObjectGroup.layerGroup.removeLayer(this.enemyConnectionsLayerGroup);
        }
    }

    /**
     * Throws away all current visible connections to enemies, and rebuilds the visuals.
     */
    redrawConnectionsToEnemies() {
        console.assert(this instanceof KillZone, this, 'this is not an KillZone');

        let self = this;

        let killZoneMapObjectGroup = self.map.getMapObjectGroupByName('killzone');

        this.removeExistingConnectionsToEnemies();

        // Create & add new layer
        this.enemyConnectionsLayerGroup = new L.LayerGroup();
        killZoneMapObjectGroup.layerGroup.addLayer(this.enemyConnectionsLayerGroup);

        // Add connections from each enemy to our location
        let enemyMapObjectGroup = self.map.getMapObjectGroupByName('enemy');
        let latLngs = [];
        $.each(this.enemies, function (i, id) {
            let enemy = enemyMapObjectGroup.findMapObjectById(id);

            if (enemy !== null) {
                let latLng = enemy.layer.getLatLng();
                latLngs.push([latLng.lat, latLng.lng]);
            } else {
                console.warn('Unable to find enemy with id ' + id + ' for KZ ' + self.id + 'on floor ' + self.floor_id + ', ' +
                    'cannot draw connection, this enemy was probably removed during a migration?');
            }
        });


        // Alpha shapes
        let p = hull(latLngs, 100);

        if (p.length > 1) {
            console.log("Latlngs: " + latLngs);
            console.log("Alpha shape: ", p);

            let offset = new Offset();
            p = offset.data(p).arcSegments(c.map.killzone.arcSegments).margin(c.map.killzone.margin);

            let layer = L.polygon(p, c.map.killzone.polygonOptions);

            // do not prevent clicking on anything else
            self.enemyConnectionsLayerGroup.setZIndex(-1000);

            self.enemyConnectionsLayerGroup.addLayer(layer);
        }
    }

    // To be overridden by any implementing classes
    onLayerInit() {
        console.assert(this instanceof KillZone, this, 'this is not an KillZone');
        super.onLayerInit();

        let self = this;

        if (this.map.edit) {
            this.layer.on('click', function (event) {
                // Can only interact with select mode if we're the one that is currently being selected
                if (!self.map.deleteModeActive &&
                    (self.map.currentSelectModeKillZone === self || self.map.currentSelectModeKillZone === null)) {
                    if (self.map.isKillZoneSelectModeEnabled()) {
                        self.cancelSelectMode();
                    } else {
                        self.startSelectMode();
                    }
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

    cleanup() {
        // this.unregister('synced', this); // Not needed as super.cleanup() does this
        this.map.unregister('map:mapobjectgroupsfetchsuccess', this);
        this.map.unregister('map:beforerefresh', this);

        let enemyMapObjectGroup = this.map.getMapObjectGroupByName('enemy');
        $.each(enemyMapObjectGroup.objects, function (i, enemy) {
            enemy.setKillZoneSelectable(false);
            enemy.unregister('killzone:selected', self);
        });

        super.cleanup();
    }
}