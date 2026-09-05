/**
 * A point on a map plane, together with the floor that plane belongs to.
 *
 * 1:1 port of app/Logic/Structs/LatLng.php - keep the two in sync. Like the PHP version, scale()
 * and rotate() mutate this instance and return it; clone() first if you need the original.
 */
class LatLng {
    /**
     * @param lat {Number}
     * @param lng {Number}
     * @param floor {Object|null}
     */
    constructor(lat = 0, lng = 0, floor = null) {
        this.lat = lat;
        this.lng = lng;
        this.floor = floor;
    }

    /**
     * @param precision {Number|null}
     * @returns {Number}
     */
    getLat(precision = null) {
        return precision === null ? this.lat : roundHalfAwayFromZero(this.lat, precision);
    }

    /**
     * @param lat {Number}
     * @returns {LatLng}
     */
    setLat(lat) {
        this.lat = lat;

        return this;
    }

    /**
     * @param precision {Number|null}
     * @returns {Number}
     */
    getLng(precision = null) {
        return precision === null ? this.lng : roundHalfAwayFromZero(this.lng, precision);
    }

    /**
     * @param lng {Number}
     * @returns {LatLng}
     */
    setLng(lng) {
        this.lng = lng;

        return this;
    }

    /**
     * @returns {Object|null}
     */
    getFloor() {
        return this.floor;
    }

    /**
     * @param floor {Object|null}
     * @returns {LatLng}
     */
    setFloor(floor) {
        this.floor = floor;

        return this;
    }

    /**
     * @param currentMapCenter {LatLng}
     * @param currentMapSize {Number}
     * @param targetMapCenter {LatLng}
     * @param targetMapSize {Number}
     * @returns {LatLng}
     */
    scale(currentMapCenter, currentMapSize, targetMapCenter, targetMapSize) {
        let currentMapSizeLat = currentMapSize;
        let currentMapSizeLng = currentMapSize * MAP_ASPECT_RATIO;
        // Lat is inverted. The dead center is top left, not bottom left
        let currentMapOffsetLat = currentMapCenter.getLat() + (currentMapSizeLat / 2);
        let currentMapOffsetLng = currentMapCenter.getLng() - (currentMapSizeLng / 2);

        let targetMapSizeLat = targetMapSize;
        let targetMapSizeLng = targetMapSize * MAP_ASPECT_RATIO;
        // Lat is inverted. The dead center is top left, not bottom left
        let targetMapOffsetLat = targetMapCenter.getLat() + (targetMapSizeLat / 2);
        let targetMapOffsetLng = targetMapCenter.getLng() - (targetMapSizeLng / 2);

        // Undo the offset. Then scale by the correct factor, and apply the new offset
        this.lat = ((this.lat - currentMapOffsetLat) * (targetMapSizeLat / currentMapSizeLat)) + targetMapOffsetLat;
        this.lng = ((this.lng - currentMapOffsetLng) * (targetMapSizeLng / currentMapSizeLng)) + targetMapOffsetLng;

        return this;
    }

    /**
     * @param centerLatLng {LatLng}
     * @param degrees {Number}
     * @returns {LatLng}
     */
    rotate(centerLatLng, degrees) {
        // rotateLatLng mutates the lat/lng it is given and returns it, which is exactly what
        // LatLng::rotate() does in PHP - so pass ourselves in rather than copying the math
        rotateLatLng(centerLatLng, this, degrees);

        return this;
    }

    /**
     * @returns {{lat: Number, lng: Number}}
     */
    toArray() {
        return {
            lat: this.lat,
            lng: this.lng
        };
    }

    /**
     * @returns {{lat: Number, lng: Number, floor_id: Number|null}}
     */
    toArrayWithFloor() {
        return {
            lat: this.lat,
            lng: this.lng,
            floor_id: this.floor?.id ?? null
        };
    }

    /**
     * @returns {LatLng}
     */
    clone() {
        return new LatLng(this.lat, this.lng, this.floor);
    }

    /**
     * @param latLng {{lat: Number, lng: Number}}
     * @param floor {Object|null}
     * @returns {LatLng}
     */
    static fromArray(latLng, floor = null) {
        return new LatLng(latLng.lat, latLng.lng, floor);
    }
}

if (typeof module !== 'undefined' && module.exports) {
    module.exports = LatLng;
}
