class Hotkeys extends Signalable {
    constructor(map) {
        super();
        this.map = map;

        this.map.register('map:refresh', this, (this._mapRefreshed).bind(this));
        this.keys = [];
    }

    /**
     * Called whenever leaflet map has been refreshed.
     * @param refreshEvent {Object}
     * @private
     */
    _mapRefreshed(refreshEvent) {
        console.assert(this instanceof Hotkeys, 'this is not an instance of Hotkeys', this);
        let self = this;

        this.map.leafletMap.on('keydown', function (event) {
            // Ignore keypress events in a text area
            if (!self.map.hasPopupOpen()) {
                self.onKeyPressed.call(self, event);
            }
        });
    }

    /**
     * Associates a key with a classname to press (of the button in the drawcontrol)
     * @param key {String}
     * @param className {String}
     * @param enabled {Function|null}
     */
    attach(key, className, enabled) {
        this.keys.push({
            key: key,
            className: className,
            enabled: enabled
        })
    }

    /**
     * Called whenever a key was pressed on the leaflet map.
     * @param event {Object}
     */
    onKeyPressed(event) {
        console.assert(this instanceof Hotkeys, 'this is not an instance of Hotkeys', this);

        let keyEvent = event.originalEvent;
        let keyObj = null;
        for (let i = 0; i < this.keys.length; i++) {
            let keyObjCandidate = this.keys[i];
            if (keyObjCandidate.key === keyEvent.key &&
                (typeof keyObjCandidate.enabled !== 'function' || (typeof keyObjCandidate.enabled === 'function' && keyObjCandidate.enabled()))
            ) {
                keyObj = keyObjCandidate;
                break;
            }
        }

        if (keyObj !== null) {
            this._triggerClickOnClass(keyObj.className);

            this.signal('hotkey:pressed', {
                key: keyObj,
                event: event
            });
        }
    }

    /**
     * Forces a click event directly on the first object with the associated class name.
     * @param className {String}
     * @private
     */
    _triggerClickOnClass(className) {
        console.assert(this instanceof Hotkeys, 'this is not an instance of Hotkeys', this);

        let newEvent = document.createEvent('Event');
        newEvent.initEvent('click', true, true);

        let cb = document.getElementsByClassName(className);
        !cb[0].dispatchEvent(newEvent);
    }
}
