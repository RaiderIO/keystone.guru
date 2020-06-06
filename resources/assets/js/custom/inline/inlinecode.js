class InlineCode {


    constructor(options) {
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