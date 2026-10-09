class AdminDungeonTransport extends DungeonTransport {
    constructor(map, layer) {
        super(map, layer);

        this.setSynced(false);

        // The popup's linked transport select lists the other transports, which may load or change after ours
        this._getMapObjectGroup().register(['loadcomplete', 'save:success', 'object:deleted'], this, () => this._assignPopup());
    }

    /**
     * @inheritDoc
     */
    onLayerInit() {
        console.assert(this instanceof AdminDungeonTransport, 'this is not an AdminDungeonTransport', this);
        super.onLayerInit();

        // Clicking opens the edit popup in the mapping editor instead of travelling
        this.layer.off('click');
    }

    /**
     * @inheritDoc
     */
    cleanup() {
        super.cleanup();

        this._getMapObjectGroup().unregister(['loadcomplete', 'save:success', 'object:deleted'], this);
    }
}
