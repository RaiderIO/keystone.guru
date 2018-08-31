class AdminDungeonMap extends DungeonMap {

    /**
     * Get a new instance of a DrawControls object which will be added to the map
     * @param drawnItemsLayer
     * @returns {DrawControls}
     * @protected
     */
    _getDrawControls(drawnItemsLayer) {
        return new AdminDrawControls(this, drawnItemsLayer);
    }

    /**
     *
     * @returns {[]}
     * @protected
     */
    _createMapObjectGroups() {
        // For this page, let the enemy pack be the admin version with more functions which are otherwise hidden from the user
        return [
            new EnemyMapObjectGroup(this, 'enemy', 'AdminEnemy', true),
            new EnemyPatrolMapObjectGroup(this, 'enemypatrol', 'AdminEnemyPatrol', true),
            new EnemyPackMapObjectGroup(this, 'enemypack', 'AdminEnemyPack', true),
            new DungeonStartMarkerMapObjectGroup(this, 'dungeonstartmarker', 'AdminDungeonStartMarker', true),
            new DungeonFloorSwitchMarkerMapObjectGroup(this, 'dungeonfloorswitchmarker', 'AdminDungeonFloorSwitchMarker', true),
        ];
    }

    refreshLeafletMap() {
        super.refreshLeafletMap();

        let verboseEvents = false;

        let self = this;

        this.enemyAttaching = new EnemyAttaching(this);

        // Set all edited layers to no longer be synced.
        this.leafletMap.on(L.Draw.Event.EDITED, function (e) {
            let layers = e.layers;
            layers.eachLayer(function (layer) {
                console.log(layer, "Edited a layer!");
                let mapObject = self.findMapObjectByLayer(layer);
                console.assert(mapObject instanceof MapObject, mapObject, 'mapObject is not a MapObject');
                mapObject.setSynced(false);
            });
        });

        if (verboseEvents) {
            this.leafletMap.on(L.Draw.Event.DRAWSTART, function (e) {
                console.log(L.Draw.Event.DRAWSTART, e);
            });

            this.leafletMap.on('layeradd', function (e) {
                console.log('layeradd', e);
            });

            this.leafletMap.on(L.Draw.Event.CREATED, function (e) {
                console.log(L.Draw.Event.CREATED, e);
            });
            this.leafletMap.on(L.Draw.Event.EDITED, function (e) {
                console.log(L.Draw.Event.EDITED, e);
            });
            this.leafletMap.on(L.Draw.Event.DELETED, function (e) {
                console.log(L.Draw.Event.DELETED, e);
            });
            this.leafletMap.on(L.Draw.Event.DRAWSTART, function (e) {
                console.log(L.Draw.Event.DRAWSTART, e);
            });
            this.leafletMap.on(L.Draw.Event.DRAWSTOP, function (e) {
                console.log(L.Draw.Event.DRAWSTOP, e);
            });
            this.leafletMap.on(L.Draw.Event.DRAWVERTEX, function (e) {
                console.log(L.Draw.Event.DRAWVERTEX, e);
            });
            this.leafletMap.on(L.Draw.Event.EDITSTART, function (e) {
                console.log(L.Draw.Event.EDITSTART, e);
            });
            this.leafletMap.on(L.Draw.Event.EDITMOVE, function (e) {
                console.log(L.Draw.Event.EDITMOVE, e);
            });
            this.leafletMap.on(L.Draw.Event.EDITRESIZE, function (e) {
                console.log(L.Draw.Event.EDITRESIZE, e);
            });
            this.leafletMap.on(L.Draw.Event.EDITVERTEX, function (e) {
                console.log(L.Draw.Event.EDITVERTEX, e);
            });
            this.leafletMap.on(L.Draw.Event.EDITSTOP, function (e) {
                console.log(L.Draw.Event.EDITSTOP, e);
            });
            this.leafletMap.on(L.Draw.Event.DELETESTART, function (e) {
                console.log(L.Draw.Event.DELETESTART, e);
            });
            this.leafletMap.on(L.Draw.Event.DELETESTOP, function (e) {
                console.log(L.Draw.Event.DELETESTOP, e);
            });
            this.leafletMap.on(L.Draw.Event.TOOLBAROPENED, function (e) {
                console.log(L.Draw.Event.TOOLBAROPENED, e);
            });
            this.leafletMap.on(L.Draw.Event.TOOLBARCLOSED, function (e) {
                console.log(L.Draw.Event.TOOLBARCLOSED, e);
            });
            this.leafletMap.on(L.Draw.Event.MARKERCONTEXT, function (e) {
                console.log(L.Draw.Event.MARKERCONTEXT, e);
            });
        }
    }
}