class AdminDungeonTransport extends DungeonTransport {
    constructor(map, layer) {
        super(map, layer);

        this.setSynced(false);

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

        new L.Draw.DungeonTransportPath(this.map.leafletMap, {
            shapeOptions: c.map.dungeontransport.connectionPolylineOptions,
            onCreated: this.onPathDrawn.bind(this),
        }).enable();
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
            linkedDungeonTransport.save();
        }

        this.path_vertices_json = pathVertices.length > 0 ? JSON.stringify(pathVertices) : null;
        this.save();
    }

    /**
     * @inheritDoc
     */
    onLayerInit() {
        console.assert(this instanceof AdminDungeonTransport, 'this is not an AdminDungeonTransport', this);
        super.onLayerInit();

        // Clicking opens the edit popup in the mapping editor instead of travelling
        this.layer.off('click');
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
