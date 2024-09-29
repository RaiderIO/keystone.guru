class MountableAreaMapObjectGroup extends PolygonMapObjectGroup {
    constructor(manager, editable) {
        super(manager, MAP_OBJECT_GROUP_MOUNTABLE_AREA, editable);

        this.title = 'Hide/show mountable areas';
        this.fa_class = 'fa-horse-head';

        this._setColor(c.map.mountablearea.color);
    }

    /**
     * @inheritDoc
     **/
    _getRawObjects() {
        return getState().getMapContext().getMountableAreas();
    }

    _createMapObject(layer, options = {}) {
        console.assert(this instanceof MountableAreaMapObjectGroup, 'this is not an MountableAreaMapObjectGroup', this);

        if (getState().isMapAdmin()) {
            return new AdminMountableArea(this.manager.map, layer);
        } else {
            return new MountableArea(this.manager.map, layer);
        }
    }
}
