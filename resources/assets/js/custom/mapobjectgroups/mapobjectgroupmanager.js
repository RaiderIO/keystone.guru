const MAP_OBJECT_GROUP_ENEMY = 'enemy';
const MAP_OBJECT_GROUP_ENEMY_PATROL = 'enemypatrol';
const MAP_OBJECT_GROUP_ENEMY_PACK = 'enemypack';
const MAP_OBJECT_GROUP_PATH = 'path';
const MAP_OBJECT_GROUP_KILLZONE = 'killzone';
const MAP_OBJECT_GROUP_BRUSHLINE = 'brushline';
const MAP_OBJECT_GROUP_MAPCOMMENT = 'mapcomment';
const MAP_OBJECT_GROUP_DUNGEON_START_MARKER = 'dungeonstartmarker';
const MAP_OBJECT_GROUP_DUNGEON_FLOOR_SWITCH_MARKER = 'dungeonfloorswitchmarker';

const MAP_OBJECT_GROUP_NAMES = [
    MAP_OBJECT_GROUP_ENEMY,
    MAP_OBJECT_GROUP_ENEMY_PATROL,
    MAP_OBJECT_GROUP_ENEMY_PACK,
    MAP_OBJECT_GROUP_PATH,
    MAP_OBJECT_GROUP_KILLZONE,
    MAP_OBJECT_GROUP_BRUSHLINE,
    MAP_OBJECT_GROUP_MAPCOMMENT,
    MAP_OBJECT_GROUP_DUNGEON_START_MARKER,
    MAP_OBJECT_GROUP_DUNGEON_FLOOR_SWITCH_MARKER
];

class MapObjectGroupManager extends Signalable {

    constructor(map, mapObjectGroupNames) {
        super();
        this.map = map;

        this.mapObjectGroups = [];
        for (let i = 0; i < mapObjectGroupNames.length; i++) {
            this.mapObjectGroups.push(this._createMapObjectGroup(mapObjectGroupNames[i]));
        }

        this.map.register('map:refresh', this, this._fetchFromServer.bind(this));
    }

    /**
     * Creates a map object group based off a passed name.
     * @param name
     * @returns {*}
     * @private
     */
    _createMapObjectGroup(name) {
        console.assert(this instanceof MapObjectGroupManager, 'this is not a MapObjectGroupManager', this);

        let result = null;

        if (name === MAP_OBJECT_GROUP_ENEMY) {
            result = new EnemyMapObjectGroup(this, MAP_OBJECT_GROUP_ENEMY, 'Enemy', isAdmin);
        } else if (name === MAP_OBJECT_GROUP_ENEMY_PATROL) {
            result = new EnemyPatrolMapObjectGroup(this, MAP_OBJECT_GROUP_ENEMY_PATROL, 'EnemyPatrol', isAdmin);
        } else if (name === MAP_OBJECT_GROUP_ENEMY_PACK) {
            result = new EnemyPackMapObjectGroup(this, MAP_OBJECT_GROUP_ENEMY_PACK, 'EnemyPack', isAdmin);
        } else if (name === MAP_OBJECT_GROUP_PATH) {
            result = new PathMapObjectGroup(this, MAP_OBJECT_GROUP_PATH, !isAdmin);
        } else if (name === MAP_OBJECT_GROUP_KILLZONE) {
            result = new KillZoneMapObjectGroup(this, MAP_OBJECT_GROUP_KILLZONE, !isAdmin);
        } else if (name === MAP_OBJECT_GROUP_BRUSHLINE) {
            result = new BrushlineMapObjectGroup(this, MAP_OBJECT_GROUP_BRUSHLINE, !isAdmin);
        } else if (name === MAP_OBJECT_GROUP_MAPCOMMENT) {
            result = new MapCommentMapObjectGroup(this, MAP_OBJECT_GROUP_MAPCOMMENT, !isAdmin);
        } else if (name === MAP_OBJECT_GROUP_DUNGEON_START_MARKER) {
            result = new DungeonStartMarkerMapObjectGroup(this, MAP_OBJECT_GROUP_DUNGEON_START_MARKER, 'DungeonStartMarker', isAdmin);
        } else if (name === MAP_OBJECT_GROUP_DUNGEON_FLOOR_SWITCH_MARKER) {
            result = new DungeonFloorSwitchMarkerMapObjectGroup(this, MAP_OBJECT_GROUP_DUNGEON_FLOOR_SWITCH_MARKER, 'DungeonFloorSwitchMarker', isAdmin);
        }

        console.assert(result !== null, 'Unable to find map object group ' + name, this);

        return result;
    }

    /**
     * Get the names of all loaded map object groups.
     * @returns {Array}
     * @private
     */
    _getLoadedNames() {
        console.assert(this instanceof MapObjectGroupManager, 'this is not a MapObjectGroupManager', this);

        let result = [];
        for (let i = 0; i < this.mapObjectGroups.length; i++) {
            result.push(this.mapObjectGroups[i].name);
        }

        return result;
    }

    /**
     * Retrieves a map object group by its name.
     * @param name
     * @returns {boolean}|{MapObjectGroup}
     */
    getByName(name) {
        console.assert(this instanceof MapObjectGroupManager, 'this is not a MapObjectGroupManager', this);

        let result = false;
        for (let i = 0; i < this.mapObjectGroups.length; i++) {
            if (this.mapObjectGroups[i].name === name) {
                result = this.mapObjectGroups[i];
            }
        }

        return result;
    }

    /**
     * Set the visibility of a map object group.
     * @param objectGroupName object The name of the group to hide/show.
     * @param visible boolean True to display, false to hide.
     */
    setVisibility(objectGroupName, visible) {
        console.assert(this instanceof MapObjectGroupManager, 'this is not a MapObjectGroupManager', this);

        let objectGroup = this.getByName(objectGroupName);
        console.assert(objectGroup instanceof MapObjectGroup, objectGroup, 'objectGroup is not a MapObjectGroup');

        // @TODO Move this to mapobject instead? But then mapobject will have a dependency on their map object group which
        // I may or may not want
        objectGroup.setVisibility(visible);
    }

    /**
     * Refreshes the objects that are displayed on the map based on the current dungeon & selected floor.
     */
    _fetchFromServer() {
        console.assert(this instanceof MapObjectGroupManager, 'this is not a MapObjectGroupManager', this);

        let self = this;

        $.ajax({
            type: 'GET',
            url: '/ajax/' + this.map.getDungeonRoute().publicKey + '/data',
            dataType: 'json',
            data: {
                fields: this._getLoadedNames().join(','),
                floor: this.map.getCurrentFloor().id
            },
            success: function (json) {
                self.signal('fetchsuccess', {response: json});
            }
        });
    }
}