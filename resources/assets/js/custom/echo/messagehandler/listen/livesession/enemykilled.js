class EnemyKilledHandler extends MessageHandler {

    constructor(echo) {
        super(echo, EnemyKilledMessage.getName());
    }

    /**
     * @param e {EnemyKilledMessage}
     */
    onReceive(e) {
        super.onReceive(e);

        let mapContext = getState().getMapContext();
        if (mapContext instanceof MapContextLiveSession) {
            mapContext.addKilledEnemy(e.enemy_id);
        }

        let enemyMapObjectGroup = this.echo.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_ENEMY);

        /** @type {Enemy} */
        let enemy = enemyMapObjectGroup.findMapObjectById(e.enemy_id);

        if (enemy !== null) {
            enemy.setObsolete(true);
        } else {
            console.warn(`Unable to find killed enemy ${e.enemy_id}`);
        }
    }
}
