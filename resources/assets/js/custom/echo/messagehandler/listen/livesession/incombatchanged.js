class InCombatEnemiesChangedHandler extends MessageHandler {

    constructor(echo) {
        super(echo, InCombatEnemiesChangedMessage.getName());
    }

    /**
     * @param e {InCombatEnemiesChangedMessage}
     */
    onReceive(e) {
        super.onReceive(e);

        let enemyMapObjectGroup = this.echo.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_ENEMY);

        for (let key in enemyMapObjectGroup.objects) {
            if (enemyMapObjectGroup.objects.hasOwnProperty(key)) {
                /** @type {Enemy} */
                let enemy = enemyMapObjectGroup.objects[key];

                enemy.setInCombat(e.enemy_ids.includes(enemy.id));
            }
        }
    }
}
