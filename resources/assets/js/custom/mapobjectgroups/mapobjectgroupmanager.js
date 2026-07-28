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
            try {
                if (!self._loaded) {
                    self._loadMapObjectGroups();
                }
                self._updateMapObjectGroups();
            } catch (e) {
                console.error(e);
            }
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

        let isMapAdmin = getState().isMapAdmin();

        // Group name -> factory. Built inside the method so class load order doesn't matter. The second
        // constructor argument is whether the group's objects are editable here: mapping-owned groups are
        // editable for admins, route-owned groups for everyone BUT admins, and map icons always (they
        // exist on both sides).
        let factories = {
            [MAP_OBJECT_GROUP_USER_MOUSE_POSITION]: () => new UserMousePositionMapObjectGroup(this, isMapAdmin),
            [MAP_OBJECT_GROUP_ENEMY]: () => new EnemyMapObjectGroup(this, isMapAdmin),
            [MAP_OBJECT_GROUP_ENEMY_PATROL]: () => new EnemyPatrolMapObjectGroup(this, isMapAdmin),
            [MAP_OBJECT_GROUP_ENEMY_PACK]: () => new EnemyPackMapObjectGroup(this, isMapAdmin),
            [MAP_OBJECT_GROUP_PATH]: () => new PathMapObjectGroup(this, !isMapAdmin),
            [MAP_OBJECT_GROUP_KILLZONE]: () => new KillZoneMapObjectGroup(this, !isMapAdmin),
            [MAP_OBJECT_GROUP_KILLZONE_PATH]: () => new KillZonePathMapObjectGroup(this, !isMapAdmin),
            [MAP_OBJECT_GROUP_BRUSHLINE]: () => new BrushlineMapObjectGroup(this, !isMapAdmin),
            [MAP_OBJECT_GROUP_ARROW]: () => new ArrowMapObjectGroup(this, !isMapAdmin),
            [MAP_OBJECT_GROUP_MAPICON]: () => new MapIconMapObjectGroup(this, true),
            [MAP_OBJECT_GROUP_DUNGEON_FLOOR_SWITCH_MARKER]: () => new DungeonFloorSwitchMarkerMapObjectGroup(this, isMapAdmin),
            [MAP_OBJECT_GROUP_ENEMY_FORCES_CHECKPOINT]: () => new EnemyForcesCheckpointMapObjectGroup(this, isMapAdmin),
            [MAP_OBJECT_GROUP_MOUNTABLE_AREA]: () => new MountableAreaMapObjectGroup(this, isMapAdmin),
            [MAP_OBJECT_GROUP_FLOOR_UNION]: () => new FloorUnionMapObjectGroup(this, isMapAdmin),
            [MAP_OBJECT_GROUP_FLOOR_UNION_AREA]: () => new FloorUnionAreaMapObjectGroup(this, isMapAdmin),
        };

        let result = factories.hasOwnProperty(name) ? factories[name]() : null;

        console.assert(result !== null, `Unable to find map object group ${name}`, this);

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
