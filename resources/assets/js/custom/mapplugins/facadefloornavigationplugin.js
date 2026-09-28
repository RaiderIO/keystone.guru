/**
 * On a facade floor with facade_navigation enabled, highlights the floor union under the mouse and opens the floor
 * behind it on click. Hit testing runs on the map's own mouse events rather than on the polygons, because the
 * heatmap canvas may be layered on top of them and would swallow their events.
 */
class FacadeFloorNavigationPlugin extends MapPlugin {
    constructor(map) {
        super(map);

        /** @type {L.LayerGroup|null} */
        this.layerGroup = null;
        /** @type {Object|null} The floor union currently highlighted */
        this.hoveredFloorUnion = null;
        /** @type {CoordinatesService|null} */
        this._coordinatesService = null;

        this._onMouseMoveRef = this._onLeafletMapMouseMove.bind(this);
        this._onMouseOutRef = this._onLeafletMapMouseOut.bind(this);
        this._onClickRef = this._onLeafletMapClick.bind(this);
    }

    /**
     * @returns {Boolean}
     */
    isActive() {
        let state = getState();
        if (state.isMapAdmin() || !state.isCurrentDungeonFacadeEnabled()) {
            return false;
        }

        let currentFloor = state.getCurrentFloor();

        return !!currentFloor && !!currentFloor.facade && !!currentFloor.facade_navigation;
    }

    addToMap() {
        if (!this.isActive()) {
            return;
        }

        this.layerGroup = L.layerGroup().addTo(this.map.leafletMap);

        this.map.leafletMap
            .on('mousemove', this._onMouseMoveRef)
            .on('mouseout', this._onMouseOutRef)
            .on('click', this._onClickRef);
    }

    removeFromMap() {
        this.map.leafletMap
            .off('mousemove', this._onMouseMoveRef)
            .off('mouseout', this._onMouseOutRef)
            .off('click', this._onClickRef);

        this._setHoveredFloorUnion(null);

        if (this.layerGroup !== null) {
            this.map.leafletMap.removeLayer(this.layerGroup);
            this.layerGroup = null;
        }
    }

    /**
     * @returns {CoordinatesService}
     * @private
     */
    _getCoordinatesService() {
        return this._coordinatesService ??= new CoordinatesService(getState().getMapContext());
    }

    /**
     * @param floorUnion {Object}
     * @returns {Array<Array<{lat: Number, lng: Number}>>}
     * @private
     */
    _getFloorUnionAreaVertices(floorUnion) {
        let mapContext = getState().getMapContext();
        let floorUnionAreas = floorUnion.floor_union_areas ??
            (mapContext.getFloorUnionAreas() ?? []).filter(floorUnionArea => floorUnionArea.floor_union_id === floorUnion.id);

        return floorUnionAreas
            .map(floorUnionArea => floorUnionArea._cachedVertices ??= JSON.parse(floorUnionArea.vertices_json))
            .filter(vertices => vertices.length >= 3);
    }

    /**
     * Same resolution order as CoordinatesService.convertFacadeMapLocationToMapLocation(), so the highlighted floor
     * is the floor a click navigates to.
     *
     * @param latLng {LatLng}
     * @returns {Object|null}
     * @private
     */
    _getFloorUnionAtLatLng(latLng) {
        let coordinatesService = this._getCoordinatesService();
        let floorId = latLng.getFloor().id;

        for (let floorUnion of getState().getMapContext().getFloorUnions() ?? []) {
            if (floorUnion.floor_id !== floorId || floorUnion.target_floor_id === null) {
                continue;
            }

            for (let vertices of this._getFloorUnionAreaVertices(floorUnion)) {
                if (coordinatesService.polygonContainsPoint(latLng, vertices)) {
                    return floorUnion;
                }
            }
        }

        return null;
    }

    /**
     * @param leafletLatLng {L.LatLng}
     * @returns {LatLng}
     * @private
     */
    _toLatLng(leafletLatLng) {
        return new LatLng(leafletLatLng.lat, leafletLatLng.lng, getState().getCurrentFloor());
    }

    /**
     * @param floorUnion {Object|null}
     * @private
     */
    _setHoveredFloorUnion(floorUnion) {
        if (this.hoveredFloorUnion === floorUnion) {
            return;
        }

        this.hoveredFloorUnion = floorUnion;

        if (this.layerGroup !== null) {
            this.layerGroup.clearLayers();
        }

        this.map.leafletMap.getContainer().classList.toggle('facade_floor_navigation_hover', floorUnion !== null);

        if (floorUnion === null || this.layerGroup === null) {
            return;
        }

        let targetFloor = getState().getMapContext().getFloorById(floorUnion.target_floor_id);
        for (let vertices of this._getFloorUnionAreaVertices(floorUnion)) {
            let polygon = L.polygon(vertices, c.map.facadefloornavigation.polygonOptions);
            if (targetFloor) {
                polygon.bindTooltip(lang.get(targetFloor.name), c.map.facadefloornavigation.tooltipOptions);
            }

            this.layerGroup.addLayer(polygon);
        }
    }

    /**
     * @param event {L.LeafletMouseEvent}
     * @private
     */
    _onLeafletMapMouseMove(event) {
        if (this.map.getMapState() !== null) {
            this._setHoveredFloorUnion(null);

            return;
        }

        this._setHoveredFloorUnion(this._getFloorUnionAtLatLng(this._toLatLng(event.latlng)));
    }

    _onLeafletMapMouseOut() {
        this._setHoveredFloorUnion(null);
    }

    /**
     * @param event {L.LeafletMouseEvent}
     * @private
     */
    _onLeafletMapClick(event) {
        if (this.map.getMapState() !== null) {
            return;
        }

        let latLng = this._toLatLng(event.latlng);
        let floorUnion = this._getFloorUnionAtLatLng(latLng);
        if (floorUnion === null) {
            return;
        }

        let targetFloor = getState().getMapContext().getFloorById(floorUnion.target_floor_id);
        if (!targetFloor || !getState().isVisibleFloorId(targetFloor.id)) {
            return;
        }

        let targetLatLng = this._getCoordinatesService().convertFacadeMapLocationToMapLocation(latLng, targetFloor);

        getState().setFloorId(targetFloor.id, [targetLatLng.getLat(), targetLatLng.getLng()]);
    }
}

// Allow the class to be required in tests. In the browser bundle this file is concatenated as a plain script,
// where `module` is undefined, so it does not affect the concatenated bundle.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = FacadeFloorNavigationPlugin;
}
