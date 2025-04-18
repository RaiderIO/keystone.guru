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
        super(map, layer, {name: 'enemypatrol', has_route_model_binding: true});

        let self = this;

        this.weight = c.map.enemypatrol.defaultWeight;

        this.label = 'EnemyPatrol';
        this.highlighted = false;

        // The assigned enemies to this enemy patrol
        this.enemies = [];
        getState().register('focusedenemy:changed', this, function (focusedEnemyChangedEvent) {
            let enemy = focusedEnemyChangedEvent.data.focusedenemy;
            // console.log('focusedenemy:changed', enemy, self.enemies, self.enemies.includes(enemy));
            if (enemy === null && self.highlighted) {
                self._onAttachedEnemyMouseOut();
            } else if (self.enemies.includes(enemy)) {
                self._onAttachedEnemyMouseOver();
            }
        });
    }


    _getAttributes(force) {
        console.assert(this instanceof EnemyPatrol, 'this was not an EnemyPack', this);

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
     * Smoothes out the patrol a bit so the edges aren't that sharp
     *
     * @returns {L.Layer|null}
     */
    _updateOffsetLayer() {
        console.assert(this instanceof EnemyPatrol, 'this is not an EnemyPatrol', this);

        // Build a layer based off a hull if we're supposed to
        let vertices = this.getVertices();


        let latLngs = [];
        for (let index in vertices) {
            let vertex = vertices[index];
            latLngs.push([vertex.lat, vertex.lng]);
        }

        // Must have at least 3 points to create a polygon
        if (latLngs.length > 3) {
            try {
                latLngs = (new Offset()).data(latLngs).arcSegments(c.map.enemypatrol.arcSegments(latLngs.length))
                    .margin(c.map.enemypatrol.margin);

                // Sometimes the offset creates 2 polygons - not passing just the 1st entry will suddenly create
                // multiple lines. The 2nd entry will be a line inside the polygon, somehow
                this.layer.setLatLngs(latLngs[0]);
                this.rebindTooltip();
            } catch (error) {
                // Not particularly interesting to spam the console with
                console.error('Unable to create offset for patrol', this.id, error, vertices, latLngs);
            }
        }
    }

    /**
     * Gets the actual decorator for this map object.
     * @returns {*}
     * @private
     */
    _getDecorator() {
        console.assert(this instanceof EnemyPatrol, 'this was not an EnemyPatrol', this);

        return L.polylineDecorator(this.layer, {
            patterns: [
                {
                    offset: 12,
                    repeat: 25,
                    symbol: L.Symbol.dash({
                        pixelSize: 10,
                        pathOptions: $.extend({}, c.map.enemypatrol.polylineDecoratorOptions, {color: this.polyline.color})
                    })
                },
                {
                    offset: 25,
                    repeat: 50,
                    symbol: L.Symbol.arrowHead({
                        pixelSize: 12,
                        pathOptions: $.extend({}, c.map.enemypatrol.polylineDecoratorOptions, {
                            weight: 0,
                            color: this.polyline.color
                        })
                    })
                }
            ]
        });
    }

    /**
     *
     * @returns {*[]}
     * @private
     */
    _getEnemiesLatLngs() {
        console.assert(this instanceof EnemyPatrol, 'this was not an EnemyPatrol', this);

        let result = [];
        for (let index in this.enemies) {
            let enemyCandidate = this.enemies[index];

            result.push(enemyCandidate.layer.getLatLng());
        }

        return result;
    }

    /**
     * @inheritDoc
     **/
    loadRemoteMapObject(remoteMapObject, parentAttribute = null) {
        super.loadRemoteMapObject(remoteMapObject, parentAttribute);

        // Only called when not in admin state
        if (!(getState().getMapContext() instanceof MapContextMappingVersionEdit)) {
            this._updateOffsetLayer();
        }
    }

    /**
     *
     * @returns {*}
     */
    getLayerLatLng() {
        console.assert(this instanceof EnemyPatrol, 'this was not an EnemyPatrol', this);

        let vertices = this.getVertices();

        if (vertices.length === 0) {
            // Make it obvious something is wrong
            return {'lat': 0, 'lng': 0};
        } else if (vertices.length === 2) {
            return {'lat': (vertices[0].lat + vertices[1].lat) / 2, 'lng': (vertices[0].lng + vertices[1].lng) / 2};
        } else {
            // Just get the middle one
            return vertices[Math.floor(vertices.length / 2)];
        }
    }

    /**
     *
     * @param enemy
     */
    addEnemy(enemy) {
        console.assert(this instanceof EnemyPatrol, 'this was not an EnemyPatrol', this);

        this.enemies.push(enemy);
    }

    /**
     *
     * @param enemy
     */
    removeEnemy(enemy) {
        console.assert(this instanceof EnemyPatrol, 'this was not an EnemyPatrol', this);

        let newEnemies = [];
        for (let index in this.enemies) {
            let enemyCandidate = this.enemies[index];
            if (enemyCandidate.id !== enemy.id) {
                newEnemies.push(enemyCandidate);
            }
        }

        this.enemies = newEnemies;
    }

    /**
     *
     */
    _onAttachedEnemyMouseOver() {
        console.assert(this instanceof EnemyPatrol, 'this was not an EnemyPatrol', this);

        this.highlighted = true;

        this.layer.setStyle($.extend({}, c.map.enemypatrol.polylineOptionsHighlighted, {color: this.polyline.color}));
        this.layer.redraw();

        // Refresh the decorator by firing a changed event
        this.signal('object:changed');
    }

    /**
     *
     */
    _onAttachedEnemyMouseOut() {
        console.assert(this instanceof EnemyPatrol, 'this was not an EnemyPatrol', this);

        this.highlighted = false;

        this.layer.setStyle($.extend({}, c.map.enemypatrol.polylineOptions, {color: this.polyline.color}));
        this.layer.redraw();

        // Refresh the decorator by firing a changed event
        this.signal('object:changed');
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
        return `Enemy patrol-${this.id}`;
    }

    cleanup() {
        super.cleanup();

        getState().unregister('focusedenemy:changed', this);
    }
}
