class PatherMapState extends MapState {
    constructor(map) {
        super(map);
    }

    getName() {
        return 'PatherMapState';
    }

    start() {
        super.start();

        this.map.togglePather(true);
    }

    stop() {
        super.stop();

        this.map.togglePather(false);
    }
}
