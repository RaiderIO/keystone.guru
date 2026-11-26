/**
 * Applies the live state of a live session to the map: once the map object groups have loaded (from the state the
 * map context carried on page load), again for every state broadcast received after that, and for the state fetched
 * each time the presence channel is (re)subscribed, since broadcasts sent while disconnected never arrive. Applying
 * is idempotent and also undoes whatever the state leaves out, so the latest state is all that ever matters.
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
        /** @type {Number} Increases with every state broadcast received */
        this._receivedStateCount = 0;
        /** @type {Number} Identifies the most recent state fetch; the response of any earlier one is stale */
        this._latestRefreshId = 0;

        let self = this;

        /** @type {EchoHandler|null} */
        this._echoHandler = getState().getEchoHandler() ?? null;
        if (this._echoHandler !== null) {
            this._echoHandler.register('presence:subscribed', this, function () {
                self.refreshState();
            });
        }

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
     * Takes the state a broadcast carried.
     *
     * @param data {LiveSessionStateData}
     */
    setState(data) {
        console.assert(this instanceof LiveSessionStateManager, 'this is not a LiveSessionStateManager', this);

        this._receivedStateCount++;

        this._storeAndApplyState(data);
    }

    /**
     * Fetches the current state from the server and takes it, unless a broadcast or a later fetch has delivered a
     * state in the meantime: either one is at least as recent as this response.
     */
    refreshState() {
        console.assert(this instanceof LiveSessionStateManager, 'this is not a LiveSessionStateManager', this);

        let self = this;
        let mapContext = getState().getMapContext();
        let refreshId = ++this._latestRefreshId;
        let receivedStateCount = this._receivedStateCount;

        $.ajax({
            type: 'GET',
            url: `/ajax/${mapContext.getPublicKey()}/live/${mapContext.getLiveSessionPublicKey()}/state`,
            dataType: 'json',
            success: function (json) {
                if (refreshId !== self._latestRefreshId || receivedStateCount !== self._receivedStateCount) {
                    return;
                }

                self._storeAndApplyState(json);
            },
            error: function (xhr, textStatus, errorThrown) {
                console.error('LiveSessionStateManager: unable to refresh the live session state', textStatus, errorThrown);
            }
        });
    }

    /**
     * @param data {LiveSessionStateData}
     * @private
     */
    _storeAndApplyState(data) {
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

        if (this._echoHandler !== null) {
            this._echoHandler.unregister('presence:subscribed', this);
        }
    }
}

// Guarded export for the test runner (Vitest). This is a no-op in the browser,
// where `module` is undefined, so it does not affect the concatenated bundle.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        LiveSessionStateManager,
    };
}
