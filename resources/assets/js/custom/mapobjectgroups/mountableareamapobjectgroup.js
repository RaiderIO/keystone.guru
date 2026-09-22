class MountableAreaMapObjectGroup extends PolylineMapObjectGroup {
    constructor(manager, editable) {
        super(manager, MAP_OBJECT_GROUP_MOUNTABLE_AREA, editable);

        this.title = 'Hide/show mountable areas';
        this.fa_class = 'fa-horse-head';
    }

    /**
     * @inheritDoc
     */
    _getPolylineOptions() {
        return {color: c.map.mountablearea.color};
    }

    /**
     * @inheritDoc
     */
    _isClosedShape() {
        return true;
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

// Guarded export for the test runner (Vitest). This is a no-op in the browser,
// where `module` is undefined, so it does not affect the concatenated bundle.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {MountableAreaMapObjectGroup};
}
