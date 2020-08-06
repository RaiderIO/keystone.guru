class MapContextDungeon extends MapContext {
    constructor(options) {
        super(options);
    }

    /**
     *
     * @returns {String}
     */
    getPublicKey() {
        return 'admin';
    }

    /**
     * @inheritDoc
     **/
    getTeeming() {
        return true;
    }
}