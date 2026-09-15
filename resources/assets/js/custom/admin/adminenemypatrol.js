class AdminEnemyPatrol extends EnemyPatrol {

    constructor(map, layer) {
        super(map, layer);

        this.setSynced(false);

        // Draws the lines from this patrol to each of its connected enemies.
        this.enemyConnections = new AdminEnemyConnections(c.map.adminenemypatrol.polylineOptions);

        getState().register('floorid:changed', this, this.redrawConnectionsToEnemies.bind(this));
    }

    /**
     * @inheritDoc
     */
    onSaveSuccess(json, massSave = false) {
        super.onSaveSuccess(json, massSave);

        this.redrawConnectionsToEnemies();
    }

    /**
     *
     * @returns {*[]}
     * @private
     */
    _getVisibleEnemiesLatLngs() {
        console.assert(this instanceof EnemyPatrol, 'this was not an EnemyPatrol', this);

        let result = [];
        for (let index in this.enemies) {
            let enemyCandidate = this.enemies[index];

            if (enemyCandidate.shouldBeVisible()) {
                result.push(enemyCandidate.layer.getLatLng());
            }
        }

        return result;
    }

    /**
     * Must be explicitly overriden since EnemyPatrols cannot be deleted; admin ones can.
     * @returns {boolean}
     */
    isEditable() {
        return true;
    }

    /**
     * @param {Enemy} enemy
     */
    addEnemy(enemy) {
        super.addEnemy(enemy);

        enemy.register('object:changed', this, this.redrawConnectionsToEnemies.bind(this));
    }

    /**
     * @param {Enemy} enemy
     */
    removeEnemy(enemy) {
        super.removeEnemy(enemy);

        enemy.unregister('object:changed', this);
    }

    /**
     * Removes any existing UI connections to enemies.
     */
    removeExistingConnectionsToEnemies() {
        console.assert(this instanceof EnemyPatrol, 'this is not an EnemyPatrol', this);

        this.enemyConnections.remove();
    }

    /**
     *
     */
    redrawConnectionsToEnemies() {
        console.assert(this instanceof EnemyPatrol, 'this is not an EnemyPatrol', this);

        this.removeExistingConnectionsToEnemies();

        // If the patrol is not visible, don't draw any new stuff
        if (!this.shouldBeVisible()) {
            return;
        }

        // Attached to the patrol group's own layer group, so the Map Elements toggle covers the lines too
        let enemyPatrolMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_ENEMY_PATROL);

        this.enemyConnections.draw(
            enemyPatrolMapObjectGroup.layerGroup,
            this.getLayerLatLng(),
            this._getVisibleEnemiesLatLngs()
        );
    }

    localDelete(massDelete = false) {
        super.localDelete(massDelete);

        // Add all the enemies in said pack to the toggle display
        let enemyMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_ENEMY);

        for (let key in enemyMapObjectGroup.objects) {
            let enemy = enemyMapObjectGroup.objects[key];

            // Detach all enemies from this patrol if it's deleted
            if (enemy.enemy_patrol_id === this.id) {
                enemy.setEnemyPatrol(null);
                enemy.save();
            }
        }

        this.removeExistingConnectionsToEnemies();
    }

    cleanup() {
        super.cleanup();

        getState().unregister('floorid:changed', this);
    }
}

// Guarded export for the test runner (Vitest). This is a no-op in the browser,
// where `module` is undefined, so it does not affect the concatenated bundle.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        AdminEnemyPatrol,
    };
}
