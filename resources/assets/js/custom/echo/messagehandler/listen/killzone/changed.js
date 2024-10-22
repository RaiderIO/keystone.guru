class KillZoneChangedHandler extends MessageHandler {

    constructor(echo) {
        super(echo, '.killzone-changed');
    }


    /**
     *
     * @param e {KillZoneChangedMessage}
     */
    onReceive(e) {
        super.onReceive(e);

        console.log(`KillZoneChangedHandler::onReceive:`, e);
    }
}
