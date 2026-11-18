class ViewPortHandler extends WhisperMessageHandler {

    constructor(echo) {
        super(echo, ViewPortMessage.getName());

        let self = this;

        this.echo.map.register('map:mapobjectgroupsloaded', this, function () {
            self.echo.map.leafletMap.on('moveend', self._sendViewPort.bind(self));
            self.echo.map.leafletMap.on('zoomlevelschange', self._sendViewPort.bind(self));
        });

        this.register('message:received', this, this._onReceive.bind(this));
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

    /**
     * @param viewPortReceivedEvent {Object}
     * @private
     */
    _onReceive(viewPortReceivedEvent) {
        /** @type ViewPortMessage */
        let message = viewPortReceivedEvent.data.message;
        /** @type EchoUser */
        let echoUser = viewPortReceivedEvent.data.echoUser;

        // Keep track of all user's most recent zoom location
        echoUser.setCenter(message.center);
        echoUser.setZoom(message.zoom);

        // If we are following this user, adjust our viewport to match theirs
        if (this.echo.isFollowingUser()) {
            this.echo.getFollowingUser().adjustViewportToThisUser();
        }
    }

    cleanup() {
        super.cleanup();

        this.echo.map.leafletMap.off('moveend', this._sendViewPort);
        this.echo.map.leafletMap.off('zoomlevelschange', this._sendViewPort);
    }
}

if (typeof module !== 'undefined' && module.exports) {
    module.exports = ViewPortHandler;
}
