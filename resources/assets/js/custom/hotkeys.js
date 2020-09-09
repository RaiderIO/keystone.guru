class Hotkeys {
    constructor(map) {
        this.map = map;

        this.map.register('map:refresh', this, (this._mapRefreshed).bind(this));
        this.keys = [];
    }

    /**
     * Called whenever leaflet map has been refreshed.
     * @param refreshEvent
     * @private
     */
    _mapRefreshed(refreshEvent) {
        console.assert(this instanceof Hotkeys, 'this is not an instance of Hotkeys', this);
        let self = this;

        this.map.leafletMap.on('keypress', function (event) {
            // Ignore keypress events in a text area
            if (!self.map.hasPopupOpen()) {
                self.onKeyPressed.call(self, event);
            }
        });
    }

    /**
     * Associates a key with a classname to press (of the button in the drawcontrol)
     * @param key string
     * @param className string
     * @param enabled function|null
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
     * @param event
     */
    onKeyPressed(event) {
        console.assert(this instanceof Hotkeys, 'this is not an instance of Hotkeys', this);

        let keyEvent = event.originalEvent;
        let className = '';
        for (let i = 0; i < this.keys.length; i++) {
            let keyObj = this.keys[i];
            if (keyObj.key === keyEvent.key &&
                (typeof keyObj.enabled !== 'function' || (typeof keyObj.enabled === 'function' && keyObj.enabled()))
            ) {
                className = keyObj.className;
                break;
            }
        }

        if (className !== '') {
            this._triggerClickOnClass(className);
        }
    }

    /**
     * Forces a click event directly on the first object with the associated class name.
     * @param className
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