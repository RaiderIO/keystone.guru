class OverpulledEnemyDeletedHandler extends MessageHandler {

    constructor(echo) {
        super(echo, OverpulledEnemyDeletedMessage.getName());
    }


    /**
     *
     * @param e {OverpulledEnemyDeletedMessage}
     */
    onReceive(e) {
        super.onReceive(e);

        let enemyMapObjectGroup = this.echo.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_ENEMY);

        /** @type {Enemy} */
        let enemy = enemyMapObjectGroup.findMapObjectById(e.enemy_id);

        if (enemy !== null) {
            enemy.setOverpulled(false);
        } else {
            console.warn(`Unable to find overpulled enemy ${e.enemy_id}`);
        }
    }
}