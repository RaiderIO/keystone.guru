class AdminDungeonMap extends DungeonMap {

    constructor(mapid, dungeonData, floorID, edit) {
        // Always teeming on admin!
        super(mapid, dungeonData, floorID, edit, true);
    }

    /**
     * Create instances of all controls that will be added to the map (UI on the map itself)
     * @param drawnItemsLayer
     * @returns {*[]}
     * @private
     */
    _createMapControls(drawnItemsLayer){
        console.assert(this instanceof DungeonMap, this, 'this is not a DungeonMap');

        return [
            new AdminDrawControls(this, drawnItemsLayer),
            new MapObjectGroupControls(this)
        ]
    }

    /**
     *
     * @returns {[]}
     * @protected
     */
    _createMapObjectGroups() {
        // For this page, let the enemy pack be the admin version with more functions which are otherwise hidden from the user
        return [
            new EnemyMapObjectGroup(this, 'enemy', 'AdminEnemy', true),
            new EnemyPatrolMapObjectGroup(this, 'enemypatrol', 'AdminEnemyPatrol', true),
            new EnemyPackMapObjectGroup(this, 'enemypack', 'AdminEnemyPack', true),
            new DungeonStartMarkerMapObjectGroup(this, 'dungeonstartmarker', 'AdminDungeonStartMarker', true),
            new DungeonFloorSwitchMarkerMapObjectGroup(this, 'dungeonfloorswitchmarker', 'AdminDungeonFloorSwitchMarker', true),
        ];
    }

    refreshLeafletMap() {
        super.refreshLeafletMap();


        let self = this;

        this.enemyAttaching = new EnemyAttaching(this);
    }
}