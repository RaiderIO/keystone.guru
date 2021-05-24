class ViewPortHandler extends WhisperMessageHandler {

    constructor(echo) {
        super(echo, ViewPortMessage.getName());

        let self = this;

        this.echo.map.register('map:mapobjectgroupsloaded', this, function () {
            self.echo.map.leafletMap.on('moveend', self._sendViewPort.bind(self));
            self.echo.map.leafletMap.on('zoomlevelschange', self._sendViewPort.bind(self));
        });
    }

    /**
     *
     * @private
     */
    _sendViewPort() {
        this.send(new ViewPortMessage({
            center: this.echo.map.leafletMap.getCenter(),
            zoom: this.echo.map.leafletMap.getZoom()
        }));
    }

    onReceive(e) {
        super.onReceive(e);

        let echoUser = this.echo.getUserById(e.user.id);
        if (echoUser !== null) {
            // Keep track of all user's most recent zoom location
            echoUser.setCenter(e.center);
            echoUser.setZoom(e.zoom);
        } else {
            console.warn(`Unable to find echo user ${e.user.id}!`);
        }
    }

    cleanup() {
        super.cleanup();

        this.echo.map.leafletMap.off('moveend', this._sendViewPort.bind(this));
        this.echo.map.leafletMap.off('zoomlevelschange', this._sendViewPort.bind(this));
    }
}