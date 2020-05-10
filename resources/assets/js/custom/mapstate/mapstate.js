class MapState extends Signalable {
    constructor(map) {
        super();
        console.assert(map instanceof DungeonMap, 'map is not a Map', map);

        this.map = map;
        this._started = false;
        this._stopped = false;
    }

    start() {
        console.assert(this instanceof MapState, 'this is not a MapState', this);
        console.warn('Starting MapState ' + this.constructor.name);
        this._started = true;
    }

    stop() {
        console.assert(this instanceof MapState, 'this is not a MapState', this);
        console.warn('Stopping MapState ' + this.constructor.name);
        this._stopped = true;
    }


    isStarted() {
        return this._started;
    }

    isStopped() {
        return this._stopped;
    }
}