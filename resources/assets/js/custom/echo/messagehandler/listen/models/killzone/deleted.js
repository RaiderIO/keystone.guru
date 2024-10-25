class KillZoneDeletedHandler extends ModelDeletedHandler {

    constructor(echo) {
        super(echo, KillZoneDeletedMessage.getName());
    }

    /**
     *
     * @param e {KillZoneDeletedMessage}
     */
    onReceive(e) {
        let shouldHandle = super.onReceive(e);

        console.log(`KillZoneDeletedHandler::onReceive: ${e.model_id} ${e.model_class}`);

        if (shouldHandle) {

            let killZoneMapObjectGroup = this.echo.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_KILLZONE);

            let mapObject = killZoneMapObjectGroup.findMapObjectById(e.model_id);
            if (mapObject !== null) {
                mapObject.localDelete();
                this._showDeletedFromEcho(mapObject, e.user);
            }
        }

        return shouldHandle;
    }
}
