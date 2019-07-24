class AdminEnemyPack extends EnemyPack {

    constructor(map, layer) {
        super(map, layer);

        this.setSynced(false);
    }

    /**
     * Get the location of the Beguiling NPC we're supposed to display.
     * @returns LatLng
     * @private
     */
    _getBeguilingEnemyDefaultLocation() {
        console.assert(this instanceof EnemyPack, 'this is not an EnemyPack', this);

        return this.layer.getBounds().getCenter();
    }

    /**
     * Adds a row to the popup where we can enter another Beguiling Enemy.
     * @param preset int
     * @param npcId int
     * @private
     */
    _addBeguilingEnemyRow(preset = -1, npcId = -1) {
        console.assert(this instanceof AdminEnemyPack, 'this was not an AdminEnemyPack', this);
        let self = this;

        let $container = $('#enemy_pack_edit_popup_beguiling_container_' + self.id);

        // Sensible default
        if (preset === -1) {
            preset = $container.children().length + 1;
        }

        let beguilingTemplate = Handlebars.templates['map_enemy_pack_beguiling_row_template'];
        let index = $container.children().length;

        let beguilingData = $.extend({
            id: self.id,
            npcs: $.grep(this.map.options.npcs, function (npc, index) {
                return npc.dungeon_id === -1;
            }),
            index: index,
            preset: preset,
            npc_id: npcId
        }, getHandlebarsDefaultVariables());

        $container.append(
            beguilingTemplate(beguilingData)
        );

        // On remove
        let $delete = $('#enemy_pack_edit_popup_beguiling_delete_' + self.id + '_' + index);
        $delete.data('preset', preset);
        $delete.bind('click', function () {
            // Remove the beguiling enemy from our list
            self._removeBeguilingEnemyByPreset(parseInt($(this).data('preset')));

            // Remove from interface
            $('#enemy_pack_edit_popup_beguiling_row_' + self.id + '_' + index).remove();
        });

        refreshSelectPickers();
    }

    /**
     * Destroys a beguiling enemy by its preset.
     * @param preset int
     * @private
     */
    _removeBeguilingEnemyByPreset(preset) {
        console.assert(typeof preset === 'number', 'preset was not a number', this);

        for (let i = 0; i < this.beguilingenemies.length; i++) {
            let enemy = this.beguilingenemies[i];

            if (enemy.beguiling_preset === preset) {
                // Delete it from the server
                enemy.delete();

                // Remove it from our array
                this.beguilingenemies.splice(i, 1);
                break;
            }
        }
    }

    /**
     * Creates a beguiling enemy.
     * @param preset int
     * @param npcId int
     * @private
     * @return Enemy
     */
    _createBeguilingEnemy(preset, npcId) {
        console.assert(typeof preset === 'number', 'preset was not a number', this);
        console.assert(typeof npcId === 'number', 'npcId was not a number', this);

        // Our data
        let enemyMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_ENEMY);

        // Location handling
        let location = this._getBeguilingEnemyDefaultLocation();
        if (this.beguilingenemies.length > 0) {
            // Get the first enemy's lat/lng, it should already be in-sync with the others, so this one will be as well.
            location = this.beguilingenemies[0].layer.getLatLng();
        }

        // Create the enemy
        return enemyMapObjectGroup.createBeguilingEnemy(this, preset, npcId, location);
    }

    /**
     * Refreshes the local beguiling NPCs in favor of the ones currently on display.
     * @private
     */
    _updateBeguilingEnemies() {
        console.assert(this instanceof AdminEnemyPack, 'this was not an AdminEnemyPack', this);

        let $beguilingPreset;
        let index = 0;
        while (($beguilingPreset = $('#enemy_pack_edit_popup_beguiling_preset_' + this.id + '_' + index)).length > 0) {
            let preset = parseInt($beguilingPreset.val());
            let npcId = parseInt($('#enemy_pack_edit_popup_beguiling_npc_' + this.id + '_' + index).val());

            // May be null if it's a new enemy for this preset
            let localEnemy = this._getBeguilingEnemyByPreset(preset);
            if (localEnemy === null) {
                console.log('Creating new beguiling enemy...', localEnemy);
                localEnemy = this._createBeguilingEnemy(preset, npcId);
                // Create a new one, add it to our list
                this.beguilingenemies.push(localEnemy);
            } else {
                console.log('Updating existing beguiling enemy...', localEnemy);
                // Update the values instead
                localEnemy.beguiling_preset = preset;
                localEnemy.npc_id = npcId;
            }
            console.log('Result: ', localEnemy);

            // Save the new or updated enemy to the database
            localEnemy.save();

            // To the next enemy!
            index++;
        }
    }

    onLayerInit() {
        console.assert(this instanceof AdminEnemyPack, 'this was not an AdminEnemyPack', this);
        super.onLayerInit();
        this.onPopupInit();
    }

    onPopupInit() {
        console.assert(this instanceof AdminEnemyPack, 'this was not an AdminEnemyPack', this);
        let self = this;

        // Popup trigger function, needs to be outside the synced function to prevent multiple bindings
        // This also cannot be a private function since that'll apparently give different signatures as well.
        let popupOpenFn = function (event) {
            $('#enemy_pack_edit_popup_teeming_' + self.id).val(self.teeming);
            $('#enemy_pack_edit_popup_faction_' + self.id).val(self.faction);
            $('#enemy_pack_edit_popup_beguiling_add_' + self.id).bind('click', function () {
                self._addBeguilingEnemyRow();
            });

            if (self.beguilingenemies.length === 0) {
                // Add a row to start off with
                self._addBeguilingEnemyRow('');
            } else {
                // Restore selected beguiling NPCs
                $.each(self.beguilingenemies, function (index, value) {
                    self._addBeguilingEnemyRow(value.beguiling_preset, value.npc_id);
                });
            }

            // Refresh all select pickers so they work again
            refreshSelectPickers();

            let $submitBtn = $('#enemy_pack_edit_popup_submit_' + self.id);

            $submitBtn.unbind('click');
            $submitBtn.bind('click', function () {
                self.teeming = $('#enemy_pack_edit_popup_teeming_' + self.id).val();
                self.faction = $('#enemy_pack_edit_popup_faction_' + self.id).val();

                self._updateBeguilingEnemies();

                self.edit();
            });
        };

        // When we're synced, construct the popup.  We don't know the ID before that so we cannot properly bind the popup.
        let syncedFn = function (event) {
            // Remove template so our
            let template = Handlebars.templates['map_enemy_pack_template'];

            let data = $.extend({
                id: self.id,
                teeming: self.map.options.teeming,
                factions: self.map.options.factions
            }, getHandlebarsDefaultVariables());

            let customOptions = {
                'maxWidth': '400',
                'minWidth': '400',
                'className': 'popupCustom'
            };

            self.layer.unbindPopup();
            self.layer.bindPopup(template(data), customOptions);

            // Have you tried turning it off and on again?
            self.layer.off('popupopen');
            self.layer.on('popupopen', popupOpenFn);
        };

        this.unregister('synced', this, syncedFn);
        this.register('synced', this, syncedFn);

        self.map.leafletMap.on('contextmenu', function () {
            if (self.currentPatrolPolyline !== null) {
                self.map.leafletMap.addLayer(self.currentPatrolPolyline);
                self.currentPatrolPolyline.disable();
            }
        });
    }

    delete() {
        let self = this;
        console.assert(this instanceof AdminEnemyPack, 'this was not an AdminEnemyPack', this);
        $.ajax({
            type: 'POST',
            url: '/ajax/enemypack/' + self.id,
            dataType: 'json',
            data: {
                _method: 'DELETE'
            },
            success: function (json) {
                self.localDelete();
            },
            error: function (xhr, textStatus, errorThrown) {
                self.layer.setStyle({
                    fillColor: c.map.admin.mapobject.colors.unsaved,
                    color: c.map.admin.mapobject.colors.unsavedBorder
                });
            }
        });
    }

    edit() {
        console.assert(this instanceof AdminEnemyPack, 'this was not an AdminEnemyPack', this);
        this.save();
    }

    save() {
        let self = this;
        console.assert(this instanceof AdminEnemyPack, 'this was not an AdminEnemyPack', this);
        $.ajax({
            type: 'POST',
            url: '/ajax/enemypack',
            dataType: 'json',
            data: {
                id: self.id,
                floor_id: getState().getCurrentFloor().id,
                label: self.label,
                teeming: self.teeming,
                faction: self.faction,
                beguilingnpcs: self.beguilingnpcs,
                vertices: self.getVertices()
            },
            beforeSend: function () {
                $('#enemy_pack_edit_popup_submit_' + self.id).attr('disabled', 'disabled');
                self.layer.setStyle({
                    fillColor: c.map.admin.mapobject.colors.edited,
                    color: c.map.admin.mapobject.colors.editedBorder
                });
            },
            success: function (json) {
                self.map.leafletMap.closePopup();
                self.id = json.id;
                // ID has changed, rebuild the popup
                self.onPopupInit();
                self.layer.setStyle({
                    fillColor: c.map.admin.mapobject.colors.saved,
                    color: c.map.admin.mapobject.colors.savedBorder
                });
                self.setSynced(true);
            },
            complete: function () {
                $('#enemy_pack_edit_popup_submit_' + self.id).removeAttr('disabled');
            },
            error: function (xhr, textStatus, errorThrown) {
                self.layer.setStyle({
                    fillColor: c.map.admin.mapobject.colors.unsaved,
                    color: c.map.admin.mapobject.colors.unsavedBorder
                });
                // Even if we were synced, make sure user knows it's no longer / an error occurred
                self.setSynced(false);

                defaultAjaxErrorFn(xhr, textStatus, errorThrown);
            }
        });
    }
}