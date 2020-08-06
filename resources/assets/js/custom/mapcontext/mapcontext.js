class MapContext extends Signalable {
    constructor(options) {
        super();

        this._options = options;
    }

    /**
     *
     * @returns {string}
     */
    getType() {
        return this._options.type;
    }

    /**
     *
     * @returns {null}
     */
    getFloorId(){
        return this._options.floorId;
    }

    /**
     *
     * @returns {Boolean}
     */
    getTeeming() {
        return this._options.teeming;
    }

    /**
     *
     * @param teeming {Boolean}
     */
    setTeeming(teeming) {
        this._options.teeming = teeming;

        // Let everyone know it's changed
        this.signal('teeming:changed', {teeming: this._options.teeming});
    }
}