class MapIconDeletedHandler extends ModelDeletedHandler {

    constructor(echo) {
        super(echo, MapIconDeletedMessage.getName());
    }

    /**
     *
     * @param e {MapIconDeletedMessage}
     */
    onReceive(e) {
        let shouldHandle = super.onReceive(e);

        console.log(`MapIconDeletedHandler::onReceive: ${e.model_id} ${e.model_class}`);

        if (shouldHandle) {

            let mapIconMapObjectGroup = this.echo.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_MAPICON);

            let mapObject = mapIconMapObjectGroup.findMapObjectById(e.model_id);
            if (mapObject !== null) {
                mapObject.localDelete();
                this._showDeletedFromEcho(mapObject, e.user);
            }
        }

        return shouldHandle;
    }
}
