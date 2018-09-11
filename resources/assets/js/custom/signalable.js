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
        console.assert(this instanceof Signalable, this, 'this is not a Signalable!');
        console.assert(typeof name === 'string', name, 'name is not a string!');
        console.assert(typeof listener !== 'function', listener, 'listener is not a function! You probably forgot to add the listener');
        console.assert(typeof fn === 'function', listener, 'fn should be a function');

        // console.log('pushing ', name, fn);
        this.signals.push({
            name: name,
            listener: listener,
            callback: fn
        })
    }

    /**
     * Stop listening to a signal.
     * @param name string The name of the event you want to stop listening to.
     * @param listener object Whoever you are and what you used to register yourself with.
     */
    unregister(name, listener) {
        console.assert(this instanceof Signalable, this, 'this is not a Signalable!');
        let toRemove = [];

        for (let i = 0; i < this.signals.length; i++) {
            let caller = this.signals[i];
            if (caller.name === name && caller.listener === listener) {
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

    signal(name, data = {}) {
        console.assert(this instanceof Signalable, this, 'this is not a Signalable!');
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
     * @private
     */
    _cleanupSignals() {
        // Should be enough to get rid of all signals
        this.signals = [];
    }
}