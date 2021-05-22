class WhisperMessageHandler extends MessageHandler {
    constructor(echo, message) {
        super(echo, message);
    }

    /**
     *
     * @param presenceChannel {Channel}
     */
    setup(presenceChannel) {
        let self = this;

        // Set up the private channel so that we may communicate using it
        this.privateChannel = window.Echo.private(getState().getMapContext().getEchoChannelName())
            .listenForWhisper(this.getMessage(), (e) => {
                self.onReceive(e);
            });
    }

    /**
     * Sends an object across the whisper channel
     * @param obj
     * @protected
     */
    send(obj) {
        let e = $.extend({}, {
            // Send some additional data with every whisper
            user: {
                id: getState().getMapContext().getUserId()
            },
            floor_id: getState().getCurrentFloor().id
        }, obj);

        this.privateChannel.whisper(this.getMessage(), e);
        this.signal('message:sent', e);
    }
}