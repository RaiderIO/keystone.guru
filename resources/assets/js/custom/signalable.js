class Signalable {

    constructor() {
        this.signals = [];
    }

    /**
     * Registers for listening to a signal sent by this Signalable.
     * @param name string The name of the event you want to listen for.
     * @param listener object Who are you? Pass this.
     * @param fn function The function that should be triggered.
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


        for (let i = 0; i < name.length; i++) {
            for (let j = 0; j < this.signals.length; j++) {
                let signal = this.signals[j];
                if (signal.name === name && signal.listener === listener && signal.callback === fn) {
                    console.warn('About to hook the same signal for the 2nd time!', name, listener);
                }
            }
            this.signals.push({
                name: name[i],
                listener: listener,
                callback: fn
            });
        }
    }

    /**
     * Stop listening to a signal.
     * @param name string The name of the event you want to stop listening to.
     * @param listener object Whoever you are and what you used to register yourself with.
     * @param fn callable|null The function you wish to unregister. Null to remove everything.
     */
    unregister(name, listener, fn = null) {
        console.assert(this instanceof Signalable, 'this is not a Signalable!', this);
        let toRemove = [];

        for (let i = 0; i < this.signals.length; i++) {
            let caller = this.signals[i];
            if (caller.name === name && caller.listener === listener && (fn === null || caller.callback === fn)) {
                toRemove.push(i);
            }
        }

        // console.assert(toRemove.length > 0, 'Unregistered event "' + name + '" for listener ', listener, ' but said listener was not found on our ', this);

        // Reverse the loop, we're removing multiple indices. If we start with smallest first,
        // we're going to remove the wrong indexes after the first one. Not good. Reverse preserves the proper order.
        for (let i = toRemove.length - 1; i >= 0; i--) {
            this.signals.splice(toRemove[i], 1);
        }

    }

    /**
     * Signals to listeners that something has happened.
     * @param name string The name of the signal you're sending.
     * @param data object Any data you wish to pass, optional.
     */
    signal(name, data = {}) {
        console.assert(this instanceof Signalable, 'this is not a Signalable!', this);
        let self = this;

        for (let i = 0; i < this.signals.length; i++) {
            let caller = this.signals[i];

            if (caller.name === name) {
                caller.callback({context: self, data: data});
            }
        }
    }

    /**
     * DROPS ALL LISTENERS FROM ALL SIGNALS OF THIS INSTANCE. May fuck your shit up.
     * @protected
     */
    _cleanupSignals() {
        // Should be enough to get rid of all signals
        this.signals = [];
    }
}