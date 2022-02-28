class CommonMapsKillzonessidebar extends InlineCode {


    constructor(options) {
        super(options);

        this.sidebar = new Sidebar(options);

        this._colorPickers = [];
        this._currentlyActiveColorPicker = null;
        this._newPullKillZone = null;
        this._draggable = null;

        /** @type RowElement[] */
        this.rowElements = [];
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
     *
     * @param killZone
     * @returns {RowElementKillZone|null}
     * @private
     */
    _getRowElementKillZone(killZone) {
        console.assert(this instanceof CommonMapsKillzonessidebar, 'this is not a CommonMapsKillzonessidebar', this);
        console.assert(killZone instanceof KillZone, 'killZone is not a KillZone', this);

        let result = null;

        for (let index in this.rowElements) {
            let rowElement = this.rowElements[index];
            if (rowElement instanceof RowElementKillZone && rowElement.getKillZone().id === killZone.id) {
                result = rowElement;
                break;
            }
        }

        return result;
    }

    /**
     * Triggered whenever the user has selected a killzone
     * @param killZone {KillZone}
     * @param selected {Boolean}
     * @private
     */
    _selectKillZone(killZone, selected) {
        for (let index in this.rowElements) {
            let rowElement = this.rowElements[index];
            if (rowElement instanceof RowElementKillZone) {
                // Select if the killZones match, deselect otherwise
                rowElement.select(selected && killZone.id === rowElement.getKillZone().id);
            }
        }
    }

    /**
     * Adds a killzone to the sidebar.
     * @param killZone {KillZone}
     * @private
     */
    _addKillZone(killZone) {
        console.assert(this instanceof CommonMapsKillzonessidebar, 'this is not a CommonMapsKillzonessidebar', this);

        let showFloorSwitches = getState().getPullsSidebarFloorSwitchVisibility();

        if (showFloorSwitches && this.rowElements.length === 0) {
            this._addFloorSwitch(killZone, getState().getMapContext().getDungeon().floors[0], true);
        }

        /** @type KillZoneMapObjectGroup */
        let killZoneMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_KILLZONE);

        let previousKillZoneRowElementVisual = null;
        let previousKillZone = killZoneMapObjectGroup.findKillZoneByIndex(killZone.index - 1);
        if (previousKillZone instanceof KillZone) {
            let previousKillZoneRowElement = this._getRowElementKillZone(previousKillZone);
            previousKillZoneRowElementVisual = previousKillZoneRowElement ? previousKillZoneRowElement.getVisual() : null;
        }

        let rowElementKillZone = new RowElementKillZone(this, killZone);
        rowElementKillZone.render($(this.options.killZonesContainerSelector), previousKillZoneRowElementVisual);
        this.rowElements.push(rowElementKillZone);

        $('#killzones_no_pulls').hide();

        // No need to refresh - synced will be set to true, then this function will be triggered (because we listen for it)
        rowElementKillZone.refresh();

        // Only do this when there is actually a previous killzone
        if (showFloorSwitches && killZone.index > 1) {
            let previousKillZone = killZoneMapObjectGroup.findKillZoneByIndex(killZone.index - 1);

            // If there's a difference in floors then we should display a floor switch row
            if (previousKillZone !== null) {
                let floorDifference = _.difference(killZone.getFloorIds(), previousKillZone.getFloorIds());
                if (floorDifference.length > 0) {
                    this._addFloorSwitch(killZone, getState().getMapContext().getFloorById(floorDifference[0]));
                }
            }
        }

        // A new pull was created; make sure it's selected by default
        if (this._newPullKillZone !== null && this._newPullKillZone.id > 0) {
            rowElementKillZone.select(true);

            this._newPullKillZone = null;
        }
    }

    /**
     *
     * @param killZone {KillZone}
     * @param targetFloor {Object}
     * @param start {Boolean}
     * @private
     */
    _addFloorSwitch(killZone, targetFloor, start = false) {
        console.assert(this instanceof CommonMapsKillzonessidebar, 'this is not a CommonMapsKillzonessidebar', this);

        let $killZoneElement = $(`#map_killzonessidebar_killzone_${killZone.id}`);
        let rowElementFloorSwitch = new RowElementFloorSwitch(this, killZone, targetFloor, start);
        // If it's the first element to be added..
        if (start && $killZoneElement.length === 0) {
            rowElementFloorSwitch.render($(this.options.killZonesContainerSelector));
        } else if ($killZoneElement.length !== 0) {
            rowElementFloorSwitch.renderBefore($killZoneElement);
        } else {
            console.error(`Unable to render floor switch - KillZone element was not found`);
        }

        this.rowElements.push(rowElementFloorSwitch);
    }

    /**
     *
     * @private
     */
    _rebuildFloorSwitches() {
        console.assert(this instanceof CommonMapsKillzonessidebar, 'this is not a CommonMapsKillzonessidebar', this);

        let toRemove = [];

        for (let i = 0; i < this.rowElements.length; i++) {
            if (this.rowElements[i] instanceof RowElementFloorSwitch) {
                toRemove.push(i);
            }
        }

        // Reverse the loop, we're removing multiple indices. If we start with smallest first,
        // we're going to remove the wrong indexes after the first one. Not good. Reverse preserves the proper order.
        for (let i = toRemove.length - 1; i >= 0; i--) {
            this.rowElements.splice(toRemove[i], 1);
        }

        // Remove all from the sidebar
        $('.map_killzonessidebar_floor_switch').remove();

        // Re-add them only if we should
        if (getState().getPullsSidebarFloorSwitchVisibility()) {
            let killZoneMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_KILLZONE);
            /** @type KillZone */
            let previousKillZone = null;
            let sortedObjects = _.sortBy(killZoneMapObjectGroup.objects, 'index');
            for (let i = 0; i < sortedObjects.length; i++) {
                let killZone = sortedObjects[i];
                if (i === 0) {
                    this._addFloorSwitch(killZone, getState().getMapContext().getDungeon().floors[0], true);
                } else {
                    let floorDifference = _.difference(killZone.getFloorIds(), previousKillZone.getFloorIds());
                    if (floorDifference.length > 0) {
                        this._addFloorSwitch(killZone, getState().getMapContext().getFloorById(floorDifference[0]));
                    }
                }

                previousKillZone = killZone;
            }
        }
    }

    /**
     * Should be called whenever something's changed in the killzone that warrants a UI update
     * @param killZone {KillZone}
     * @param cascadeRefresh {Boolean} True to cascade refreshes to all subsequent killzones, false to just update their pull texts instead
     * @private
     */
    _refreshKillZone(killZone, cascadeRefresh = false) {
        console.assert(this instanceof CommonMapsKillzonessidebar, 'this is not a CommonMapsKillzonessidebar', this);
        let self = this;

        // Update this particular row element to refresh enemy lists etc
        let rowElement = this._getRowElementKillZone(killZone);
        rowElement.refresh();

        if (cascadeRefresh) {
            let killZoneMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_KILLZONE);
            $.each(killZoneMapObjectGroup.objects, function (index, futureKillZone) {
                // Do not update pull texts for killzones that do not have
                if (futureKillZone.id > 0 && futureKillZone.getIndex() >= killZone.index) {
                    self._getRowElementKillZone(futureKillZone).refresh();
                }
            });
        }

        // Update everything after ourselves as well (cumulative enemy forces may be changed going forward).
        this._updatePullTexts(killZone.getIndex());
    }

    /**
     * @param minIndex {Number}
     * @private
     */
    _updatePullTexts(minIndex = 0) {
        console.assert(this instanceof CommonMapsKillzonessidebar, 'this is not a CommonMapsKillzonessidebar', this);

        let self = this;

        let killZoneMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_KILLZONE);
        $.each(killZoneMapObjectGroup.objects, function (index, killZone) {
            // Do not update pull texts for killzones that do not have
            if (killZone.id > 0 && killZone.getIndex() >= minIndex) {
                self._updatePullText(killZone);
            }
        });
    }

    /**
     * Rebuilds the upper text of a killzone (${index}: ${x} enemies (${enemyForces})
     * @param killZone {KillZone}
     * @private
     */
    _updatePullText(killZone) {
        console.assert(this instanceof CommonMapsKillzonessidebar, 'this is not a CommonMapsKillzonessidebar', this);

        let rowElementKillZone = this._getRowElementKillZone(killZone);
        if (rowElementKillZone instanceof RowElement) {
            rowElementKillZone.updateText();
        } else {
            console.warn(`Unable to find RowElementKillZone for ${killZone.id}`);
        }
    }

    /**
     * Removes a killzone from the sidebar.
     * @param killZone
     * @private
     */
    _removeKillZone(killZone) {
        console.assert(this instanceof CommonMapsKillzonessidebar, 'this is not a CommonMapsKillzonessidebar', this);

        let rowElementKillZone = this._getRowElementKillZone(killZone);
        if (rowElementKillZone instanceof RowElement) {
            // We deleted this pull, all other indices may be messed up because of it
            rowElementKillZone.remove(this._updatePullTexts.bind(this));

            for (let index in this.rowElements) {
                if (this.rowElements.hasOwnProperty(index) && this.rowElements[index] === rowElementKillZone) {
                    this.rowElements.splice(index, 1);
                    break;
                }
            }
        } else {
            console.warn(`Unable to find RowElementKillZone for ${killZone.id}`);
        }
    }

    /**
     *
     * @param killZone
     * @private
     */
    _onKillZoneSaved(killZone) {
        console.assert(this instanceof CommonMapsKillzonessidebar, 'this is not a CommonMapsKillzonessidebar', this);

        // On save, add the row first time 'round
        if (this._getRowElementKillZone(killZone) === null) {
            let self = this;

            // Add the killzone to our list
            this._addKillZone(killZone);

            // Listen to changes in the killzone
            killZone.register(['killzone:enemyadded', 'killzone:enemyremoved', 'object:changed'], this, function (killZoneChangedEvent) {
                // Don't perform this when mass-saving - that is handled already and causes a big slowdown
                let isMassSave = killZoneChangedEvent.data.hasOwnProperty('mass_save') && killZoneChangedEvent.data.mass_save;

                if (!isMassSave) {
                    self._onKillZoneEnemyChanged(killZoneChangedEvent.context);
                }
            });
        }
    }

    /**
     *
     * @param killZone
     * @private
     */
    _onKillZoneEnemyChanged(killZone) {
        console.assert(this instanceof CommonMapsKillzonessidebar, 'this is not a CommonMapsKillzonessidebar', this);

        // Do not change the sidebar as we're refreshing the map; that's pointless (lots of adds/removes going on)
        if (!this.map.isRefreshingMap()) {
            this._refreshKillZone(killZone);
        }
    }

    /**
     * User clicked on the "New pull" button in the sidebar
     * @private
     */
    _onNewKillZoneClicked() {
        let killZoneMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_KILLZONE);
        let killZone = killZoneMapObjectGroup.createNewPull();

        this.map.setMapState(new EditKillZoneEnemySelection(this.map, killZone));
    }

    _draggedKillZoneRow() {
        console.assert(this instanceof CommonMapsKillzonessidebar, 'this is not a CommonMapsKillzonessidebar', this);

        this._dragHasSwitchedOrder = true;
    }

    _dragStop() {
        console.assert(this instanceof CommonMapsKillzonessidebar, 'this is not a CommonMapsKillzonessidebar', this);

        if (this._dragHasSwitchedOrder) {
            /** @type KillZoneMapObjectGroup */
            let killZoneMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_KILLZONE);

            let $killZonesContainerChildren = $('#killzones_container').children('.map_killzonessidebar_killzone');
            let count = 1;
            for (let index in $killZonesContainerChildren) {
                if ($killZonesContainerChildren.hasOwnProperty(index)) {
                    let $killZoneRow = $($killZonesContainerChildren[index]);
                    let id = parseInt($killZoneRow.data('id'));

                    // NaN check, there's a mirror created and inserted in the container, this filters it out
                    if (id === id && !$killZoneRow.attr('class').includes('draggable-mirror') && !$killZoneRow.attr('class').includes('draggable--original')) {
                        /** @type KillZone */
                        let killZone = killZoneMapObjectGroup.findMapObjectById(id);
                        console.assert(killZone instanceof KillZone, 'Unable to find killZone!', $killZoneRow);

                        // Re-set the indices
                        killZone.setIndex(count);
                        count++;
                    }
                }
            }

            // Update after all indices are set, otherwise cumulative enemy forces will not be correct
            this._updatePullTexts();

            // As killzones are switched around, the floor switch indicators should be updated
            this._rebuildFloorSwitches();

            // If the gradient should be retained, do it
            if (getState().getMapContext().getPullGradientApplyAlways()) {
                killZoneMapObjectGroup.applyPullGradient();
            }

            killZoneMapObjectGroup.massSave(['index', 'color'], null);
        }
        this._dragHasSwitchedOrder = false;
    }

    /**
     *
     * @returns {Pickr|null}
     */
    getCurrentlyActiveColorPicker() {
        return this._currentlyActiveColorPicker;
    }

    /**
     *
     * @param colorpicker {Pickr|null}
     */
    setCurrentlyActiveColorPicker(colorpicker) {
        this._currentlyActiveColorPicker = colorpicker;
    }

    /**
     *
     */
    activate() {
        super.activate();

        console.assert(this instanceof CommonMapsKillzonessidebar, 'this is not a CommonMapsKillzonessidebar', this);

        this.map = getState().getDungeonMap();

        let self = this;

        this.sidebar.activate();

        // Setup new pull button
        $(this.options.newKillZoneSelector).bind('click', this._newPullClicked.bind(this));


        $(this.options.killZonesPullsSettingsMapNumberStyleSelector).bind('change', function () {
            getState().setMapNumberStyle($(this).is(':checked') ? NUMBER_STYLE_PERCENTAGE : NUMBER_STYLE_ENEMY_FORCES);
        });

        $(this.options.killZonesPullsSettingsNumberStyleSelector).bind('change', function () {
            getState().setKillZonesNumberStyle($(this).is(':checked') ? NUMBER_STYLE_PERCENTAGE : NUMBER_STYLE_ENEMY_FORCES);
        });

        $(this.options.killZonesPullsSettingsDeleteAllSelector).bind('click', function () {
            showConfirmYesCancel(lang.get('messages.killzone_sidebar_delete_all_pulls_confirm_label'), function () {
                let killZoneMapObjectGroup = self.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_KILLZONE);

                killZoneMapObjectGroup.deleteAll();
                self._rebuildFloorSwitches();
            });
        });

        // This must be the longest variable name I've ever made :)
        $(this.options.killZonesPullsSettingsPullsSidebarFloorSwitchVisibilitySelector).bind('change', function () {
            getState().setPullsSidebarFloorSwitchVisibility($(this).is(':checked'));
        });

        getState().register('killzonesnumberstyle:changed', this, function () {
            self._updatePullTexts();
        });

        getState().register('pullssidebarfloorswitchvisibility:changed', this, function () {
            self._rebuildFloorSwitches();
        });


        this.map.register('map:mapstatechanged', this, function (mapStateChangedEvent) {
            // Update the UI based on the new map states
            let previousMapState = mapStateChangedEvent.data.previousMapState;
            if (previousMapState instanceof EnemySelection) {
                self._selectKillZone(previousMapState.getMapObject(), false);
            }

            // Refresh all killzones when we finished selecting overpulled enemies
            if (previousMapState instanceof SelectKillZoneEnemySelectionOverpull) {
                self._refreshKillZone(previousMapState.getMapObject(), true);
            }

            let newMapState = mapStateChangedEvent.data.newMapState;
            if (newMapState instanceof EnemySelection) {
                self._selectKillZone(newMapState.getMapObject(), true);
            }
        });

        let killZoneMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_KILLZONE);
        // User interface action created a new killzone
        killZoneMapObjectGroup.register('killzone:new', this, function (killZoneCreatedEvent) {
            // We do not know the ID before this so we cannot scroll to the new killzone instantly
            self._newPullKillZone = killZoneCreatedEvent.data.newKillZone;
        });
        killZoneMapObjectGroup.register(['object:add', 'save:success'], this, function (killZoneSaveSuccessEvent) {
            self._onKillZoneSaved(killZoneSaveSuccessEvent.data.object);
        });
        // If the killzone was deleted, get rid of our display too
        killZoneMapObjectGroup.register('object:deleted', this, function (killZoneDeletedEvent) {
            let isMassDelete = killZoneDeletedEvent.data.hasOwnProperty('mass_delete') && killZoneDeletedEvent.data.mass_delete;

            let killZone = killZoneDeletedEvent.data.object;
            // Add the killzone to our list
            self._removeKillZone(killZone);

            // If the killzone switched floors, we gotta rebuild the floor switches.
            if (!isMassDelete) {
                self._rebuildFloorSwitches();
            }

            // Stop listening to changes in the killzone
            killZone.unregister(['killzone:enemyadded', 'killzone:enemyremoved', 'object:changed'], self);
        });

        killZoneMapObjectGroup.register([
            'killzone:obsoleteenemychanged'
        ], this, function (overpulledEnemyChangedEvent) {
            self._refreshKillZone(overpulledEnemyChangedEvent.data.killzone);
        });

        console.assert(killZoneMapObjectGroup.isInitialized(), 'KillZoneMapObjectGroup must be initialized!', this);

        $('#killzones_loading').hide();
        if (killZoneMapObjectGroup.objects.length === 0) {
            $('#killzones_no_pulls').show();
        } else {
            // Load all existing killzones
            for (let i = 0; i < killZoneMapObjectGroup.objects.length; i++) {
                self._onKillZoneSaved(killZoneMapObjectGroup.objects[i]);
            }
        }

        $('#killzones_new_pull').on('click', this._onNewKillZoneClicked.bind(this));

        if (this.map.options.edit) {
            this._draggable = new Draggable.Sortable(document.querySelectorAll('#killzones_container'), {
                draggable: '.map_killzonessidebar_killzone',
                classes: 'bg-primary',
                distance: 5
            });
            this._draggable.on('drag:out', self._draggedKillZoneRow.bind(self));
            this._draggable.on('drag:stop', self._dragStop.bind(self));
            // let events = ['drag:start', 'drag:move', 'drag:over', 'drag:over:container', 'drag:out', 'drag:out:container', 'drag:stop', 'drag:pressure'];
            // for (let index in events) {
            //     this._draggable.on(events[index], function () {
            //         console.log(events[index]);
            //     });
            // }
        }

        // Handle selection of pulls with A+D or arrow keys
        $(document).keypress(function (keyPressEvent) {
            if ($(keyPressEvent.target).attr('id') !== 'map') {
                // Ignore key presses that aren't on the map itself
                return;
            }

            // A key
            let selectPrevious = keyPressEvent.charCode === 97;
            // D key
            let selectNext = keyPressEvent.charCode === 100;

            if (selectPrevious || selectNext) {
                // Get the currently selected killzone
                let mapState = self.map.getMapState();
                let newSelectedKillZone = null;

                if (mapState instanceof SelectKillZoneEnemySelectionOverpull ||
                    mapState instanceof EditKillZoneEnemySelection ||
                    mapState instanceof ViewKillZoneEnemySelection) {
                    if (selectNext) {
                        // Search from the first to the end
                        for (let i = 0; i < killZoneMapObjectGroup.objects.length; i++) {
                            let killZone = killZoneMapObjectGroup.objects[i];
                            if (killZone.index > mapState.getMapObject().index) {
                                newSelectedKillZone = killZone;
                                break;
                            }
                        }
                    } else {
                        // Search from the end to the first
                        for (let i = killZoneMapObjectGroup.objects.length - 1; i >= 0; i--) {
                            let killZone = killZoneMapObjectGroup.objects[i];
                            if (killZone.index < mapState.getMapObject().index) {
                                newSelectedKillZone = killZone;
                                break;
                            }
                        }
                    }
                } else if (mapState === null) {
                    // Grab the first
                    newSelectedKillZone = killZoneMapObjectGroup.objects[0];
                }

                // Only if we have one to select
                if (newSelectedKillZone instanceof KillZone) {
                    let newMapState = null;
                    if (getState().getMapContext() instanceof MapContextLiveSession) {
                        newMapState = new SelectKillZoneEnemySelectionOverpull(self.map, newSelectedKillZone, mapState);
                    } else if (self.map.options.edit) {
                        newMapState = new EditKillZoneEnemySelection(self.map, newSelectedKillZone, mapState);
                    } else {
                        newMapState = new ViewKillZoneEnemySelection(self.map, newSelectedKillZone, mapState);
                    }
                    self.map.setMapState(newMapState);

                    // Move the map to the killzone's center location
                    self.map.focusOnKillZone(newSelectedKillZone);
                }
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
        killZoneMapObjectGroup.unregister(['object:add', 'object:deleted', 'killzone:new', 'killzone:overpulledenemyadded', 'killzone:overpulledenemyremoved'], this);

        getState().unregister('killzonesnumberstyle:changed', this);
    }
}
