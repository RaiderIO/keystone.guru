class CommonMapsKillzonessidebar extends InlineCode {


    constructor(options) {
        super(options);
        this.sidebar = new Sidebar(options);

        this._colorPickers = [];
        this._currentlyActiveColorPicker = null;
        this._newPullKillZone = null;
    }

    /**
     * Called when the 'new pull' button has been pressed
     * @private
     */
    _newPullClicked() {
        let killZoneMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_KILLZONE);
        killZoneMapObjectGroup.createNewPull();
    }

    /**
     * Selects a killzone based on a killzone, instead of a button click.
     * @param killZone
     * @private
     */
    _selectKillZoneByMapObject(killZone) {
        console.assert(this instanceof CommonMapsKillzonessidebar, 'this is not a CommonMapsKillzonessidebar', this);

        this._killZoneRowClicked.call($(`#map_killzonessidebar_killzone_${killZone.id} .selectable`));
    }

    /**
     * Triggered whenever the user has selected a killzone
     * @param killZone {KillZone}
     * @param selected {Boolean}
     * @private
     */
    _killZoneSelected(killZone, selected) {
        // Deselect everything
        let classes = 'selected bg-success';
        $('#killzones_container .selected').removeClass(classes);

        // Select the new one if we should
        if (selected) {
            $(`#map_killzonessidebar_killzone_${killZone.id} .selectable`).addClass(classes);
        }

        // Make sure we can see the killzone in the sidebar
        if (!$(`#map_killzonessidebar_killzone_${killZone.id}`).visible()) {
            $(this.options.sidebarScrollSelector).mCustomScrollbar('scrollTo', `#map_killzonessidebar_killzone_${killZone.id}`);
        }
    }

    /**
     * Called when someone clicked on a killzone row and wants to switch selections accordingly
     * @private
     */
    _killZoneRowClicked(clickEvent) {
        // If there was an event, prevent clicking the 'expand' button also selecting the killzone
        if (clickEvent !== null && typeof clickEvent !== 'undefined' &&
            ($(clickEvent.target).hasClass('btn') || $(clickEvent.target).hasClass('pcr-button') || $(clickEvent.target).hasClass('fa'))) {
            return;
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
            let killZone = killZoneMapObjectGroup.findMapObjectById(selectedKillZoneId);
            if (killZone !== null) {
                // Same as this.options.edit, really
                if (map.options.edit) {
                    newMapState = new KillZoneEnemySelection(map, killZone);
                } else {
                    // Just highlight the pull when the user clicked a pull
                    newMapState = new ViewKillZoneEnemySelection(map, killZone);
                }

                // Center the map to this killzone
                if (killZone.floor_id === getState().getCurrentFloor().id && killZone.enemies.length > 0) {
                    getState().getDungeonMap().leafletMap.setView(killZone.getLayerCenteroid(), getState().getMapZoomLevel())
                }
            }
        }

        // Either de-select, or add a new state to the map
        map.setMapState(newMapState);
    }

    /**
     * Initializes a color picker.
     * @param killZone
     * @returns {*}
     * @private
     */
    _initColorPicker(killZone) {
        console.assert(this instanceof CommonMapsKillzonessidebar, 'this is not a CommonMapsKillzonessidebar', this);

        // Simple example, see optional options for more configuration.
        return Pickr.create($.extend(c.map.colorPickerDefaultOptions, {
            el: `#map_killzonessidebar_killzone_${killZone.id}_color`,
            default: killZone.color
        })).on('save', (color, instance) => {
            // Apply the new color
            let newColor = '#' + color.toHEXA().join('');
            // Only save when the color is valid
            if (killZone.color !== newColor && newColor.length === 7) {
                killZone.color = newColor;
                killZone.save();
            }

            // Reset ourselves
            instance.hide();
        });
    }

    /**
     * Adds a killzone to the sidebar.
     * @param killZone
     * @private
     */
    _addKillZone(killZone) {
        console.assert(this instanceof CommonMapsKillzonessidebar, 'this is not a CommonMapsKillzonessidebar', this);
        let self = this;

        let template = Handlebars.templates[
            this.options.edit ? 'map_killzonessidebar_killzone_row_edit_template' : 'map_killzonessidebar_killzone_row_view_template'
            ];

        let data = $.extend({}, getHandlebarsDefaultVariables(), {
            'id': killZone.id,
            'text-class': 'text-white',
            'color': killZone.color // For viewing
        });

        $(this.options.killZonesContainerSelector).append(
            $(template(data))
        );

        $('#killzones_no_pulls').hide();
        $(`#map_killzonessidebar_killzone_${killZone.id} .selectable`).bind('click', this._killZoneRowClicked);

        if (this.options.edit) {
            $(`#map_killzonessidebar_killzone_${killZone.id}_color`).bind('click', function (clickedEvent) {
                // Only one at a time
                if (self._currentlyActiveColorPicker !== null) {
                    self._currentlyActiveColorPicker.hide();
                }

                // Show the new color picker
                self._currentlyActiveColorPicker = self._colorPickers[killZone.id];
                self._currentlyActiveColorPicker.show();
            });
            let $hasKillZone = $(`#map_killzonessidebar_killzone_${killZone.id}_has_killzone`).bind('click', function () {
                // Inject the selectable in the _selectKillZone call to simulate selecting the actual killzone
                self._selectKillZoneByMapObject(killZone);

                if (killZone.layer === null) {
                    // Start drawing a killzone
                    $('.leaflet-draw-draw-killzone')[0].click();
                } else {
                    // @TODO This entire piece of code is hacky, should be done differently eventually
                    getState().getDungeonMap().drawnLayers.removeLayer(killZone.layer);
                    getState().getDungeonMap().editableLayers.removeLayer(killZone.layer);

                    let killZoneMapObjectGroup = getState().getDungeonMap().mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_KILLZONE);
                    // It's been removed; unset it
                    killZoneMapObjectGroup.setLayerToMapObject(null, killZone);

                    killZone.floor_id = getState().getCurrentFloor().id;
                    // Update its visuals
                    killZone.redrawConnectionsToEnemies();
                    killZone.save();
                }
            });
            // If we have a killzone layer
            if (killZone.isKillZoneVisible()) {
                // Was inactive (always starts inactive), is active now
                $hasKillZone.button('toggle');
            }
            $(`#map_killzonessidebar_killzone_${killZone.id}_delete`).bind('click', this._deleteKillZoneClicked);
            this._colorPickers[killZone.id] = this._initColorPicker(killZone);
            // Small hack to get it to look better
            $(`#map_killzonessidebar_killzone_${killZone.id} .pcr-button`).addClass('h-100 w-100');
        }

        // No need to refresh - synced will be set to true, then this function will be triggered (because we listen for it)
        // this._refreshKillZone(killZone);

        // A new pull was created; make sure it's selected by default
        if (this._newPullKillZone !== null) {
            this._killZoneSelected(this._newPullKillZone, true);

            this._newPullKillZone = null;
        }
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
                if (killZone.isKillZoneVisible()) {
                    getState().getDungeonMap().drawnLayers.removeLayer(killZone.layer);
                    getState().getDungeonMap().editableLayers.removeLayer(killZone.layer);
                }

                killZone.unregister('object:deleted', '123123');
            });
            killZone.register('synced', '123123', function () {
                if (!killZone.synced) {
                    // Failed to delete
                    $(self).find('i').addClass(trashIcon).removeClass(loadingIcon)
                }

                killZone.unregister('synced', '123123');
            });

            killZone.delete();
        }
    }

    /**
     *
     * @private
     */
    _rebuildPullIndices() {
        console.assert(this instanceof CommonMapsKillzonessidebar, 'this is not a CommonMapsKillzonessidebar', this);

        let self = this;

        let killZoneMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_KILLZONE);
        $.each(killZoneMapObjectGroup.objects, function(index, killZone){
            self._setPullText(killZone);
        });
    }

    /**
     * Rebuilds the upper text of a killzone (${index}: ${x} enemies (${enemyForces})
     * @param killZone {KillZone}
     * @private
     */
    _setPullText(killZone) {
        console.assert(this instanceof CommonMapsKillzonessidebar, 'this is not a CommonMapsKillzonessidebar', this);
        console.assert(killZone instanceof KillZone, 'killZone is not a KillZone', this);

        $(`#map_killzonessidebar_killzone_${killZone.id}_index`).text(killZone.getIndex());
        $(`#map_killzonessidebar_killzone_${killZone.id}_enemies`).text(`${killZone.enemies.length} enemies (${killZone.getEnemyForces()})`);
    }

    /**
     * Removes a killzone from the sidebar.
     * @param killZone
     * @private
     */
    _removeKillZone(killZone) {
        console.assert(this instanceof CommonMapsKillzonessidebar, 'this is not a CommonMapsKillzonessidebar', this);
        let self = this;

        $(`#map_killzonessidebar_killzone_${killZone.id}`).fadeOut({
            complete: function () {
                // When done, remove completely
                $(`#map_killzonessidebar_killzone_${killZone.id}`).remove();

                // Tell the user what to do next!
                if ($('#killzones_container .selectable').length === 0) {
                    $('#killzones_no_pulls').show();
                }

                // We deleted this pull, all other indices may be messed up because of it
                self._rebuildPullIndices();
            }
        });

        // Unset it, ish
        this._colorPickers[killZone.id] = null;
        $('#killzones_no_pulls').hide();
    }

    /**
     * Should be called whenever something's changed in the killzone that warrants a UI update
     * @param killZone
     * @private
     */
    _refreshKillZone(killZone) {
        console.assert(this instanceof CommonMapsKillzonessidebar, 'this is not a CommonMapsKillzonessidebar', this);
        // console.warn('refreshing killzone!', killZone.color);
        // let enemyForcesPercent = (killZone.getEnemyForces() / this.map.getEnemyForcesRequired()) * 100;
        // enemyForcesPercent = Math.floor(enemyForcesPercent * 100) / 100;

        this._setPullText(killZone);
        $(`#map_killzonessidebar_killzone_${killZone.id}_kill_area_label`)
            .attr('title', lang.get(killZone.isKillZoneVisible() ? 'messages.remove_kill_area_label' : 'messages.add_kill_area_label'));


        // Fill the enemy list
        let npcs = [];
        let enemies = getState().getEnemies();
        for (let i = 0; i < killZone.enemies.length; i++) {
            let enemyId = killZone.enemies[i];
            for (let j = 0; j < enemies.length; j++) {
                let enemy = enemies[j];
                // If enemy found and said enemy has an npc
                if (enemy.id === enemyId && enemy.npc !== null) {
                    // If not in our array, add it
                    if (!npcs.hasOwnProperty(enemy.npc.id)) {
                        npcs[enemy.npc.id] = {
                            'npc': enemy.npc,
                            'count': 0
                        };
                    }

                    npcs[enemy.npc.id].count++;
                }
            }
        }

        let $enemyList = $(`#map_killzonessidebar_killzone_${killZone.id}_enemy_list`);
        $enemyList.children().remove();
        for (let index in npcs) {
            if (npcs.hasOwnProperty(index)) {
                let obj = npcs[index];

                let template = Handlebars.templates['map_killzonessidebar_killzone_row_enemy_row_template'];

                let data = $.extend({}, getHandlebarsDefaultVariables(), {
                    'enemy_forces': obj.count * obj.npc.enemy_forces,
                    'count': obj.count,
                    'name': obj.npc.name,
                    'dangerous': obj.npc.dangerous === 1
                });

                $enemyList.append($(template(data)));
            }
        }

        if (this.options.edit) {
            if (this._colorPickers.hasOwnProperty(killZone.id)) {
                this._colorPickers[killZone.id].setColor(killZone.color);
            } else {
                console.warn('Color picker not found!', killZone, killZone.id);
            }
        }

        refreshTooltips();
    }

    /**
     *
     */
    activate() {
        console.assert(this instanceof CommonMapsKillzonessidebar, 'this is not a CommonMapsKillzonessidebar', this);
        this.sidebar.activate();

        this.map = getState().getDungeonMap();

        let self = this;

        // Setup new pull button

        $(this.options.newKillZoneSelector).bind('click', this._newPullClicked.bind(this));

        this.map.register('map:mapstatechanged', this, function (mapStateChangedEvent) {
            // Update the UI based on the new map states
            let previousMapState = mapStateChangedEvent.data.previousMapState;
            if (previousMapState instanceof EnemySelection) {
                self._killZoneSelected(previousMapState.getMapObject(), false);
            }
            let newMapState = mapStateChangedEvent.data.newMapState;
            if (newMapState instanceof EnemySelection) {
                self._killZoneSelected(newMapState.getMapObject(), true);
            }
        });

        // this.map.register('map:beforerefresh', this, function(beforeRefreshEvent){
        //     $('#killzones_no_pulls').hide();
        //     $('#killzones_loading').show();
        // });

        let killZoneMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_KILLZONE);
        // User interface action created a new killzone
        killZoneMapObjectGroup.register('killzone:new', this, function (killZoneCreatedEvent) {
            self._newPullKillZone = killZoneCreatedEvent.data.newKillZone;
        });
        killZoneMapObjectGroup.register('object:add', this, function (killZoneAddedEvent) {
            let killZone = killZoneAddedEvent.data.object;
            // Add the killzone to our list
            self._addKillZone(killZone);
            // Listen to changes in the killzone
            killZone.register(['killzone:enemyadded', 'killzone:enemyremoved', 'synced'], self, function (killZoneChangedEvent) {
                self._refreshKillZone(killZoneChangedEvent.context);
            });
        });
        // If the killzone was deleted, get rid of our display too
        killZoneMapObjectGroup.register('object:deleted', this, function (killZoneDeletedEvent) {
            let killZone = killZoneDeletedEvent.data.object;
            // Add the killzone to our list
            self._removeKillZone(killZone);
            // Stop listening to changes in the killzone
            killZone.unregister(['killzone:enemyadded', 'killzone:enemyremoved', 'synced'], self);
        });
        killZoneMapObjectGroup.register('restorecomplete', this, function () {
            $('#killzones_loading').hide();
            if (killZoneMapObjectGroup.objects.length === 0) {
                $('#killzones_no_pulls').show();
            }
        });
    }

    /**
     *
     */
    cleanup() {
        console.assert(this instanceof CommonMapsKillzonessidebar, 'this is not a CommonMapsKillzonessidebar', this);

        this.map.unregister('map:mapstatechanged', this);
        // this.map.unregister('map:beforerefresh', this);
        let killZoneMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_KILLZONE);
        killZoneMapObjectGroup.unregister(['object:add', 'object:deleted', 'killzone:new'], this);

        this.sidebar.cleanup();
    }
}