class AdminEnemyPatrol extends EnemyPatrol {

    constructor(map, layer) {
        super(map, layer);

        this.setSynced(false);

        // Container
        this.enemyConnectionsLayerGroup = null;
        // Layer to has all polylines for the connected enemies to this patrol
        this.connectedEnemiesLayer = null;
    }

    /**
     * Must be explicitly overriden since EnemyPatrols cannot be deleted; admin ones can.
     * @returns {boolean}
     */
    isEditable() {
        return true;
    }

    /**
     * Removes any existing UI connections to enemies.
     */
    removeExistingConnectionsToEnemies() {
        console.assert(this instanceof EnemyPatrol, 'this is not an EnemyPatrol', this);

        // Remove previous layers if it's needed
        if (this.enemyConnectionsLayerGroup !== null) {
            let enemyPatrolMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_ENEMY_PATROL);
            // Remove layers we no longer need from the layer group
            if (this.connectedEnemiesLayer !== null) {
                this.enemyConnectionsLayerGroup.removeLayer(this.connectedEnemiesLayer);
                this.connectedEnemiesLayer = null;
            }
            // And finally remove the layer group from the KZ layer group
            enemyPatrolMapObjectGroup.layerGroup.removeLayer(this.enemyConnectionsLayerGroup);

            this.enemyConnectionsLayerGroup = null;
        }
    }

    /**
     *
     */
    redrawConnectionsToEnemies() {
        console.assert(this instanceof EnemyPatrol, 'this is not an EnemyPatrol', this);
        // for (let index in this.enemies) {
        //     console.log(`Draw enemy patrol ${this.id} line to enemy ${this.enemies[index]}`);
        // }

        this.removeExistingConnectionsToEnemies();

        this.enemyConnectionsLayerGroup = new L.LayerGroup();

        let enemyPatrolMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_ENEMY_PATROL);
        enemyPatrolMapObjectGroup.layerGroup.addLayer(this.enemyConnectionsLayerGroup);

        // Add connections from each enemy to our location
        let enemyLatLngs = this._getEnemiesLatLngs();

        if (enemyLatLngs.length > 0) {
            this.centerLatLng = this.getLayerLatLng();
            this.connectedEnemiesLayer = new L.LayerGroup();

            for (let index in enemyLatLngs) {
                if (enemyLatLngs.hasOwnProperty(index)) {
                    let coupledEnemyLatLng = enemyLatLngs[index];

                    this.connectedEnemiesLayer.addLayer(
                        L.polyline([
                            [this.centerLatLng.lat, this.centerLatLng.lng],
                            coupledEnemyLatLng
                        ], c.map.enemypatrol.polylineOptions)
                    );
                }
            }

            // do not prevent clicking on anything else
            this.enemyConnectionsLayerGroup.setZIndex(-1000);

            this.enemyConnectionsLayerGroup.addLayer(this.connectedEnemiesLayer);
        }
    }
}
