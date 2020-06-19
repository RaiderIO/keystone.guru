class AdminEnemyPack extends EnemyPack {

    constructor(map, layer) {
        super(map, layer);

        this.setSynced(false);
    }

    _getRouteSuffix() {
        return 'enemypack';
    }
}