class KillZoneDeletedHandler extends MessageHandler {

    constructor(echo) {
        super(echo, '.killzone-deleted');
    }

    /**
     *
     * @param e {KillZoneDeletedMessage}
     */
    onReceive(e) {
        super.onReceive(e);

        console.log(`KillZoneDeletedHandler::onReceive: ${e.model_id} ${e.model_class}`);
    }
}
