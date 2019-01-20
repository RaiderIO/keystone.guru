class AdminDungeonMap extends DungeonMap {

    constructor(mapid, dungeonData, options) {
        super(mapid, dungeonData, options);
        this.currentMDTEnemyMappingEnemy = null;
    }

    /**
     * Create instances of all controls that will be added to the map (UI on the map itself)
     * @param drawnItemsLayer
     * @returns {*[]}
     * @private
     */
    _createMapControls(drawnItemsLayer) {
        console.assert(this instanceof DungeonMap, this, 'this is not a DungeonMap');
        let result = [];

        // @TODO This breaks the admin
        if (this.dungeonData.name === 'Siege of Boralus') {
            result.push(new FactionDisplayControls(this));
        }

        result.push(new AdminDrawControls(this, drawnItemsLayer));
        result.push(new EnemyVisualControls(this));
        result.push(new MapObjectGroupControls(this));

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
     * Gets if there is currently an MDT enemy being mapped to a Keystone.guru enemy.
     * @returns {boolean}
     */
    isMDTEnemyMappingModeEnabled() {
        return this.currentMDTEnemyMappingEnemy !== null;
    }

    /**
     * Sets the MDT enemy that is currently being mapped to a Keystone.guru enemy.
     * @param enemy
     */
    setMDTEnemyMappingEnemy(enemy = null) {
        console.assert(enemy.is_mdt, enemy, 'setMDTEnemyMappingEnemy enemy is not an MDT enemy');

        let changed = this.currentMDTEnemyMappingEnemy !== enemy;
        let previousEnemy = this.currentMDTEnemyMappingEnemy;
        this.currentMDTEnemyMappingEnemy = enemy;
        if (changed) {
            this.signal('map:mdtenemymappingenenemychanged', {previousEnemy: previousEnemy, enemy: enemy});
        }
    }

    /**
     * There's no try mode for admins, they just know.
     * @returns {boolean}
     */
    isTryModeEnabled() {
        return false;
    }
}