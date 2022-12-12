class MapState extends Signalable {
    constructor(map) {
        super();
        console.assert(map instanceof DungeonMap, 'map is not a Map', map);

        /** @type {DungeonMap} */
        this.map = map;
        this._started = false;
        this._stopped = false;
    }

    _onBeforeUnload(event) {
        // Cancel the event as stated by the standard.
        event.preventDefault();
        // Chrome requires returnValue to be set.
        event.returnValue = '';
    }

    start() {
        console.assert(this instanceof MapState, 'this is not a MapState', this);
        console.warn(`Starting MapState ${this.getName()}`);
        let self = this;

        this._started = true;

        window.addEventListener('beforeunload', this._onBeforeUnload);

        // $(document).bind('keydown', function (event) {
        //     // Escape
        //     if (event.originalEvent.keyCode === 27) {
        //         self.stop();
        //     }
        // });
    }

    stop() {
        console.assert(this instanceof MapState, 'this is not a MapState', this);
        console.warn(`Stopping MapState ${this.getName()}`);
        this._stopped = true;

        window.removeEventListener('beforeunload', this._onBeforeUnload);
    }

    getName() {
        return 'UnknownMapState';
    }

    isModal() {
        return false;
    }

    isStarted() {
        return this._started;
    }

    isStopped() {
        return this._stopped;
    }
}
