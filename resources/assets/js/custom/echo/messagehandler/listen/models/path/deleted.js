class PathDeletedHandler extends ModelDeletedHandler {

    constructor(echo) {
        super(echo, PathDeletedMessage.getName());
    }

    /**
     *
     * @param e {PathDeletedMessage}
     */
    onReceive(e) {
        let shouldHandle = super.onReceive(e);

        console.log(`PathDeletedHandler::onReceive: ${e.model_id} ${e.model_class}`);

        if (shouldHandle) {
            let pathMapObjectGroup = this.echo.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_PATH);

            let mapObject = pathMapObjectGroup.findMapObjectById(e.model_id);
            if (mapObject !== null) {
                mapObject.localDelete();
                this._showDeletedFromEcho(mapObject, e.user);
            }
        }

        return shouldHandle;
    }
}
