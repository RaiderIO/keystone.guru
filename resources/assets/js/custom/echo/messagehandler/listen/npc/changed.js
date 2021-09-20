class NpcChangedHandler extends MessageHandler {

    constructor(echo) {
        super(echo, '.npc-changed');
    }


    /**
     *
     * @param e {NpcChangedMessage}
     */
    onReceive(e) {
        super.onReceive(e);
        let mapContext = getState().getMapContext();

        // Remove any existing NPC
        mapContext.removeRawNpcById(e.model.id);

        // Add the new NPC
        mapContext.addRawNpc(e.model);

        // Redraw all enemies that have this npc so that we're up-to-date
        let enemyMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_ENEMY);
        for (let i = 0; i < enemyMapObjectGroup.objects.length; i++) {
            let enemy = enemyMapObjectGroup.objects[i];
            if (enemy.npc_id === e.model.id) {
                enemy.visual.refresh();
                // Don't break - there is probably more than one, we need to check all enemies
            }
        }
    }
}
