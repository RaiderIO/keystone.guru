/**
 * A closed polyline that, outside the mapping editor, is drawn as the concave hull of a set of points pushed
 * outwards by a margin. It has no weight picker and no animated layer.
 */
class HullPolyline extends Polyline {
    /**
     * @inheritDoc
     */
    _isWeightEditable() {
        return false;
    }

    /**
     * @inheritDoc
     */
    _isAnimatable() {
        return false;
    }

    /**
     * The points to wrap the hull around.
     * @returns {Array} List of [lat, lng] points
     * @protected
     */
    _getHullPoints() {
        return [];
    }

    /**
     * @returns {Number}
     * @protected
     */
    _getHullMargin() {
        return 0;
    }

    /**
     * @returns {Function} Receives the number of hull points, returns the number of segments of each rounded corner
     * @protected
     */
    _getHullArcSegments() {
        return () => 0;
    }

    /**
     * @returns {Object}
     * @protected
     */
    _getHullPolygonOptions() {
        return {};
    }

    /**
     * @returns {MapObjectGroup}
     * @protected
     */
    _getHullMapObjectGroup() {
        return null;
    }

    /**
     * Builds a polygon around the concave hull of the given points, pushed outwards by the hull margin.
     *
     * @param points {Array} List of [lat, lng] points
     * @returns {L.Polygon|null} Null when fewer than two points were given, or no offset could be made
     * @protected
     */
    _createHullLayer(points) {
        let result = null;

        if (points.length > 1) {
            let hullPoints = hull(points, 100);
            // Only if we can actually make an offset
            if (hullPoints.length > 1) {
                try {
                    let offsetLatLngs = createOffsetPolygon(
                        hullPoints.map(point => ({lat: point[0], lng: point[1]})),
                        this._getHullMargin(),
                        this._getHullArcSegments()(hullPoints.length)
                    );

                    result = L.polygon([offsetLatLngs], this._getHullPolygonOptions());
                } catch (error) {
                    // Not particularly interesting to spam the console with
                    console.error('Unable to create offset hull polygon', error);
                }
            }
        }

        return result;
    }

    /**
     * Replaces this map object's layer with the hull around its current points.
     */
    _updateHullLayer() {
        console.assert(this instanceof HullPolyline, 'this is not a HullPolyline', this);

        this._getHullMapObjectGroup().setLayerToMapObject(this._createHullLayer(this._getHullPoints()), this);
        this.rebindTooltip();
    }
}

// Guarded export for the test runner (Vitest). This is a no-op in the browser,
// where `module` is undefined, so it does not affect the concatenated bundle.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {HullPolyline};
}
