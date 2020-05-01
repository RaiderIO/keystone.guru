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
        this.color = c.map.killzone.polygonOptions.color;
        // List of IDs of selected enemies
        this.enemies = [];
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
        // this.map.register('map:mapstatechanged', this, function (event) {
        //     // Only if the toolbar is active, not when we just de-selected ourselves
        //     if(event.data.finished && event.data.enemySelection instanceof KillZoneEnemySelection && self.map.toolbarActive){
        //         self.stop(true);
        //     }
        // });
    }

    /**
     * Removes an enemy from this killzone.
     * @param enemy Object The enemy object to remove.
     * @private
     */
    _removeEnemy(enemy) {
        enemy.setKillZone(null);
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
        enemy.setKillZone(this);
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
        console.assert(this instanceof KillZone, 'this was not a KillZone', this);

        this.removeExistingConnectionsToEnemies();

        let enemyMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_ENEMY);
        for (let i = 0; i < this.enemies.length; i++) {
            let enemyId = this.enemies[i];
            let enemy = enemyMapObjectGroup.findMapObjectById(enemyId);
            // When found, actually detach it
            if (enemy !== null) {
                enemy.setKillZone(null);
            }
        }
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
                self.removeExistingConnectionsToEnemies();
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
                lat: self.layer.getLatLng().lat,
                lng: self.layer.getLatLng().lng,
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

    /**
     * Bulk sets the enemies for this killzone.
     * @param enemies
     */
    setEnemies(enemies) {
        console.assert(this instanceof KillZone, 'this is not an KillZone', this);
        let self = this;

        let enemyMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_ENEMY);
        $.each(enemies, function (i, id) {
            let enemy = enemyMapObjectGroup.findMapObjectById(id);
            if (enemy !== null) {
                enemy.setKillZone(self);
            } else {
                console.warn('Unable to find enemy with id ' + id + ' for KZ ' + self.id + ' on floor ' + self.floor_id + ', ' +
                    'this enemy was probably removed during a migration?');
            }
        });

        this.enemies = enemies;
        this.redrawConnectionsToEnemies();
    }

    /**
     * Triggered when an enemy was selected by the user when edit mode was enabled.
     * @param enemy The enemy that was selected (or de-selected). Will add/remove the enemy to the list to be redrawn.
     */
    enemySelected(enemy) {
        console.assert(enemy instanceof Enemy, 'enemy is not an Enemy', enemy);
        console.assert(this instanceof KillZone, 'this is not an KillZone', this);

        let index = $.inArray(enemy.id, this.enemies);
        // Already exists, user wants to deselect the enemy
        let removed = index >= 0;

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
        let latLngs = [];
        $.each(this.enemies, function (i, id) {
            let enemy = enemyMapObjectGroup.findMapObjectById(id);

            if (enemy !== null) {
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
            } else {
                console.warn('Unable to find enemy with id ' + id + ' for KZ ' + self.id + 'on floor ' + self.floor_id + ', ' +
                    'cannot draw connection, this enemy was probably removed during a migration?');
            }
        });


        // Alpha shapes
        let selfLatLng = self.layer.getLatLng();
        latLngs.unshift([selfLatLng.lat, selfLatLng.lng]);
        let p = hull(latLngs, 100);

        // Only if we can actually make an offset
        if (latLngs.length > 1 && p.length > 1) {
            let offset = new Offset();
            p = offset.data(p).arcSegments(c.map.killzone.arcSegments(p.length)).margin(c.map.killzone.margin);

            let opts = $.extend({}, c.map.killzone.polygonOptions, {color: this.color});

            let polygon = L.polygon(p, opts);

            // do not prevent clicking on anything else
            this.enemyConnectionsLayerGroup.setZIndex(-1000);

            this.enemyConnectionsLayerGroup.addLayer(polygon);

            // Only add popup to the killzone
            if (this.isEditable() && this.map.options.edit) {
                // Popup trigger function, needs to be outside the synced function to prevent multiple bindings
                // This also cannot be a private function since that'll apparently give different signatures as well.
                let popupOpenFn = function (event) {
                    console.assert(self instanceof KillZone, 'this was not a KillZone', self);
                    // Give a default color if it was not set
                    let color = self.color === '' ? c.map.killzone.polygonOptions.color : self.color;
                    $('#map_killzone_edit_popup_color_' + self.id).val(color);

                    // Prevent multiple binds to click
                    let $submitBtn = $('#map_killzone_edit_popup_submit_' + self.id);

                    $submitBtn.unbind('click');
                    $submitBtn.bind('click', function _popupSubmitClicked() {
                        console.assert(self instanceof KillZone, 'this was not a KillZone', self);
                        self.color = $('#map_killzone_edit_popup_color_' + self.id).val();

                        self.edit();
                    });
                };

                let template = Handlebars.templates['map_killzone_edit_popup_template'];

                let data = $.extend({id: self.id}, getHandlebarsDefaultVariables());

                // Build the status bar from the template
                polygon.unbindPopup();
                polygon.bindPopup(template(data), {
                    'maxWidth': '400',
                    'minWidth': '300',
                    'className': 'popupCustom'
                });

                polygon.off('popupopen');
                polygon.on('popupopen', popupOpenFn);
            }
        }
    }

    /**
     * Called when enemy selection for this killzone has changed (started/finished)
     * @param mapStateChangedEvent
     * @private
     */
    _mapStateChanged(mapStateChangedEvent) {
        console.assert(this instanceof KillZone, 'this is not a KillZone', this);

        if (mapStateChangedEvent.data.previousMapState instanceof EnemySelection) {
            // Redraw any changes as necessary
            this.redrawConnectionsToEnemies();

            // May save when nothing has changed, but that's okay
            this.save();

            // We're done with this event now (after finishing! otherwise we won't process the result)
            this.map.unregister('map:mapstatechanged', this);
        }
    }

    // To be overridden by any implementing classes
    onLayerInit() {
        console.assert(this instanceof KillZone, 'this is not a KillZone', this);
        super.onLayerInit();

        let self = this;

        if (this.map.options.edit) {
            this.layer.on('click', function (clickEvent) {
                let mapState = self.map.getMapState();
                // Can only interact with select mode if we're the one that is currently being selected
                if (mapState instanceof EnemySelection && mapState.getMapObject() === self) {
                    self.map.setMapState(null);
                } else if (mapState === null) {
                    let kzEnemySelection = new KillZoneEnemySelection(self.map, self);
                    kzEnemySelection.register('enemyselection:enemyselected', this, function (selectedEvent) {
                        self.enemySelected(selectedEvent.data.enemy);
                    });

                    // Register for changes to the selection event
                    self.map.register('map:mapstatechanged', self, self._mapStateChanged.bind(self));

                    // Start selecting enemies
                    self.map.setMapState(kzEnemySelection);
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

            // let customPopupHtml = $("#killzone_edit_popup_template").html();
            // // Remove template so our
            // let template = Handlebars.compile(customPopupHtml);
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

        let enemyMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_ENEMY);
        $.each(enemyMapObjectGroup.objects, function (i, enemy) {
            enemy.setSelectable(false);
            enemy.unregister('enemy:selected', self);
        });

        super.cleanup();
    }
}