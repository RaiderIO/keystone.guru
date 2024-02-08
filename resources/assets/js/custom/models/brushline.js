L.Draw.Brushline = L.Draw.Polyline.extend({
    statics: {
        TYPE: 'brushline'
    },
    initialize: function (map, options) {
        options.showLength = false;
        // Save the type so super can fire, need to do this as cannot do this.TYPE :(
        this.type = L.Draw.Brushline.TYPE;
        L.Draw.Feature.prototype.initialize.call(this, map, options);
    }
});

class Brushline extends Polyline {
    constructor(map, layer) {
        super(map, layer, {name: 'brushline', has_route_model_binding: true});

        this.label = 'Brushline';
        this.type = 'brushline';
        this.decorator = null;

        this.setSynced(false);
    }

    toString() {
        return 'Line';
    }
}
