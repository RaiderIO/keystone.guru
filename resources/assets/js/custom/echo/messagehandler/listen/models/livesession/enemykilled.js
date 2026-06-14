class EnemyKilledHandler extends ModelChangedHandler {

    constructor(echo) {
        super(echo, EnemyKilledMessage.getName());
    }

    _shouldHandleEchoEvent() {
        return true;
    }

    /**
     * @param e {EnemyKilledMessage}
     * @return boolean
     */
    onReceive(e) {
        let shouldHandle = super.onReceive(e);

        if (shouldHandle) {
            let mapContext = getState().getMapContext();
            if (mapContext instanceof MapContextLiveSession) {
                mapContext.addKilledEnemy(e.model.id);
            }

            let enemyMapObjectGroup = this.echo.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_ENEMY);

            /** @type {Enemy} */
            let enemy = enemyMapObjectGroup.findMapObjectById(e.model.id);

            if (enemy !== null) {
                enemy.setObsolete(true);
            } else {
                console.warn(`Unable to find killed enemy ${e.model.id}`);
            }
        }

        return shouldHandle;
    }
}
