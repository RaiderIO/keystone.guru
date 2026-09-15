class MapState extends Signalable {
    constructor(map) {
        super();
        console.assert(map instanceof DungeonMap, 'map is not a Map', map);

        /** @type {DungeonMap} */
        this.map = map;
        this._started = false;
        this._stopped = false;
        // The handler asks this instance whether it should warn, so it needs `this`. Binding once and
        // keeping the result means start() and stop() add/remove the exact same function object.
        this._onBeforeUnload = this._onBeforeUnload.bind(this);
    }

    _onBeforeUnload(event) {
        if (!this.shouldWarnUnsavedChanges()) {
            return;
        }

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

    /**
     * Whether navigating away while this map state is active should warn the user that they may lose
     * changes. States that know their changes are already synced with the server should override this.
     * @returns {boolean}
     */
    shouldWarnUnsavedChanges() {
        return true;
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
