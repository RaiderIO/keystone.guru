class ModelChangedHandler extends BaseModelHandler {
    // /**
    //  * Checks if a received _changed_ event is applicable to this map object group.
    //  *
    //  * @param e {Object}
    //  * @returns {boolean}
    //  * @private
    //  */
    // _shouldHandleChangedEchoEvent(e) {
    //     console.assert(this instanceof ModelChangedHandler, 'this is not a ModelChangedHandler', this);
    //     console.assert(typeof e.model !== 'undefined', 'model was not defined in received event!', this, e);
    //     console.assert(typeof e.model.floor_id !== 'undefined', 'model.floor_id was not defined in received event!', this, e);
    //
    //     // floor -1 means it's omnipresent (such as killzones)
    //     let result = this._shouldHandleEchoEvent(e);
    //
    //     return result;

    // let state = getState();
    // let currentFloorId = state.getCurrentFloor().id;
    //
    // if (typeof e.model.model_data !== 'undefined' && typeof e.model.model_data.coordinates !== 'undefined') {
    //     result = result && (
    //         // Use facade?
    //         state.isCurrentDungeonFacadeEnabled() ?
    //             //
    //             e.model.model_data.coordinates.facade.floor_id === currentFloorId :
    //             e.model.model_data.coordinates.split_floors.floor_id === currentFloorId
    //     )
    // } else {
    //     result = result && (e.model.floor_id === currentFloorId || e.model.floor_id === -1);
    // }
    //
    // return result;
    // }

    /**
     *
     * @param e
     * @param expectsCoordinates {boolean}
     * @returns {MessageCoordinate|MessageCoordinate[]}
     * @protected
     */
    _getCorrectLatLngFromEvent(e, expectsCoordinates = true) {
        console.assert(this instanceof ModelChangedHandler, 'this is not a ModelChangedHandler', this);

        let result;

        if (typeof e.model_data !== 'undefined' && typeof e.model_data.coordinates !== 'undefined') {
            let coordinates = e.model_data.coordinates;
            let state = getState();

            if (state.isCurrentDungeonFacadeEnabled() && state.getMapFacadeStyle() === MAP_FACADE_STYLE_FACADE) {
                result = coordinates.facade;
            } else {
                result = coordinates.split_floors;
            }
        } else {
            if (expectsCoordinates) {
                console.error('No coordinates found in changed event!', e);
            }
            result = false;
        }

        return result;
    }

    /**
     *
     * @param localMapObject {MapObject}
     * @param user {Object}
     * @protected
     */
    _showChangedFromEchoNotification(localMapObject, user) {
        console.assert(this instanceof ModelChangedHandler, 'this is not a ModelChangedHandler', this);

        let state = getState();
        if (state.isEchoEnabled() && state.getUser().public_key !== user.public_key && user.name !== null) {
            showInfoNotification(lang.get('js.echo_object_changed_notification', {
                object: localMapObject.toString(),
                user: user.name
            }));
        }
    }
}
