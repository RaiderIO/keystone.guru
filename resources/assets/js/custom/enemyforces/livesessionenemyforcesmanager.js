class LiveSessionEnemyForcesManager extends EnemyForcesManager {
    constructor(map) {
        super(map);

        let self = this;

        // The enemies' initial overpulled/obsolete state is applied during map object group load. We must wait
        // until everything is loaded so that the kill zones have finished accumulating the base enemy forces
        // before we compute an override based on overpulled/obsolete enemies.
        this.map.register('map:mapobjectgroupsloaded', this, function () {
            let enemyMapObjectGroup = self.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_ENEMY);

            console.log(`[EnemyForces] LiveSession map:mapobjectgroupsloaded - attaching listeners`, {enemyCount: Object.keys(enemyMapObjectGroup.objects).length, baseEnemyForces: self.enemyForces});

            // Attach to enemies that may be added later
            enemyMapObjectGroup.register('object:add', self, function (addEvent) {
                addEvent.data.object.register(['overpulled:changed', 'obsolete:changed'], self,
                    self._recalculateEnemyForcesOverride.bind(self));
            });

            // Attach to all enemies that are already loaded
            for (let key in enemyMapObjectGroup.objects) {
                if (enemyMapObjectGroup.objects.hasOwnProperty(key)) {
                    enemyMapObjectGroup.objects[key].register(['overpulled:changed', 'obsolete:changed'], self,
                        self._recalculateEnemyForcesOverride.bind(self));
                }
            }

            self._recalculateEnemyForcesOverride();
        });
    }

    /**
     * Recalculates the enemy forces override based on the live overpulled/obsolete enemy state. Overpulled
     * enemies are killed off-route (extra forces, not part of the base count) and are added; obsolete enemies
     * are planned enemies we may now skip and are subtracted. Killed-on-route enemies need no correction as
     * they remain part of their kill zone and are already counted.
     * @private
     */
    _recalculateEnemyForcesOverride() {
        console.assert(this instanceof LiveSessionEnemyForcesManager, 'this is not LiveSessionEnemyForcesManager', this);

        let enemyMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_ENEMY);

        let overpulledEnemyForces = 0;
        let obsoleteEnemyForces = 0;
        let overpulledEnemyIds = [];
        let obsoleteEnemyIds = [];

        for (let key in enemyMapObjectGroup.objects) {
            if (enemyMapObjectGroup.objects.hasOwnProperty(key)) {
                /** @type {Enemy} */
                let enemy = enemyMapObjectGroup.objects[key];

                if (enemy.getOverpulledKillZoneId() !== null) {
                    overpulledEnemyForces += enemy.getEnemyForces();
                    overpulledEnemyIds.push({id: enemy.id, forces: enemy.getEnemyForces()});
                }

                if (enemy.isObsolete()) {
                    obsoleteEnemyForces += enemy.getEnemyForces();
                    obsoleteEnemyIds.push({id: enemy.id, forces: enemy.getEnemyForces()});
                }
            }
        }

        let override = this.enemyForces + overpulledEnemyForces - obsoleteEnemyForces;

        console.log(`[EnemyForces] _recalculateEnemyForcesOverride`, {
            base: this.enemyForces,
            overpulledEnemyForces: overpulledEnemyForces,
            obsoleteEnemyForces: obsoleteEnemyForces,
            computedOverride: override,
            willSetOverride: override === this.enemyForces ? null : override,
            overpulledEnemies: overpulledEnemyIds,
            obsoleteEnemies: obsoleteEnemyIds
        });

        // When the state returns to baseline, clear the override so the route shows its planned value again
        this.setEnemyForcesOverride(override === this.enemyForces ? null : override);
    }

    cleanup() {
        super.cleanup();

        this.map.unregister('map:mapobjectgroupsloaded', this);

        let enemyMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_ENEMY);
        enemyMapObjectGroup.unregister('object:add', this);

        for (let key in enemyMapObjectGroup.objects) {
            if (enemyMapObjectGroup.objects.hasOwnProperty(key)) {
                enemyMapObjectGroup.objects[key].unregister(['overpulled:changed', 'obsolete:changed'], this);
            }
        }
    }
}
