class Signalable {

    constructor() {
        this.signals = [];
    }

    register(name, fn) {
        console.assert(this instanceof Signalable, this, 'this is not a Signalable!');

        // console.log('pushing ', name, fn);
        this.signals.push({
            name: name,
            callback: fn
        })
    }

    unregister(name){
        // TODO
    }

    signal(name, data) {
        let self = this;
        console.assert(self instanceof Signalable, self, 'this is not a Signalable!');

        $.each(this.signals, function (index, caller) {
            // console.log('caller:', caller);
            if (caller.name === name) {
                caller.callback({context: self, data: data});
            }
        });
    }
}