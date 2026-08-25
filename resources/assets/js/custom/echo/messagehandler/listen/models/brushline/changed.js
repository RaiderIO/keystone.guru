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
            let self = this;
            let brushlineMapObjectGroup = this.echo.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_BRUSHLINE);

            // The broadcast no longer carries the brushline's coordinates - a brushline can have
            // enough vertices to exceed Reverb's message size cap (#3909) - so fetch them from
            // the server instead.
            $.ajax({
                type: 'GET',
                url: `/ajax/${getState().getMapContext().getPublicKey()}/brushline/${e.model.id}`,
                dataType: 'json',
                success: function (json) {
                    e.model_data = json.model_data;

                    // Apply the correct coordinates for our choice of facade
                    /** @type {MessageCoordinate[]} */
                    let coordinates = self._getCorrectLatLngFromEvent(e);
                    if (coordinates !== false) {
                        if (coordinates.length > 0) {
                            e.model.floor_id = coordinates[0].floor_id;
                        }
                        e.model.polyline.vertices_json = JSON.stringify(coordinates);
                    }

                    let mapObject = brushlineMapObjectGroup.loadMapObject(e.model, null, e.user);
                    brushlineMapObjectGroup.setMapObjectVisibility(mapObject, mapObject.shouldBeVisible());
                }
            });
        }

        return shouldHandle;
    }
}
