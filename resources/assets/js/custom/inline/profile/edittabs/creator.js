/**
 * @typedef {Object} ProfileEdittabsCreatorOptions
 * @property {string} pinnedRoutesTableSelector
 * @property {string} pinnedRoutesTableInlineId
 * @property {string} pinnedRoutesOrderedSelectInlineId
 * @property {string} pinnedRoutesListSelector
 * @property {Object.<string, Number>} dungeonRouteIdsByPublicKey Every route that may be pinned.
 */

/**
 * Ticking a route in the selectable route table pins it, by adding it to the ordered list of pinned routes (which
 * posts the ids, in the order the user arranged); removing it from that list unticks it again.
 *
 * @property {ProfileEdittabsCreatorOptions} options
 */
class ProfileEdittabsCreator extends InlineCode {

    activate() {
        super.activate();

        $(this.options.pinnedRoutesTableSelector).on('dungeonroutetable:selectionchanged', this._onTableSelectionChanged.bind(this));
        $(this.options.pinnedRoutesListSelector).on('orderedselect:changed', this._syncTableToList.bind(this));
    }

    /**
     * @param {Event} event
     * @param {{publicKey: string, selected: boolean, row: Object|null}} change
     * @private
     */
    _onTableSelectionChanged(event, change) {
        let orderedSelect = _inlineManager.getInlineCodeById(this.options.pinnedRoutesOrderedSelectInlineId);
        let id = this.options.dungeonRouteIdsByPublicKey[change.publicKey];

        if (orderedSelect !== undefined && id !== undefined) {
            if (!change.selected) {
                orderedSelect.removeItem(id);
            } else if (change.row !== null) {
                orderedSelect.addItem(id, `${change.row.title} — ${lang.get(change.row.dungeon.name)}`);
            }
        }

        // A route that cannot be pinned is unticked again
        this._syncTableToList();
    }

    /**
     * @private
     */
    _syncTableToList() {
        let orderedSelect = _inlineManager.getInlineCodeById(this.options.pinnedRoutesOrderedSelectInlineId);
        let table = _inlineManager.getInlineCodeById(this.options.pinnedRoutesTableInlineId);
        if (orderedSelect === undefined || table === undefined) {
            return;
        }

        let publicKeysById = {};
        for (let [publicKey, id] of Object.entries(this.options.dungeonRouteIdsByPublicKey)) {
            publicKeysById[id] = publicKey;
        }

        table.setSelectedPublicKeys(
            orderedSelect.getIds()
                .map(id => publicKeysById[id])
                .filter(publicKey => publicKey !== undefined)
        );
    }
}
