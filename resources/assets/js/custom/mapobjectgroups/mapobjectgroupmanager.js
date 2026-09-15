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
     * Retrieves a map object group by its name. Prefer the named accessors below when the name is known up front.
     *
     * A group is absent when the page suppressed it through its hiddenMapObjectGroups option, and while the
     * constructor is still creating the groups: a group cannot see one that comes after it in
     * MAP_OBJECT_GROUP_NAMES from inside its own constructor.
     *
     * @param {string} name One of the MAP_OBJECT_GROUP_* constants.
     * @returns {MapObjectGroup|null} Null when this map has no such map object group.
     */
    getByName(name) {
        console.assert(this instanceof MapObjectGroupManager, 'this is not a MapObjectGroupManager', this);

        let result = null;
        for (let i = 0; i < this.mapObjectGroups.length; i++) {
            if (this.mapObjectGroups[i].names.includes(name)) {
                result = this.mapObjectGroups[i];
                break;
            }
        }

        return result;
    }

    /**
     * Named accessors, one for each entry of MAP_OBJECT_GROUP_NAMES. Each returns null when this map has no
     * such group, exactly like getByName().
     *
     * MAP_OBJECT_GROUP_MAPICON_AWAKENED_OBELISK deliberately has none: it is an alias that resolves to the map
     * icon group through its multi-name registration, not a group of its own.
     *
     * @returns {UserMousePositionMapObjectGroup|null}
     */
    getUserMousePositionMapObjectGroup() {
        return this.getByName(MAP_OBJECT_GROUP_USER_MOUSE_POSITION);
    }

    /**
     * @returns {EnemyPatrolMapObjectGroup|null}
     */
    getEnemyPatrolMapObjectGroup() {
        return this.getByName(MAP_OBJECT_GROUP_ENEMY_PATROL);
    }

    /**
     * @returns {EnemyMapObjectGroup|null}
     */
    getEnemyMapObjectGroup() {
        return this.getByName(MAP_OBJECT_GROUP_ENEMY);
    }

    /**
     * @returns {EnemyPackMapObjectGroup|null}
     */
    getEnemyPackMapObjectGroup() {
        return this.getByName(MAP_OBJECT_GROUP_ENEMY_PACK);
    }

    /**
     * @returns {EnemyForcesCheckpointMapObjectGroup|null}
     */
    getEnemyForcesCheckpointMapObjectGroup() {
        return this.getByName(MAP_OBJECT_GROUP_ENEMY_FORCES_CHECKPOINT);
    }

    /**
     * @returns {PathMapObjectGroup|null}
     */
    getPathMapObjectGroup() {
        return this.getByName(MAP_OBJECT_GROUP_PATH);
    }

    /**
     * @returns {DungeonFloorSwitchMarkerMapObjectGroup|null}
     */
    getDungeonFloorSwitchMarkerMapObjectGroup() {
        return this.getByName(MAP_OBJECT_GROUP_DUNGEON_FLOOR_SWITCH_MARKER);
    }

    /**
     * @returns {BrushlineMapObjectGroup|null}
     */
    getBrushlineMapObjectGroup() {
        return this.getByName(MAP_OBJECT_GROUP_BRUSHLINE);
    }

    /**
     * @returns {ArrowMapObjectGroup|null}
     */
    getArrowMapObjectGroup() {
        return this.getByName(MAP_OBJECT_GROUP_ARROW);
    }

    /**
     * @returns {MapIconMapObjectGroup|null}
     */
    getMapIconMapObjectGroup() {
        return this.getByName(MAP_OBJECT_GROUP_MAPICON);
    }

    /**
     * @returns {KillZoneMapObjectGroup|null}
     */
    getKillZoneMapObjectGroup() {
        return this.getByName(MAP_OBJECT_GROUP_KILLZONE);
    }

    /**
     * @returns {KillZonePathMapObjectGroup|null}
     */
    getKillZonePathMapObjectGroup() {
        return this.getByName(MAP_OBJECT_GROUP_KILLZONE_PATH);
    }

    /**
     * @returns {MountableAreaMapObjectGroup|null}
     */
    getMountableAreaMapObjectGroup() {
        return this.getByName(MAP_OBJECT_GROUP_MOUNTABLE_AREA);
    }

    /**
     * @returns {FloorUnionMapObjectGroup|null}
     */
    getFloorUnionMapObjectGroup() {
        return this.getByName(MAP_OBJECT_GROUP_FLOOR_UNION);
    }

    /**
     * @returns {FloorUnionAreaMapObjectGroup|null}
     */
    getFloorUnionAreaMapObjectGroup() {
        return this.getByName(MAP_OBJECT_GROUP_FLOOR_UNION_AREA);
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

// Guarded export for the test runner (Vitest). This is a no-op in the browser,
// where `module` is undefined, so it does not affect the concatenated bundle.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {MapObjectGroupManager};
}
