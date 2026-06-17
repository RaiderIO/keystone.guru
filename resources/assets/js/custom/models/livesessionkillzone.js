/**
 * Splits off all live-session specific concerns (overpulled enemies) from a regular {@see KillZone}, keeping those
 * concerns separated from the base pull behaviour.
 *
 * @property {Number[]} overpulledEnemies List of IDs of enemies that were overpulled in/after this pull
 */
class LiveSessionKillZone extends KillZone {
    constructor(map, layer) {
        super(map, layer);

        // List of IDs of enemies that were overpulled in/after this pull
        this.overpulledEnemies = [];
        // Layer that draws the connections to the overpulled enemies. May be null
        this.overpulledEnemiesLayer = null;
    }

    /**
     * Get the enemy forces that will be added if this enemy pack is killed.
     */
    getEnemyForces() {
        let result = 0;

        let enemyMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_ENEMY);

        // We must consider overpulled enemies part of our enemy forces as well - even though they may technically not be part of the current pull
        let allEnemies = this.enemies.concat(this.getOverpulledEnemies());
        for (let i = 0; i < allEnemies.length; i++) {
            /** @type {Enemy} */
            let enemy = enemyMapObjectGroup.findMapObjectById(allEnemies[i]);
            // Unless this enemy is obsolete - then we don't consider it anymore for this pull
            if (enemy !== null && !enemy.isObsolete()) {
                result += enemy.getEnemyForces();
            }
        }

        return result;
    }

    /**
     * @inheritDoc
     */
    getOverpulledEnemies() {
        return this.overpulledEnemies;
    }

    /**
     * Marks an enemy as being overpulled in/after this pull.
     * @param enemy {LiveSessionEnemy}
     */
    addOverpulledEnemy(enemy) {
        console.assert(this instanceof LiveSessionKillZone, 'this was not a LiveSessionKillZone', this);

        if (!this.overpulledEnemies.includes(enemy.id)) {
            this.overpulledEnemies.push(enemy.id);

            enemy.register('overpulled:changed', this, this._enemyOverpulledChanged.bind(this));
            this.signal('killzone:overpulledenemyadded', {enemy: enemy});
        }
    }

    /**
     * Removes an enemy from the list of overpulled enemies.
     * @param enemy {Enemy}
     */
    removeOverpulledEnemy(enemy) {
        console.assert(this instanceof LiveSessionKillZone, 'this was not a LiveSessionKillZone', this);

        let index = $.inArray(enemy.id, this.overpulledEnemies);
        if (index !== -1) {
            // Remove it
            let deleted = this.overpulledEnemies.splice(index, 1);
            if (deleted.length === 1) {
                let enemyMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_ENEMY);
                let enemy = enemyMapObjectGroup.findMapObjectById(deleted[0]);
                enemy.unregister('overpulled:changed', this);

                this.signal('killzone:overpulledenemyremoved', {enemy: enemy});
            }
        }
    }

    /**
     * @inheritDoc
     */
    _onEnemyAdded(enemy) {
        enemy.register('obsolete:changed', this, this._enemyObsoleteChanged.bind(this));
    }

    /**
     * @inheritDoc
     */
    _onEnemyRemoved(enemy) {
        enemy.unregister('obsolete:changed', this);
    }

    /**
     * Called whenever the obsolete state of one of our enemies has changed.
     * @param enemyObsoleteChangedEvent {Object}
     * @private
     */
    _enemyObsoleteChanged(enemyObsoleteChangedEvent) {
        this.redrawConnectionsToEnemies();

        this.signal('killzone:obsoleteenemychanged', {enemy: enemyObsoleteChangedEvent.context});
    }

    /**
     * Called whenever the overpulled state of one of our overpulled enemies has changed.
     * @param enemyOverpulledChangedEvent {Object}
     * @private
     */
    _enemyOverpulledChanged(enemyOverpulledChangedEvent) {
        /** @type {Enemy} */
        let enemy = enemyOverpulledChangedEvent.context;

        if (enemy.getOverpulledKillZoneId() !== this.id) {
            this.removeOverpulledEnemy(enemy);
        }

        this.redrawConnectionsToEnemies();
    }

    /**
     * @inheritDoc
     */
    _addOrRemoveEnemy(enemy, removed, isOverpulled) {
        if (isOverpulled) {
            if (removed) {
                this.removeOverpulledEnemy(enemy);
            } else {
                this.addOverpulledEnemy(enemy);
            }
        } else {
            super._addOrRemoveEnemy(enemy, removed, isOverpulled);
        }
    }

    /**
     * @inheritDoc
     */
    removeExistingConnectionsToEnemies() {
        if (this.enemyConnectionsLayerGroup !== null && this.overpulledEnemiesLayer !== null) {
            this.enemyConnectionsLayerGroup.removeLayer(this.overpulledEnemiesLayer);
            this.overpulledEnemiesLayer = null;
        }

        super.removeExistingConnectionsToEnemies();
    }

    /**
     * @inheritDoc
     */
    redrawConnectionsToEnemies() {
        console.assert(this instanceof LiveSessionKillZone, 'this is not a LiveSessionKillZone', this);

        super.redrawConnectionsToEnemies();

        let opts = $.extend({}, c.map.killzone.polygonOptions, {color: this.color, fillColor: this.color});
        // Add connections from each overpulled enemy to our location
        let overpulledEnemyLatLngs = this._getVisibleEntitiesLatLngs(this.overpulledEnemies);

        if (overpulledEnemyLatLngs.length > 0) {
            this.centeroid = this.getLayerCenteroid();
            this.overpulledEnemiesLayer = new L.LayerGroup();

            for (let index in overpulledEnemyLatLngs) {
                if (overpulledEnemyLatLngs.hasOwnProperty(index)) {
                    let overpulledEnemyLatLng = overpulledEnemyLatLngs[index];

                    this.overpulledEnemiesLayer.addLayer(
                        L.polyline([
                            [this.centeroid.lat, this.centeroid.lng],
                            overpulledEnemyLatLng
                        ], opts)
                    );
                }
            }

            // do not prevent clicking on anything else
            this.enemyConnectionsLayerGroup.setZIndex(-1000);

            this.enemyConnectionsLayerGroup.addLayer(this.overpulledEnemiesLayer);
        }
    }

    /**
     * @inheritDoc
     */
    cleanup() {
        let self = this;

        let enemyMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_ENEMY);
        $.each(enemyMapObjectGroup.objects, function (i, enemy) {
            enemy.unregister('overpulled:changed', self);
            enemy.unregister('obsolete:changed', self);
        });

        super.cleanup();
    }
}
