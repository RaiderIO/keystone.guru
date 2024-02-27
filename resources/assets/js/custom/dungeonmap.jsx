class DungeonMap extends Signalable {

    constructor(mapid, options) { // floorID, edit, teeming
        super();
        let self = this;

        /** @type Boolean */
        this._refreshingMap = false;

        this.options = options;

        let state = getState();
        let mapContext = state.getMapContext();

        if (!(mapContext instanceof MapContextLiveSession)) {
            if (state.isMapAdmin()) {
                if (mapContext.getMappingVersion().merged) {
                    let template = Handlebars.templates['map_controls_snackbar_mapping_version_readonly'];

                    let data = $.extend({}, getHandlebarsDefaultVariables(), {});

                    state.addSnackbar(template(data));

                    this.options.readonly = true;
                }
            } else if (this.options.edit && mapContext.getMappingVersion().version < mapContext.getDungeonLatestMappingVersion().version) {
                let template = Handlebars.templates['map_controls_snackbar_mapping_version_upgrade'];

                let data = $.extend({}, getHandlebarsDefaultVariables(), {
                    'upgrade_url': mapContext.getMappingVersionUpgradeUrl()
                });

                state.addSnackbar(template(data));
            }
        }

        // Apply the map to our state first thing
        state.setDungeonMap(this);

        // Listen for floor changes
        state.register('floorid:changed', this, function (floorIdChangedEvent) {
            self.refreshLeafletMap(false, floorIdChangedEvent.data.center, floorIdChangedEvent.data.zoom);
        });

        // How many map objects have returned a success status
        if (!this.options.readonly) {
            this.hotkeys = this._getHotkeys();
        }
        this.mapObjectGroupManager = new MapObjectGroupManager(this, this._getMapObjectGroupNames());
        this.mapObjectGroupManager.register('loaded', this, function () {
            self.signal('map:mapobjectgroupsloaded');
        });
        this.enemyVisualManager = new EnemyVisualManager(this);
        this.enemyForcesManager = new EnemyForcesManager(this);

        // Pather instance
        this.pather = null;

        // Keep track of all objects that are added to the groups through whatever means; put them in the mapObjects array
        for (let i = 0; i < this.mapObjectGroupManager.mapObjectGroups.length; i++) {
            let mapObjectGroup = this.mapObjectGroupManager.mapObjectGroups[i];

            mapObjectGroup.register('object:add', this, function (addEvent) {
                let mapObject = addEvent.data.object;
                self.mapObjects.push(mapObject);

                if (mapObject instanceof Enemy && !(self instanceof AdminDungeonMap)) {
                    mapObject.register('enemy:clicked', self, self._enemyClicked.bind(self));
                }
            });

            // Add and remove layers as they are added to the layer
            mapObjectGroup.register('object:layerchanged', this, function (layerChangedEvent) {
                let mapObject = layerChangedEvent.data.object;

                // Remove the old layer if there was any
                let oldLayer = layerChangedEvent.data.oldLayer;
                if (oldLayer !== null) {
                    if (self.drawnLayers.hasLayer(oldLayer)) {
                        self.drawnLayers.removeLayer(oldLayer);
                    }
                    if (self.editableLayers.hasLayer(oldLayer)) {
                        self.editableLayers.removeLayer(oldLayer);
                    }
                }

                // Add the new layer when we should
                let newLayer = layerChangedEvent.data.newLayer;
                if (newLayer !== null) {
                    if (mapObject.shouldBeVisible()) {
                        self.drawnLayers.addLayer(newLayer);

                        // Make sure we know it's editable; PridefulEnemies are part of the EnemyMapObjectGroup which is not editable, but they should be
                        if (mapObject.isEditable() && (layerChangedEvent.data.objectgroup.editable || mapObject instanceof PridefulEnemy) && self.options.edit) {
                            self.editableLayers.addLayer(newLayer);
                        }
                    }
                }
            });

            mapObjectGroup.register('object:deleted', this, function (deletedEvent) {
                let object = deletedEvent.data.object;
                for (let index in self.mapObjects) {
                    if (self.mapObjects.hasOwnProperty(index)) {
                        let mapObject = self.mapObjects[index];
                        if (mapObject === object) {
                            // Remove it from the list of tracked map objects
                            self.mapObjects.splice(index, 1);
                        }
                    }
                }

                if (object.layer !== null) {
                    self.editableLayers.removeLayer(object.layer);
                    self.drawnLayers.removeLayer(object.layer);
                }

                // Provide functionality for when an enemy gets clicked and we need to create a new killzone for it
                if (object instanceof Enemy && !(self instanceof AdminDungeonMap)) {
                    object.unregister('enemy:clicked', self);
                }
            });

            // Make sure we don't try to edit layers that aren't visible because they're hidden
            // If we don't do this and we have a hidden object, editing layers will break the moment you try to use it
            mapObjectGroup.register(['mapobject:shown', 'mapobject:hidden'], this, function (visibilityEvent) {
                let mapObject = visibilityEvent.data.object;
                // If it's visible now and the layer is not added already
                if (mapObject.layer !== null) {
                    if (visibilityEvent.data.visible && !self.drawnLayers.hasLayer(mapObject.layer)) {
                        // Add it
                        self.drawnLayers.addLayer(mapObject.layer);
                        // Only if we may add the layer; PridefulEnemies are part of the EnemyMapObjectGroup which is not editable, but they should be
                        if (mapObject.isEditable() && (visibilityEvent.data.objectgroup.editable || mapObject instanceof PridefulEnemy) && self.options.edit) {
                            self.editableLayers.addLayer(mapObject.layer);
                        }
                    }
                    // If it should not be visible but it's visible now
                    else if (!visibilityEvent.data.visible && self.drawnLayers.hasLayer(mapObject.layer)) {
                        // Remove it from the layer
                        self.drawnLayers.removeLayer(mapObject.layer);
                        self.editableLayers.removeLayer(mapObject.layer);
                    }
                }
            });

            mapObjectGroup.register('visibility:changed', this, function (visibilityChangedEvent) {
                if (visibilityChangedEvent.data.visible) {
                    self.leafletMap.addLayer(visibilityChangedEvent.context.layerGroup);
                } else {
                    self.leafletMap.removeLayer(visibilityChangedEvent.context.layerGroup);
                }
            });
        }

        /** @type MapObject[] Stores all possible objects that are displayed on the map */
        this.mapObjects = [];
        /** @var Array Stores all UI elements that are drawn on the map */
        this.mapControls = [];
        /** @type MapState */
        this.mapState = null;

        this.mapTileLayer = null;

        L.Map.addInitHook('addHandler', 'gestureHandling', GestureHandling.GestureHandling);

        // Create the map object
        this.leafletMap = L.map(mapid, $.extend({
            center: [0, 0],
            // We use a custom draw control, so don't use this
            // drawControl: true,
            // Simple 1:1 coordinates to meters, don't use Mercator or anything like that
            crs: L.CRS.Simple,
            gestureHandling: this.options.gestureHandling
        }, c.map.settings));
        // Make sure we can place things in the center of the map
        this._createAdditionalControlPlaceholders();
        // Top left is reserved for the sidebar
        // this.leafletMap.zoomControl.setPosition('topright');

        // Special handling for brush drawing
        this.leafletMap.on(L.Draw.Event.DRAWSTART + ' ' + L.Draw.Event.EDITSTART + ' ' + L.Draw.Event.DELETESTART, function (e) {
            // Disable pather if we were doing it
            self.togglePather(false);
        });
        this.leafletMap.on(L.Draw.Event.DRAWSTOP, function (e) {
            // After adding, there may be layers when there were none. Fix the edit/delete tooltips
            refreshTooltips();
        });
        // Set all edited layers to no longer be synced.
        this.leafletMap.on(L.Draw.Event.EDITED, function (e) {
            let layers = e.layers;
            layers.eachLayer(function (layer) {
                let mapObject = self.findMapObjectByLayer(layer);
                console.assert(mapObject instanceof MapObject, 'mapObject is not a MapObject', mapObject);

                // No longer synced
                mapObject.setSynced(false);
                mapObject.save();
            });
        });

        this.leafletMap.on(L.Draw.Event.DELETED, function (e) {
            let layers = e.layers;
            let layersDeleted = 0;
            let layersLength = 0; // No default function for this

            let layerDeletedFn = function () {
                layersDeleted++;
                if (layersDeleted === layersLength) {
                    showSuccessNotification(lang.get('messages.object.deleted'));
                }
            };

            layers.eachLayer(function (layer) {
                let mapObject = self.findMapObjectByLayer(layer);
                console.assert(mapObject instanceof MapObject, 'mapObject is not a MapObject', mapObject);

                if (typeof mapObject.delete === 'function') {
                    mapObject.register('object:deleted', self, layerDeletedFn);
                    mapObject.delete();
                } else {
                    console.error(mapObject, ' does not have a delete() function!');
                }

                // Remove from both layers
                self.drawnLayers.removeLayer(layer);
                self.editableLayers.removeLayer(layer);

                layersLength++;
            });

            // After deleting, there may be no layers left. Fix the edit/delete tooltips
            refreshTooltips();
        });

        this.leafletMap.on(L.Draw.Event.TOOLBAROPENED, function (e) {
            self.toolbarActive = true;
            // If we were doing anything, we're no longer doing it
            // self.setMapState(null);
        });
        this.leafletMap.on(L.Draw.Event.TOOLBARCLOSED, function (e) {
            self.toolbarActive = false;
        });
        this.leafletMap.on(L.Draw.Event.DELETESTART, function (e) {
            self.setMapState(new DeleteMapState(self));
        });
        this.leafletMap.on(L.Draw.Event.DELETESTOP, function (e) {
            if (self.getMapState() instanceof DeleteMapState) {
                self.setMapState(null);
            }
        });

        this.leafletMap.on(L.Draw.Event.EDITSTART, function (e) {
            self.setMapState(new EditMapState(self));
        });
        this.leafletMap.on(L.Draw.Event.EDITSTOP, function (e) {
            if (self.getMapState() instanceof EditMapState) {
                self.setMapState(null);
            }
        });

        // If we created something
        this.leafletMap.on(L.Draw.Event.CREATED, function (event) {
            if (event.layerType === 'pridefulenemy') {
                let mapObjectGroup = self.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_ENEMY);
                let pridefulEnemy = mapObjectGroup.getFreePridefulEnemy();
                // Place the prideful enemy at the correct position
                pridefulEnemy.setAssignedLocation(event.layer.getLatLng().lat, event.layer.getLatLng().lng, getState().getCurrentFloor().id);
                // Make it visible
                mapObjectGroup.setMapObjectVisibility(pridefulEnemy, pridefulEnemy.shouldBeVisible());
                // Save the enemy - this way we're persisting across sessions
                pridefulEnemy.save();
            } else {
                // Find the corresponding map object group
                let mapObjectGroup = self.mapObjectGroupManager.getByName(event.layerType);
                if (mapObjectGroup !== false) {
                    let mapObject;
                    // Catch creating a KillZone - we want to add a layer to an existing KillZone, not create a new KillZone object
                    if (mapObjectGroup instanceof KillZoneMapObjectGroup) {
                        let mapState = self.getMapState();
                        console.assert(mapState instanceof AddKillZoneMapState, 'MapState was not in AddKillZoneMapState!', mapState);

                        // Get the killzone that we should add this layer to
                        mapObject = mapState.getMapObject();
                        console.assert(mapObject instanceof KillZone, 'object is not a KillZone!', mapObject);
                        // Apply the layer to the killzone
                        mapObjectGroup.setLayerToMapObject(event.layer, mapObject);

                        // No longer in AddKillZoneMapState; we finished
                        self.setMapState(null);
                    } else {
                        mapObject = mapObjectGroup.onNewLayerCreated(event.layer);
                    }
                    // Save it to server instantly, manually saving is meh
                    mapObject.save();
                } else {
                    console.warn('Unable to find MapObjectGroup after creating a ' + event.layerType);
                }
            }
        });

        // Not very pretty but needed for debugging
        let verboseEvents = false;
        if (verboseEvents) {
            this.leafletMap.on('layeradd', function (e) {
                console.log('layeradd', e);
            });

            this.leafletMap.on(L.Draw.Event.CREATED, function (e) {
                console.log(L.Draw.Event.CREATED, e);
            });
            this.leafletMap.on(L.Draw.Event.EDITED, function (e) {
                console.log(L.Draw.Event.EDITED, e);
            });
            this.leafletMap.on(L.Draw.Event.DELETED, function (e) {
                console.log(L.Draw.Event.DELETED, e);
            });
            this.leafletMap.on(L.Draw.Event.DRAWSTART, function (e) {
                console.log(L.Draw.Event.DRAWSTART, e);
            });
            this.leafletMap.on(L.Draw.Event.DRAWSTOP, function (e) {
                console.log(L.Draw.Event.DRAWSTOP, e);
            });
            this.leafletMap.on(L.Draw.Event.DRAWVERTEX, function (e) {
                console.log(L.Draw.Event.DRAWVERTEX, e);
            });
            this.leafletMap.on(L.Draw.Event.EDITSTART, function (e) {
                console.log(L.Draw.Event.EDITSTART, e);
            });
            this.leafletMap.on(L.Draw.Event.EDITMOVE, function (e) {
                console.log(L.Draw.Event.EDITMOVE, e);
            });
            this.leafletMap.on(L.Draw.Event.EDITRESIZE, function (e) {
                console.log(L.Draw.Event.EDITRESIZE, e);
            });
            this.leafletMap.on(L.Draw.Event.EDITVERTEX, function (e) {
                console.log(L.Draw.Event.EDITVERTEX, e);
            });
            this.leafletMap.on(L.Draw.Event.EDITSTOP, function (e) {
                console.log(L.Draw.Event.EDITSTOP, e);
            });
            this.leafletMap.on(L.Draw.Event.DELETESTART, function (e) {
                console.log(L.Draw.Event.DELETESTART, e);
            });
            this.leafletMap.on(L.Draw.Event.DELETESTOP, function (e) {
                console.log(L.Draw.Event.DELETESTOP, e);
            });
            this.leafletMap.on(L.Draw.Event.TOOLBAROPENED, function (e) {
                console.log(L.Draw.Event.TOOLBAROPENED, e);
            });
            this.leafletMap.on(L.Draw.Event.TOOLBARCLOSED, function (e) {
                console.log(L.Draw.Event.TOOLBARCLOSED, e);
            });
            this.leafletMap.on(L.Draw.Event.MARKERCONTEXT, function (e) {
                console.log(L.Draw.Event.MARKERCONTEXT, e);
            });
        }

        this.leafletMap.on('zoomend', function () {
            if (typeof self.leafletMap !== 'undefined') {
                // Propagate to any other listeners
                getState().setMapZoomLevel(self.leafletMap.getZoom());
            }
        });
    }

    /**
     * Set the map to be interactive or not. https://gis.stackexchange.com/a/54925
     * @param enabled
     * @private
     */
    _setMapInteraction(enabled) {
        console.assert(this instanceof DungeonMap, 'this is not a DungeonMap', this);

        if (enabled) {
            this.leafletMap.dragging.enable();
            this.leafletMap.touchZoom.enable();
            this.leafletMap.doubleClickZoom.enable();
            this.leafletMap.scrollWheelZoom.enable();
            this.leafletMap.boxZoom.enable();
            this.leafletMap.keyboard.enable();
            if (this.leafletMap.tap) this.leafletMap.tap.enable();
            document.getElementById('map').style.cursor = 'grab';
        } else {
            this.leafletMap.dragging.disable();
            this.leafletMap.touchZoom.disable();
            this.leafletMap.doubleClickZoom.disable();
            this.leafletMap.scrollWheelZoom.disable();
            this.leafletMap.boxZoom.disable();
            this.leafletMap.keyboard.disable();
            if (this.leafletMap.tap) this.leafletMap.tap.disable();
            document.getElementById('map').style.cursor = 'default';
        }
    }

    /**
     * Someone clicked on an enemy
     * @private
     */
    _enemyClicked(enemyClickedEvent) {
        console.assert(this instanceof DungeonMap, 'this is not a DungeonMap', this);

        /** @type KillZoneMapObjectGroup */
        let killZoneMapObjectGroup = this.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_KILLZONE);

        let enemy = enemyClickedEvent.context;
        let currentMapState = this.getMapState();

        // If we selected an enemy
        if (getState().getMapContext() instanceof MapContextLiveSession) {

        } else if (this.options.edit && EditKillZoneEnemySelection.isEnemySelectable(enemy)) {
            let shiftKeyPressed = enemyClickedEvent.data.clickEvent.originalEvent.shiftKey;
            let ctrlKeyPressed = enemyClickedEvent.data.clickEvent.originalEvent.ctrlKey;

            // If part of a pack, select the pack instead of creating a new one
            let selectedEnemyExistingKillZone = enemy.getKillZone();

            // When ctrl is pressed, we need to add it to a new pull. When you have another pull, enemy:selected is fired
            // instead of enemy:clicked and the event is handled that way instead
            if (this.mapState === null && ctrlKeyPressed && this.options.edit) {
                // Add it to a new pull
                let newKillZone = killZoneMapObjectGroup.createNewPull([enemy.id]);

                this.setMapState(new EditKillZoneEnemySelection(this, newKillZone, this.getMapState()));
            } else if (selectedEnemyExistingKillZone instanceof KillZone && this.mapState === null && !shiftKeyPressed) {
                // Only when we're not doing anything right now
                if (this.options.edit) {
                    this.setMapState(new EditKillZoneEnemySelection(this, selectedEnemyExistingKillZone));
                } else {
                    this.setMapState(new ViewKillZoneEnemySelection(this, selectedEnemyExistingKillZone));
                }
            }
            // Shift click creates a new pack always
            else if (this.mapState === null || (this.mapState instanceof EditKillZoneEnemySelection && shiftKeyPressed)) {
                // Create a new pack instead

                // Add ourselves to this new pull
                let enemyIds = [];
                // Add all buddies in this pack to the list of ids (if any)
                let packBuddies = enemy.getPackBuddies();
                packBuddies.push(enemy);

                for (let index in packBuddies) {
                    if (packBuddies.hasOwnProperty(index)) {
                        enemyIds.push(packBuddies[index].id);
                    }
                }

                // Create a new pull; all UI will update based on the events fired here.
                let selectedKillZoneIndex = currentMapState instanceof EditKillZoneEnemySelection ? currentMapState.getMapObject().index : null;
                let newKillZone = killZoneMapObjectGroup.createNewPull(enemyIds, selectedKillZoneIndex);

                this.setMapState(new EditKillZoneEnemySelection(this, newKillZone, this.getMapState()));
            }
        }
    }

    /**
     * Get the current HotKeys object used for binding actions to hotkeys.
     * @returns {Hotkeys}
     * @private
     */
    _getHotkeys() {
        console.assert(this instanceof DungeonMap, 'this is not a DungeonMap', this);

        return new Hotkeys(this);
    }

    /**
     * https://stackoverflow.com/questions/33614912/how-to-locate-leaflet-zoom-control-in-a-desired-position/33621034
     * @private
     */
    _createAdditionalControlPlaceholders() {
        console.assert(this instanceof DungeonMap, 'this is not a DungeonMap', this);

        let corners = this.leafletMap._controlCorners,
            l = 'leaflet-',
            container = this.leafletMap._controlContainer;

        function createCorner(vSide, hSide) {
            let className = l + vSide + ' ' + l + hSide;

            corners[vSide + hSide] = L.DomUtil.create('div', className, container);
        }

        createCorner('verticalcenter', 'left');
        createCorner('verticalcenter', 'right');

        createCorner('top', 'horizontalcenter');
        createCorner('bottom', 'horizontalcenter');
    }

    /**
     *
     * @returns {[]}
     * @protected
     */
    _getMapObjectGroupNames() {
        console.assert(this instanceof DungeonMap, 'this is not a DungeonMap', this);

        // Remove the hidden groups from the list of available groups
        return _.difference(MAP_OBJECT_GROUP_NAMES, this.options.hiddenMapObjectGroups);
    }

    /**
     * Create instances of all controls that will be added to the map (UI on the map itself)
     * @param editableLayers
     * @returns {*[]}
     * @private
     */
    _getMapControls(editableLayers) {
        console.assert(this instanceof DungeonMap, 'this is not a DungeonMap', this);

        let mapControls = [];
        // No UI = no map controls at all
        if (!this.options.noUI) {
            if (this.options.edit && !this.options.readonly && this.options.showControls.draw) {
                mapControls.push(new DrawControls(this, editableLayers));
            }

            // Only when enemy forces are relevant in their display (not in a view)
            if (!getState().isMapAdmin()) {
                if (getState().getMapContext().isDungeonSpeedrunEnabled()) {
                    mapControls.push(new DungeonSpeedrunRequiredNpcsControls(this));
                } else if(this.options.showControls.enemyForces) {
                    mapControls.push(new EnemyForcesControls(this));
                }
            }
            if (!this.options.embed) {
                // mapControls.push(new EnemyVisualControls(this));
            }

            if (this.isSandboxModeEnabled() && getState().getMapContext().getDungeon().key === 'siegeofboralus') {
                mapControls.push(new FactionDisplayControls(this));
            }

            if (getState().isEchoEnabled()) {
                mapControls.push(new EchoControls(this));
            }

            // result.push(new AdDisplayControls(this));
        }

        return mapControls;
    }

    /**
     * Create instances of all controls that will be added to the map (UI on the map itself)
     * @param editableLayers
     * @returns {*[]}
     * @private
     */
    _addMapControls(editableLayers) {
        console.assert(this instanceof DungeonMap, 'this is not a DungeonMap', this);

        // Remove existing map controls
        for (let i = 0; i < this.mapControls.length; i++) {
            this.mapControls[i].cleanup();
        }

        this.mapControls = this._getMapControls(editableLayers);

        for (let i = 0; i < this.mapControls.length; i++) {
            this.mapControls[i].addControl();
        }
    }

    /**
     *
     * @returns {boolean}
     */
    hasPopupOpen() {
        console.assert(this instanceof DungeonMap, 'this is not a DungeonMap', this);
        let result = false;
        for (let i = 0; i < this.mapObjects.length; i++) {
            let mapObject = this.mapObjects[i];
            if (mapObject.layer !== null) {
                let popup = mapObject.layer.getPopup();
                if (typeof popup !== 'undefined' && popup !== null && popup.isOpen()) {
                    result = true;
                    break;
                }
            }
        }
        return result;
    }

    /**
     * Finds a floor by id.
     * @param floorId
     * @returns {*}|bool
     */
    getFloorById(floorId) {
        console.assert(this instanceof DungeonMap, 'this is not a DungeonMap', this);
        let result = false;

        let dungeonData = getState().getMapContext().getDungeon();
        for (let i = 0; i < dungeonData.floors.length; i++) {
            let floor = dungeonData.floors[i];
            if (floor.id === floorId) {
                result = floor;
                break;
            }
        }

        return result;
    }

    /**
     * Finds a map object by means of a Leaflet layer.
     * @param layer object The layer you want the map object for.
     * @return {MapObject|bool}
     */
    findMapObjectByLayer(layer) {
        console.assert(this instanceof DungeonMap, 'this is not a DungeonMap', this);

        let result = false;
        for (let i = 0; i < this.mapObjects.length; i++) {
            let mapObject = this.mapObjects[i];
            if (mapObject.layer === layer) {
                result = mapObject;
                break;
            }
        }
        return result;
    }

    /**
     * Refreshes the leaflet map
     * @param clearMapState {Boolean}
     * @param center {Array}
     * @param zoom {Number}
     */
    refreshLeafletMap(clearMapState = true, center = null, zoom = null) {
        console.assert(this instanceof DungeonMap, 'this is not a DungeonMap', this);

        let self = this;

        this._refreshingMap = true;

        this.signal('map:beforerefresh', {dungeonmap: this});

        // If we were doing anything, we're no longer doing it
        if (clearMapState) {
            this.setMapState(null);
        }

        if (this.mapTileLayer !== null) {
            this.leafletMap.removeLayer(this.mapTileLayer);
        }
        this.leafletMap.setView(center ?? [-128, 192], zoom ?? this.options.defaultZoom);
        let southWest = this.leafletMap.unproject([0, 8192], this.leafletMap.getMaxZoom());
        let northEast = this.leafletMap.unproject([12288, 0], this.leafletMap.getMaxZoom());


        let dungeonData = getState().getMapContext().getDungeon();
        this.mapTileLayer = L.tileLayer(`/images/tiles/${dungeonData.expansion.shortname}/${dungeonData.key}/${getState().getCurrentFloor().index}/{z}/{x}_{y}.png`, {
            maxZoom: 5,
            attribution: 'Map data © Blizzard Entertainment',
            tileSize: L.point(384, 256),
            noWrap: true,
            continuousWorld: true,
            bounds: new L.LatLngBounds(southWest, northEast)
        }).addTo(this.leafletMap);

        // if( typeof this.drawnLayers !== 'undefined' ) {
        //     this.leafletMap.removeLayer(this.drawnLayers);
        // }
        // if( typeof this.editableLayers !== 'undefined' ) {
        //     this.leafletMap.removeLayer(this.editableLayers);
        // }

        this.editableLayers = new L.FeatureGroup();
        // Refresh the list of drawn items
        this.drawnLayers = new L.FeatureGroup();

        this.leafletMap.addLayer(this.drawnLayers);
        this.leafletMap.addLayer(this.editableLayers);

        // If we confirmed editing something..
        this.signal('map:refresh', {dungeonmap: this});

        // Show/hide the attribution
        if (!this.options.showAttribution) {
            $('.leaflet-control-attribution').hide();
        }

        // Pather for drawing lines
        if (this.pather !== null) {
            this.leafletMap.removeLayer(this.pather);
        }

        this.pather = new L.Pather();
        this.pather.on('created', function (patherEvent) {
            // Add the newly created polyline to our system
            let mapObjectGroup = self.mapObjectGroupManager.getByName('brushline');

            // Create a new brushline
            let points = [];

            // Convert the latlngs into something the polyline constructor understands
            let vertices = patherEvent.latLngs;
            for (let i = 0; i < vertices.length; i++) {
                let vertex = vertices[i];
                points.push([vertex.lat, vertex.lng]);
            }

            let layer = L.polyline(points);

            let object = mapObjectGroup.onNewLayerCreated(layer);
            object.save();

            // Remove it from Pather, we only use Pather for creating the actual layer
            self.pather.removePath(patherEvent.polyline);
        });
        this.leafletMap.addLayer(this.pather);
        this.pather.setMode(L.Pather.MODE.VIEW);
        // Set its options properly
        this.refreshPather();
        // Not enabled at this time
        this.togglePather(false);

        // Add new controls; we're all loaded now and user should now be able to edit their route
        this._addMapControls(this.editableLayers);

        // Used for preview image generation
        if (this.options.zoomToContents) {
            this.leafletMap.fitBounds(this.drawnLayers.getBounds());
        }

        refreshTooltips();

        if ($('#finished_loading').length === 0) {
            $('body').append(
                jQuery('<div>').attr('id', 'finished_loading')
            );
        }


        this._refreshingMap = false;
    }

    isRefreshingMap() {
        return this._refreshingMap;
    }

    /**
     * Gets the current enemy selection instance.
     * @returns MapState|null
     */
    getMapState() {
        console.assert(this instanceof DungeonMap, 'this is not a DungeonMap', this);

        return this.mapState;
    }

    /**
     * Sets the current map state to a new state.
     * @param mapState
     */
    setMapState(mapState) {
        console.assert(this instanceof DungeonMap, 'this is not a DungeonMap', this);
        console.assert(mapState instanceof MapState || mapState === null, 'mapState is not a MapState|null', mapState);

        // Stop if necessary
        if (this.mapState instanceof MapState && this.mapState.isStarted() && !this.mapState.isStopped()) {
            this.mapState.stop();
        }

        // Start new map state
        let previousMapState = this.mapState;
        this.mapState = mapState;
        this.signal('map:mapstatechanged', {previousMapState: previousMapState, newMapState: this.mapState});
        if (this.mapState instanceof MapState) {
            this.mapState.start();
        }
    }

    /**
     * Checks if sandbox mode is currently enabled or not.
     * @returns {boolean|*}
     */
    isSandboxModeEnabled() {
        console.assert(this instanceof DungeonMap, 'this is not a DungeonMap', this);

        return this.options.sandbox && this.options.edit;
    }

    /**
     * Toggle pather to be enabled or not.
     * @param enabled
     */
    togglePather(enabled) {
        console.assert(this instanceof DungeonMap, 'this is not a DungeonMap', this);

        // May be null when initializing
        if (this.pather !== null) {
            //  When enabled, add to the map
            if (enabled) {
                this.pather.setMode(L.Pather.MODE.CREATE);
                if (!(this.getMapState() instanceof PatherMapState)) {
                    this.setMapState(new PatherMapState(this));
                    this.signal('map:pathertoggled', {enabled: enabled});
                }
            } else {
                this.pather.setMode(L.Pather.MODE.VIEW);
                // Only disable it when we're actively in the pather map state
                if (this.getMapState() instanceof PatherMapState) {
                    this.setMapState(null);
                    this.signal('map:pathertoggled', {enabled: enabled});
                }
            }
        }
    }

    /**
     *
     */
    refreshPather() {
        console.assert(this instanceof DungeonMap, 'this is not a DungeonMap', this);
        console.assert(this.pather instanceof L.Pather, 'this.pather is not a L.Pather', this.pather);

        this.pather.setOptions({
            strokeWidth: c.map.polyline.defaultWeight,
            smoothFactor: 5,
            pathColour: c.map.polyline.defaultColor()
        });
    }

    /**
     * Focuses the map on a killzone
     * @param killZone {KillZone}
     */
    focusOnKillZone(killZone) {
        console.assert(this instanceof DungeonMap, 'this is not a DungeonMap', this);
        console.assert(killZone instanceof KillZone, 'killZone is not a KillZone', this);

        // Cache the zoom level
        let currentZoomLevel = getState().getMapZoomLevel();

        // Switch floors if the floor is not on the current map
        let floorIds = killZone.getFloorIds();
        if (floorIds.length > 0 && !floorIds.includes(getState().getCurrentFloor().id)) {
            getState().setFloorId(floorIds[0]);
        }

        // Center the map to this killzone
        if (killZone.enemies.length > 0 && killZone.isVisible()) {
            this.leafletMap.setView(killZone.getLayerCenteroid(), currentZoomLevel);
        }
    }
}


// let amount = 16;// 8192 / space
// for (let x = 0; x <= amount; x++) {
//     for (let y = 0; y <= amount; y++) {
//         L.marker(this.leafletMap.unproject([x * (6144 / amount), y * (4096 / amount)], this.leafletMap.getMaxZoom())).addTo(this.leafletMap);
//     }
// }

// L.marker(southWest).addTo(this.leafletMap);
// L.marker(northEast).addTo(this.leafletMap);

// var geoJsonTest = new L.geoJson(geojsonFeature, {
//     coordsToLatLng: function (newcoords) {
//         return (map.unproject([newcoords[1], newcoords[0]], map.getMaxZoom()));
//     },
//     pointToLayer: function (feature, coords) {
//         return L.circleMarker(coords, geojsonMarkerOptions);
//     }
// });
