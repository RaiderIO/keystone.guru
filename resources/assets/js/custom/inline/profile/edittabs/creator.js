/**
 * @typedef {Object} ProfileEdittabsCreatorOptions
 * @property {string} pickerSelector                         The route picker drawer.
 * @property {string} pickerInlineId
 * @property {string} orderedSelectSelector                  Root of the ordered list of pinned routes.
 * @property {string} orderedSelectInlineId
 * @property {Object<string, Number>} dungeonRouteIdsByPublicKey Every route that may be pinned.
 */

/**
 * Routes picked in the route picker drawer are appended to the ordered list of pinned routes, which posts their ids
 * in the order the user arranged; a route removed from that list can be picked again.
 *
 * @property {ProfileEdittabsCreatorOptions} options
 */
class ProfileEdittabsCreator extends InlineCode {

    activate() {
        super.activate();

        $(this.options.pickerSelector).on('dungeonroutepicker:confirmed', this._onPicked.bind(this));
        $(this.options.orderedSelectSelector).on('orderedselect:removed', this._refresh.bind(this));
    }

    /**
     * @param {Event} event
     * @param {CommonDungeonroutePickerResult} result
     * @private
     */
    _onPicked(event, result) {
        let orderedSelect = this._getOrderedSelect();

        (result.dungeonRoutes || []).forEach(dungeonRoute => {
            let id = this.options.dungeonRouteIdsByPublicKey[dungeonRoute.publicKey];

            if (typeof id !== 'undefined') {
                orderedSelect.addItem(id, `${dungeonRoute.title} — ${dungeonRoute.getDungeonName()}`);
            }
        });

        this._refresh();
    }

    /**
     * The list leaves counting towards its maximum to the host page, and the drawer must know which routes are pinned.
     * @private
     */
    _refresh() {
        let orderedSelect = this._getOrderedSelect();
        let publicKeysById = {};

        for (let [publicKey, id] of Object.entries(this.options.dungeonRouteIdsByPublicKey)) {
            publicKeysById[id] = publicKey;
        }

        let ids = orderedSelect.getIds();
        orderedSelect.setFullCount(ids.length);
        _inlineManager.getInlineCodeById(this.options.pickerInlineId).setExistingPublicKeys(ids.map(id => publicKeysById[id]));
    }

    /**
     * @returns {CommonFormsOrderedselect}
     * @private
     */
    _getOrderedSelect() {
        return _inlineManager.getInlineCodeById(this.options.orderedSelectInlineId);
    }
}

// Guarded export for the test runner (Vitest). This is a no-op in the browser,
// where `module` is undefined, so it does not affect the concatenated bundle.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {ProfileEdittabsCreator};
}
