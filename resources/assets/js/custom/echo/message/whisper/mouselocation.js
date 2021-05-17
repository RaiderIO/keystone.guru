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
                    self.mouseLocations.push(mouseMoveEvent.latlng);
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
                points: this.mouseLocations
            });

            // Clear
            this.mouseLocations = [];
        }
    }

    onReceive(e) {
        super.onReceive(e);

        console.log(e);
    }
}