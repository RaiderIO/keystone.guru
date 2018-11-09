class AdminDungeonMap extends DungeonMap {

    constructor(mapid, dungeonData, options) {
        super(mapid, dungeonData, options);
    }

    /**
     * Create instances of all controls that will be added to the map (UI on the map itself)
     * @param drawnItemsLayer
     * @returns {*[]}
     * @private
     */
    _createMapControls(drawnItemsLayer) {
        console.assert(this instanceof DungeonMap, this, 'this is not a DungeonMap');

        let result = [
            new AdminDrawControls(this, drawnItemsLayer),
            new EnemyVisualControls(this),
            new MapObjectGroupControls(this)
        ];

        // @TODO This breaks the admin
        // if (this.dungeonData.name === 'Siege of Boralus') {
        //     result.push(new FactionDisplayControls(this));
        // }

        return result;
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
            new MapCommentMapObjectGroup(this, 'mapcomment', 'AdminMapComment', true),
        ];
    }

    refreshLeafletMap() {
        super.refreshLeafletMap();


        let self = this;

        this.enemyAttaching = new EnemyAttaching(this);
    }

    /**
     * There's no try mode for admins, they just know.
     * @returns {boolean}
     */
    isTryModeEnabled() {
        return false;
    }
}