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

        if (enemy !== null) {
            enemy.setOverpulledKillZoneId(e.kill_zone_id);
        } else {
            console.warn(`Unable to find overpulled enemy ${e.enemy_id}`);
        }
    }
}
