$(function () {
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
});

class Brushline extends Polyline {
    constructor(map, layer) {
        super(map, layer);

        this.label = 'Brushline';
        this.type = 'brushline';
        this.decorator = null;

        this.setSynced(false);
    }

    /**
     * @inheritDoc
     */
    _getRouteSuffix() {
        return 'brushline';
    }

    isEditable() {
        return true;
    }

    toString() {
        return 'Line';
    }
}