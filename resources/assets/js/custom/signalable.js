class Signalable {

    constructor() {
        this._signals = [];
        this._cleanedUp = false;
    }

    /**
     * Registers for listening to a signal sent by this Signalable.
     * @param name {string|array} The name of the event you want to listen for.
     * @param listener {object} Who are you? Pass this.
     * @param fn {function(*): void} The function that should be triggered.
     */
    register(name, listener, fn) {
        console.assert(this instanceof Signalable, 'this is not a Signalable!', this);
        console.assert(typeof name === 'string' || Array.isArray(name), 'name is not a string|array!', name);
        console.assert(typeof listener !== 'function', 'listener should not be a function! You probably forgot to pass reference to this/self', listener);
        console.assert(typeof fn === 'function', 'fn should be a function', listener);

        if (typeof name === 'string') {
            // Convert to array
            name = [name];
        }

        // Check if we're already registered, if so throw an error
        for (let i = 0; i < name.length; i++) {
            for (let j = 0; j < this._signals.length; j++) {
                let caller = this._signals[j];

                if (caller.name === name[i] && caller.listener === listener && caller.callback === fn) {
                    console.error(`Already registered for '${name[i]}'! Unable to double register, aborting`, caller);
                    return false;
                }
            }
        }

        for (let i = 0; i < name.length; i++) {
            for (let j = 0; j < this._signals.length; j++) {
                let signal = this._signals[j];
                if (signal.name === name[i] && signal.listener === listener && signal.callback === fn) {
                    console.warn('About to hook the same signal for the 2nd time!', name[i], listener);
                }
            }
            this._signals.push({
                name: name[i],
                listener: listener,
                callback: fn
            });
        }
    }

    /**
     * Stop listening to a signal.
     * @param name {string|array} The name of the event you want to stop listening to.
     * @param listener {object} Whoever you are and what you used to register yourself with.
     * @param fn {callable|null} The function you wish to unregister. Null to remove everything.
     */
    unregister(name, listener, fn = null) {
        console.assert(this instanceof Signalable, 'this is not a Signalable!', this);
        console.assert(typeof name === 'string' || Array.isArray(name), 'name is not a string|array!', name);

        if (typeof name === 'string') {
            // Convert to array
            name = [name];
        }

        for (let i = 0; i < name.length; i++) {
            let toRemove = [];

            for (let j = 0; j < this._signals.length; j++) {
                let caller = this._signals[j];
                if (caller.name === name[i] && caller.listener === listener && (fn === null || caller.callback === fn)) {
                    toRemove.push(j);
                }
            }

            // console.assert(toRemove.length > 0, 'Unregistered event "' + name + '" for listener ', listener, ' but said listener was not found on our ', this);

            // Reverse the loop, we're removing multiple indices. If we start with smallest first,
            // we're going to remove the wrong indexes after the first one. Not good. Reverse preserves the proper order.
            for (let j = toRemove.length - 1; j >= 0; j--) {
                this._signals.splice(toRemove[j], 1);
            }
        }

    }

    /**
     * Signals to listeners that something has happened.
     * @param name {string} The name of the signal you're sending.
     * @param data {object} Any data you wish to pass, optional.
     */
    signal(name, data = {}) {
        console.assert(this instanceof Signalable, 'this is not a Signalable!', this);
        let self = this;

        for (let i = 0; i < this._signals.length; i++) {
            let caller = this._signals[i];

            if (caller.name === name) {
                if (caller.listener instanceof Signalable && caller.listener._cleanedUp) {
                    console.error(`Unable to send signal '${caller.name}' to object because it's cleaned up and it should have unregged!`, caller);
                } else {
                    caller.callback({name: name, context: self, data: data});
                }
            }
        }
    }

    /**
     * DROPS ALL LISTENERS FROM ALL SIGNALS OF THIS INSTANCE. May fuck your shit up.
     * @protected
     */
    _cleanupSignals() {
        // Should be enough to get rid of all _signals
        this._signals = [];
    }

    cleanup() {
        this._cleanedUp = true;
    }
}
