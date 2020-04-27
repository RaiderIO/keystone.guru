class PatherMapState extends MapState {
    constructor(map) {
        super(map);
    }


    start() {
        super.start();

        this.map.pather.setMode(L.Pather.MODE.CREATE);
    }

    stop() {
        super.stop();

        this.map.pather.setMode(L.Pather.MODE.VIEW);
    }
}