/**
 * @property objects {MapObject[]}
 */
class MapObjectGroup extends Signalable {

    constructor(manager, names, editable = false) {
        super();
        // Ensure its an array
        if (typeof names === 'string') {
            names = [names];
        }
        console.assert(manager instanceof MapObjectGroupManager, 'this is not a MapObjectGroupManager', this);
        console.assert(typeof names === 'object', 'name is not an object', this);
        console.assert(typeof editable === 'boolean', 'editable is not a boolean', this);

        this.manager = manager;
        this.names = names;
        this.editable = editable;

        // False initially when not loaded anything in yet (from server). True after the initial loading.
        this._initialized = false;

        this.objects = [];
        this.layerGroup = null;

        let self = this;

        // Callback to when the manager has received data from the server
        this.manager.map.register('map:beforerefresh', this, this._onBeforeRefresh.bind(this));
        // Whenever the map refreshes, we need to add ourselves to the map again
        this.manager.map.register('map:refresh', this, (function (mapRefreshEvent) {
            // Rebuild the layer group
            self.layerGroup = new L.LayerGroup();

            // Set it to be visible if it was
            // @todo self.isShown(), currently the layer will ALWAYS show regardless of MapControl status
            self.setVisibility(true);
        }).bind(this));
        getState().getMapContext().register('teeming:changed', this, this._updateVisibility.bind(this));

        if (!(this.manager.map instanceof AdminDungeonMap)) {
            getState().getMapContext().register('seasonalindex:changed', this, this._seasonalIndexChanged.bind(this));
        }

        if (getState().isEchoEnabled()) {
            let presenceChannel = window.Echo.join(getState().getEchoChannelName());

            for (let index in this.names) {
                if (this.names.hasOwnProperty(index)) {
                    presenceChannel.listen(`.${this.names[index]}-changed`, (e) => {
                        if (self._shouldHandleChangedEchoEvent(e)) {
                            self._loadMapObject(e.model, e.user);
                        }
                    }).listen(`.${this.names[index]}-deleted`, (e) => {
                        if (self._shouldHandleDeletedEchoEvent(e)) {
                            let mapObject = self.findMapObjectById(e.model_id);
                            if (mapObject !== null) {
                                mapObject.localDelete();
                                self._showDeletedFromEcho(mapObject, e.user);
                            }
                        }
                    });
                }
            }
        }
    }

    /**
     * @returns {[]}
     * @protected
     */
    _getRawObjects() {
        console.error('Implement _getRawObjects()!');
    }

    /**
     * Basic checks for if a received echo event is applicable to this map object group.
     * @param e {Object}
     * @returns {boolean}
     * @private
     */
    _shouldHandleEchoEvent(e) {
        console.assert(this instanceof MapObjectGroup, 'this is not a MapObjectGroup', this);

        return e.user !== getState().getUserName();
    }

    /**
     * Checks if a received _changed_ event is applicable to this map object group.
     * @param e {Object}
     * @returns {boolean}
     * @private
     */
    _shouldHandleChangedEchoEvent(e) {
        console.assert(this instanceof MapObjectGroup, 'this is not a MapObjectGroup', this);
        console.assert(typeof e.model !== 'undefined', 'model was not defined in received event!', this, e);
        console.assert(typeof e.model.floor_id !== 'undefined', 'model.floor_id was not defined in received event!', this, e);

        return this._shouldHandleEchoEvent(e) && e.model.floor_id === getState().getCurrentFloor().id;
    }

    /**
     * Checks if a received _deleted_ event is applicable to this map object group.
     * @param e {Object}
     * @returns {boolean|boolean}
     * @private
     */
    _shouldHandleDeletedEchoEvent(e) {
        console.assert(this instanceof MapObjectGroup, 'this is not a MapObjectGroup', this);
        console.assert(typeof e.model_id !== 'undefined', 'model_id was not defined in received event!', this, e);

        return this._shouldHandleEchoEvent(e);
    }

    /**
     * Triggered when the seasonal index was changed.
     * @param seasonalIndexChangedEvent {object}
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
     *
     * @protected
     */
    _onBeforeRefresh() {
        console.assert(this instanceof MapObjectGroup, 'this is not a MapObjectGroup', this);

        // Remove any layers that were added before
        this._hideAllMapObjects();

        this.setVisibility(false);
    }

    /**
     * Removes all objects' layer from the map layer.
     * @protected
     */
    _hideAllMapObjects() {
        console.assert(this instanceof MapObjectGroup, 'this is not a MapObjectGroup', this);

        // Remove any layers that were added before
        for (let i = 0; i < this.objects.length; i++) {
            let mapObject = this.objects[i];
            // Remove all layers
            if (mapObject.layer !== null) {
                // Clean it up properly
                this.setMapObjectVisibility(mapObject, false);
            }
        }
    }

    /**
     *
     * @param remoteMapObject {Object}
     * @protected
     * @returns {{}}
     */
    _getOptions(remoteMapObject) {
        return {};
    }

    /**
     *
     * @param remoteMapObject {Object}
     * @protected
     * @return {L.layer}|null
     */
    _createLayer(remoteMapObject) {
        console.error('override the _createLayer function!');
    }

    /**
     * @param layer {L.layer}
     * @param options {Object}
     * @protected
     * @return {MapObject}
     */
    _createMapObject(layer, options = {}) {
        console.error('override the _createMapObject function!');
    }

    /**
     * @param remoteMapObject {Object}
     * @param mapObject {MapObject}
     * @param options {Object}
     * @protected
     * @return {MapObject}
     */
    _updateMapObject(remoteMapObject, mapObject, options = {}) {
        console.assert(this instanceof MapObjectGroup, 'this is not an MapObjectGroup', this);
        console.assert(mapObject instanceof MapObject, 'mapObject is not of type MapObject', mapObject);
        console.assert(typeof options === 'object', 'options is not of type Object', options);

        if (mapObject.layer !== null) {
            mapObject.layer.setLatLng(L.latLng(remoteMapObject.lat, remoteMapObject.lng));
        }

        return mapObject;
    }

    /**
     * Restores an object that was received from the server.
     *
     * @param remoteMapObject {object}
     * @param username {string|null} The user that created this object (if done from Echo)
     * @return {MapObject}
     * @protected
     */
    _loadMapObject(remoteMapObject, username = null) {
        console.assert(this instanceof MapObjectGroup, 'this is not a MapObjectGroup', this);

        let mapObject = this.findMapObjectById(remoteMapObject.id);
        let options = this._getOptions(remoteMapObject);

        if (mapObject === null) {
            mapObject = this.createNewMapObject(this._createLayer(remoteMapObject), options);
        } else {
            mapObject = this._updateMapObject(remoteMapObject, mapObject, options);
        }

        mapObject.loadRemoteMapObject(remoteMapObject);

        // Bit of a hack to properly load lines, may need to rework
        if (typeof remoteMapObject.polyline !== 'undefined') {
            mapObject.loadRemoteMapObject(remoteMapObject.polyline);
        }

        mapObject.setSynced(true);

        // Show echo notification or not
        this._showReceivedFromEcho(mapObject, username);

        return mapObject;
    }

    /**
     *
     * @param localMapObject {MapObject}
     * @param username {string}
     * @protected
     */
    _showReceivedFromEcho(localMapObject, username) {
        console.assert(this instanceof MapObjectGroup, 'this is not a MapObjectGroup', this);

        if (getState().isEchoEnabled() && getState().getUserName() !== username && username !== null) {
            let userColor = getState().getEcho().getUserColor(username);
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
                    className: `user_color_${convertToSlug(username)} ${fontClass}`,
                    direction: 'top'
                });

                // Fadeout after some time
                setTimeout(function () {
                    tooltip.closeTooltip();

                    // Do not re-bind a tooltip that shouldn't be there permanently
                    if (typeof oldTooltip !== 'undefined' &&
                        oldTooltip.options !== null &&
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
     * @param localMapObject {MapObject}
     * @param username {string}
     * @protected
     */
    _showDeletedFromEcho(localMapObject, username) {
        console.assert(this instanceof MapObjectGroup, 'this is not a MapObjectGroup', this);

        if (getState().isEchoEnabled() && getState().getUserName() !== username && username !== null) {
            showInfoNotification(
                lang.get('messages.echo_object_deleted_notification')
                    .replace('{object}', localMapObject.toString())
                    .replace('{user}', username)
            );
        }
    }

    /**
     * Called whenever an object has deleted itself.
     * @param objectDeletedEvent {object}
     * @private
     */
    _onObjectDeleted(objectDeletedEvent) {
        console.assert(this instanceof MapObjectGroup, 'this is not a MapObjectGroup', this);

        if (objectDeletedEvent.context.layer !== null) {
            this.layerGroup.removeLayer(objectDeletedEvent.context.layer);
            // @TODO Should this be put in the dungeonmap instead?
            this.manager.map.leafletMap.removeLayer(objectDeletedEvent.context.layer);
            // Clean it up properly
            objectDeletedEvent.context.setVisible(false);
        }

        let object = objectDeletedEvent.context;

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
     * @param objectSyncedEvent {object}
     * @private
     */
    _onObjectSynced(objectSyncedEvent) {
        console.assert(this instanceof MapObjectGroup, 'this is not a MapObjectGroup', this);

        let object = objectSyncedEvent.context;

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
     * Loads MapObjects into this MapObjectGroup
     */
    load() {
        console.assert(this instanceof MapObjectGroup, 'this is not a MapObjectGroup', this);
        console.assert(!this._initialized, 'MapObjectGroup already loaded; cannot load again!', this);

        if (!this._initialized) {
            // Get the objects that we need to load
            let mapObjects = this._getRawObjects();

            console.assert(typeof mapObjects === 'object', 'mapObjects is not an array', mapObjects);

            // Now draw the map objects on the map
            for (let i = 0; i < mapObjects.length; i++) {
                this._loadMapObject(mapObjects[i]);
            }

            this._initialized = true;

            this.signal('loadcomplete');
        }
    }

    /**
     *
     */
    update() {
        console.assert(this instanceof MapObjectGroup, 'this is not a MapObjectGroup', this);
        console.assert(this._initialized, 'MapObjectGroup is not yet loaded loaded!', this);

        if (this._initialized) {
            this._updateVisibility();
        }
    }

    /**
     * Set the visibility of an individual object.
     * @param mapObject {MapObject}
     * @param visible {boolean}
     */
    setMapObjectVisibility(mapObject, visible) {
        console.assert(this instanceof MapObjectGroup, 'this is not a MapObjectGroup', this);

        // @TODO Move this to mapobject instead? But then mapobject will have a dependency on their map object group which
        // I may or may not want
        if (mapObject.layer !== null) {
            if (visible) {
                if (!this.layerGroup.hasLayer(mapObject.layer)) {
                    this.layerGroup.addLayer(mapObject.layer);
                }
                // Trigger this on the object
                mapObject.setVisible(true);

                this.signal('mapobject:shown', {object: mapObject, objectgroup: this, visible: true});
            } else {
                if (this.layerGroup.hasLayer(mapObject.layer)) {
                    this.layerGroup.removeLayer(mapObject.layer);
                }
                // Trigger this on the object
                mapObject.setVisible(false);

                this.signal('mapobject:hidden', {object: mapObject, objectgroup: this, visible: false});
            }
        }
    }

    /**
     * Checks if a map object is visible on the map or not.
     * @param mapObject {MapObject}
     * @returns {boolean}
     */
    isMapObjectVisible(mapObject) {
        console.assert(this instanceof MapObjectGroup, 'this is not a MapObjectGroup', this);

        return this.layerGroup.hasLayer(mapObject.layer);
    }

    /**
     * Finds an object in this map object group by its ID.
     * @param id {Number}
     * @returns {MapObject}
     */
    findMapObjectById(id) {
        console.assert(this instanceof MapObjectGroup, 'this is not a MapObjectGroup', this);

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
     * @param layer {L.layer}
     * @param mapObject {MapObject}
     */
    setLayerToMapObject(layer, mapObject) {
        console.assert(this instanceof MapObjectGroup, 'this is not a MapObjectGroup', this);
        console.assert(this.findMapObjectById(mapObject.id) !== null, 'mapObject is not part of this MapObjectGroup', mapObject);

        // Unset previous layer
        if (mapObject.layer !== null) {
            this.layerGroup.removeLayer(mapObject.layer);
            mapObject.layer = null;
            mapObject.setVisible(false);
        }

        // Set new layer (if user wants to)
        if (layer !== null) {
            mapObject.layer = layer;
            this.layerGroup.addLayer(mapObject.layer);
            mapObject.setVisible(true);
            mapObject.onLayerInit();
        }
    }

    /**
     *
     * @param layer {L.layer}
     * @param options {object}
     * @return MapObject
     */
    createNewMapObject(layer, options) {
        console.assert(this instanceof MapObjectGroup, 'this is not a MapObjectGroup', this);

        let mapObject = this._createMapObject(layer, options);
        if (layer !== null) {
            mapObject.onLayerInit();
        }
        this.objects.push(mapObject);

        mapObject.register('object:deleted', this, (this._onObjectDeleted).bind(this));
        mapObject.register('synced', this, (this._onObjectSynced).bind(this));

        return mapObject;
    }

    /**
     * True if the object group is shown, false if it is hidden.
     * @returns {boolean}
     */
    isShown() {
        console.assert(this instanceof MapObjectGroup, 'this was not a MapObjectGroup', this);
        return this.manager.map.leafletMap.hasLayer(this.layerGroup);
    }

    /**
     * Sets the visibility of this entire map object group
     * @param visible {boolean}
     */
    setVisibility(visible) {
        console.assert(this instanceof MapObjectGroup, 'this was not a MapObjectGroup', this);
        if (this.layerGroup !== null) {
            let added = (!this.isShown() && visible);
            let removed = (this.isShown() && !visible);
            if (added) {
                this.signal('visibility:changed', {visible: true});
            } else if (removed) {
                this.signal('visibility:changed', {visible: false});
            }

            if (added || removed) {
                // Remove any layers that were added before
                for (let i = 0; i < this.objects.length; i++) {
                    let mapObject = this.objects[i];
                    // Remove all layers
                    if (mapObject.layer !== null) {
                        // Clean it up properly (if added, it's visible, if removed, not visible)
                        mapObject.setVisible(added);
                    }
                }
            }
        }
    }
}