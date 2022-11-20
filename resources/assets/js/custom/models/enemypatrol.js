// $(function () {
L.Draw.EnemyPatrol = L.Draw.Polyline.extend({
    statics: {
        TYPE: 'enemypatrol'
    },
    initialize: function (map, options) {
        // Save the type so super can fire, need to do this as cannot do this.TYPE :(
        this.type = L.Draw.EnemyPatrol.TYPE;
        L.Draw.Feature.prototype.initialize.call(this, map, options);
    }
});

// });

class EnemyPatrol extends Polyline {
    constructor(map, layer) {
        super(map, layer, {name: 'enemypatrol', hasRouteModelBinding: true});

        this.weight = c.map.enemypatrol.defaultWeight;

        this.label = 'EnemyPatrol';
    }


    _getAttributes(force) {
        console.assert(this instanceof EnemyPack, 'this was not an EnemyPack', this);

        if (this._cachedAttributes !== null && !force) {
            return this._cachedAttributes;
        }

        let self = this;

        return this._cachedAttributes = super._getAttributes(force).concat([
            new Attribute({
                name: 'couple_enemies',
                type: 'button',
                buttonType: 'info',
                buttonText: lang.get('messages.enemypatrol_couple_enemies_button_text_label'),
                default: getState().getCurrentFloor().id,
                clicked: function (e) {
                    self.map.leafletMap.closePopup();

                    if (self.map.getMapState() instanceof EnemyPatrolEnemySelection) {
                        self.map.setMapState(null);
                    } else {
                        self.map.setMapState(
                            new EnemyPatrolEnemySelection(self.map, self)
                        );
                    }
                }
            })
        ]);
    }

    /**
     *
     * @returns {function}
     * @protected
     */
    _getPolylineColorDefault() {
        return c.map.enemypatrol.defaultColor;
    }

    /**
     * Gets the actual decorator for this map object.
     * @returns {*}
     * @private
     */
    _getDecorator() {
        return L.polylineDecorator(this.layer, {
            patterns: [
                {
                    offset: 12,
                    repeat: 25,
                    symbol: L.Symbol.dash({
                        pixelSize: 10,
                        pathOptions: {color: this.polyline.color, weight: 2}
                    })
                },
                {
                    offset: 25,
                    repeat: 50,
                    symbol: L.Symbol.arrowHead({
                        pixelSize: 12,
                        pathOptions: {fillOpacity: 1, weight: 0, color: this.polyline.color}
                    })
                }
            ]
        });
    }

    /**
     * Users cannot delete this. AdminEnemyPatrols may be deleted instead.
     * @returns {boolean}
     */
    isDeletable() {
        return false;
    }

    /**
     * Users cannot edit this. AdminEnemyPatrols may be edited instead.
     * @returns {boolean}
     */
    isEditable() {
        return false;
    }

    toString() {
        return 'Enemy patrol-' + this.id;
    }
}
