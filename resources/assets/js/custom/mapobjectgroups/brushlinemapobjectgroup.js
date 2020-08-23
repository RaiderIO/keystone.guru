class BrushlineMapObjectGroup extends PolylineMapObjectGroup {
    constructor(manager, editable) {
        super(manager, MAP_OBJECT_GROUP_BRUSHLINE, editable);

        this.title = 'Hide/show brushlines';
        this.fa_class = 'fa-paint-brush';
    }

    /**
     * @inheritDoc
     **/
    _getRawObjects() {
        return getState().getMapContext().getBrushlines();
    }

    _createMapObject(layer, options = {}) {
        console.assert(this instanceof BrushlineMapObjectGroup, 'this is not an BrushlineMapObjectGroup', this);

        return new Brushline(this.manager.map, layer);
    }
}