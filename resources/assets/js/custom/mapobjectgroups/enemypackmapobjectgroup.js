class EnemyPackMapObjectGroup extends PolygonMapObjectGroup {
    constructor(manager, editable) {
        super(manager, MAP_OBJECT_GROUP_ENEMY_PACK, editable);

        this.fa_class = 'fa-draw-polygon';
    }

    /**
     * @inheritDoc
     **/
    _getRawObjects() {
        return getState().getMapContext().getEnemyPacks();
    }

    _createMapObject(layer, options = {}) {
        console.assert(this instanceof EnemyPackMapObjectGroup, 'this is not an EnemyPackMapObjectGroup', this);

        if (getState().isMapAdmin()) {
            return new AdminEnemyPack(this.manager.map, layer);
        } else {
            return new EnemyPack(this.manager.map, layer);
        }
    }
}
