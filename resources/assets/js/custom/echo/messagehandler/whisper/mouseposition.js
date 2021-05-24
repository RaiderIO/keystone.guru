class MousePositionHandler extends WhisperMessageHandler {

    constructor(echo) {
        super(echo, MousePositionMessage.getName());

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

        this.register('message:received', this, this._onReceive.bind(this));

        // Periodically send new mouse locations
        setInterval(this._sendMousePositions.bind(this), c.map.echo.mouseSendFrequencyMs);
    }

    /**
     *
     * @private
     */
    _sendMousePositions() {
        if (this.mousePositions.length > 0) {
            this.send(new MousePositionMessage({
                points: this.mousePositions
            }));

            // Clear
            this.mousePositions = [];
        }

        // Always save this - even if we don't send anything
        this.previousSyncTime = (new Date()).getTime();
    }

    _onReceive(mousePositionReceivedEvent) {
        /** @type MousePositionMessage */
        let message = mousePositionReceivedEvent.data.message;

        let echoUser = this.echo.getUserById(message.user.id);
        if( echoUser !== null ) {
            echoUser.mapobject.onPositionsReceived(message);
            echoUser.mapobject.setSynced(true);
        } else {
            console.warn(`Unable to find echo user ${message.user.id}!`);
        }
    }
}