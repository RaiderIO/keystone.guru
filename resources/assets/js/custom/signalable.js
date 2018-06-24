class Signalable {

    constructor() {
        this.signals = [];
    }

    on(name, fn) {
        // console.log('pushing ', name, fn);
        this.signals.push({
            name: name,
            callback: fn
        })
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