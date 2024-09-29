class EnemyPatrolMapObjectGroup extends PolylineMapObjectGroup {
    constructor(manager, editable) {
        super(manager, MAP_OBJECT_GROUP_ENEMY_PATROL, editable);

        this.fa_class = 'fa-exchange-alt';
    }

    _getPolylineOptions() {
        return c.map.enemypatrol.polylineOptions;
    }

    /**
     * @inheritDoc
     **/
    _getRawObjects() {
        return getState().getMapContext().getEnemyPatrols();
    }

    /**
     * @inheritDoc
     */
    _createMapObject(layer, options = {}) {
        console.assert(this instanceof EnemyPatrolMapObjectGroup, 'this is not an EnemyPatrolMapObjectGroup', this);

        if (getState().isMapAdmin()) {
            return new AdminEnemyPatrol(this.manager.map, layer);
        } else {
            return new EnemyPatrol(this.manager.map, layer);
        }
    }
}
