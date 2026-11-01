class AdminDungeonTransport extends DungeonTransport {
    constructor(map, layer) {
        super(map, layer);

        this.setSynced(false);
        /** @type {CoordinatesService|null} */
        this._coordinatesService = null;
        /** @type {Boolean} True while the layer sits where the facade shows this transport stored on another floor */
        this._layerOnFacade = false;
        /** @type {Boolean} */
        this._movedSinceSave = false;

        // The popup's linked transport select lists the other transports, which may load or change after ours
        this._getMapObjectGroup().register(['loadcomplete', 'save:success', 'object:deleted'], this, () => this._assignPopup());
    }

    /**
     * @inheritDoc
     */
    _getAttributes(force = false) {
        console.assert(this instanceof AdminDungeonTransport, 'this is not an AdminDungeonTransport', this);

        if (this._cachedAttributes !== null && !force) {
            return this._cachedAttributes;
        }

        return this._cachedAttributes = super._getAttributes(force).concat([
            new Attribute({
                name: 'draw_path',
                type: 'button',
                buttonType: 'info',
                buttonText: lang.get('js.dungeontransport_draw_path_button_text_label'),
                save: false,
                clicked: this.startDrawingPath.bind(this),
            }),
            new Attribute({
                name: 'clear_path',
                type: 'button',
                buttonType: 'danger',
                buttonText: lang.get('js.dungeontransport_clear_path_button_text_label'),
                save: false,
                clicked: this.clearPath.bind(this),
            }),
        ]);
    }

    /**
     * Off the facade, editing a path that leaves the current floor would drop the vertices that cannot be seen.
     *
     * @returns {Boolean}
     */
    canEditPath() {
        let currentFloor = getState().getCurrentFloor();

        return !!currentFloor.facade || this.getPairPathVertices().every(vertex => vertex.floor_id === currentFloor.id);
    }

    startDrawingPath() {
        this.map.leafletMap.closePopup();

        if (!this.canEditPath()) {
            showErrorNotification(lang.get('js.dungeontransport_path_not_editable'));
            return;
        }

        let drawPathHandler = new L.Draw.DungeonTransportPath(this.map.leafletMap, {
            shapeOptions: c.map.dungeontransport.connectionPolylineOptions,
            onCreated: this.onPathDrawn.bind(this),
        });

        let snackbarId = getState().addSnackbar(this._getDrawPathSnackbarHtml(), {
            onDomAdded: (id) => $(`#${id} .draw_tool_snackbar`).append(this._getDrawPathActions(drawPathHandler)),
        });
        // onPathDrawn() stamps every vertex with the current floor, so a path cannot continue onto another floor
        getState().register('floorid:changed', drawPathHandler, () => drawPathHandler.disable());
        drawPathHandler.once('disabled', () => {
            getState().removeSnackbar(snackbarId);
            getState().unregister('floorid:changed', drawPathHandler);
        });

        drawPathHandler.enable();
    }

    /**
     * @returns {String} The snackbar naming the path being drawn, like the one the draw toolbar shows for its tools.
     * @private
     */
    _getDrawPathSnackbarHtml() {
        let label = lang.get('js.dungeontransport_draw_path_button_text_label');
        let status = Handlebars.templates['map_controls_draw_tool_status_template']({
            status: lang.get('js.draw_tool_status', {label: label}),
            fa_class: 'fa-route',
            text: label,
        });

        return `<div class="draw_tool_snackbar">${status}</div>`;
    }

    /**
     * The draw toolbar's actions for a polyline, which a path drawn from the popup has no toolbar to show.
     *
     * @param drawPathHandler {L.Draw.DungeonTransportPath}
     * @returns {jQuery}
     * @private
     */
    _getDrawPathActions(drawPathHandler) {
        let toolbarLocal = L.drawLocal.draw.toolbar;
        let $drawActions = $('<ul>', {class: 'leaflet-draw-actions row g-0'});

        for (let action of [
            {local: toolbarLocal.finish, icon: 'fa-check', callback: () => drawPathHandler.completeShape()},
            {local: toolbarLocal.undo, icon: 'fa-undo', callback: () => drawPathHandler.deleteLastVertex()},
            {local: toolbarLocal.actions, icon: 'fa-times', callback: () => drawPathHandler.disable()},
        ]) {
            $drawActions.append($('<li>', {class: 'col btn btn-info mx-2 p-0'}).append(
                $('<a>', {href: '#', class: 'd-inline-block w-100 h-100', title: action.local.title}).append(
                    $('<i>', {class: `fas ${action.icon} me-1`, 'aria-hidden': 'true'}),
                    document.createTextNode(action.local.text),
                ).on('click', (event) => {
                    event.preventDefault();
                    action.callback();
                })
            ));
        }

        return $drawActions;
    }

    /**
     * Stores the drawn path on this transport, running away from it, on the floor it was drawn on. The server
     * converts vertices drawn on the facade onto their own floors.
     *
     * @param latLngs {L.LatLng[]}
     */
    onPathDrawn(latLngs) {
        let leafletMap = this.map.leafletMap;
        let transportPoint = leafletMap.latLngToLayerPoint(this.layer.getLatLng());
        let firstDistance = leafletMap.latLngToLayerPoint(latLngs[0]).distanceTo(transportPoint);
        let lastDistance = leafletMap.latLngToLayerPoint(latLngs[latLngs.length - 1]).distanceTo(transportPoint);

        let floorId = getState().getCurrentFloor().id;
        let pathVertices = latLngs.map(latLng => ({lat: latLng.lat, lng: latLng.lng, floor_id: floorId}));
        if (lastDistance < firstDistance) {
            pathVertices.reverse();
        }

        this._setPairPath(pathVertices);
    }

    clearPath() {
        this.map.leafletMap.closePopup();

        if (!this.canEditPath()) {
            showErrorNotification(lang.get('js.dungeontransport_path_not_editable'));
            return;
        }

        this._setPairPath([]);
    }

    /**
     * A pair has one path, so the partner's own path goes once this transport holds the pair's path.
     *
     * @param pathVertices {{lat: Number, lng: Number, floor_id: Number}[]}
     * @private
     */
    _setPairPath(pathVertices) {
        let linkedDungeonTransport = this.getLinkedDungeonTransport();
        if (linkedDungeonTransport !== null && this.isTwoWayWith(linkedDungeonTransport) &&
            linkedDungeonTransport.getPathVertices().length > 0) {
            linkedDungeonTransport.path_vertices_json = null;
            linkedDungeonTransport.facade_path_vertices_json = null;
            linkedDungeonTransport.save();
        }

        this.path_vertices_json = pathVertices.length > 0 ? JSON.stringify(pathVertices) : null;
        // The save response brings the new path's facade view
        this.facade_path_vertices_json = null;
        this.save();
    }

    /**
     * @returns {CoordinatesService}
     * @private
     */
    _getCoordinatesService() {
        return this._coordinatesService ??= new CoordinatesService(getState().getMapContext());
    }

    /**
     * @returns {LatLng|null} Where the facade shows this transport, when it is stored on a floor that the facade holds.
     */
    getFacadeLatLng() {
        let floor = getState().getMapContext().getFloorById(this.floor_id);
        if (floor === false || floor.facade || typeof this.lat !== 'number' || typeof this.lng !== 'number') {
            return null;
        }

        let facadeLatLng = this._getCoordinatesService().convertMapLocationToFacadeMapLocation(new LatLng(this.lat, this.lng, floor));

        return facadeLatLng.getFloor().facade ? facadeLatLng : null;
    }

    /**
     * Shows on the facade as well, so a pair that spans several floors can be edited together.
     *
     * @inheritDoc
     */
    isOnFloor(floor) {
        return super.isOnFloor(floor) || (!!floor.facade && this.getFacadeLatLng() !== null);
    }

    /**
     * Moves the layer to where the current floor shows this transport. Off the facade the layer keeps its position,
     * since that holds any move that is not saved yet.
     *
     * @private
     */
    _positionLayer() {
        if (this.layer === null) {
            return;
        }

        let currentFloor = getState().getCurrentFloor();
        let facadeLatLng = currentFloor.facade && currentFloor.id !== this.floor_id ? this.getFacadeLatLng() : null;

        let wasLayerOnFacade = this._layerOnFacade;
        this._layerOnFacade = facadeLatLng !== null;

        if (facadeLatLng !== null) {
            this.layer.setLatLng(L.latLng(facadeLatLng.getLat(), facadeLatLng.getLng()));
        } else if (wasLayerOnFacade) {
            this.layer.setLatLng(L.latLng(this.lat, this.lng));
        } else {
            return;
        }

        this._rebuildPairLines();
    }

    /**
     * @returns {{lat: Number, lng: Number, floor_id: Number}} Where the layer puts this transport on the floor it is stored on.
     * @private
     */
    _getLayerLocation() {
        let latLng = this.layer.getLatLng();

        if (!this._layerOnFacade) {
            return {lat: latLng.lat, lng: latLng.lng, floor_id: this.floor_id};
        }

        // Converting the facade location back drifts in the last digits
        let facadeLatLng = this.getFacadeLatLng();
        if (facadeLatLng !== null && facadeLatLng.getLat() === latLng.lat && facadeLatLng.getLng() === latLng.lng) {
            return {lat: this.lat, lng: this.lng, floor_id: this.floor_id};
        }

        let coordinatesService = this._getCoordinatesService();
        let movedLatLng = new LatLng(latLng.lat, latLng.lng, getState().getCurrentFloor());
        let floorLatLng = coordinatesService.convertFacadeMapLocationToMapLocation(movedLatLng);

        // A harbour often lies outside its floor's union areas; the transport stays on its floor unless moved onto another
        if (floorLatLng.getFloor().facade) {
            floorLatLng = coordinatesService.convertFacadeMapLocationToMapLocation(
                movedLatLng, getState().getMapContext().getFloorById(this.floor_id),
            );
        }

        return {lat: floorLatLng.getLat(), lng: floorLatLng.getLng(), floor_id: floorLatLng.getFloor().id};
    }

    /**
     * A path always ends on its transports, so the vertex at this transport follows it to where it was moved.
     *
     * @param location {{lat: Number, lng: Number, floor_id: Number}}
     * @private
     */
    _snapPathsToLocation(location) {
        let pathVertices = this.getPathVertices();
        if (pathVertices.length > 0) {
            pathVertices[0] = location;
            this.path_vertices_json = JSON.stringify(pathVertices);
            this.facade_path_vertices_json = null;
        }

        Object.values(this._getMapObjectGroup().objects)
            .filter(dungeonTransport => dungeonTransport !== this && dungeonTransport.linked_dungeon_transport_id === this.id)
            .forEach(dungeonTransport => {
                let linkedPathVertices = dungeonTransport.getPathVertices();
                if (linkedPathVertices.length > 0) {
                    linkedPathVertices[linkedPathVertices.length - 1] = location;
                    dungeonTransport.path_vertices_json = JSON.stringify(linkedPathVertices);
                    dungeonTransport.facade_path_vertices_json = null;
                    dungeonTransport.save();
                }
            });
    }

    /**
     * Saves this transport on the floor it is stored on, also while the facade shows it.
     *
     * @inheritDoc
     */
    getSaveData(fields = '*') {
        let data = super.getSaveData(fields);

        if (this._layerOnFacade && data.hasOwnProperty('lat')) {
            Object.assign(data, this._getLayerLocation());
        }

        return data;
    }

    /**
     * @inheritDoc
     */
    save() {
        if (this._movedSinceSave && this.layer !== null) {
            this._movedSinceSave = false;
            this._snapPathsToLocation(this._getLayerLocation());
        }

        super.save();
    }

    /**
     * @inheritDoc
     */
    loadRemoteMapObject(remoteMapObject, parentAttribute = null) {
        super.loadRemoteMapObject(remoteMapObject, parentAttribute);

        // The group put the layer on the stored location
        if (parentAttribute === null) {
            this._positionLayer();
        }
    }

    /**
     * @inheritDoc
     */
    setVisible(visible) {
        this._positionLayer();

        super.setVisible(visible);
    }

    /**
     * @inheritDoc
     */
    onLayerInit() {
        console.assert(this instanceof AdminDungeonTransport, 'this is not an AdminDungeonTransport', this);
        super.onLayerInit();

        // Clicking opens the edit popup in the mapping editor instead of travelling
        this.layer.off('click');
        this.layer.on('dragend', () => this._movedSinceSave = true);
    }

    /**
     * @inheritDoc
     */
    cleanup() {
        super.cleanup();

        this._getMapObjectGroup().unregister(['loadcomplete', 'save:success', 'object:deleted'], this);
    }
}

// Guarded export for the test runner (Vitest). This is a no-op in the browser,
// where `module` is undefined, so it does not affect the concatenated bundle.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        AdminDungeonTransport,
    };
}
