class ModelChangedHandler extends BaseModelHandler {
    /**
     * Checks if a received _changed_ event is applicable to this map object group.
     *
     * @param e {Object}
     * @returns {boolean}
     * @private
     */
    _shouldHandleChangedEchoEvent(e) {
        console.assert(this instanceof ModelChangedHandler, 'this is not a ModelChangedHandler', this);
        console.assert(typeof e.model !== 'undefined', 'model was not defined in received event!', this, e);
        console.assert(typeof e.model.floor_id !== 'undefined', 'model.floor_id was not defined in received event!', this, e);

        // floor -1 means it's omnipresent (such as killzones)
        // @TODO support facades?
        return this._shouldHandleEchoEvent(e) && (e.model.floor_id === getState().getCurrentFloor().id || e.model.floor_id === -1);
    }


    /**
     *
     * @param e {KillZoneChangedMessage}
     * @return boolean
     */
    onReceive(e) {
        super.onReceive(e);

        return this._shouldHandleChangedEchoEvent(e);
    }
}
