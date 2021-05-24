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
     * @param message Message
     * @protected
     */
    send(message) {
        // Prevent sending of private properties
        let obj = message.toObject();

        this.privateChannel.whisper(this.getMessage(), obj);
        this.signal('message:sent', obj);
    }
}