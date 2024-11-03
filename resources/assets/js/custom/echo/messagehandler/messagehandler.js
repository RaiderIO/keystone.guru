/**
 * @property {Echo} echo
 * @property {String} message
 */
class MessageHandler extends Signalable {
    /**
     * @param echo {Echo}
     * @param message {String}
     */
    constructor(echo, message) {
        super();

        this.echo = echo;
        // Ensure the message starts with a dot
        this.message = message[0] !== '.' ? `.${message}` : message;
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
        console.assert(this instanceof MessageHandler, 'this is not a MessageHandler', this);

        console.log(`Listening for ${this.getMessage()}`);
        // console.log(presenceChannel);
        presenceChannel.listen(this.getMessage(), this.onReceive.bind(this));
    }

    /**
     * @param e {Object}
     */
    onReceive(e) {
        console.assert(this instanceof MessageHandler, 'this is not a MessageHandler', this);

        if(!(['mouse-position', 'viewport'].includes(e.__name))) {
            console.log(`MessageHandler::onReceive:`, e);
        }

        // Try to re-map the received message to an object that we know of
        let message = new MessageFactory().create(e.__name, e);

        if (message !== null) {
            let echoUser = this.echo.getUserByPublicKey(message.user.public_key);

            // Floor ID is always set - so set it here
            if (echoUser !== null) {
                echoUser.setFloorId(message.floor_id);
            }

            this.signal('message:received', {message: message});
        }
    }
}
