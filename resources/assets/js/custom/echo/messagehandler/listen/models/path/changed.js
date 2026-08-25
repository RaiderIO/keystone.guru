class PathChangedHandler extends ModelChangedHandler {

    constructor(echo) {
        super(echo, PathChangedMessage.getName());

        // Coordinates are now fetched asynchronously (see onReceive below), so two changed
        // events for the same path arriving close together may have their GET requests resolve
        // out of order. Track the most recently received event per path id and let a stale
        // response's callback no-op instead of overwriting newer state.
        this._latestRequestIdByModelId = {};
    }

    /**
     *
     * @param e {PathChangedMessage}
     * @return boolean
     */
    onReceive(e) {
        let shouldHandle = super.onReceive(e);

        console.log(`PathChangedHandler::onReceive:`, shouldHandle, e);
        if (shouldHandle) {
            let self = this;
            let pathMapObjectGroup = this.echo.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_PATH);

            let requestId = (this._latestRequestIdByModelId[e.model.id] || 0) + 1;
            this._latestRequestIdByModelId[e.model.id] = requestId;

            // The broadcast no longer carries the path's coordinates - a path can have enough
            // vertices to exceed Reverb's message size cap (#3909) - so fetch them from the
            // server instead.
            $.ajax({
                type: 'GET',
                url: `/ajax/${getState().getMapContext().getPublicKey()}/path/${e.model.id}`,
                dataType: 'json',
                success: function (json) {
                    // A newer changed event for this path arrived and issued its own request
                    // while this one was in flight - let that one win instead.
                    if (self._latestRequestIdByModelId[e.model.id] !== requestId) {
                        return;
                    }

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

                    let mapObject = pathMapObjectGroup.loadMapObject(e.model, null, e.user);
                    pathMapObjectGroup.setMapObjectVisibility(mapObject, mapObject.shouldBeVisible());
                }
            });
        }

        return shouldHandle;
    }
}
