class AdminMountableArea extends MountableArea {
    constructor(map, layer) {
        super(map, layer);
    }

    isEditableByPopup() {
        return true;
    }

    toString() {
        console.assert(this instanceof AdminMountableArea, 'this is not an AdminMountableArea', this);

        return 'Mountable area-' + this.id;
    }

    cleanup() {
        console.assert(this instanceof AdminMountableArea, 'this is not an AdminMountableArea', this);

        super.cleanup();
    }
}
