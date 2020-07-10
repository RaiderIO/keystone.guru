/**
 * @property objects {MapObject[]}
 */
class MapObjectGroup extends Signalable {

    constructor(manager, names, field, editable = false) {
        super();
        // Ensure its an array
        if (typeof names === 'string') {
            names = [names];
        }
        console.assert(manager instanceof MapObjectGroupManager, 'this is not a MapObjectGroupManager', this);
        console.assert(typeof names === 'object', 'name is not an object', this);
        console.assert(typeof field === 'string', 'this is not a String', this);
        console.assert(typeof editable === 'boolean', 'editable is not a boolean', this);

        this.manager = manager;
        this.names = names;
        this.field = field;
        this.editable = editable;

        // False initially when not loaded anything in yet (from server). True after the initial loading.
        this.initialized = false;

        this.objects = [];
        this.layerGroup = null;

        let self = this;

        // Callback to when the manager has received data from the server
        this.manager.register('fetchsuccess', this, this._onFetchSuccess.bind(this));
        this.manager.map.register('map:beforerefresh', this, this._onBeforeRefresh.bind(this));
        // Whenever the map refreshes, we need to add ourselves to the map again
        this.manager.map.register('map:refresh', this, (function (data) {
            // Rebuild the layer group
            self.layerGroup = new L.LayerGroup();

            // Set it to be visible if it was
            // @todo self.isShown(), currently the layer will ALWAYS show regardless of MapControl status
            self.setVisibility(true);
        }).bind(this));
        getState().register('teeming:changed', this, this._updateVisibility.bind(this));

        if (!(this.manager.map instanceof AdminDungeonMap)) {
            getState().register('seasonalindex:changed', this, this._seasonalIndexChanged.bind(this));
        }
    }

    /**
     * Triggered when the seasonal index was changed.
     * @param seasonalIndexChangedEvent
     * @private
     */
    _seasonalIndexChanged(seasonalIndexChangedEvent) {
        console.assert(this instanceof MapObjectGroup, 'this is not a MapObjectGroup', this);

        for (let i = 0; i < this.objects.length; i++) {
            let mapObject = this.objects[i];
            if (mapObject.hasOwnProperty('seasonal_index') && mapObject.seasonal_index !== null) {
                this.setMapObjectVisibility(mapObject, mapObject.seasonal_index === seasonalIndexChangedEvent.data.seasonalIndex);
            }
        }
    }

    /**
     * Checks if map objects should be visible and update the visibility as necessary according to it.
     * @private
     */
    _updateVisibility() {
        console.assert(this instanceof MapObjectGroup, 'this is not a MapObject', this);

        for (let i = 0; i < this.objects.length; i++) {
            let mapObject = this.objects[i];
            // Set this map object to be visible or not
            this.setMapObjectVisibility(mapObject, mapObject.shouldBeVisible());
        }
    }

    /**
     * May be overridden by implementing classes
     * @param fetchEvent
     * @private
     */
    _onFetchSuccess(fetchEvent) {
        console.assert(this.objects.length === 0, 'objects must be empty after refresh', this.names, this.objects.length);

        this._fetchSuccess(fetchEvent.data.response);
    }

    /**
     *
     * @protected
     */
    _onBeforeRefresh() {
        console.assert(this instanceof MapObjectGroup, 'this is not a MapObjectGroup', this);

        // Remove any layers that were added before
        this._removeObjectsFromLayer.call(this);

        this.setVisibility(false);

        while (this.objects.length > 0) {
            let obj = this.objects[0];
            obj.localDelete();
            obj.cleanup();
        }
        this.objects = [];
    }

    /**
     * Refreshes the objects that are displayed on the map based on the current dungeon & selected floor.
     */
    _fetchSuccess(response) {
        console.assert(this instanceof MapObjectGroup, 'this is not a MapObjectGroup', this);

        let mapObjects = response[this.field];

        console.assert(typeof mapObjects === 'object', 'mapObjects is not an array', mapObjects);

        // Now draw the map objects on the map
        for (let i = 0; i < mapObjects.length; i++) {
            this._restoreObject(mapObjects[i]);
        }

        this.initialized = true;

        this.signal('restorecomplete');
    }

    /**
     * Removes all objects' layer from the map layer.
     * @protected
     */
    _removeObjectsFromLayer() {
        console.assert(this instanceof MapObjectGroup, 'this is not a MapObjectGroup', this);

        // Remove any layers that were added before
        for (let i = 0; i < this.objects.length; i++) {
            let obj = this.objects[i];
            // Remove all layers
            if (obj.layer !== null) {
                this.manager.map.leafletMap.removeLayer(obj.layer);
                // Clean it up properly
                obj.setVisible(false);
            }
        }
    }

    /**
     * @param layer
     * @param options Object
     * @protected
     * @return MapObject
     */
    _createObject(layer, options = {}) {
        console.error('override the _createObject function!');
    }

    /**
     * Restores an object that was received from the server
     * @param remoteMapObject object
     * @param username string
     * @return {MapObject}
     * @protected
     */
    _restoreObject(remoteMapObject, username = null) {
        console.error('override the _restoreObject function!');
    }

    /**
     *
     * @param localMapObject
     * @param username
     * @protected
     */
    _showReceivedFromEcho(localMapObject, username) {
        if (this.manager.map.options.echo && this.manager.map.options.username !== username && username !== null) {
            let userColor = this.manager.map.echo.getUserColor(username);
            let fontClass = '';

            // Must be a hex color
            if (userColor.indexOf('#') === 0) {
                // Check if the user's color is 'dark' or 'light'. When it's dark we want a white font, black otherwise.
                fontClass = isColorDark(userColor) ? 'text-white' : 'text-dark';
            }

            // @TODO Bit hacky?
            let layer = localMapObject.layer;
            if (localMapObject instanceof KillZone) {
                // First layer should contain the polygon that is displayed
                layer = localMapObject.enemyConnectionsLayerGroup.getLayers().length > 0 ? localMapObject.enemyConnectionsLayerGroup.getLayers()[0] : null;
            }

            if (layer !== null) {
                let oldTooltip = layer.getTooltip();

                let tooltip = layer.bindTooltip(username, {
                    permanent: true,
                    className: 'user_color_' + username + ' ' + fontClass,
                    direction: 'top'
                });

                // Fadeout after some time
                setTimeout(function () {
                    tooltip.closeTooltip();

                    // Do not re-bind a tooltip that shouldn't be there permanently
                    if (typeof oldTooltip !== 'undefined' &&
                        !oldTooltip.options.className.includes('user_color_')) {
                        // Rebind killzone pull index tooltip
                        layer.bindTooltip(oldTooltip._content, oldTooltip.options);
                    }
                }, c.map.echo.tooltipFadeOutTimeout);
            } else {
                console.warn('Unable to display echo received action to user, layer was null', localMapObject);
            }
        }
    }

    /**
     *
     * @param localMapObject
     * @param username
     * @protected
     */
    _showDeletedFromEcho(localMapObject, username) {
        if (this.manager.map.options.echo && this.manager.map.options.username !== username && username !== null) {
            showInfoNotification(
                lang.get('messages.echo_object_deleted_notification')
                    .replace('{object}', localMapObject.toString())
                    .replace('{user}', username)
            );
        }
    }

    /**
     * Called whenever an object has deleted itself.
     * @param data
     * @private
     */
    _onObjectDeleted(data) {
        console.assert(this instanceof MapObjectGroup, 'this is not a MapObjectGroup', this);

        if (data.context.layer !== null) {
            this.layerGroup.removeLayer(data.context.layer);
            // @TODO Should this be put in the dungeonmap instead?
            this.manager.map.leafletMap.removeLayer(data.context.layer);
            // Clean it up properly
            data.context.setVisible(false);
        }

        let object = data.context;

        // Remove it from our records
        let newObjects = [];
        for (let i = 0; i < this.objects.length; i++) {
            let objectCandidate = this.objects[i];
            if (objectCandidate.id !== object.id) {
                newObjects.push(objectCandidate);
            }
        }
        this.objects = newObjects;

        // Fire the event
        this.signal('object:deleted', {object: object, objectgroup: this});
    }

    /**
     * Called whenever an object we created has finished wrapping up and is now synced
     * @param data
     * @private
     */
    _onObjectSynced(data) {
        let object = data.context;

        // We only use this trigger once to fire the object:add event, so unregister..
        object.unregister('synced', this);
        // Fire the event
        this.signal('object:add', {object: object, objectgroup: this});

        // Hide the objects if they're not visible by default
        if (!object.isDefaultVisible()) {
            this.setMapObjectVisibility(object, false);
        }
    }

    /**
     * Set the visibility of an individual object.
     * @param object
     * @param visible
     */
    setMapObjectVisibility(object, visible) {
        console.assert(this instanceof MapObjectGroup, 'this is not a MapObjectGroup', this);

        // @TODO Move this to mapobject instead? But then mapobject will have a dependency on their map object group which
        // I may or may not want
        if (object.layer !== null) {
            if (visible) {
                if (!this.layerGroup.hasLayer(object.layer)) {
                    this.layerGroup.addLayer(object.layer);
                    // Trigger this on the object
                    object.setVisible(true);
                    this.signal('object:shown', {object: object, objectgroup: this, visible: true});
                }
            } else {
                if (this.layerGroup.hasLayer(object.layer)) {
                    this.layerGroup.removeLayer(object.layer);
                    // Trigger this on the object
                    object.setVisible(false);
                    this.signal('object:hidden', {object: object, objectgroup: this, visible: false});
                }
            }
        }
    }

    /**
     * Checks if a map object is visible on the map or not.
     * @returns {*|boolean}
     */
    isMapObjectVisible(object) {
        return this.layerGroup.hasLayer(object.layer);
    }

    /**
     * Finds an object in this map object group by its ID.
     * @param id int
     * @returns {*}
     */
    findMapObjectById(id) {
        let result = null;

        for (let i = 0; i < this.objects.length; i++) {
            let objectCandidate = this.objects[i];
            if (objectCandidate.id === id) {
                result = objectCandidate;
                break;
            }
        }

        return result;
    }

    /**
     * Sets a layer to an existing map object.
     * @param layer
     * @param mapObject
     */
    setLayerToMapObject(layer, mapObject) {
        console.assert(this instanceof MapObjectGroup, 'this is not a MapObjectGroup', this);
        console.assert(this.findMapObjectById(mapObject.id) !== null, 'mapObject is not part of this MapObjectGroup', mapObject);

        if (layer !== null) {
            mapObject.layer = layer;
            this.layerGroup.addLayer(mapObject.layer);
            mapObject.setVisible(true);
            mapObject.onLayerInit();
        }
        // User wants to unset the mapObject's layer, remove its references
        else if (mapObject.layer !== null) {
            this.layerGroup.removeLayer(mapObject.layer);
            // Set to null
            mapObject.layer = layer;
            mapObject.setVisible(false);
        }
    }

    /**
     *
     * @param layer L.Layer
     * @param options Object
     * @return MapObject
     */
    createNew(layer, options) {
        console.assert(this instanceof MapObjectGroup, 'this is not a MapObjectGroup', this);

        let mapObject = this._createObject(layer, options);
        this.objects.push(mapObject);
        this.setLayerToMapObject(layer, mapObject);

        mapObject.register('object:deleted', this, (this._onObjectDeleted).bind(this));
        mapObject.register('synced', this, (this._onObjectSynced).bind(this));

        return mapObject;
    }

    /**
     * True if the object group is shown, false if it is hidden.
     * @returns {*|boolean}
     */
    isShown() {
        console.assert(this instanceof MapObjectGroup, 'this was not a MapObjectGroup', this);
        return this.manager.map.leafletMap.hasLayer(this.layerGroup);
    }

    /**
     * Sets the visibility of this entire map object group
     * @param visible
     */
    setVisibility(visible) {
        console.assert(this instanceof MapObjectGroup, 'this was not a MapObjectGroup', this);
        if (this.layerGroup !== null) {
            let added = (!this.isShown() && visible);
            let removed = (this.isShown() && !visible);
            if (added) {
                this.manager.map.leafletMap.addLayer(this.layerGroup);
            } else if (removed) {
                this.manager.map.leafletMap.removeLayer(this.layerGroup);
            }

            if (added || removed) {
                // Remove any layers that were added before
                for (let i = 0; i < this.objects.length; i++) {
                    let obj = this.objects[i];
                    // Remove all layers
                    if (obj.layer !== null) {
                        // Clean it up properly (if added, it's visible, if removed, not visible)
                        obj.setVisible(added);
                    }
                }
            }
        }
    }
}