class MessageHandler extends Signalable {
    constructor(echo, message) {
        super();

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
    setup(presenceChannel) {
        presenceChannel.listen(this.getMessage(), this.onReceive.bind(this));
    }

    /**
     * @param e {Object}
     */
    onReceive(e) {
        // Try to re-map the received message to an object that we know of
        let message = new MessageFactory().create(e.__name, e);

        if (message !== null) {
            console.log('Received', message);
            let echoUser = this.echo.getUserById(message.user.id);

            // Floor ID is always set - so set it here
            if (echoUser !== null) {
                echoUser.setFloorId(message.floor_id);
            }

            this.signal('message:received', {message: message});
        }
    }
}