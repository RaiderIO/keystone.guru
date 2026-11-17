class WhisperMessageHandler extends MessageHandler {
    constructor(echo, message) {
        super(echo, message);

        /** @type {Channel|null} */
        this.presenceChannel = null;
    }

    /**
     *
     * @param presenceChannel {Channel}
     */
    setup(presenceChannel) {
        this.presenceChannel = presenceChannel;
        this.presenceChannel.listenForWhisper(this.getMessage(), this._onWhisperReceived.bind(this));
    }

    /**
     * Sends an object across the whisper channel
     * @param message Message
     * @protected
     */
    send(message) {
        // Prevent sending of private properties
        let obj = message.toObject();
        // Receivers take the sender from the presence member the websocket server attaches to the event
        delete obj.user;

        this.presenceChannel.whisper(this.getMessage(), obj);
        this.signal('message:sent', obj);
    }

    /**
     * @param payload {Object}
     * @param metadata {Object|undefined} Set by pusher-js; carries the sender's presence member id as user_id
     * @private
     */
    _onWhisperReceived(payload, metadata) {
        let echoUser = this._getSender(metadata);
        if (echoUser === null) {
            return;
        }

        /** @type {Message|null} */
        let message = new MessageFactory().create(this.getMessage().substring(1), payload);
        if (message === null) {
            return;
        }

        message.user = {public_key: echoUser.getPublicKey()};
        echoUser.setFloorId(message.floor_id);

        this.signal('message:received', {message: message, echoUser: echoUser});
    }

    /**
     * @param metadata {Object|undefined}
     * @returns {EchoUser|null} The connected user that sent the whisper, or null if they are not a member of the presence channel
     * @private
     */
    _getSender(metadata) {
        if (typeof metadata !== 'object' || metadata === null ||
            typeof metadata.user_id === 'undefined' || metadata.user_id === null) {
            return null;
        }

        let member = this.presenceChannel.subscription.members.get(metadata.user_id);
        if (member === null || typeof member.info !== 'object' || member.info === null ||
            typeof member.info.public_key !== 'string') {
            return null;
        }

        return this.echo.getUserByPublicKey(member.info.public_key);
    }
}

if (typeof module !== 'undefined' && module.exports) {
    module.exports = WhisperMessageHandler;
}
