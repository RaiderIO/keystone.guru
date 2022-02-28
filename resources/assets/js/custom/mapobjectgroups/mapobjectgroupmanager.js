class MapObjectGroupManager extends Signalable {

    /**
     *
     * @param {DungeonMap} map
     * @param {Array} mapObjectGroupNames
     */
    constructor(map, mapObjectGroupNames) {
        super();
        let self = this;

        this.map = map;

        this._loaded = false;

        this.mapObjectGroups = [];
        for (let i = 0; i < mapObjectGroupNames.length; i++) {
            this.mapObjectGroups.push(this._createMapObjectGroup(mapObjectGroupNames[i]));
        }

        this.map.register('map:refresh', this, function () {
            if (!self._loaded) {
                self._loadMapObjectGroups();
            }
            self._updateMapObjectGroups();
        });
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

        if (name === MAP_OBJECT_GROUP_USER_MOUSE_POSITION) {
            result = new UserMousePositionMapObjectGroup(this, getState().isMapAdmin());
        } else if (name === MAP_OBJECT_GROUP_ENEMY) {
            result = new EnemyMapObjectGroup(this, getState().isMapAdmin());
        } else if (name === MAP_OBJECT_GROUP_ENEMY_PATROL) {
            result = new EnemyPatrolMapObjectGroup(this, getState().isMapAdmin());
        } else if (name === MAP_OBJECT_GROUP_ENEMY_PACK) {
            result = new EnemyPackMapObjectGroup(this, getState().isMapAdmin());
        } else if (name === MAP_OBJECT_GROUP_PATH) {
            result = new PathMapObjectGroup(this, !getState().isMapAdmin());
        } else if (name === MAP_OBJECT_GROUP_KILLZONE) {
            result = new KillZoneMapObjectGroup(this, !getState().isMapAdmin());
        } else if (name === MAP_OBJECT_GROUP_KILLZONE_PATH) {
            result = new KillZonePathMapObjectGroup(this, !getState().isMapAdmin());
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
    _loadMapObjectGroups() {
        console.assert(this instanceof MapObjectGroupManager, 'this is not a MapObjectGroupManager', this);

        for (let i = 0; i < this.mapObjectGroups.length; i++) {
            this.mapObjectGroups[i].load();
        }

        this._loaded = true;
        this.signal('loaded');
    }

    /**
     * Update
     * @private
     */
    _updateMapObjectGroups() {
        for (let i = 0; i < this.mapObjectGroups.length; i++) {
            this.mapObjectGroups[i].update();
        }

        this.signal('updated');
    }
}
