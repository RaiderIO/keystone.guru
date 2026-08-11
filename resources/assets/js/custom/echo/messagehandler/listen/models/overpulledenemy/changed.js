class OverpulledEnemyChangedHandler extends MessageHandler {

    constructor(echo) {
        super(echo, OverpulledEnemyChangedMessage.getName());
    }


    /**
     *
     * @param e {OverpulledEnemyChangedMessage}
     */
    onReceive(e) {
        super.onReceive(e);

        let enemyMapObjectGroup = this.echo.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_ENEMY);

        /** @type {LiveSessionEnemy} */
        let enemy = enemyMapObjectGroup.findMapObjectById(e.enemy_id);
        if (enemy === null) {
            console.warn(`Unable to find overpulled enemy ${e.enemy_id}`);
            return;
        }

        // Register the kill zone for this enemy before changing its state. This mirrors the boot path in
        // KillZoneMapObjectGroup.load() and ensures the kill zone listens for the overpulled:changed signal,
        // so it redraws its connection line to the enemy right away instead of only after a full page reload.
        let killZoneMapObjectGroup = this.echo.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_KILLZONE);
        /** @type {KillZone} */
        let killZone = killZoneMapObjectGroup.findMapObjectById(e.kill_zone_id);
        if (killZone !== null) {
            killZone.addOverpulledEnemy(enemy);
        }

        enemy.setOverpulledKillZoneId(e.kill_zone_id);
    }
}
