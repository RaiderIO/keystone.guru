class NpcDeletedHandler extends MessageHandler {

    constructor(echo) {
        super(echo, NpcDeletedMessage.getName());
    }


    onReceive(e) {
        super.onReceive(e);
        let mapContext = getState().getMapContext();

        // Remove any existing NPC
        mapContext.removeRawNpcById(e.model.id);
    }
}
