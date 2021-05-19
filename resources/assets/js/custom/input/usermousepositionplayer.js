class UserMousePositionPlayer extends Signalable {
    /**
     *
     * @param mapobject UserMousePosition
     * @param e
     * @param previousPlayer UserMousePositionPlayer|null
     */
    constructor(mapobject, e, previousPlayer) {
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

        console.log(`Playing ${e.points.length} points`);
    }

    _doFrame() {
        // Calculate the new location based on the next point in time
        let nextPointTarget = this.e.points[0];

        let timeRemaining = nextPointTarget.time - this.currMS;
        let timePassed = this.currMS - this.previousPointTime;


        let progressFactor = timePassed / (timeRemaining + timePassed);


        if (progressFactor >= 1) {
            // Snap to the location we targetted
            this.mapobject.setLocation(nextPointTarget.lat, nextPointTarget.lng);
            // Remove first element from the array so that we move to the next array but remember it
            this.previousLatLng = this.e.points.shift();
            console.log('Swapping!');
        } else {

            let differenceLat = nextPointTarget.lat - this.previousLatLng.lat;
            let differenceLng = nextPointTarget.lng - this.previousLatLng.lng;

            let targetLat = this.previousLatLng.lat + (differenceLat * progressFactor);
            let targetLng = this.previousLatLng.lng + (differenceLng * progressFactor);

            this.mapobject.setLocation(targetLat, targetLng);
            console.log(this.currMS, nextPointTarget, timeRemaining, timePassed, progressFactor, differenceLat, differenceLng);
        }


        this.currMS += this.frameTime;

        if (this.e.points.length === 0) {
            this.stop();
        }


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
        console.log('Starting');
        this.handle = setInterval(this._doFrame.bind(this), this.frameTime);
    }

    stop() {
        clearInterval(this.handle);
        console.log('Stopping');
    }
}