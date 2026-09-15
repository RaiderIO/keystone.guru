/**
 * Converts between the four coordinate planes the site works in: facade map lat/lng, per-floor map
 * lat/lng, and in-game x/y.
 *
 * 1:1 port of app/Service/Coordinates/CoordinatesService.php - keep the two in sync, and port any
 * new method line for line rather than rewriting it, so the two implementations stay diffable and
 * produce bit-identical results.
 *
 * One deliberate difference from the PHP interface: the methods do not take a MappingVersion. The
 * front-end reaches floor unions, floor union areas and facade_enabled through the MapContext, so
 * the context is handed to the constructor instead and the mapping version argument is dropped.
 * The floor union lookups that live on MappingVersion in PHP are the private methods at the bottom
 * of this class.
 */
class CoordinatesService {
    /**
     * @param mapContext {MapContext}
     */
    constructor(mapContext) {
        this.mapContext = mapContext;
        this._warnedMissingTargetFloorIds = [];
    }

    /**
     * @param latLng {LatLng}
     * @returns {IngameXY}
     */
    calculateIngameLocationForMapLocation(latLng) {
        let floor = latLng.getFloor();

        if (floor === null) {
            throw new Error('No floor set for latlng!');
        } else if (floor.facade) {
            throw new Error(`Unable to convert latlng ${JSON.stringify(latLng.toArrayWithFloor())} that is on facade floor!`);
        }

        let ingameMapSizeX = floor.ingame_max_x - floor.ingame_min_x;
        let ingameMapSizeY = floor.ingame_max_y - floor.ingame_min_y;

        // Invert the lat/lngs
        let factorLat = ((MAP_MAX_LAT - latLng.getLat()) / MAP_MAX_LAT);
        let factorLng = ((MAP_MAX_LNG - latLng.getLng()) / MAP_MAX_LNG);

        return new IngameXY(
            (ingameMapSizeX * factorLng) + floor.ingame_min_x,
            (ingameMapSizeY * factorLat) + floor.ingame_min_y,
            floor
        );
    }

    /**
     * @param ingameXY {IngameXY}
     * @returns {LatLng}
     */
    calculateMapLocationForIngameLocation(ingameXY) {
        let targetFloor = ingameXY.getFloor();

        if (targetFloor === null) {
            throw new Error('No floor set for ingame XY!');
        }

        let ingameMapSizeX = targetFloor.ingame_max_x - targetFloor.ingame_min_x;
        let ingameMapSizeY = targetFloor.ingame_max_y - targetFloor.ingame_min_y;

        if (Math.trunc(ingameMapSizeX) === 0 || Math.trunc(ingameMapSizeY) === 0) {
            throw new Error(`Floor ${targetFloor.name} (${targetFloor.id}) does not have ingame coordinates set!`);
        }

        let factorX = ((targetFloor.ingame_min_x - ingameXY.getX()) / ingameMapSizeX);
        let factorY = ((targetFloor.ingame_min_y - ingameXY.getY()) / ingameMapSizeY);

        return new LatLng(
            (MAP_MAX_LAT * factorY) + MAP_MAX_LAT,
            (MAP_MAX_LNG * factorX) + MAP_MAX_LNG,
            targetFloor
        );
    }

    /**
     * The returned LatLng keeps the facade floor when the point falls in dead space - a part of the
     * facade that no floor union area covers. Callers that need to tell the two apart must compare
     * the returned floor with the one they passed in; the PHP side behaves identically and
     * AjaxKillZoneController relies on it.
     *
     * @param latLng {LatLng}
     * @param forceFloor {Object|null}
     * @returns {LatLng}
     */
    convertFacadeMapLocationToMapLocation(latLng, forceFloor = null) {
        let sourceFloor = latLng.getFloor();
        if (sourceFloor === null) {
            throw new Error('No floor set for latlng!');
        }

        let result = latLng.clone();
        // Nothing to do if facade is not enabled - the coordinates are the same always
        if (!this.mapContext.getMappingVersion().facade_enabled) {
            return result;
        }

        // Check if this floor has unions.
        // If it has unions, check if the lat/lng is inside the union floor area
        // If it is, we must use the target floor of the union instead to fetch the ingame_max_x etc.
        // Then, we must apply rotation to the MAP location (rotate it around union lat/lng) and do the conversion
        let floorUnions = this._getFloorUnionsOnFloor(sourceFloor.id);

        for (let floorUnion of floorUnions) {
            // We must find the floor union we should perform our translation on
            let targetFloor = null;

            // If we're forcing the translation on a certain floor, check if this floor union matches that forced floor
            if (forceFloor !== null && floorUnion.target_floor_id === forceFloor.id) {
                targetFloor = forceFloor;
            } else {
                // Otherwise, check if the floor union area contains the target point, then we use this floor union's
                // target floor
                for (let floorUnionArea of this._getFloorUnionAreas(floorUnion)) {
                    if (this._floorUnionAreaContainsPoint(floorUnionArea, latLng)) {
                        targetFloor = this._getTargetFloor(floorUnion);
                        break;
                    }
                }
            }

            // Did we find the target floor, either through forced floor or through the floor union area?
            if (targetFloor !== null) {
                result.setFloor(targetFloor);

                // 1. Rotate the point according to the floor union's rotation
                result.rotate(this._getFloorUnionLatLng(floorUnion), floorUnion.rotation);

                // Move the point according to the floor union's latlng + size
                // 2. Scale the point from the current floor map to the new floor map
                result.scale(
                    this._getFloorUnionLatLng(floorUnion),
                    floorUnion.size,
                    CoordinatesService.getMapCenterLatLng(targetFloor),
                    MAP_SIZE
                );

                // The point is now on the new map plane
                break;
            }
        }

        return result;
    }

    /**
     * @param latLng {LatLng}
     * @param forceFloorUnion {Object|null}
     * @returns {LatLng}
     */
    convertMapLocationToFacadeMapLocation(latLng, forceFloorUnion = null) {
        let sourceFloor = latLng.getFloor();

        if (sourceFloor === null) {
            throw new Error('No floor set for latlng!');
        }

        // Check if this floor has unions.
        // If it has unions, check if the lat/lng is inside the union floor area
        // If it is, we must use the target floor of the union instead to fetch the ingame_max_x etc.
        // Then, we must apply rotation to the MAP location (rotate it around union lat/lng) and do the conversion
        let floorUnion = forceFloorUnion ?? this._getFloorUnionForLatLng(latLng);

        // No floor unions mean we don't need to do anything - we're done
        if (floorUnion === null) {
            return latLng;
        }

        let result = latLng.clone();

        // Ok this lat lng is inside a floor union area - this means we must use it's attached floor union's target floor
        result.setFloor(this._getFacadeFloor(floorUnion));

        // Move the enemy according to the floor union's latlng + size
        // 1. Scale the point from the current floor map to the new floor map
        result.scale(
            CoordinatesService.getMapCenterLatLng(this._getTargetFloor(floorUnion)),
            MAP_SIZE,
            this._getFloorUnionLatLng(floorUnion),
            floorUnion.size
        );

        // 2. Rotate the point according to the floor union's rotation
        result.rotate(this._getFloorUnionLatLng(floorUnion), floorUnion.rotation * -1);

        return result;
    }

    /**
     * @param x1 {Number}
     * @param x2 {Number}
     * @param y1 {Number}
     * @param y2 {Number}
     * @returns {Number}
     */
    distanceBetweenPoints(x1, x2, y1, y2) {
        // Pythagoras theorem: a^2+b^2=c^2
        return Math.sqrt(
            Math.pow(x1 - x2, 2) +
            Math.pow(y1 - y2, 2)
        );
    }

    /**
     * @param latLngA1 {LatLng}
     * @param latLngA2 {LatLng}
     * @param latLngB1 {LatLng}
     * @param latLngB2 {LatLng}
     * @returns {LatLng|null}
     */
    intersection(latLngA1, latLngA2, latLngB1, latLngB2) {
        // Line AB represented as a1lng + b1lat = c1
        let a1 = latLngA2.getLat() - latLngA1.getLat();
        let b1 = latLngA1.getLng() - latLngA2.getLng();
        let c1 = a1 * (latLngA1.getLng()) + b1 * (latLngA1.getLat());

        // Line CD represented as a2lng + b2lat = c2
        let a2 = latLngB2.getLat() - latLngB1.getLat();
        let b2 = latLngB1.getLng() - latLngB2.getLng();
        let c2 = a2 * (latLngB1.getLng()) + b2 * (latLngB1.getLat());

        let determinant = a1 * b2 - a2 * b1;

        if (determinant === 0) {
            // The lines are parallel and will never intersect
            return null;
        }

        let lng = (b2 * c1 - b1 * c2) / determinant;
        let lat = (a1 * c2 - a2 * c1) / determinant;

        let l1Length = this.distanceBetweenPoints(latLngA1.getLng(), latLngA2.getLng(), latLngA1.getLat(), latLngA2.getLat());
        // If the distance to the found point is greater than the length of EITHER of the lines, it's not a correct intersection!
        // This means that the intersection occurred in the extended line past the points of p1 and p2. We don't want them.
        if (l1Length < this.distanceBetweenPoints(latLngA1.getLng(), lng, latLngA1.getLat(), lat) ||
            l1Length < this.distanceBetweenPoints(latLngA2.getLng(), lng, latLngA2.getLat(), lat)) {
            return null;
        }

        let l2Length = this.distanceBetweenPoints(latLngB1.getLng(), latLngB2.getLng(), latLngB1.getLat(), latLngB2.getLat());
        if (l2Length < this.distanceBetweenPoints(latLngB1.getLng(), lng, latLngB1.getLat(), lat) ||
            l2Length < this.distanceBetweenPoints(latLngB2.getLng(), lng, latLngB2.getLat(), lat)) {
            return null;
        }

        return LatLng.fromArray({lat: lat, lng: lng});
    }

    /**
     * @param latLngA {LatLng}
     * @param latLngB {LatLng}
     * @returns {Number}
     */
    distance(latLngA, latLngB) {
        return this.distanceBetweenPoints(latLngA.getLng(), latLngB.getLng(), latLngA.getLat(), latLngB.getLat());
    }

    /**
     * @param ingameXYA {IngameXY}
     * @param ingameXYB {IngameXY}
     * @returns {Number}
     */
    distanceIngameXY(ingameXYA, ingameXYB) {
        return this.distanceBetweenPoints(ingameXYA.getX(), ingameXYB.getX(), ingameXYA.getY(), ingameXYB.getY());
    }

    /**
     * @param latLng {LatLng}
     * @param polygon {{lat: Number, lng: Number}[]}
     * @returns {Boolean}
     */
    polygonContainsPoint(latLng, polygon) {
        let last = polygon[polygon.length - 1];
        if (polygon[0].lat !== last.lat || polygon[0].lng !== last.lng) {
            polygon = polygon.concat([polygon[0]]);
        }

        let j = 0;
        let oddNodes = false;
        let lat = latLng.getLat();
        let lng = latLng.getLng();
        let n = polygon.length;
        for (let i = 0; i < n; i++) {
            j++;
            if (j === n) {
                j = 0;
            }

            if (((polygon[i].lng < lng) && (polygon[j].lng >= lng)) || ((polygon[j].lng < lng) && (polygon[i].lng >= lng))) {
                if (polygon[i].lat + (lng - polygon[i].lng) / (polygon[j].lng - polygon[i].lng) * (polygon[j].lat -
                    polygon[i].lat) < lat) {
                    oddNodes = !oddNodes;
                }
            }
        }

        return oddNodes;
    }

    /**
     * @param ingameXY {IngameXY}
     * @param gridSizeX {Number}
     * @param gridSizeY {Number}
     * @returns {IngameXY}
     */
    calculateGridLocationForIngameLocation(ingameXY, gridSizeX, gridSizeY) {
        let floor = ingameXY.getFloor();

        if (floor === null) {
            throw new Error('No floor set for ingameXY!');
        }

        let width = floor.ingame_max_x - floor.ingame_min_x;
        let height = floor.ingame_max_y - floor.ingame_min_y;
        let stepX = width / gridSizeX;
        let stepY = height / gridSizeY;

        let gx = Math.trunc(((ingameXY.getX() - floor.ingame_min_x) / width) * gridSizeX);
        let gy = Math.trunc(((ingameXY.getY() - floor.ingame_min_y) / height) * gridSizeY);

        return new IngameXY(
            (gx * stepX) + floor.ingame_min_x,
            (gy * stepY) + floor.ingame_min_y,
            floor
        );
    }

    /**
     * @param floor {Object|null}
     * @returns {LatLng}
     */
    static getMapCenterLatLng(floor = null) {
        return new LatLng(
            MAP_MAX_LAT / 2,
            MAP_MAX_LNG / 2,
            floor
        );
    }

    /**
     * Port of MappingVersion::getFloorUnionsOnFloor().
     *
     * @param floorId {Number}
     * @returns {Object[]}
     * @private
     */
    _getFloorUnionsOnFloor(floorId) {
        return this._getFloorUnions().filter(floorUnion => floorUnion.floor_id === floorId);
    }

    /**
     * Port of MappingVersion::getFloorUnionsForFloor().
     *
     * @param floorId {Number}
     * @returns {Object[]}
     * @private
     */
    _getFloorUnionsForFloor(floorId) {
        return this._getFloorUnions().filter(floorUnion => floorUnion.target_floor_id === floorId);
    }

    /**
     * Port of MappingVersion::getFloorUnionForLatLng().
     *
     * @param latLng {LatLng}
     * @returns {Object|null}
     * @private
     */
    _getFloorUnionForLatLng(latLng) {
        let floor = latLng.getFloor();
        if (floor === null) {
            return null;
        }

        let floorUnions = this._getFloorUnionsForFloor(floor.id);

        // Now that we know the floor union candidates, check which floor union we need to use
        // If we have more than 1 target, we must make a choice based on the floor union areas attached to the floor union
        if (floorUnions.length > 1) {
            for (let floorUnion of floorUnions) {
                // We need to translate the target point using this floor union first, before checking the floor union areas
                // Only if the translated point falls in the floor union area, can we properly check if this floor union matches
                let tmpConvertedLatLng = this.convertMapLocationToFacadeMapLocation(latLng, floorUnion);
                for (let floorUnionArea of this._getFloorUnionAreas(floorUnion)) {
                    if (this._floorUnionAreaContainsPoint(floorUnionArea, tmpConvertedLatLng)) {
                        return floorUnion;
                    }
                }
            }

            return null;
        }

        return floorUnions[0] ?? null;
    }

    /**
     * The mapping editor can move, resize, rotate, add and remove floor unions and their areas. The
     * map context payload they were built from is a snapshot taken at page load, so read the live
     * map objects there instead - otherwise the readout keeps answering with the pre-edit geometry
     * until the page is reloaded.
     *
     * @returns {Boolean}
     * @private
     */
    _useLiveMapObjects() {
        return getFloorUnionMapObjectGroup() !== null && getState().isMapAdmin();
    }

    /**
     * @returns {Object[]}
     * @private
     */
    _getFloorUnions() {
        if (this._useLiveMapObjects()) {
            return Object.values(getFloorUnionMapObjectGroup().objects);
        }

        return this.mapContext.getFloorUnions() ?? [];
    }

    /**
     * The areas come nested in each floor union (FloorUnion::$with), but fall back to the flat list
     * the map context also carries in case that ever changes.
     *
     * @param floorUnion {Object}
     * @returns {Object[]}
     * @private
     */
    _getFloorUnionAreas(floorUnion) {
        if (this._useLiveMapObjects()) {
            return Object.values(getFloorUnionAreaMapObjectGroup()?.objects ?? {})
                .filter(floorUnionArea => floorUnionArea.floor_union_id === floorUnion.id);
        }

        return floorUnion.floor_union_areas ??
            (this.mapContext.getFloorUnionAreas() ?? []).filter(floorUnionArea => floorUnionArea.floor_union_id === floorUnion.id);
    }

    /**
     * Port of FloorUnionArea::containsPoint(). The decoded vertices are cached on the area the same
     * way the PHP model caches them - this runs for every mouse move.
     *
     * @param floorUnionArea {Object}
     * @param latLng {LatLng}
     * @returns {Boolean}
     * @private
     */
    _floorUnionAreaContainsPoint(floorUnionArea, latLng) {
        // A live editor object reports its current vertices; a payload row carries them as JSON,
        // which is decoded once because this runs for every mouse move
        let vertices = typeof floorUnionArea.getVertices === 'function'
            ? floorUnionArea.getVertices()
            : (floorUnionArea._cachedVertices ??= JSON.parse(floorUnionArea.vertices_json));

        // An area that is still being drawn has no polygon yet
        if (vertices.length < 3) {
            return false;
        }

        return this.polygonContainsPoint(latLng, vertices);
    }

    /**
     * @param floorUnion {Object}
     * @returns {LatLng}
     * @private
     */
    _getFloorUnionLatLng(floorUnion) {
        return new LatLng(floorUnion.lat, floorUnion.lng, this._getFacadeFloor(floorUnion));
    }

    /**
     * @param floorUnion {Object}
     * @returns {Object|null}
     * @private
     */
    _getTargetFloor(floorUnion) {
        // target_floor is nested in the map context payload; the lookup covers the mapping editor,
        // whose visibleFloors holds every floor, and a payload cached before that nesting existed
        let targetFloor = floorUnion.target_floor ?? this.mapContext.getFloorById(floorUnion.target_floor_id);

        if (typeof targetFloor !== 'object' || targetFloor === null) {
            if (!this._warnedMissingTargetFloorIds.includes(floorUnion.target_floor_id)) {
                this._warnedMissingTargetFloorIds.push(floorUnion.target_floor_id);
                console.warn(`Unable to resolve target floor ${floorUnion.target_floor_id} of floor union ${floorUnion.id}`);
            }

            return null;
        }

        return targetFloor;
    }

    /**
     * The facade floor a union sits on. It is not part of the union payload (FloorUnion::$hidden),
     * so it can only be resolved when the facade floor is currently visible.
     *
     * @param floorUnion {Object}
     * @returns {Object|null}
     * @private
     */
    _getFacadeFloor(floorUnion) {
        let floor = this.mapContext.getFloorById(floorUnion.floor_id);

        return typeof floor === 'object' && floor !== null ? floor : null;
    }
}

if (typeof module !== 'undefined' && module.exports) {
    module.exports = CoordinatesService;
}
