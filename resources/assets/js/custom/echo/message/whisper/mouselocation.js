class MouseLocation extends WhisperMessageHandler {

    constructor(echo) {
        super(echo, 'mouse-location');

        let self = this;

        this.mouseLocations = [];

        let previousPollTime = (new Date()).getTime();

        self.echo.map.register('map:mapobjectgroupsloaded', this, function () {
            self.echo.map.leafletMap.on('mousemove', function (mouseMoveEvent) {
                let currTime = (new Date()).getTime();

                // If we should save the mouse location - this event is fired a LOT, we should throttle and interpolate
                if (currTime - previousPollTime > c.map.echo.mousePollFrequencyMs) {
                    self.mouseLocations.push({
                        time: currTime,
                        lat: mouseMoveEvent.latlng.lat,
                        lng: mouseMoveEvent.latlng.lng
                    });
                    previousPollTime = currTime;
                }
            })
        });

        // Periodically send new mouse locations
        setInterval(this._sendMouseLocations.bind(this), c.map.echo.mouseSendFrequency);
    }

    /**
     *
     * @private
     */
    _sendMouseLocations() {
        if (this.mouseLocations.length > 0) {
            this.send({
                // The current time is important so that it can be played back properly in other clients (reference)
                time: (new Date()).getTime(),
                points: this.mouseLocations,
                floor_id: getState().getCurrentFloor().id
            });

            // Clear
            this.mouseLocations = [];
        }
    }

    onReceive(e) {
        super.onReceive(e);

        let echoUser = this.echo.getUserById(e.user.id);
        if( echoUser !== null ) {
            echoUser.mapobject.onLocationsReceived(e);
            echoUser.mapobject.setSynced(true);
        } else {
            console.warn(`Unable to find echo user ${e.user.id}!`);
        }
    }
}