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
    setup(presenceChannel){
        presenceChannel.listen(this.getMessage(), this.onReceive.bind(this));
    }

    /**
     *
     * @param e
     */
    onReceive(e) {
        let echoUser = this.echo.getUserById(e.user.id);

        // Floor ID is always set - so set it here
        if( echoUser !== null ) {
            echoUser.setFloorId(e.floor_id);
        }

        this.signal('message:received', e);
    }
}