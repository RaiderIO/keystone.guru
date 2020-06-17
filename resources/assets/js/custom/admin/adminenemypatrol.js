class AdminEnemyPatrol extends EnemyPatrol {

    constructor(map, layer) {
        super(map, layer);

        this.setSynced(false);

        this.enemy_id = -1;
    }

    _getRouteSuffix() {
        return 'enemypatrol';
    }
}