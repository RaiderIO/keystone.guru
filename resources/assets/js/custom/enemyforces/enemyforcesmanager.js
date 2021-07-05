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
     * Get the amount of enemy forces that are required to complete this dungeon.
     * @returns {*}
     */
    getEnemyForcesRequired() {
        console.assert(this instanceof EnemyForcesManager, 'this is not EnemyForcesManager', this);

        let dungeonData = getState().getMapContext().getDungeon();
        let result = dungeonData.enemy_forces_required;

        if (getState().getMapContext().getTeeming() && dungeonData.enemy_forces_required_teeming > 0) {
            result = dungeonData.enemy_forces_required_teeming;
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
    }
}