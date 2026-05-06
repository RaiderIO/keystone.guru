class BaseModelHandler extends MessageHandler {

    /**
     * @protected
     *
     * @param echo {EchoHandler}
     * @param message {String}
     */
    constructor(echo, message) {
        super(echo, message);
    }

    /**
     * Basic checks for if a received echo event is applicable to this model handler.
     *
     * @param e {Object}
     * @returns {boolean}
     * @protected
     */
    _shouldHandleEchoEvent(e) {
        console.assert(this instanceof BaseModelHandler, 'this is not a BaseModelHandler', this);

        // Do not process events that WE fired
        return e.user.public_key !== getState().getUser().public_key;
    }


    /**
     *
     * @param e
     * @return boolean
     */
    onReceive(e) {
        super.onReceive(e);

        return this._shouldHandleEchoEvent(e);
    }
}
