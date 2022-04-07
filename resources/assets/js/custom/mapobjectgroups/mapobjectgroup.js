/**
 * @property objects {MapObject[]}
 */
class MapObjectGroup extends Signalable {

    /**
     *
     * @param {MapObjectGroupManager} manager
     * @param {Array|String} names
     * @param {Boolean} editable
     */
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
        // May be set depending on which map object groups are hidden or not
        this._visible = true;

        this.objects = [];
        this.layerGroup = new L.LayerGroup([], {
            pane: this._getMapPane()
        });

        let self = this;

        // Callback to when the manager has received data from the server
        this.manager.map.register('map:beforerefresh', this, this._onBeforeRefresh.bind(this));
        // Whenever the map refreshes, we need to add ourselves to the map again
        this.manager.map.register('map:refresh', this, (function (mapRefreshEvent) {
            // Rebuild the layer group
            self.layerGroup = new L.LayerGroup();
        }).bind(this));
        getState().getMapContext().register('teeming:changed', this, this._updateVisibility.bind(this));

        if (!(this.manager.map instanceof AdminDungeonMap)) {
            getState().getMapContext().register('seasonalindex:changed', this, this._seasonalIndexChanged.bind(this));
        }

        // @TODO Convert this to the new echo message system
        if (getState().isEchoEnabled()) {
            let presenceChannel = window.Echo.join(getState().getMapContext().getEchoChannelName());

            for (let index in this.names) {
                if (this.names.hasOwnProperty(index)) {
                    presenceChannel.listen(`.${this.names[index]}-changed`, (e) => {
                        if (self._shouldHandleChangedEchoEvent(e)) {
                            let mapObject = self._loadMapObject(e.model, null, e.user);
                            self.setMapObjectVisibility(mapObject, true);
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
     * @returns {string}
     * @protected
     */
    _getMapPane() {
        return LEAFLET_PANE_MARKER;
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

        return e.user.public_key !== getState().getUser().public_key;
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

        // floor -1 means it's omnipresent (such as killzones)
        return this._shouldHandleEchoEvent(e) && (e.model.floor_id === getState().getCurrentFloor().id || e.model.floor_id === -1);
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
                // Only hide/show awakened enemies based on their seasonal index
                if (!mapObject.hasOwnProperty('seasonal_type') || mapObject.seasonal_type === ENEMY_SEASONAL_TYPE_AWAKENED) {
                    this.setMapObjectVisibility(mapObject, mapObject.seasonal_index === seasonalIndexChangedEvent.data.seasonalIndex);
                }
            }
        }
    }

    /**
     * Checks if map objects should be visible and update the visibility as necessary according to it.
     * @param force {boolean|null} Null to let map object decide for itself, true/false to force
     * @protected
     */
    _updateVisibility(force = null) {
        console.assert(this instanceof MapObjectGroup, 'this is not a MapObject', this);

        for (let i = 0; i < this.objects.length; i++) {
            let mapObject = this.objects[i];
            // Set this map object to be visible or not
            this.setMapObjectVisibility(mapObject, force === null ? mapObject.shouldBeVisible() : force);
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

        // Do not use this.setVisibility(false), this will interfere with the Map Elements selector (hide/show this map object group)
        this._updateVisibility(false);
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

        // If the lat/lng is now null, the layer has no right to exist anymore
        if (remoteMapObject.lat === null || remoteMapObject.lng === null) {
            this.setLayerToMapObject(null, mapObject);
        }
        // Otherwise, if it has a layer, update its position
        else if (mapObject.layer !== null) {
            mapObject.layer.setLatLng(L.latLng(remoteMapObject.lat, remoteMapObject.lng));
        }

        return mapObject;
    }

    /**
     * Restores an object that was received from the server.
     *
     * @param remoteMapObject {object}
     * @param layer {L.layer|null} Optional layer that was created already
     * @param user {Object|null} The user that created this object (if done from Echo)
     * @return {MapObject}
     * @protected
     */
    _loadMapObject(remoteMapObject, layer = null, user = null) {
        console.assert(this instanceof MapObjectGroup, 'this is not a MapObjectGroup', this);

        let mapObject = this.findMapObjectById(remoteMapObject.id);
        let options = this._getOptions(remoteMapObject);

        if (mapObject === null) {
            mapObject = this._createNewMapObject(layer === null ? this._createLayer(remoteMapObject) : layer, options);
        } else {
            mapObject = this._updateMapObject(remoteMapObject, mapObject, options);
        }

        mapObject.loadRemoteMapObject(remoteMapObject);

        // If id is not set we're creating a new Map Object from the map; at this point we should not sync
        // since the ID still needs to be generated from the server.
        // Sometimes, mapObject.id is 'undefined', for example MDT enemies. This simply means a MapObject is not dealing
        // with IDs, but we still want to fire the Synced event at some point.
        if (mapObject.id > 0) {
            mapObject.setSynced(true);
        }

        // Show echo notification or not
        this._showReceivedFromEcho(mapObject, user);

        return mapObject;
    }

    /**
     *
     * @param layer {L.layer}
     * @param options {object}
     * @return MapObject
     */
    _createNewMapObject(layer, options) {
        console.assert(this instanceof MapObjectGroup, 'this is not a MapObjectGroup', this);

        let mapObject = this._createMapObject(layer, options);
        if (layer !== null) {
            mapObject.onLayerInit();
        }
        this.objects.push(mapObject);

        // Make us listen to their changes
        mapObject.register('object:initialized', this, (this._onObjectInitialized).bind(this));
        mapObject.register('object:changed', this, (this._onObjectChanged).bind(this));
        mapObject.register('object:deleted', this, (this._onObjectDeleted).bind(this));
        mapObject.register('save:beforesend', this, (this._onObjectSaveBeforeSend).bind(this));
        mapObject.register('save:success', this, (this._onObjectSaveSuccess).bind(this));

        return mapObject;
    }

    /**
     *
     * @param localMapObject {MapObject}
     * @param user {object|null}
     * @protected
     */
    _showReceivedFromEcho(localMapObject, user = null) {
        console.assert(this instanceof MapObjectGroup, 'this is not a MapObjectGroup', this);

        if (getState().isEchoEnabled() && user !== null && getState().getUser().public_key !== user.public_key) {
            let fontClass = '';

            // Must be a hex color
            if (user.color.indexOf('#') === 0) {
                // Check if the user's color is 'dark' or 'light'. When it's dark we want a white font, black otherwise.
                fontClass = isColorDark(user.color) ? 'text-white' : 'text-dark';
            }

            // @TODO This should NOT use this layer but instead a new layer somehow
            let layer = localMapObject.layer;
            if (localMapObject instanceof KillZone) {
                // First layer should contain the polygon that is displayed
                layer = localMapObject.enemyConnectionsLayerGroup.getLayers().length > 0 ? localMapObject.enemyConnectionsLayerGroup.getLayers()[0] : null;
            }

            if (layer !== null) {
                let oldTooltip = layer.getTooltip();
                let oldTooltipLayerId = layer._leaflet_id;

                let tooltip = layer.bindTooltip(user.name, {
                    permanent: true,
                    className: `user_color_${user.public_key} ${fontClass}`,
                    direction: 'top'
                });

                // Fadeout after some time
                setTimeout(function () {
                    tooltip.closeTooltip();

                    // Do not re-bind a tooltip that shouldn't be there permanently
                    if (typeof oldTooltip !== 'undefined' &&
                        oldTooltip.options !== null &&
                        !oldTooltip.options.className.includes('user_color_') &&
                        // And only if the layer is still the same - don't start adding ghost tooltips
                        // The layer COULD have been changed at this time (killzones are notorious for this)
                        (localMapObject.layer !== null && localMapObject.layer._leaflet_id === oldTooltipLayerId)) {
                        // Rebind killzone pull index tooltip
                        localMapObject.layer.bindTooltip(oldTooltip._content, oldTooltip.options);
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
     * @param user {Object}
     * @protected
     */
    _showDeletedFromEcho(localMapObject, user) {
        console.assert(this instanceof MapObjectGroup, 'this is not a MapObjectGroup', this);

        if (getState().isEchoEnabled() && getState().getUser().public_key !== user.public_key && user.name !== null) {
            showInfoNotification(
                lang.get('messages.echo_object_deleted_notification')
                    .replace('{object}', localMapObject.toString())
                    .replace('{user}', user.name)
            );
        }
    }

    /**
     * Called whenever a map object is initialized for the first time
     * @param objectInitializedEvent {object}
     * @private
     */
    _onObjectInitialized(objectInitializedEvent) {
        console.assert(this instanceof MapObjectGroup, 'this is not a MapObjectGroup', this);

        let object = objectInitializedEvent.context;

        this.signal('object:add', {object: object, objectgroup: this});

        // Hide the objects if they're not visible by default
        if (!object.isDefaultVisible()) {
            this.setMapObjectVisibility(object, false);
        }
    }

    /**
     * Called whenever an object we created has finished wrapping up and is now synced
     * @param objectChangedEvent {object}
     * @protected
     */
    _onObjectChanged(objectChangedEvent) {
        console.assert(this instanceof MapObjectGroup, 'this is not a MapObjectGroup', this);

        this.signal('object:changed', {object: objectChangedEvent.context, objectgroup: this});
    }

    /**
     * Called whenever an object has deleted itself.
     * @param objectDeletedEvent {object}
     * @protected
     */
    _onObjectDeleted(objectDeletedEvent) {
        console.assert(this instanceof MapObjectGroup, 'this is not a MapObjectGroup', this);

        let mapObject = objectDeletedEvent.context;

        if (mapObject.layer !== null) {
            this.layerGroup.removeLayer(mapObject.layer);
            // @TODO Should this be put in the dungeonmap instead?
            this.manager.map.leafletMap.removeLayer(mapObject.layer);
            // Clean it up properly
            mapObject.setVisible(false);
        }

        // Remove it from our records
        let newObjects = [];
        for (let i = 0; i < this.objects.length; i++) {
            let objectCandidate = this.objects[i];
            if (objectCandidate.id !== mapObject.id) {
                newObjects.push(objectCandidate);
            }
        }
        this.objects = newObjects;

        // Fire the event
        this.signal('object:deleted', {object: mapObject, objectgroup: this});

        // Not _really_ required but doing it anyways
        mapObject.unregister('object:initialized', this);
        mapObject.unregister('object:changed', this);
        mapObject.unregister('object:deleted', this);
        mapObject.unregister('save:beforesend', this);
        mapObject.unregister('save:success', this);
    }

    /**
     * Called when an object in our map object group is saved to the server
     * @param mapObjectBeforeSendEvent {object}
     * @private
     */
    _onObjectSaveBeforeSend(mapObjectBeforeSendEvent) {
        this.signal('save:beforesend', {object: mapObjectBeforeSendEvent.context, objectgroup: this});
    }


    /**
     * Called when an object in our map object group is saved to the server
     * @param mapObjectSaveSuccessEvent {object}
     * @private
     */
    _onObjectSaveSuccess(mapObjectSaveSuccessEvent) {
        this.signal('save:success', {object: mapObjectSaveSuccessEvent.context, objectgroup: this});
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
     * Called whenever the floor is changed and the map object groups need to update their elements to show new ones.
     */
    update() {
        console.assert(this instanceof MapObjectGroup, 'this is not a MapObjectGroup', this);
        console.assert(this._initialized, 'MapObjectGroup is not yet loaded loaded!', this);

        if (this._initialized) {
            // Set it to be visible if it was
            this.setVisibility(this._visible);
        }
    }

    /**
     * Set the visibility of an individual object.
     * @param mapObject {MapObject}
     * @param visible {boolean}
     */
    setMapObjectVisibility(mapObject, visible) {
        console.assert(this instanceof MapObjectGroup, 'this is not a MapObjectGroup', this);

        if (!this._visible && visible) {
            // console.warn(`Unable to make map object visible - the MapObjectGroup is hidden`, mapObject);
        }
            // @TODO Move this to mapobject instead? But then mapobject will have a dependency on their map object group which
        // I may or may not want
        else if (mapObject.layer !== null) {
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
     *
     * @returns {boolean}
     */
    isInitialized() {
        console.assert(this instanceof MapObjectGroup, 'this is not a MapObjectGroup', this);

        return this._initialized;
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

        // Do not return an already saving map object which has id -1 of which multiple can exist
        if (id > 0) {
            for (let i = 0; i < this.objects.length; i++) {
                let objectCandidate = this.objects[i];
                if (objectCandidate.id === id) {
                    result = objectCandidate;
                    break;
                }
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
        console.assert(mapObject.id <= 0 || this.findMapObjectById(mapObject.id) !== null, 'mapObject is not part of this MapObjectGroup', mapObject);

        // Unset previous layer
        let oldLayer = mapObject.layer;
        if (mapObject.layer !== null) {
            // If it had a tooltip make sure to unset it so it doesn't get left over
            mapObject.layer.unbindTooltip();
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

        if (oldLayer !== layer) {
            this.signal('object:layerchanged', {
                object: mapObject,
                oldLayer: oldLayer,
                newLayer: mapObject.layer,
                objectgroup: this
            });
        }
    }

    /**
     *
     * @param layer
     * @returns {MapObject}
     */
    onNewLayerCreated(layer) {
        console.assert(this instanceof MapObjectGroup, 'this was not a MapObjectGroup', this);

        let createdMapObject = this._loadMapObject({id: -1}, layer);

        // Make sure it's visible on the map
        this.setMapObjectVisibility(createdMapObject, true);

        return createdMapObject;
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

        this._visible = visible;

        if (this.layerGroup !== null) {
            let added = (!this.isShown() && visible);
            let removed = (this.isShown() && !visible);
            if (added) {
                this.signal('visibility:changed', {visible: true});
            } else if (removed) {
                this.signal('visibility:changed', {visible: false});
            }

            // When true, let objects decide for themselves, when false, hide everything
            this._updateVisibility(visible ? null : false);
        }
    }

    /**
     * Checks if this map object group is toggleable by the user or not.
     * @returns {boolean}
     */
    isUserToggleable() {
        console.assert(this instanceof MapObjectGroup, 'this was not a MapObjectGroup', this);

        return true;
    }
}
