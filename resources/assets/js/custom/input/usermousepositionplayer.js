class UserMousePositionPlayer extends Signalable {
    /**
     *
     * @param mapobject UserMousePosition
     * @param e
     * @param previousPlayer UserMousePositionPlayer|null
     */
    constructor(mapobject, e, previousPlayer = null) {
        super();

        this.mapobject = mapobject;
        this.e = e;

        // 30 fps
        this.frameTime = 1000 / 30;
        this.handle = null;

        // Start interpolating from the current start position
        this.currMS = 0;

        // Take over the previous point time from the previous player so that we know when the previous
        // point was added to the screen
        this.previousPointTime = previousPlayer === null ? 0 : previousPlayer.previousPointTime;
        this.previousLatLng = mapobject.layer.getLatLng();
    }

    _doFrame() {
        // Calculate the new location based on the next point in time
        let nextPointTarget = this.e.points[0];

        let timeRemaining = nextPointTarget.time - this.currMS;
        let timePassed = this.currMS - this.previousPointTime;

        //
        // timePassed = timePassed > 1000 ? 0 : timePassed;




        this.previousLatLng = nextPointTarget;
        this.currMS += this.frameTime;





        // Grab the points and schedule their execution
        // let firstTime = this.points[0].time;
        // for (let i = 0; i < this.points.length; i++) {
        //     let point = this.points[i];
        //
        //     let offsetSinceStart = point.time - firstTime;
        //     // First one
        //     if (offsetSinceStart === 0) {
        //         this.mapobject.setLocation(point.lat, point.lng);
        //     } else {
        //         // Schedule the mouse movement
        //         setTimeout(this.mapobject.setLocation.bind(this.mapobject, point.lat, point.lng), offsetSinceStart);
        //     }
        // }
    }

    start() {
        this.handle = setInterval(this._doFrame.bind(this), this.frameTime);
    }

    stop() {
        clearInterval(this.handle);
    }
}