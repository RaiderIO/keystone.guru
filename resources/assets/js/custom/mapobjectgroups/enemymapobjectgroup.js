class EnemyMapObjectGroup extends MapObjectGroup {
    constructor(manager, editable) {
        super(manager, MAP_OBJECT_GROUP_ENEMY, editable);

        this.title = 'Hide/show enemies';
        this.fa_class = 'fa-users';

        getState().register('mdtmappingmodeenabled:changed', this, this._onMdtMappingModeEnabledChanged.bind(this));
    }

    /**
     * Called when the MDT mapping mode enabled has changed
     * @private
     */
    _onMdtMappingModeEnabledChanged() {
        // Refresh visibility of all enemies
        this._updateVisibility();
    }

    /**
     *
     * @param assignedEvent {Object}
     * @private
     */
    _onPridefulEnemyAssigned(assignedEvent) {
        console.assert(this instanceof EnemyMapObjectGroup, 'this is not a EnemyMapObjectGroup', this);

        this.signal('pridefulenemy:assigned', {pridefulenemy: assignedEvent.context});
    }

    /**
     *
     * @param unassignedEvent {Object}
     * @private
     */
    _onPridefulEnemyUnassigned(unassignedEvent) {
        console.assert(this instanceof EnemyMapObjectGroup, 'this is not a EnemyMapObjectGroup', this);

        this.signal('pridefulenemy:unassigned', {pridefulenemy: unassignedEvent.context});
    }

    /**
     * @inheritDoc
     **/
    _getRawObjects() {
        let mapContext = getState().getMapContext();
        let enemies = mapContext.getEnemies();
        if (mapContext instanceof MapContextDungeon) {
            // Union to create new array
            enemies = _.union(enemies, mapContext.getMdtEnemies());
        }
        return enemies;
    }

    /**
     * @inheritDoc
     */
    _createLayer(remoteMapObject) {
        let layer = new LeafletEnemyMarker();
        layer.setLatLng(L.latLng(remoteMapObject.lat, remoteMapObject.lng));
        return layer;
    }

    /**
     * @inheritDoc
     */
    _getOptions(remoteMapObject) {
        return {seasonalType: remoteMapObject.seasonal_type};
    }

    /**
     * @inheritDoc
     */
    _createMapObject(layer, options = {}) {
        console.assert(this instanceof EnemyMapObjectGroup, 'this is not a EnemyMapObjectGroup', this);

        if (getState().isMapAdmin()) {
            return new AdminEnemy(this.manager.map, layer);
        } else if (options.hasOwnProperty('seasonalType') && options.seasonalType === 'prideful') {
            return new PridefulEnemy(this.manager.map, layer);
        } else {
            return new Enemy(this.manager.map, layer);
        }
    }

    /**
     *
     * @param remoteMapObject {Object}
     * @param mapObject {Enemy|MapObject}
     * @param options {Object}
     * @returns {MapObject}
     * @protected
     */
    _updateMapObject(remoteMapObject, mapObject, options = {}) {
        super._updateMapObject(remoteMapObject, mapObject, options);

        // Refresh visual if not created new, AFTER setting the NPC again etc.
        mapObject.visual.refresh();

        return mapObject;
    }

    load() {
        super.load();

        let mapContext = getState().getMapContext();
        let isRouteAwakened = mapContext.hasAffix(AFFIX_AWAKENED);
        let isRoutePrideful = mapContext.hasAffix(AFFIX_PRIDEFUL);

        let enemyPatrolMapObjectGroup = this.manager.getByName(MAP_OBJECT_GROUP_ENEMY_PATROL);

        // Couple awakened enemies to each other
        for (let key in this.objects) {
            /** @type {Enemy} */
            let enemy = this.objects[key];

            // Check only those Awakened mobs that are not part of the final boss pack
            if (isRouteAwakened && enemy.npc !== null && enemy.isAwakenedNpc() && enemy.enemy_pack_id === -1) {
                for (let nestedKey in this.objects) {
                    let enemyCandidate = this.objects[nestedKey];

                    // Don't check ourselves, only match those enemies with the same npc id and seasonal_index that are part of the final boss pack
                    if (enemyCandidate.id !== enemy.id && enemyCandidate.npc !== null &&
                        enemyCandidate.isAwakenedNpc() && enemyCandidate.npc.id === enemy.npc.id &&
                        enemyCandidate.seasonal_index === enemy.seasonal_index &&
                        enemyCandidate.enemy_pack_id !== null) {

                        enemy.setLinkedAwakenedEnemy(enemyCandidate);
                        break;
                    }
                }
            }

            // Check if the enemy is a Prideful enemy, and if so if we should move it to a different floor / lat+lng
            if (isRoutePrideful && enemy instanceof PridefulEnemy) {
                let pridefulEnemiesData = mapContext.getPridefulEnemies();
                for (let i = 0; i < pridefulEnemiesData.length; i++) {
                    let pridefulEnemyData = pridefulEnemiesData[i];

                    // If we have a match..
                    if (pridefulEnemyData.enemy_id === enemy.id) {
                        enemy.setAssignedLocation(pridefulEnemyData.lat, pridefulEnemyData.lng, pridefulEnemyData.floor_id);
                        // May stop now
                        break;
                    }
                }

                enemy.register('pridefulenemy:assigned', this, this._onPridefulEnemyAssigned.bind(this));
                enemy.register('pridefulenemy:unassigned', this, this._onPridefulEnemyUnassigned.bind(this));
            }

            // Assign overpulled enemies from cache
            if (mapContext instanceof MapContextLiveSession) {
                let overpulledEnemiesData = mapContext.getOverpulledEnemies();
                for (let i = 0; i < overpulledEnemiesData.length; i++) {
                    let overpulledEnemyData = overpulledEnemiesData[i];

                    // If we have a match..
                    if (overpulledEnemyData.enemy_id === enemy.id) {
                        enemy.setOverpulledKillZoneId(overpulledEnemyData.kill_zone_id);

                        // May stop now
                        break;
                    }
                }

                // Assign obsolete enemies from cache
                let obsoleteEnemiesData = getState().getMapContext().getObsoleteEnemies();
                enemy.setObsolete(obsoleteEnemiesData.includes(enemy.id));
            }

            if (mapContext instanceof MapContextDungeonRoute) {
                let enemyRaidMarkers = mapContext.getEnemyRaidMarkers();
                // Assign raid markers to this enemy if it was assigned one
                for (let i = 0; i < enemyRaidMarkers.length; i++) {
                    let enemyRaidMarker = enemyRaidMarkers[i];
                    if (enemyRaidMarker.enemy_id === enemy.id) {
                        enemy.setRaidMarkerName(enemyRaidMarker.raid_marker_name);
                        break;
                    }
                }
            }

            // enemyPatrolMapObjectGroup may be false if we're generating thumbnails
            // Assign patrols to enemies if they have it
            if (enemyPatrolMapObjectGroup instanceof EnemyPatrolMapObjectGroup && enemy.enemy_patrol_id !== null) {
                /** @type {EnemyPatrol} */
                let enemyPatrol = enemyPatrolMapObjectGroup.findMapObjectById(enemy.enemy_patrol_id);
                enemyPatrol.addEnemy(enemy);
            }
        }

        if (getState().isMapAdmin()) {
            // After adding all the enemies, redraw the connections to all enemies in the patrols
            for (let key in enemyPatrolMapObjectGroup.objects) {
                enemyPatrolMapObjectGroup.objects[key].redrawConnectionsToEnemies();
            }
        }
    }

    /**
     * Helper function to fetch the final boss of this dungeon.
     *
     * @return {Enemy|null}
     */
    getFinalBoss() {
        let finalBoss = null;
        for (let key in this.objects) {
            let enemy = this.objects[key];
            if (enemy.npc !== null && enemy.npc.classification_id === NPC_CLASSIFICATION_ID_FINAL_BOSS) {
                finalBoss = enemy;
                break;
            }
        }

        return finalBoss;
    }

    /**
     *
     * @returns {PridefulEnemy|null}
     */
    getFreePridefulEnemy() {
        let result = null;

        for (let key in this.objects) {
            let enemy = this.objects[key];
            if (enemy instanceof PridefulEnemy) {
                if (!enemy.isAssigned()) {
                    result = enemy;
                    break;
                }
            }
        }

        return result;
    }

    /**
     * Get the amount of free prideful enemies.
     * @returns {Number}
     */
    getAssignedPridefulEnemies() {
        let result = 0;

        for (let key in this.objects) {
            let enemy = this.objects[key];
            if (enemy instanceof PridefulEnemy) {
                if (enemy.isAssigned()) {
                    result++;
                }
            }
        }

        return result;
    }
}
