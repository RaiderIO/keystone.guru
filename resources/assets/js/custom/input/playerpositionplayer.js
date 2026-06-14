/**
 * Smoothly interpolates a received player position over time. Unlike the user mouse positions - which receive a batch
 * of pre-timed points per message - player moved events carry a single position each. We therefore tween from the
 * marker's current location to each newly received location using requestAnimationFrame. The tween duration is the
 * elapsed time since this player's previous update (capped to a max) so the marker arrives just as the next update lands.
 */
class PlayerPositionPlayer extends Signalable {
    /**
     * @param mapobject {PlayerPosition}
     */
    constructor(mapobject) {
        super();

        this.mapobject = mapobject;

        this.handle = null;
        this.lastUpdateTime = null;

        this.startLat = 0;
        this.startLng = 0;
        this.targetLat = 0;
        this.targetLng = 0;
        this.startTime = 0;
        this.duration = 0;
    }

    /**
     * Starts gliding the marker towards the newly received location.
     *
     * @param lat {Number}
     * @param lng {Number}
     */
    moveTo(lat, lng) {
        console.assert(this instanceof PlayerPositionPlayer, 'this is not a PlayerPositionPlayer', this);

        let now = (new Date()).getTime();

        // The very first position has nothing to interpolate from - snap to it
        if (this.lastUpdateTime === null) {
            this.lastUpdateTime = now;
            this.mapobject.setInterpolatedPosition(lat, lng);
            return;
        }

        // Animate over roughly the time it took for this update to arrive, so the marker keeps gliding between updates
        this.duration = Math.min(now - this.lastUpdateTime, c.map.echo.playerPositionSmoothingMaxMs);
        this.lastUpdateTime = now;

        this.startLat = this.mapobject.lat;
        this.startLng = this.mapobject.lng;
        this.targetLat = lat;
        this.targetLng = lng;
        this.startTime = now;

        this.stop();

        if (this.duration <= 0) {
            this.mapobject.setInterpolatedPosition(lat, lng);
            return;
        }

        this.handle = window.requestAnimationFrame(this._step.bind(this));
    }

    /**
     * Stops any in-flight interpolation.
     */
    stop() {
        if (this.handle !== null) {
            window.cancelAnimationFrame(this.handle);
            this.handle = null;
        }
    }

    /**
     * @private
     */
    _step() {
        console.assert(this instanceof PlayerPositionPlayer, 'this is not a PlayerPositionPlayer', this);

        let progress = Math.min(Math.max(((new Date()).getTime() - this.startTime) / this.duration, 0), 1);

        let lat = this.startLat + ((this.targetLat - this.startLat) * progress);
        let lng = this.startLng + ((this.targetLng - this.startLng) * progress);

        this.mapobject.setInterpolatedPosition(lat, lng);

        if (progress < 1) {
            this.handle = window.requestAnimationFrame(this._step.bind(this));
        } else {
            this.handle = null;
        }
    }
}
