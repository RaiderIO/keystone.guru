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


        if (this.map.options.edit) {
            window.addEventListener('beforeunload', this._onBeforeUnload);
        }

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

        if (this.map.options.edit) {
            window.removeEventListener('beforeunload', this._onBeforeUnload);
        }
    }

    getName() {
        return 'UnknownMapState';
    }

    isModal() {
        return false;
    }

    shouldRebuildEnemyVisuals() {
        return false;
    }

    /**
     * Whether enemy tooltips should be suppressed while this map state is active. Tooltips don't
     * play nice with the yellow selection border (editing) or enemy-selection click handling -
     * they fight for the same space and get in the way.
     * @returns {boolean}
     */
    disablesTooltips() {
        return false;
    }

    isStarted() {
        return this._started;
    }

    isStopped() {
        return this._stopped;
    }
}

// Guarded export for the test runner (Vitest). This is a no-op in the browser,
// where `module` is undefined, so it does not affect the concatenated bundle.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        MapState,
    };
}
