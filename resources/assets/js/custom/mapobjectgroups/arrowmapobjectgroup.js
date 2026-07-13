class ArrowMapObjectGroup extends PolylineMapObjectGroup {
    constructor(manager, editable) {
        super(manager, MAP_OBJECT_GROUP_ARROW, editable);

        this.fa_class = 'fa-arrow-right';
    }

    /**
     * @inheritDoc
     **/
    _getRawObjects() {
        return getState().getMapContext().getArrows();
    }

    _createMapObject(layer, options = {}) {
        console.assert(this instanceof ArrowMapObjectGroup, 'this is not an ArrowMapObjectGroup', this);

        return new Arrow(this.manager.map, layer);
    }
}
