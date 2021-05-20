/**
 * The animation for this class is done using CSS - there's no smoothing going on here. See .user_mouse_position css class definition
 * We just apply the position at specific times and the css animation handles the rest
 */
class UserMousePositionPlayer extends Signalable {
    /**
     *
     * @param mapobject UserMousePosition
     * @param e Object
     * @param previousPlayer UserMousePositionPlayer|null
     */
    constructor(mapobject, e, previousPlayer) {
        super();

        this.mapobject = mapobject;
        this.e = e;

        this.handles = [];
    }

    /**
     *
     * @param point
     * @private
     */
    _setLocation(point) {
        this.mapobject.setLocation(point.lat, point.lng);
    }

    /**
     * Starts applying the received mouse positions over time.
     */
    start() {
        for (let i = 0; i < this.e.points.length; i++) {
            let point = this.e.points[i];

            this.handles.push(setTimeout(this._setLocation.bind(this, point), point.time));
        }
    }

    /**
     * Stops parsing any mouse positions.
     */
    stop() {
        for (let i = 0; i < this.handles.length; i++) {
            // Clear all timeouts (even if they were already handled)
            clearTimeout(this.handles[i]);
        }

        this.handles = [];
    }
}