class InlineCode {


    constructor(id, bladePath, options) {
        this.id = id;
        this.bladePath = bladePath;
        this.options = options;

        this._activated = false;
    }

    /**
     * Checks if activate() has been called already.
     * @returns {boolean}
     */
    isActivated() {
        return this._activated;
    }

    /**
     *
     */
    activate() {
        this._activated = true;
    }

    /**
     * Cleans up the inline code (though this is not called anywhere just yet).
     */
    cleanup() {

    }
}

// Guarded export for the test runner (Vitest). This is a no-op in the browser,
// where `module` is undefined, so it does not affect the concatenated bundle.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {InlineCode};
}
