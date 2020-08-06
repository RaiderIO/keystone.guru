const MAP_OBJECT_GROUP_ENEMY = 'enemy';
const MAP_OBJECT_GROUP_ENEMY_PATROL = 'enemypatrol';
const MAP_OBJECT_GROUP_ENEMY_PACK = 'enemypack';
const MAP_OBJECT_GROUP_PATH = 'path';
const MAP_OBJECT_GROUP_KILLZONE = 'killzone';
const MAP_OBJECT_GROUP_BRUSHLINE = 'brushline';
const MAP_OBJECT_GROUP_MAPICON = 'mapicon';
const MAP_OBJECT_GROUP_MAPICON_AWAKENED_OBELISK = 'awakenedobeliskgatewaymapicon';
const MAP_OBJECT_GROUP_DUNGEON_FLOOR_SWITCH_MARKER = 'dungeonfloorswitchmarker';

const MAP_OBJECT_GROUP_NAMES = [
    MAP_OBJECT_GROUP_ENEMY,
    MAP_OBJECT_GROUP_ENEMY_PATROL,
    // Depends on MAP_OBJECT_GROUP_ENEMY
    MAP_OBJECT_GROUP_ENEMY_PACK,
    MAP_OBJECT_GROUP_PATH,
    MAP_OBJECT_GROUP_DUNGEON_FLOOR_SWITCH_MARKER,
    MAP_OBJECT_GROUP_BRUSHLINE,
    MAP_OBJECT_GROUP_MAPICON,
    // MAP_OBJECT_GROUP_MAPICON_AWAKENED_OBELISK is missing on purpose; it's an alias for MAPICON
    // Depends on MAP_OBJECT_GROUP_ENEMY, MAP_OBJECT_GROUP_DUNGEON_FLOOR_SWITCH_MARKER
    MAP_OBJECT_GROUP_KILLZONE
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
        console.assert(name !== MAP_OBJECT_GROUP_MAPICON_AWAKENED_OBELISK, 'unable to create map object group for ' + name, this);

        let result = null;

        if (name === MAP_OBJECT_GROUP_ENEMY) {
            result = new EnemyMapObjectGroup(this, getState().isMapAdmin());
        } else if (name === MAP_OBJECT_GROUP_ENEMY_PATROL) {
            result = new EnemyPatrolMapObjectGroup(this, getState().isMapAdmin());
        } else if (name === MAP_OBJECT_GROUP_ENEMY_PACK) {
            result = new EnemyPackMapObjectGroup(this, getState().isMapAdmin());
        } else if (name === MAP_OBJECT_GROUP_PATH) {
            result = new PathMapObjectGroup(this, !getState().isMapAdmin());
        } else if (name === MAP_OBJECT_GROUP_KILLZONE) {
            result = new KillZoneMapObjectGroup(this, !getState().isMapAdmin());
        } else if (name === MAP_OBJECT_GROUP_BRUSHLINE) {
            result = new BrushlineMapObjectGroup(this, !getState().isMapAdmin());
        } else if (name === MAP_OBJECT_GROUP_MAPICON) {
            result = new MapIconMapObjectGroup(this, true);
        } else if (name === MAP_OBJECT_GROUP_DUNGEON_FLOOR_SWITCH_MARKER) {
            result = new DungeonFloorSwitchMarkerMapObjectGroup(this, getState().isMapAdmin());
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
            let names = this.mapObjectGroups[i].names;
            for (let j = 0; j < names.length; j++) {
                result.push(names[j]);
            }
        }

        return result;
    }

    /**
     * Retrieves a map object group by its name.
     * @param name
     * @returns {boolean|MapObjectGroup}
     */
    getByName(name) {
        console.assert(this instanceof MapObjectGroupManager, 'this is not a MapObjectGroupManager', this);

        let result = false;
        for (let i = 0; i < this.mapObjectGroups.length; i++) {
            if (this.mapObjectGroups[i].names.includes(name)) {
                result = this.mapObjectGroups[i];
                break;
            }
        }

        console.assert(result !== false, 'Unable to find MapObjectGroup ' + name, this);

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
        console.assert(objectGroup instanceof MapObjectGroup, 'objectGroup is not a MapObjectGroup', objectGroup);

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
            url: '/ajax/' + getState().getMapContext().getPublicKey() + '/data',
            dataType: 'json',
            data: {
                fields: this._getLoadedNames().join(','),
                floor: getState().getCurrentFloor().id,
                enemyPackEnemies: getState().isMapAdmin() ? 0 : 1,
                teeming: getState().getMapContext().getTeeming() ? 1 : 0
            },
            success: function (json) {
                self.signal('fetchsuccess', {response: json});
            }
        });
    }
}