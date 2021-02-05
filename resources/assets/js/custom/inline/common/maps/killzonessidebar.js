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
     * @param killZone
     * @private
     */
    _addKillZone(killZone) {
        console.assert(this instanceof CommonMapsKillzonessidebar, 'this is not a CommonMapsKillzonessidebar', this);

        let rowElementKillZone = new RowElementKillZone(this, killZone);

        rowElementKillZone.render($(this.options.killZonesContainerSelector));

        this.rowElements.push(rowElementKillZone);

        $('#killzones_no_pulls').hide();

        // No need to refresh - synced will be set to true, then this function will be triggered (because we listen for it)
        rowElementKillZone.refresh();

        // A new pull was created; make sure it's selected by default
        if (this._newPullKillZone !== null && this._newPullKillZone.id > 0) {
            rowElementKillZone.select(true);

            this._newPullKillZone = null;
        }
    }

    /**
     * Should be called whenever something's changed in the killzone that warrants a UI update
     * @param killZone
     * @private
     */
    _refreshKillZone(killZone) {
        console.assert(this instanceof CommonMapsKillzonessidebar, 'this is not a CommonMapsKillzonessidebar', this);

        // Update everything after ourselves as well (cumulative enemy forces may be changed going forward).
        this._updatePullTexts(killZone.getIndex());
    }

    /**
     * @param minIndex int
     * @private
     */
    _updatePullTexts(minIndex = 0) {
        console.assert(this instanceof CommonMapsKillzonessidebar, 'this is not a CommonMapsKillzonessidebar', this);

        let self = this;

        let killZoneMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_KILLZONE);
        $.each(killZoneMapObjectGroup.objects, function (index, killZone) {
            if (killZone.getIndex() >= minIndex) {
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
        if ($(`#map_killzonessidebar_killzone_${killZone.id}`).length === 0) {
            let self = this;

            // Add the killzone to our list
            this._addKillZone(killZone);

            // Listen to changes in the killzone
            killZone.register(['killzone:enemyadded', 'killzone:enemyremoved', 'object:changed'], this, function (killZoneChangedEvent) {
                self._onKillZoneEnemyChanged(killZoneChangedEvent.context);
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

        this.map.setMapState(new KillZoneEnemySelection(this.map, killZone));
    }

    _draggedKillZoneRow() {
        console.assert(this instanceof CommonMapsKillzonessidebar, 'this is not a CommonMapsKillzonessidebar', this);

        this._dragHasSwitchedOrder = true;
    }

    _dragStop() {
        console.assert(this instanceof CommonMapsKillzonessidebar, 'this is not a CommonMapsKillzonessidebar', this);

        if (this._dragHasSwitchedOrder) {
            let killZoneMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_KILLZONE);

            let $killZonesContainerChildren = $('#killzones_container').children();
            let count = 1;
            for (let index in $killZonesContainerChildren) {
                if ($killZonesContainerChildren.hasOwnProperty(index)) {
                    let $killZoneRow = $($killZonesContainerChildren[index]);
                    let id = parseInt($killZoneRow.data('id'));

                    // NaN check, there's a mirror created and inserted in the container, this filters it out
                    if (id === id && !$killZoneRow.attr('class').includes('draggable-mirror') && !$killZoneRow.attr('class').includes('draggable--original')) {
                        let killZone = killZoneMapObjectGroup.findMapObjectById(id);
                        console.assert(killZone instanceof KillZone, 'Unable to find killZone!', $killZoneRow);

                        // Re-set the indices
                        killZone.setIndex(count);
                        count++;
                    }
                }
            }

            // Update after all indices are set, otherwise cumulative enemy forces will not be correct
            for (let index in killZoneMapObjectGroup.objects) {
                if (killZoneMapObjectGroup.objects.hasOwnProperty(index)) {
                    let killZone = killZoneMapObjectGroup.objects[index];

                    this._updatePullText(killZone);
                }
            }

            if (getState().getMapContext().getPullGradientApplyAlways()) {
                killZoneMapObjectGroup.applyPullGradient();
            }
            killZoneMapObjectGroup.massSave(['index', 'color']);
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
        this.sidebar.activate();

        this.map = getState().getDungeonMap();

        let self = this;

        // Setup new pull button
        $(this.options.newKillZoneSelector).bind('click', this._newPullClicked.bind(this));
        $(this.options.killZonesPullsSettingsSelector).on('shown.bs.collapse', function () {
            $(self.options.sidebarScrollSelector).addClass('settings-shown');
        }).on('hidden.bs.collapse', function () {
            $(self.options.sidebarScrollSelector).removeClass('settings-shown');
        });


        $(this.options.killZonesPullsSettingsNumberStyleSelector).bind('change', function () {
            getState().setKillZonesNumberStyle($(this).is(':checked') ? KILL_ZONES_NUMBER_STYLE_PERCENTAGE : KILL_ZONES_NUMBER_STYLE_ENEMY_FORCES);
        });

        $(this.options.killZonesPullsSettingsDeleteAllSelector).bind('click', function () {
            showConfirmYesCancel(lang.get('messages.killzone_sidebar_delete_all_pulls_confirm_label'), function () {
                let killZoneMapObjectGroup = self.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_KILLZONE);

                killZoneMapObjectGroup.deleteAll();
            });
        });

        getState().register('numberstyle:changed', this, function () {
            self._updatePullTexts();
        });


        this.map.register('map:mapstatechanged', this, function (mapStateChangedEvent) {
            // Update the UI based on the new map states
            let previousMapState = mapStateChangedEvent.data.previousMapState;
            if (previousMapState instanceof EnemySelection) {
                self._selectKillZone(previousMapState.getMapObject(), false);
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
        killZoneMapObjectGroup.register('save:success', this, function (killZoneSaveSuccessEvent) {
            self._onKillZoneSaved(killZoneSaveSuccessEvent.data.object);
        });
        // If the killzone was deleted, get rid of our display too
        killZoneMapObjectGroup.register('object:deleted', this, function (killZoneDeletedEvent) {
            let killZone = killZoneDeletedEvent.data.object;
            // Add the killzone to our list
            self._removeKillZone(killZone);
            // Stop listening to changes in the killzone
            killZone.unregister(['killzone:enemyadded', 'killzone:enemyremoved', 'object:changed'], self);
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
                classes: 'bg-primary'
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

        getState().unregister('numberstyle:changed', this);
    }
}