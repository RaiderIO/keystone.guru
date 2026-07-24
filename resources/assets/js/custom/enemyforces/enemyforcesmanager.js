class EnemyForcesManager extends Signalable {
    constructor(map) {
        super();
        let self = this;

        this.map = map;

        this.enemyForces = 0;
        this.enemyForcesOverride = null;

        let mapContext = getState().getMapContext();
        if (mapContext instanceof MapContextLiveSession && mapContext.getEnemyForcesOverride() !== mapContext.getEnemyForces()) {
            this.enemyForcesOverride = mapContext.getEnemyForcesOverride();
        }

        // On route load, this will also fill the enemy forces to the value they should be as the route is loaded
        let killZoneMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_KILLZONE);
        // May be null in admin setting where there's no kill zones
        if (killZoneMapObjectGroup !== false) {
            killZoneMapObjectGroup.register('killzone:enemyadded', this, function (addEvent) {
                self._setEnemyForces(self.enemyForces + addEvent.data.enemy.getEnemyForces());
            });
            killZoneMapObjectGroup.register('killzone:enemyremoved', this, function (removedEvent) {
                self._setEnemyForces(self.enemyForces - removedEvent.data.enemy.getEnemyForces());
            });
            killZoneMapObjectGroup.register('killzone:enemieschanged', this, function (changedEvent) {
                self._setEnemyForces(self.enemyForces - changedEvent.data.previousForces + changedEvent.data.newForces);
            });
            killZoneMapObjectGroup.register('object:add', this, function (addEvent) {
                addEvent.data.object.register('killzone:changed', self, self._onKillZoneChanged.bind(self));
            });
        }
    }

    /**
     *
     * @param objectChangedEvent
     * @private
     */
    _onKillZoneChanged(objectChangedEvent) {
        console.assert(this instanceof EnemyForcesManager, 'this is not EnemyForcesManager', this);

        if (typeof objectChangedEvent.data.enemy_forces !== 'undefined') {
            this._setEnemyForces(objectChangedEvent.data.enemy_forces);
        }
    }

    /**
     * Sets the enemy forces to a specific value.
     * @param value {Number}
     * @param force {Boolean}
     * @private
     */
    _setEnemyForces(value, force = false) {
        console.assert(this instanceof EnemyForcesManager, 'this is not EnemyForcesManager', this);

        let previousEnemyForces = this.enemyForces;

        if (previousEnemyForces !== value || force) {
            this.enemyForces = value;

            // If there's an override, we don't fire this. We don't want to fire an event for incorrect values.
            // It's still valid to set the above in case the override is removed again later.
            if (this.enemyForcesOverride === null) {
                this.signal('enemyforces:changed', {
                    previousEnemyForces: previousEnemyForces,
                    currentEnemyForces: this.enemyForces
                });
            }
        }
    }

    /**
     *
     * @param value {Number}
     */
    setEnemyForcesOverride(value) {
        console.assert(this instanceof EnemyForcesManager, 'this is not EnemyForcesManager', this);

        let previousEnemyForces = this.enemyForcesOverride ?? this.enemyForces;

        this.enemyForcesOverride = value;

        if (this.enemyForcesOverride === null) {
            // Re-setting the enemy forces will force a refresh
            this._setEnemyForces(this.enemyForces, true);
        } else {
            this.signal('enemyforces:changed', {
                previousEnemyForces: previousEnemyForces,
                currentEnemyForces: this.enemyForces
            });
        }
    }

    /**
     * Get the amount of enemy forces that the route currently kills.
     * @returns {number}
     */
    getEnemyForces() {
        console.assert(this instanceof EnemyForcesManager, 'this is not EnemyForcesManager', this);

        return this.enemyForcesOverride ?? this.enemyForces;
    }

    /**
     * Get the enemy forces that may have been forcefully set.
     * @returns {number|null}
     */
    getEnemyForcesOverride() {
        console.assert(this instanceof EnemyForcesManager, 'this is not EnemyForcesManager', this);

        return this.enemyForcesOverride;
    }

    /**
     * Get the total amount of enemy forces available on a specific floor. Unlike getEnemyForces(),
     * which only counts pulled enemies, this sums every enemy that lives on the floor - it reflects
     * how much enemy forces is present on that floor, regardless of what is currently pulled.
     * @param floorId {Number}
     * @returns {Number}
     */
    getEnemyForcesForFloor(floorId) {
        console.assert(this instanceof EnemyForcesManager, 'this is not EnemyForcesManager', this);

        let result = 0;

        let enemyMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_ENEMY);
        // May be false in an admin setting where there's no enemies
        if (enemyMapObjectGroup === false) {
            return result;
        }

        for (let key in enemyMapObjectGroup.objects) {
            /** @type {Enemy} */
            let enemy = enemyMapObjectGroup.objects[key];

            // Mirror the filter used by KillZone.getEnemyForces() (skip obsolete) and additionally skip
            // enemies that aren't actually present (wrong seasonal type/affix), so the total matches what
            // is shown on the map.
            if (enemy.floor_id === floorId && !enemy.isObsolete() && !enemy.shouldBeIgnored()) {
                result += enemy.getEnemyForces();
            }
        }

        return result;
    }

    /**
     * Get the amount of enemy forces that are required to complete this dungeon.
     * @returns {*}
     */
    getEnemyForcesRequired() {
        console.assert(this instanceof EnemyForcesManager, 'this is not EnemyForcesManager', this);

        let mapContext = getState().getMapContext();
        let result = mapContext.getEnemyForcesRequired();

        if (mapContext.getTeeming() && mapContext.getEnemyForcesRequiredTeeming() > 0) {
            result = mapContext.getEnemyForcesRequiredTeeming();
        }

        return result;
    }

    cleanup() {
        super.cleanup();

        // Unreg from map
        this.map.unregister('map:mapobjectgroupsloaded', this);
        // Unreg killzones
        let killzoneMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_KILLZONE);
        killzoneMapObjectGroup.unregister('object:add', this);
        killzoneMapObjectGroup.unregister('killzone:enemyremoved', this);
        killzoneMapObjectGroup.unregister('killzone:enemyadded', this);
        killzoneMapObjectGroup.unregister('killzone:enemieschanged', this);
    }
}

// Guarded export for the test runner (Vitest). This is a no-op in the browser,
// where `module` is undefined, so it does not affect the concatenated bundle.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = EnemyForcesManager;
}
