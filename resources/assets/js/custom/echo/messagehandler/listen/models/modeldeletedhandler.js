class ModelDeletedHandler extends BaseModelHandler {

    /**
     * Checks if a received _deleted_ event is applicable to this map object group.
     *
     * @param e {Object}
     * @returns {boolean}
     * @private
     */
    _shouldHandleDeletedEchoEvent(e) {
        console.assert(this instanceof ModelDeletedHandler, 'this is not a ModelDeletedHandler', this);
        console.assert(typeof e.model_id !== 'undefined', 'model_id was not defined in received event!', this, e);

        return this._shouldHandleEchoEvent(e);
    }

    /**
     *
     * @param localMapObject {MapObject}
     * @param user {Object}
     * @protected
     */
    _showDeletedFromEcho(localMapObject, user) {
        console.assert(this instanceof ModelDeletedHandler, 'this is not a ModelDeletedHandler', this);

        let state = getState();
        if (state.isEchoEnabled() && state.getUser().public_key !== user.public_key && user.name !== null) {
            // The notification renders as HTML, and both values can hold another user's text
            showInfoNotification(lang.get('js.echo_object_deleted_notification', {
                object: Handlebars.escapeExpression(localMapObject.toString()),
                user: Handlebars.escapeExpression(user.name)
            }));
        }
    }

    /**
     *
     * @param e {KillZoneDeletedMessage}
     * @return boolean
     */
    onReceive(e) {
        super.onReceive(e);

        return this._shouldHandleDeletedEchoEvent(e);
    }
}

// Guarded export for the test runner (Vitest). This is a no-op in the browser,
// where `module` is undefined, so it does not affect the concatenated bundle.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        ModelDeletedHandler,
    };
}
