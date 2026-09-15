class BrushlineDeletedHandler extends ModelDeletedHandler {

    constructor(echo) {
        super(echo, BrushlineDeletedMessage.getName());
    }

    /**
     *
     * @param e {BrushlineDeletedMessage}
     */
    onReceive(e) {
        let shouldHandle = super.onReceive(e);

        console.log(`BrushlineDeletedHandler::onReceive: ${e.model_id} ${e.model_class}`);

        if (shouldHandle) {
            let brushlineMapObjectGroup = this.echo.map.mapObjectGroupManager.getBrushlineMapObjectGroup();

            let mapObject = brushlineMapObjectGroup.findMapObjectById(e.model_id);
            if (mapObject !== null) {
                mapObject.localDelete();
                this._showDeletedFromEcho(mapObject, e.user);
            }
        }

        return shouldHandle;
    }
}
