class EditMapState extends MapState {
    constructor(map) {
        super(map);
    }

    getName() {
        return 'EditMapState';
    }

    shouldRebuildEnemyVisuals() {
        return true;
    }

    start() {
        super.start();
        let self = this;
    }

    stop() {
        super.stop();
        let self = this;
    }
}