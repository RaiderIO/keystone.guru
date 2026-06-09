class ArrowDeletedHandler extends ModelDeletedHandler {

    constructor(echo) {
        super(echo, ArrowDeletedMessage.getName());
    }

    /**
     *
     * @param e {ArrowDeletedMessage}
     */
    onReceive(e) {
        let shouldHandle = super.onReceive(e);

        console.log(`ArrowDeletedHandler::onReceive: ${e.model_id} ${e.model_class}`);

        if (shouldHandle) {
            let arrowMapObjectGroup = this.echo.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_ARROW);

            let mapObject = arrowMapObjectGroup.findMapObjectById(e.model_id);
            if (mapObject !== null) {
                mapObject.localDelete();
                this._showDeletedFromEcho(mapObject, e.user);
            }
        }

        return shouldHandle;
    }
}
