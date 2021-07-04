class RowElementKillZone extends RowElement {

    /**
     *
     * @param killZonesSidebar {CommonMapsKillzonessidebar}
     * @param killZone {KillZone}
     */
    constructor(killZonesSidebar, killZone) {
        super(killZonesSidebar, killZonesSidebar.options.edit ? 'map_killzonessidebar_killzone_row_edit_template' : 'map_killzonessidebar_killzone_row_view_template');

        console.assert(killZonesSidebar instanceof CommonMapsKillzonessidebar, 'killZonesSidebar is not a CommonMapsKillzonessidebar', this);
        console.assert(killZone instanceof KillZone, 'killZone is not a KillZone', this);

        /** @type Boolean */
        this._selected = false;
        /** @type KillZone */
        this.killZone = killZone;
        /** @type Pickr|null */
        this.colorPicker = null;
    }

    /**
     * @inheritDoc
     */
    _getTemplateData() {
        console.assert(this instanceof RowElementKillZone, 'this is not a RowElementKillZone', this);

        return {
            'id': this.killZone.id,
            'color': this.killZone.color, // For viewing
            'has_kill_area': this.killZone.hasKillArea() ? '1' : '0'
        };
    }

    /**
     * Initializes a color picker.
     * @returns {*}
     * @private
     */
    _initColorPicker() {
        console.assert(this instanceof RowElementKillZone, 'this is not a RowElementKillZone', this);

        let self = this;

        // Simple example, see optional options for more configuration.
        return Pickr.create($.extend(c.map.colorPickerDefaultOptions, {
            el: `#map_killzonessidebar_killzone_${self.killZone.id}_color`,
            default: self.killZone.color
        })).on('save', (color, instance) => {
            // Apply the new color
            let newColor = '#' + color.toHEXA().join('');
            // Only save when the color is valid
            if (self.killZone.color !== newColor && newColor.length === 7) {
                self.killZone.color = newColor;
                self.killZone.save();
            }

            // Reset ourselves
            instance.hide();
        });
    }

    /**
     * Selects a killzone based on a killzone, instead of a button click.
     * @param killZone
     * @private
     */
    _selectKillZoneByMapObject(killZone) {
        console.assert(this instanceof RowElementKillZone, 'this is not a RowElementKillZone', this);

        this._killZoneRowClicked.call($(`#map_killzonessidebar_killzone_${this.killZone.id}.selectable`));
    }

    /**
     * Called when someone clicked on a killzone row and wants to switch selections accordingly
     * @private
     */
    _killZoneRowClicked(clickEvent) {
        // this is the element that was clicked, NOT this class

        // If there was an event, prevent clicking the 'expand' button also selecting the kill zone
        if (clickEvent !== null && typeof clickEvent !== 'undefined') {
            let $target = $(clickEvent.target);
            let $parent = $($target.parent());
            if ($target.hasClass('btn') || $target.hasClass('pcr-button') || $parent.is('button')) {
                return;
            }
        }

        let map = getState().getDungeonMap();
        // Get the currently selected killzone ID, if any (so we may deselect it)
        let currentlySelectedKillZoneId = 0;
        let currentMapState = map.getMapState();
        if (currentMapState !== null && currentMapState instanceof EnemySelection) {
            currentlySelectedKillZoneId = currentMapState.getMapObject().id;
        }

        // Get the ID of the killzone that the user wants to select (or deselect)
        let selectedKillZoneId = parseInt($(this).closest('.map_killzonessidebar_killzone').data('id'));
        if (selectedKillZoneId === currentlySelectedKillZoneId) {
            // Selected what was already selected; select nothing instead
            selectedKillZoneId = 0;
        }

        // Find the killzone and if found, switch our map to a selection for that killzone
        let newMapState = null;
        if (selectedKillZoneId > 0) {
            let killZoneMapObjectGroup = map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_KILLZONE);

            /** @type KillZone */
            let killZone = killZoneMapObjectGroup.findMapObjectById(selectedKillZoneId);
            if (killZone !== null) {
                // Same as this.options.edit, really
                if (getState().getMapContext() instanceof MapContextLiveSession) {
                    newMapState = new SelectKillZoneEnemySelectionOverpull(map, killZone);
                } else {
                    if (map.options.edit) {
                        newMapState = new EditKillZoneEnemySelection(map, killZone);
                    } else {
                        // Just highlight the pull when the user clicked a pull
                        newMapState = new ViewKillZoneEnemySelection(map, killZone);
                    }
                }

                // Move the map to the killzone's center location
                map.focusOnKillZone(killZone);
            }
        }

        // Either de-select, or add a new state to the map
        map.setMapState(newMapState);
    }

    /**
     * Called whenever the trash icon is clicked and the killzone should be deleted
     * @private
     */
    _deleteKillZoneClicked() {
        let self = this;

        let trashIcon = 'fa-trash';
        let loadingIcon = 'fa-circle-notch fa-spin';

        // Prevent double deletes if user presses the button twice in a row
        if ($(self).find('i').hasClass(trashIcon)) {
            let selectedKillZoneId = parseInt($(this).closest('.map_killzonessidebar_killzone').data('id'));
            let killZoneMapObjectGroup = getState().getDungeonMap().mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_KILLZONE);
            let killZone = killZoneMapObjectGroup.findMapObjectById(selectedKillZoneId);

            $(this).find('i').removeClass(trashIcon).addClass(loadingIcon);

            killZone.register('object:deleted', '123123', function () {
                showSuccessNotification(lang.get('messages.object.deleted'));

                // Bit hacky?
                if (killZone.isKillAreaVisible()) {
                    getState().getDungeonMap().drawnLayers.removeLayer(killZone.layer);
                    getState().getDungeonMap().editableLayers.removeLayer(killZone.layer);
                }

                killZone.unregister('object:deleted', '123123');
            });
            killZone.register('object:changed', '123123', function () {
                if (!killZone.synced) {
                    // Failed to delete
                    $(self).find('i').addClass(trashIcon).removeClass(loadingIcon)
                }

                killZone.unregister('object:changed', '123123');
            });

            killZone.delete();
        }
    }

    /**
     * @inheritDoc
     */
    updateText() {
        super.updateText();
        console.assert(this instanceof RowElementKillZone, 'this is not a RowElementKillZone', this);

        let killZoneEnemyForces = this.killZone.getEnemyForces();
        $(`#map_killzonessidebar_killzone_${this.killZone.id}_enemy_forces_container:not(.draggable--original)`).toggle(killZoneEnemyForces > 0);

        if (getState().getKillZonesNumberStyle() === NUMBER_STYLE_PERCENTAGE) {
            let enemyForcesCumulativePercent = getFormattedPercentage(this.killZone.getEnemyForcesCumulative(), this.map.enemyForcesManager.getEnemyForcesRequired());
            let enemyForcesPercent = getFormattedPercentage(killZoneEnemyForces, this.map.enemyForcesManager.getEnemyForcesRequired());

            $(`#map_killzonessidebar_killzone_${this.killZone.id}_enemy_forces_cumulative:not(.draggable--original)`)
                .text(`${enemyForcesCumulativePercent}%`)
                .attr('title', `+${enemyForcesPercent}%`);
        } else if (getState().getKillZonesNumberStyle() === NUMBER_STYLE_ENEMY_FORCES) {
            $(`#map_killzonessidebar_killzone_${this.killZone.id}_enemy_forces_cumulative:not(.draggable--original)`)
                .text(`${this.killZone.getEnemyForcesCumulative()}/${this.map.enemyForcesManager.getEnemyForcesRequired()}`)
                .attr('title', `+${killZoneEnemyForces}`);
        }
        $(`#map_killzonessidebar_killzone_${this.killZone.id}_index:not(.draggable--original)`).text(this.killZone.getIndex());

        // Show boss icon or not
        let hasBoss, hasAwakened, hasPrideful, hasInspiring = false;

        let enemyMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_ENEMY);
        for (let i = 0; i < this.killZone.enemies.length; i++) {
            let enemyId = this.killZone.enemies[i];
            for (let j = 0; j < enemyMapObjectGroup.objects.length; j++) {
                let enemy = enemyMapObjectGroup.objects[j];
                if (enemy.id === enemyId) {
                    if (!hasBoss && enemy.isBossNpc()) {
                        hasBoss = true;
                    } else if (!hasAwakened && enemy.isAwakenedNpc()) {
                        hasAwakened = true;
                    } else if (!hasAwakened && enemy.isPridefulNpc()) {
                        hasPrideful = true;
                    } else if (!hasAwakened && enemy.isInspiring()) {
                        hasInspiring = true;
                    }
                    break;
                }
            }
        }

        // Reset any previous states
        let $hasBoss = $(`#map_killzonessidebar_killzone_${this.killZone.id}_has_boss:not(.draggable--original)`).hide();
        let $hasAwakened = $(`#map_killzonessidebar_killzone_${this.killZone.id}_has_awakened:not(.draggable--original)`).hide();
        let $hasPrideful = $(`#map_killzonessidebar_killzone_${this.killZone.id}_has_prideful:not(.draggable--original)`).hide();
        let $hasInspiring = $(`#map_killzonessidebar_killzone_${this.killZone.id}_has_inspiring:not(.draggable--original)`).hide();

        // Apply new state - but only one
        if (hasBoss) {
            $hasBoss.show();
        } else if (hasAwakened) {
            $hasAwakened.show()
        } else if (hasPrideful) {
            $hasPrideful.show();
        } else if (hasInspiring) {
            $hasInspiring.show();
        }

        if (hasBoss || hasAwakened || hasPrideful || hasInspiring) {
            refreshTooltips();
        }

        // $(`#map_killzonessidebar_killzone_${this.killZone.id}_grip:not(.draggable--original)`).css('color', this.killZone.color);
        // .css('color', killZone.color).css('text-shadow', `1px 1px #222`);
    }

    /**
     * @inheritDoc
     */
    refresh() {
        super.refresh();
        console.assert(this instanceof RowElementKillZone, 'this is not a RowElementKillZone', this);

        this.updateText();

        // Fill the enemy list
        let npcs = [];
        let obsoleteNpcs = [];
        let overpulledNpcs = [];

        let enemyMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_ENEMY);

        let addEnemyToNpcList = (function (enemyId) {
            /** @type {Enemy} */
            let enemy = enemyMapObjectGroup.findMapObjectById(enemyId);

            // If enemy found and said enemy has an npc
            if (enemy !== null && enemy.npc !== null) {
                // Put the enemy in the correct bucket
                let npcArr = (enemy.getOverpulledKillZoneId() !== null ? overpulledNpcs : (enemy.isObsolete() ? obsoleteNpcs : npcs));
                // If not in our array, add it
                if (!npcArr.hasOwnProperty(enemy.npc.id)) {
                    npcArr[enemy.npc.id] = {
                        name: enemy.npc.name,
                        awakened: enemy.isAwakenedNpc(),
                        prideful: enemy.isPridefulNpc(),
                        inspiring: false, // Will be set below
                        obsolete: enemy.isObsolete(),
                        overpulled: enemy.getOverpulledKillZoneId() !== null,
                        enemy: enemy,
                        count: 0,
                        enemy_forces: 0
                    };
                }

                npcArr[enemy.npc.id].count++;
                npcArr[enemy.npc.id].enemy_forces += enemy.getEnemyForces();
                npcArr[enemy.npc.id].inspiring = npcArr[enemy.npc.id].inspiring || enemy.isInspiring();
            }
        }).bind(this);

        // Add both overpulled enemies and regular enemies to their respective lists
        for (let i = 0; i < this.killZone.overpulledEnemies.length; i++) {
            addEnemyToNpcList(this.killZone.overpulledEnemies[i]);
        }

        for (let i = 0; i < this.killZone.enemies.length; i++) {
            addEnemyToNpcList(this.killZone.enemies[i]);
        }

        let $enemyList = $(`#map_killzonessidebar_killzone_${this.killZone.id}_enemy_list`);
        $enemyList.children().remove();

        let addNpcToUI = (function (index, npc) {
            let template = Handlebars.templates['map_killzonessidebar_killzone_row_enemy_template'];

            let killZone = npc.enemy.getKillZone() ?? npc.enemy.getOverpulledKillZone();

            console.log(npc.enemy.getKillZone(), npc.enemy.getOverpulledKillZone());

            let data = $.extend({}, getHandlebarsDefaultVariables(), {
                'id': index,
                'pull_color': killZone.color,
                'enemy_forces': npc.enemy_forces,
                'enemy_forces_percent': getFormattedPercentage(npc.enemy_forces, this.map.enemyForcesManager.getEnemyForcesRequired()),
                'count': npc.count,
                'name': npc.name,
                'awakened': npc.awakened,
                'prideful': npc.prideful,
                'inspiring': npc.inspiring,
                'overpulled': npc.overpulled,
                'obsolete': npc.obsolete,
                'boss': npc.enemy.isBossNpc(),
                'dangerous': npc.enemy.npc.dangerous === 1
            });

            $enemyList.append($(template(data)));
        }).bind(this);

        // Rebuild the npc lists
        for (let index in overpulledNpcs) {
            if (overpulledNpcs.hasOwnProperty(index)) {
                addNpcToUI(index, overpulledNpcs[index]);
            }
        }

        for (let index in obsoleteNpcs) {
            if (obsoleteNpcs.hasOwnProperty(index)) {
                addNpcToUI(index, obsoleteNpcs[index]);
            }
        }

        for (let index in npcs) {
            if (npcs.hasOwnProperty(index)) {
                addNpcToUI(index, npcs[index]);
            }
        }

        // Toggle the row color based on overpulled or obsolete npcs
        let $row = $(`#map_killzonessidebar_killzone_${this.killZone.id}`);
        $row.toggleClass('bg-success', overpulledNpcs.length > 0);
        $row.toggleClass('bg-danger', obsoleteNpcs.length > 0);

        if (this.killZonesSidebar.options.edit) {
            /**
             * Code to prevent calling refreshTooltips too often
             */
            let $killAreaLabel = $(`#map_killzonessidebar_killzone_${this.killZone.id}_kill_area_label`);
            // We are displaying 'has kill area' now (somehow using .data() does not work at all)
            let $hasKillArea = $killAreaLabel.attr('data-haskillarea');

            let resultMessage = '';
            // Set and is currently 0
            if ($hasKillArea === '1' && !this.killZone.hasKillArea()) {
                // It was not, update it
                resultMessage = lang.get('messages.remove_kill_area_label');
            } else {
                // Default
                resultMessage = lang.get('messages.add_kill_area_label');
            }

            // Write result regardless
            // $killAreaLabel.attr('data-haskillarea', killZone.hasKillArea() ? '1' : '0');
            // If something was changed
            if ($hasKillArea !== (this.killZone.hasKillArea() ? '1' : '0')) {
                $killAreaLabel.attr('title', resultMessage).refreshTooltips();
            }


            if (this.colorPicker !== null) {
                // SetColor is slow, check if we really need to set it
                let oldColor = '#' + this.colorPicker.getColor().toHEXA().join('');
                if (oldColor !== this.killZone.color) {
                    this.colorPicker.setColor(this.killZone.color);
                }
            } else {
                console.warn('Color picker not found!', killZone, killZone.id);
            }
        }
    }

    /**
     * @inheritDoc
     **/
    render($targetContainer) {
        super.render($targetContainer);
        console.assert(this instanceof RowElementKillZone, 'this is not a RowElementKillZone', this);

        let self = this;

        // Make it interactive
        $(`#map_killzonessidebar_killzone_${self.killZone.id}.selectable`).bind('click', this._killZoneRowClicked);

        if (this.killZonesSidebar.options.edit) {
            $(`#map_killzonessidebar_killzone_${self.killZone.id}_color`).bind('click', function (clickedEvent) {
                // Only one at a time
                let currentlyActiveColorPicker = self.killZonesSidebar.getCurrentlyActiveColorPicker();
                if (currentlyActiveColorPicker !== null) {
                    currentlyActiveColorPicker.hide();
                }

                // Show the new color picker
                self.killZonesSidebar.setCurrentlyActiveColorPicker(self.colorPicker);
                self.colorPicker.show();
            });
            let $hasKillZone = $(`#map_killzonessidebar_killzone_${self.killZone.id}_has_killzone`).bind('click', function () {
                // Inject the selectable in the _selectKillZone call to simulate selecting the actual killzone
                self._selectKillZoneByMapObject(self.killZone);

                if (self.killZone.layer === null) {
                    getState().getDungeonMap().setMapState(
                        new AddKillZoneMapState(getState().getDungeonMap(), self.killZone)
                    );
                } else {
                    // @TODO This entire piece of code is hacky, should be done differently eventually
                    getState().getDungeonMap().drawnLayers.removeLayer(self.killZone.layer);
                    getState().getDungeonMap().editableLayers.removeLayer(self.killZone.layer);

                    let killZoneMapObjectGroup = getState().getDungeonMap().mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_KILLZONE);
                    // It's been removed; unset it
                    killZoneMapObjectGroup.setLayerToMapObject(null, self.killZone);

                    self.killZone.floor_id = null;
                    // Update its visuals
                    self.killZone.redrawConnectionsToEnemies();
                    self.killZone.save();
                }
            });
            // If we have a killzone layer
            if (self.killZone.hasKillArea()) {
                // Was inactive (always starts inactive), is active now
                $hasKillZone.button('toggle');
            }
            $(`#map_killzonessidebar_killzone_${self.killZone.id}_delete`).bind('click', this._deleteKillZoneClicked);
            this.colorPicker = this._initColorPicker();
            // Small hack to get it to look better
            $(`#map_killzonessidebar_killzone_${self.killZone.id} .pcr-button`).addClass('h-100 w-100');
        }
    }

    /**
     * Selects the killzone in the sidebar
     *
     * @param selected {Boolean}
     * @private
     */
    select(selected) {
        console.assert(this instanceof RowElementKillZone, 'this is not a RowElementKillZone', this);

        // Deselect if we were selected and shouldn't be
        let classes = 'selected bg-success';
        if (this._selected && !selected) {
            $(`#map_killzonessidebar_killzone_${this.killZone.id}.selected`).removeClass(classes);
        }

        // Select the new one if we should
        if (!this._selected && selected) {
            $(`#map_killzonessidebar_killzone_${this.killZone.id}.selectable`).addClass(classes);

            // Make sure we can see the killzone in the sidebar
            let $killzone = $(`#map_killzonessidebar_killzone_${this.killZone.id}`);
            if ($killzone.length > 0 && !$killzone.visible()) {
                $killzone[0].scrollIntoView({behavior: 'smooth'});
            }
        }

        this._selected = selected;
    }

    /**
     * Gets the kill zone for this enemy.
     * @returns {KillZone|null}
     */
    getKillZone() {
        console.assert(this instanceof RowElementKillZone, 'this is not a RowElementKillZone', this);

        return this.killZone;
    }

    /**
     *
     * @param callback
     */
    remove(callback) {
        super.remove();

        let self = this;
        $(`#map_killzonessidebar_killzone_${self.killZone.id}`).fadeOut({
            complete: function () {
                // When done, remove completely
                $(`#map_killzonessidebar_killzone_${self.killZone.id}`).remove();

                // Tell the user what to do next!
                if ($('#killzones_container .selectable').length === 0) {
                    $('#killzones_no_pulls').show();
                }

                if (typeof callback === 'function') {
                    callback();
                }
            }
        });

        // Unset it, ish
        this.colorPicker = null;
        $('#killzones_no_pulls').hide();
    }
}