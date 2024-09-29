class AdminMountableArea extends MountableArea {
    constructor(map, layer) {
        super(map, layer, {name: 'mountablearea', has_route_model_binding: true});

        this.color = null;
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
