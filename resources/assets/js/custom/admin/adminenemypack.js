class AdminEnemyPack extends EnemyPack {

    constructor(map, layer) {
        super(map, layer);

        this.setSynced(false);
    }


    isEditableByPopup() {
        return true;
    }

    localDelete(massDelete = false) {
        super.localDelete(massDelete);

        // Add all the enemies in said pack to the toggle display
        let enemyMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_ENEMY);

        for (let key in enemyMapObjectGroup.getMapObjects()) {
            let enemy = enemyMapObjectGroup.getMapObjects()[key];

            // Detach all enemies from this pack if it's deleted
            if (enemy.enemy_pack_id === this.id) {
                enemy.enemy_pack_id = null;
                enemy.save();
            }
        }
    }
}
