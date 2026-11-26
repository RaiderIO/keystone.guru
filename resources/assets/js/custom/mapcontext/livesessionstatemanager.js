/**
 * Applies the live state of a live session to the map: once the map object groups have loaded (from the state the
 * map context carried on page load), and again for every state broadcast received after that. Applying is
 * idempotent and also undoes whatever the state leaves out, so the latest state is all that ever matters.
 *
 * @property {DungeonMap} map
 */
class LiveSessionStateManager extends Signalable {
    /**
     * @param map {DungeonMap}
     */
    constructor(map) {
        super();

        this.map = map;
        this._isMapLoaded = false;

        let self = this;

        this.map.register('map:mapobjectgroupsloaded', this, function () {
            self._isMapLoaded = true;
            self.applyCurrentState();
        });

        // While the user is picking overpulled enemies, applying a state would undo the picks they have not saved
        // yet. Once they are being saved, the broadcast the save causes is the state that matters.
        this.map.register('map:mapstatechanged', this, function (mapStateChangedEvent) {
            let previousMapState = mapStateChangedEvent.data.previousMapState;
            if (previousMapState instanceof SelectKillZoneEnemySelectionOverpull && !previousMapState.hasChanges()) {
                self.applyCurrentState();
            }
        });
    }

    /**
     * @param data {LiveSessionStateData}
     */
    setState(data) {
        console.assert(this instanceof LiveSessionStateManager, 'this is not a LiveSessionStateManager', this);

        getState().getMapContext().setLiveSessionState(data);

        this.applyCurrentState();
    }

    /**
     * Applies the state the map context currently holds, unless the map is not ready for it yet.
     */
    applyCurrentState() {
        console.assert(this instanceof LiveSessionStateManager, 'this is not a LiveSessionStateManager', this);

        if (!this._isMapLoaded || this.map.getMapState() instanceof SelectKillZoneEnemySelectionOverpull) {
            return;
        }

        this.apply(getState().getMapContext().getLiveSessionState());
    }

    /**
     * @param liveSessionState {LiveSessionState}
     */
    apply(liveSessionState) {
        console.assert(this instanceof LiveSessionStateManager, 'this is not a LiveSessionStateManager', this);

        this._applyToEnemies(liveSessionState);
        this._applyToPlayers(liveSessionState);
    }

    /**
     * @param liveSessionState {LiveSessionState}
     * @private
     */
    _applyToEnemies(liveSessionState) {
        let enemyMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_ENEMY);
        let killZoneMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_KILLZONE);

        for (let key in enemyMapObjectGroup.objects) {
            if (!enemyMapObjectGroup.objects.hasOwnProperty(key)) {
                continue;
            }

            let enemy = enemyMapObjectGroup.objects[key];
            if (!(enemy instanceof LiveSessionEnemy)) {
                continue;
            }

            enemy.setKilled(liveSessionState.killedEnemyIds.has(enemy.id));
            enemy.setInCombat(liveSessionState.inCombatEnemyIds.has(enemy.id));
            enemy.setObsolete(liveSessionState.obsoleteEnemyIds.has(enemy.id));

            let overpulledKillZoneId = liveSessionState.getOverpulledKillZoneId(enemy.id);
            if (overpulledKillZoneId !== null) {
                // The pull must know about the enemy before the enemy changes: the pull it leaves (if any) drops it
                // on the overpulled:changed signal, the pull it joins draws its connection to it
                let killZone = killZoneMapObjectGroup.findMapObjectById(overpulledKillZoneId);
                if (killZone instanceof LiveSessionKillZone) {
                    killZone.addOverpulledEnemy(enemy);
                }
            }
            enemy.setOverpulledKillZoneId(overpulledKillZoneId);
        }
    }

    /**
     * @param liveSessionState {LiveSessionState}
     * @private
     */
    _applyToPlayers(liveSessionState) {
        let playerPositionMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_PLAYER_POSITION);
        if (!(playerPositionMapObjectGroup instanceof PlayerPositionMapObjectGroup)) {
            return;
        }

        let state = getState();
        let useFacade = state.isCurrentDungeonFacadeEnabled() && state.getMapFacadeStyle() === MAP_FACADE_STYLE_FACADE;

        for (let player of liveSessionState.players) {
            let coordinates = LiveSessionState.getPlayerCoordinates(player, useFacade);

            playerPositionMapObjectGroup.createOrUpdatePlayerPosition(
                player.guid,
                player.name,
                coordinates.lat,
                coordinates.lng,
                coordinates.floor_id,
                player.class_id ?? null,
                player.specialization_id ?? null,
                player.specialization_icon_url ?? null
            );
        }
    }

    cleanup() {
        this.map.unregister('map:mapobjectgroupsloaded', this);
        this.map.unregister('map:mapstatechanged', this);
    }
}

// Guarded export for the test runner (Vitest). This is a no-op in the browser,
// where `module` is undefined, so it does not affect the concatenated bundle.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        LiveSessionStateManager,
    };
}
