/**
 * @property {string} name
 * @property {string} type
 * @property {Array} values
 * @property {boolean} admin
 * @property {boolean} show_default
 * @property {boolean} edit
 * @property {boolean} save
 */
class Attribute {
    constructor(options) {
        this.options = options;

        for (let index in this.options) {
            if (this.options.hasOwnProperty(index)) {
                // Make all options accessible by directly calling attribute.<property>
                this[index] = this.options[index];
            }
        }
    }

    /**
     * If `admin` is true and we are in an admin map, return true. If not set or of `admin` is false (and we are NOT in an admin state)
     * return true as well. False in all other cases.
     * @returns {boolean}
     */
    isEditableAdmin() {
        return (
            // If set & the admin state is what we're looking for
            (this.options.hasOwnProperty('admin') && this.options.admin === getState().isMapAdmin()) ||
            // Or if it's not set at all
            !this.options.hasOwnProperty('admin')
        )
    }

    /**
     * Checks if the attribute may be editable in the current map state.
     * @returns {boolean}
     */
    isEditable() {
        let edit = this.options.hasOwnProperty('edit') ? this.options.edit : true;
        // If we want to display this attribute at all
        return edit && this.isEditableAdmin();
    }

    /**
     * Checks if this property may be saved to the database or not.
     * @returns {*}
     */
    isSaveable() {
        // Save by default, but allow people to override the behaviour
        return this.options.hasOwnProperty('save') ? this.options.save : true;
    }
}
