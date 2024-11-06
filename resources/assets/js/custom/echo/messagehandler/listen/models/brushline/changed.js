class BrushlineChangedHandler extends ModelChangedHandler {

    constructor(echo) {
        super(echo, BrushlineChangedMessage.getName());
    }

    /**
     *
     * @param e {BrushlineChangedMessage}
     * @return boolean
     */
    onReceive(e) {
        let shouldHandle = super.onReceive(e);

        console.log(`BrushlineChangedHandler::onReceive:`, shouldHandle, e);
        if (shouldHandle) {
            let brushlineMapObjectGroup = this.echo.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_BRUSHLINE);

            // // Apply the correct coordinates for our choice of facade
            // let coordinates = this._getCorrectLatLngFromEvent(e);
            //
            // e.model = $.extend({}, e.model, coordinates);
            //
            // let mapObject = brushlineMapObjectGroup.loadMapObject(e.model, null, e.user);
            // brushlineMapObjectGroup.setMapObjectVisibility(mapObject, mapObject.shouldBeVisible());
        }

        return shouldHandle;
    }
}
