/**
 * The selected rows of a route table, keyed by public key and kept in the order they were selected. The set lives
 * here rather than in the checkboxes, since a redraw (paging, sorting) replaces every row of the table.
 */
class DungeonRouteTableSelection {
    /**
     * @param {string[]} selectedPublicKeys The rows that start out selected, in selection order.
     * @param {Number|null} max At most this many rows may be selected; null for no limit.
     */
    constructor(selectedPublicKeys = [], max = null) {
        this._max = typeof max === 'number' ? max : null;
        this._selectedPublicKeys = [];
        this.setSelectedPublicKeys(selectedPublicKeys);
    }

    /**
     * @returns {string[]} The selected public keys, in selection order.
     */
    getSelectedPublicKeys() {
        return this._selectedPublicKeys.slice();
    }

    /**
     * Replaces the selection; duplicates and anything past the maximum are dropped.
     * @param {string[]} publicKeys
     */
    setSelectedPublicKeys(publicKeys) {
        this._selectedPublicKeys = [];
        publicKeys.forEach(publicKey => this.select(publicKey));
    }

    /**
     * @param {string} publicKey
     * @returns {boolean}
     */
    isSelected(publicKey) {
        return this._selectedPublicKeys.includes(publicKey);
    }

    /**
     * @returns {Number}
     */
    getCount() {
        return this._selectedPublicKeys.length;
    }

    /**
     * @returns {Number|null}
     */
    getMax() {
        return this._max;
    }

    /**
     * @returns {boolean}
     */
    isFull() {
        return this._max !== null && this._selectedPublicKeys.length >= this._max;
    }

    /**
     * @param {string} publicKey
     * @returns {boolean} Whether the row's checkbox may be ticked or unticked right now.
     */
    canToggle(publicKey) {
        return this.isSelected(publicKey) || !this.isFull();
    }

    /**
     * @param {string} publicKey
     * @returns {boolean} Whether the selection changed.
     */
    select(publicKey) {
        if (!this.canToggle(publicKey) || this.isSelected(publicKey)) {
            return false;
        }

        this._selectedPublicKeys.push(publicKey);
        return true;
    }

    /**
     * @param {string} publicKey
     * @returns {boolean} Whether the selection changed.
     */
    deselect(publicKey) {
        let index = this._selectedPublicKeys.indexOf(publicKey);
        if (index === -1) {
            return false;
        }

        this._selectedPublicKeys.splice(index, 1);
        return true;
    }

    /**
     * Ticks the checkboxes of the selected rows and disables the others once the selection is full.
     * @param {HTMLInputElement[]} checkboxes One per row; its value is the row's public key.
     */
    applyToCheckboxes(checkboxes) {
        checkboxes.forEach(checkbox => {
            checkbox.checked = this.isSelected(checkbox.value);
            checkbox.disabled = !this.canToggle(checkbox.value);
        });
    }
}

// Guarded export for the test runner (Vitest). This is a no-op in the browser,
// where `module` is undefined, so it does not affect the concatenated bundle.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {DungeonRouteTableSelection};
}
