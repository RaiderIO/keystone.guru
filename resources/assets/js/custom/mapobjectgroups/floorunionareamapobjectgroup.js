class FloorUnionAreaMapObjectGroup extends PolygonMapObjectGroup {
    constructor(manager, editable) {
        super(manager, MAP_OBJECT_GROUP_FLOOR_UNION_AREA, editable);

        this.fa_class = 'fa-chart-pie';

        this._setColor(c.map.floorunionarea.color);
    }

    /**
     * @inheritDoc
     **/
    _getRawObjects() {
        return getState().getMapContext().getFloorUnionAreas();
    }

    _createMapObject(layer, options = {}) {
        console.assert(this instanceof FloorUnionAreaMapObjectGroup, 'this is not an FloorUnionAreaMapObjectGroup', this);

        return new FloorUnionArea(this.manager.map, layer);
    }
}
