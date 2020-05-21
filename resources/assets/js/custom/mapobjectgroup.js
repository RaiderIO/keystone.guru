class MapObjectGroup extends Signalable {

    constructor(manager, name, editable = false) {
        super();
        this.manager = manager;
        this.name = name;
        this.editable = editable;

        this.objects = [];
        this.layerGroup = null;

        let self = this;

        // Callback to when the manager has received data from the server
        this.manager.register('fetchsuccess', this, function (fetchEvent) {
            self._fetchSuccess(fetchEvent.data.response);
        });

        this.manager.map.register('map:beforerefresh', this, function () {
            // Remove any layers that were added before
            self._removeObjectsFromLayer.call(self);

            if (self.layerGroup !== null) {
                // Remove ourselves from the map prior to refreshing
                self.manager.map.leafletMap.removeLayer(self.layerGroup);
            }

            while (self.objects.length > 0) {
                let obj = self.objects[0];
                obj.localDelete();
                obj.cleanup();
            }
            self.objects = [];
        });
        // Whenever the map refreshes, we need to add ourselves to the map again
        this.manager.map.register('map:refresh', this, (function (data) {
            // Rebuild the layer group
            self.layerGroup = new L.LayerGroup();

            // Set it to be visible if it was
            // @todo self.isShown(), currently the layer will ALWAYS show regardless of MapControl status
            self.setVisibility(true);
        }).bind(this));
    }

    /**
     * Refreshes the objects that are displayed on the map based on the current dungeon & selected floor.
     */
    _fetchSuccess(response) {
        console.assert(this instanceof MapObjectGroup, 'this is not a MapObjectGroup', this);
    }

    /**
     * Removes all objects' layer from the map layer.
     * @protected
     */
    _removeObjectsFromLayer() {
        console.assert(this instanceof MapObjectGroup, 'this is not a MapObjectGroup', this);

        // Remove any layers that were added before
        for (let i = 0; i < this.objects.length; i++) {
            // Remove all layers
            if (this.objects[i].layer !== null) {
                this.manager.map.leafletMap.removeLayer(this.objects[i].layer);
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
     * @private
     */
    _restoreObject(remoteMapObject, username = null) {
        console.error('override the _restoreObject function!');
    }

    /**
     * Checks the object's faction and teeming status, compares it to our map's status of those variables and determines
     * if it should be visible or not on the map.
     * @param remoteMapObject The object you're looking to check for visibility
     * @returns {boolean}
     * @protected
     */
    _isObjectVisible(remoteMapObject) {
        let result = true;

        let faction = getState().getDungeonRoute().faction;

        // Only when not in try mode!
        if (!this.manager.map.isTryModeEnabled() && (remoteMapObject.faction !== 'any' && faction !== 'any' && faction !== remoteMapObject.faction)) {
            // console.warn('Skipping map object that does not belong to the requested faction ', remoteMapObject, faction);
            result = false;
        }

        // If the map isn't teeming, but the enemy is teeming..
        if (!this.manager.map.options.teeming && remoteMapObject.teeming === 'visible') {
            // console.warn('Skipping teeming map object', remoteMapObject);
            result = false;
        }
        // If the map is teeming, but the enemy shouldn't be there for teeming maps..
        else if (this.manager.map.options.teeming && remoteMapObject.teeming === 'invisible') {
            // console.warn('Skipping teeming-filtered map object', remoteMapObject.id);
            result = false;
        }

        return result;
    }

    /**
     *
     * @param localMapObject
     * @param username
     * @protected
     */
    _showReceivedFromEcho(localMapObject, username) {
        if (this.manager.map.options.echo && this.manager.map.options.username !== username && username !== null) {
            let userColor = this.manager.map.getEchoControls().getUserColor(username);
            let fontClass = '';

            // Must be a hex color
            if (userColor.indexOf('#') === 0) {
                // Check if the user's color is 'dark' or 'light'. When it's dark we want a white font, black otherwise.
                fontClass = isColorDark(userColor) ? 'text-white' : 'text-dark';
            }

            let tooltip = localMapObject.layer.bindTooltip(username, {
                permanent: true,
                className: 'user_color_' + username + ' ' + fontClass,
                direction: 'top'
            });

            // Fadeout after some time
            setTimeout(function () {
                tooltip.closeTooltip();
            }, 5000);
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
                    .replace('{object}', _.upperFirst(localMapObject.constructor.name.toLowerCase()))
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
        if (visible) {
            if (!this.layerGroup.hasLayer(object.layer)) {
                this.layerGroup.addLayer(object.layer);
                // Trigger this on the object
                object.signal('shown', {object: object, visible: true});
                this.signal('object:shown', {object: object, objectgroup: this, visible: true});
            }
        } else {
            if (this.layerGroup.hasLayer(object.layer)) {
                this.layerGroup.removeLayer(object.layer);
                // Trigger this on the object
                object.signal('hidden', {object: object, visible: false});
                this.signal('object:hidden', {object: object, objectgroup: this, visible: false});
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
            mapObject.onLayerInit();
        }
        // User wants to unset the mapObject's layer, remove its references
        else if (mapObject.layer !== null) {
            this.layerGroup.removeLayer(mapObject.layer);
            // Set to null
            mapObject.layer = layer;
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
        if (!this.isShown() && visible) {
            this.manager.map.leafletMap.addLayer(this.layerGroup);
        } else if (this.isShown() && !visible) {
            this.manager.map.leafletMap.removeLayer(this.layerGroup);
        }
    }
}