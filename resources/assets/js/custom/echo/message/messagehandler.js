class MessageHandler {
    constructor(echo, message) {
        this.echo = echo;
        // Ensure the message starts with a dot
        this.message = message;
    }

    /**
     *
     * @returns {string}
     */
    getMessage() {
        return this.message;
    }

    /**
     *
     * @param presenceChannel {Channel}
     */
    setup(presenceChannel){
        presenceChannel.listen(this.getMessage(), this.onReceive.bind(this));
    }

    /**
     *
     * @param e
     */
    onReceive(e) {

    }
}