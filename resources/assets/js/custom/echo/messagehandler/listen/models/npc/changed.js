class NpcChangedHandler extends MessageHandler {

    constructor(echo) {
        super(echo, NpcChangedMessage.getName());
    }


    /**
     *
     * @param e {NpcChangedMessage}
     */
    onReceive(e) {
        super.onReceive(e);
        let mapContext = getState().getMapContext();
        // The channel is already scoped to our dungeon, so we only ever receive an event for an
        // npc that's relevant here - the only thing left to distinguish is whether this specific
        // broadcast is telling us the npc still belongs to this dungeon, or that it was just
        // removed from it (npc.dungeon_id is a legacy column NpcController never writes, so it
        // cannot be used for this)
        let isNpcUpdatedForUs = !e.npc_removed_from_dungeon;

        // Remove any existing NPC - if the npc was just renamed to a new id (e.old_npc_id),
        // its old raw entry is keyed under that old id and needs removing too
        mapContext.removeRawNpcById(e.model.id);
        if (e.old_npc_id !== null && e.old_npc_id !== undefined) {
            mapContext.removeRawNpcById(e.old_npc_id);
        }

        // Do not add the npc does not belong to this dungeon
        if (isNpcUpdatedForUs) {
            // Add the new NPC
            mapContext.addRawNpc(e.model);
        }


        // Redraw all enemies that have this npc so that we're up-to-date. An enemy may still be
        // holding the npc's old id in memory if it was just renamed (the DB rows were already
        // remapped server-side, but connected clients were never told).
        let enemyMapObjectGroup = this.echo.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_ENEMY);
        for (let key in enemyMapObjectGroup.objects) {
            let enemy = enemyMapObjectGroup.objects[key];
            if (enemy.npc_id === e.model.id || (e.old_npc_id !== null && e.old_npc_id !== undefined && enemy.npc_id === e.old_npc_id)) {
                // The npc payload from this live-update broadcast never carries enemy_forces
                // (it's scoped per mapping version, not per dungeon), so preserve whatever the
                // enemy already had instead of letting setNpc() zero it out
                let enemyForces = enemy.enemy_forces;
                let enemyForcesTeeming = enemy.enemy_forces_teeming;

                // Re-assign the enemy if it was just updated, unassign it if is no longer available
                enemy.setNpc(isNpcUpdatedForUs ? e.model : null);

                if (isNpcUpdatedForUs) {
                    enemy.enemy_forces = enemyForces;
                    enemy.enemy_forces_teeming = enemyForcesTeeming;
                }

                enemy.visual.refresh();
            }

            enemy.setSynced(true);
        }
    }
}

// Guarded export for the test runner (Vitest). This is a no-op in the browser,
// where `module` is undefined, so it does not affect the concatenated bundle.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        NpcChangedHandler,
    };
}
