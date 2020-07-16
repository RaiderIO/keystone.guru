class AdminEnemyPatrol extends EnemyPatrol {

    constructor(map, layer) {
        super(map, layer);

        this.setSynced(false);

        this.enemy_id = -1;
    }

    /**
     * Must be explicitly overriden since EnemyPatrols cannot be deleted; admin ones can.
     * @returns {boolean}
     */
    isEditable() {
        return true;
    }
}