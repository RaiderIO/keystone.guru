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

        let enemyMapObjectGroup = this.echo.map.mapObjectGroupManager.getEnemyMapObjectGroup();

        /** @type {Enemy} */
        let enemy = enemyMapObjectGroup.findMapObjectById(e.enemy_id);

        if (enemy !== null) {
            enemy.setOverpulledKillZoneId(e.kill_zone_id);
        } else {
            console.warn(`Unable to find overpulled enemy ${e.enemy_id}`);
        }
    }
}