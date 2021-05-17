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

        console.log('Listening to message ', this.getMessage());
        // Set up the private channel so that we may communicate using it
        this.privateChannel = window.Echo.private(getState().getMapContext().getEchoChannelName())
            .listenForWhisper(this.getMessage(), (e) => {
                console.log('received', e);
                self.onReceive(e);
            });
    }

    /**
     * Sends an object across the whisper channel
     * @param obj
     * @protected
     */
    send(obj) {
        console.log('Sending message ', this.getMessage());
        this.privateChannel.whisper(this.getMessage(), $.extend({}, {
            // Send some additional data with every whisper
            user: {
                id: getState().getMapContext().getUserId()
            }
        }, obj));
    }
}