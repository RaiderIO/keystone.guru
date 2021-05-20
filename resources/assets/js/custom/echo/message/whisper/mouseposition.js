class MousePosition extends WhisperMessageHandler {

    constructor(echo) {
        super(echo, 'mouse-position');

        let self = this;
        let previousPollTime = (new Date()).getTime();

        this.mousePositions = [];
        this.previousSyncTime = previousPollTime;


        self.echo.map.register('map:mapobjectgroupsloaded', this, function () {
            self.echo.map.leafletMap.on('mousemove', function (mouseMoveEvent) {
                let currTime = (new Date()).getTime();

                // If we should save the mouse location - this event is fired a LOT, we should throttle and interpolate
                if (currTime - previousPollTime > c.map.echo.mousePollFrequencyMs) {
                    self.mousePositions.push({
                        time: currTime - self.previousSyncTime,
                        lat: mouseMoveEvent.latlng.lat,
                        lng: mouseMoveEvent.latlng.lng
                    });
                    previousPollTime = currTime;
                }
            })
        });

        // Periodically send new mouse locations
        setInterval(this._sendMousePositions.bind(this), c.map.echo.mouseSendFrequencyMs);
    }

    /**
     *
     * @private
     */
    _sendMousePositions() {
        if (this.mousePositions.length > 0) {
            this.send({
                // The current time is important so that it can be played back properly in other clients (reference)
                points: this.mousePositions,
                floor_id: getState().getCurrentFloor().id
            });

            // Clear
            this.mousePositions = [];
        }

        // Always save this - even if we don't send anything
        this.previousSyncTime = (new Date()).getTime();
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