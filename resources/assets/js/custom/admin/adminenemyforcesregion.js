class AdminEnemyForcesRegion extends EnemyForcesRegion {
    constructor(map, layer) {
        super(map, layer);

        this.setSynced(false);

        // Layer group holding the lines drawn from this region to each of its member enemies.
        this.enemyConnectionsLayerGroup = null;

        getState().register('floorid:changed', this, this.redrawConnectionsToEnemies.bind(this));
        this.map.register('map:mapstatechanged', this, this.redrawConnectionsToEnemies.bind(this));
    }

    /**
     * @inheritDoc
     */
    _getAttributes(force = false) {
        console.assert(this instanceof AdminEnemyForcesRegion, 'this is not an AdminEnemyForcesRegion', this);

        if (this._cachedAttributes !== null && !force) {
            return this._cachedAttributes;
        }

        let self = this;

        return this._cachedAttributes = super._getAttributes(force).concat([
            new Attribute({
                name: 'select_enemies',
                type: 'button',
                buttonType: 'info',
                buttonText: lang.get('js.enemyforcesregion_select_enemies_button_text_label'),
                clicked: function () {
                    self.map.leafletMap.closePopup();

                    if (self.map.getMapState() instanceof EnemyForcesRegionEnemySelection) {
                        self.map.setMapState(null);
                    } else {
                        self.map.setMapState(
                            new EnemyForcesRegionEnemySelection(self.map, self)
                        );
                    }
                },
            }),
        ]);
    }

    isEditable() {
        return true;
    }

    isDeletable() {
        return true;
    }

    isEditableByPopup() {
        return true;
    }

    /**
     * Draws a line from this region to each of its member enemies that is visible on the current floor,
     * so a mapper can see at a glance what is in the region.
     */
    redrawConnectionsToEnemies() {
        console.assert(this instanceof AdminEnemyForcesRegion, 'this is not an AdminEnemyForcesRegion', this);

        this.removeExistingConnectionsToEnemies();

        // The lines start at this region's marker, so there's nothing to draw when it isn't on screen.
        if (!this.shouldBeVisible() || this.layer === null) {
            return;
        }

        let centerLatLng = this.layer.getLatLng();
        let enemies = this.getEnemies();

        let latLngs = [];
        for (let index in enemies) {
            let enemy = enemies[index];

            if (enemy.shouldBeVisible() && enemy.layer !== null) {
                latLngs.push(enemy.layer.getLatLng());
            }
        }

        if (latLngs.length === 0) {
            return;
        }

        this.enemyConnectionsLayerGroup = new L.LayerGroup();

        for (let index in latLngs) {
            this.enemyConnectionsLayerGroup.addLayer(
                L.polyline([
                    [centerLatLng.lat, centerLatLng.lng],
                    latLngs[index],
                ], c.map.adminenemyforcesregion.polylineOptions)
            );
        }

        // Do not prevent clicking on anything else
        this.enemyConnectionsLayerGroup.setZIndex(-1000);
        this.enemyConnectionsLayerGroup.addTo(this.map.leafletMap);
    }

    /**
     * Removes any existing UI connections to enemies.
     */
    removeExistingConnectionsToEnemies() {
        console.assert(this instanceof AdminEnemyForcesRegion, 'this is not an AdminEnemyForcesRegion', this);

        if (this.enemyConnectionsLayerGroup !== null) {
            this.map.leafletMap.removeLayer(this.enemyConnectionsLayerGroup);
            this.enemyConnectionsLayerGroup = null;
        }
    }

    /**
     * @inheritDoc
     */
    refreshPill() {
        console.assert(this instanceof AdminEnemyForcesRegion, 'this is not an AdminEnemyForcesRegion', this);
        super.refreshPill();

        this.redrawConnectionsToEnemies();
    }

    toString() {
        console.assert(this instanceof AdminEnemyForcesRegion, 'this is not an AdminEnemyForcesRegion', this);

        return `Enemy forces region-${this.id}`;
    }

    cleanup() {
        console.assert(this instanceof AdminEnemyForcesRegion, 'this is not an AdminEnemyForcesRegion', this);
        super.cleanup();

        this.removeExistingConnectionsToEnemies();

        getState().unregister('floorid:changed', this);
        this.map.unregister('map:mapstatechanged', this);
    }
}
