class EnemyVisualManager extends Signalable {

    constructor(map) {
        super();
        let self = this;

        this.map = map;
        // Keeping track if the map is being dragged; if so don't handle mouse move events as mouse is locked in place on map
        this._isMapBeingDragged = false;
        // The last time we checked moving the mouse and triggering mouse out/in events
        this._lastMouseMoveDistanceCheckTime = 0
        this._lastMapMoveDistanceCheckTime = 0;
        // Keep track of some data between mouse and enemy for each enemy
        this._enemyMouseMoveDistanceData = [];
        // Used for storing previous visibility states so we know when to refresh the enemy or not
        this._enemyVisibilityMap = [];

        let enemyMapObjectGroup = self.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_ENEMY);
        enemyMapObjectGroup.register('object:add', this, function (objectAddEvent) {
            self._enemyVisibilityMap[objectAddEvent.data.object.id] = {
                wasVisible: objectAddEvent.data.object.isVisibleOnMap(),
                lastRefreshedZoomLevel: getState().getMapZoomLevel()
            };
            self._enemyMouseMoveDistanceData[objectAddEvent.data.object.id] = {
                lastCheckTime: 0,
                lastDistanceSquared: 99999
            };
        });

        getState().register('mapzoomlevel:changed', this, this._onZoomLevelChanged.bind(this));
        this.map.register('map:refresh', this, function () {
            self.map.leafletMap.on('mousemove', self._onLeafletMapMouseMove.bind(self));

            self.map.leafletMap.on('movestart', self._onLeafletMapMoveStart.bind(self));
            self.map.leafletMap.on('move', self._onLeafletMapMove.bind(self));
            self.map.leafletMap.on('moveend', self._onLeafletMapMoveEnd.bind(self));
        });
        this.map.register('map:beforerefresh', this, function () {
            self.map.leafletMap.off('mousemove', self._onLeafletMapMouseMove.bind(self));
        });
    }

    /**
     * Called when the map's zoom level was changed.
     * @param zoomLevelChangedEvent
     * @private
     */
    _onZoomLevelChanged(zoomLevelChangedEvent) {
        console.assert(this instanceof EnemyVisualManager, 'this is not an EnemyVisualManager!', this);

        let currentZoomLevel = getState().getMapZoomLevel();
        let enemyMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_ENEMY);
        for (let i = 0; i < enemyMapObjectGroup.objects.length; i++) {
            let enemy = enemyMapObjectGroup.objects[i];

            // Only refresh what we can see
            if (enemy.isVisibleOnMap()) {
                // If we're mouse hovering the visual, just rebuild it entirely. There are a few things which need
                // reworking to support a full refresh of the visual
                if (enemy.visual.isHighlighted()) {
                    window.requestAnimationFrame(enemy.visual.buildVisual.bind(enemy.visual));
                } else {
                    window.requestAnimationFrame(enemy.visual.refreshSize.bind(enemy.visual));
                }
                // Keep track that we already refreshed all these so they won't be refreshed AGAIN upon move
                this._enemyVisibilityMap[enemy.id].lastRefreshedZoomLevel = currentZoomLevel;
            }
        }
    }

    /**
     * Called when the mouse has moved over the leaflet map
     * @param mouseMoveEvent
     * @private
     */
    _onLeafletMapMouseMove(mouseMoveEvent) {
        console.assert(this instanceof EnemyVisualManager, 'this is not an EnemyVisualManager!', this);

        if (!this._isMapBeingDragged) {
            let currTime = (new Date()).getTime();

            // Once every 100 ms, calculation is expensive
            if (currTime - this._lastMouseMoveDistanceCheckTime > 50) {
                let enemyMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_ENEMY);
                for (let i = 0; i < enemyMapObjectGroup.objects.length; i++) {
                    let enemy = enemyMapObjectGroup.objects[i];

                    if (enemy.isVisibleOnMap()) {
                        let lastCheckData = this._enemyMouseMoveDistanceData[enemy.id];
                        if (currTime - lastCheckData.lastCheckTime > 500 * lastCheckData.lastDistanceSquared / 1000000) {
                            // console.log('checktime: ', 200 + (1000 * (lastCheckData.lastDistanceSquared / 1000000)));
                            // console.log(this._enemyMouseMoveDistanceData[enemy.id].lastDistanceSquared);
                            this._enemyMouseMoveDistanceData[enemy.id].lastDistanceSquared =
                                enemy.visual.checkMouseOver(mouseMoveEvent.originalEvent.pageX, mouseMoveEvent.originalEvent.pageY);

                            // Direct manipulation
                            this._enemyMouseMoveDistanceData[enemy.id].lastCheckTime = currTime;
                        }
                    }
                }

                this._lastMouseMoveDistanceCheckTime = currTime;
            }

        }
    }

    /**
     * Called when the user starts dragging the map.
     * @param mouseMoveEvent
     * @private
     */
    _onLeafletMapMoveStart(mouseMoveEvent) {
        console.assert(this instanceof EnemyVisualManager, 'this is not an EnemyVisualManager!', this);

        this._isMapBeingDragged = true;
    }

    /**
     * Called when the user is actively dragging the mouse (repeatedly).
     * @param mouseMoveEvent
     * @private
     */
    _onLeafletMapMove(mouseMoveEvent) {
        console.assert(this instanceof EnemyVisualManager, 'this is not an EnemyVisualManager!', this);

        let currentZoomLevel = getState().getMapZoomLevel();

        let currTime = (new Date()).getTime();
        // Once every 100 ms, calculation is expensive
        if (currTime - this._lastMapMoveDistanceCheckTime > 100) {
            let enemyMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_ENEMY);
            for (let i = 0; i < enemyMapObjectGroup.objects.length; i++) {
                let enemy = enemyMapObjectGroup.objects[i];

                let isVisible = enemy.isVisibleOnMap();

                // If panned into view AND we didn't already refresh the zoom earlier
                if (this._enemyVisibilityMap[enemy.id].wasVisible === false && isVisible &&
                    this._enemyVisibilityMap[enemy.id].lastRefreshedZoomLevel !== currentZoomLevel) {
                    console.log(`Refreshing view of enemy ${enemy.id}`);
                    window.requestAnimationFrame(enemy.visual.refreshSize.bind(enemy.visual));
                    this._enemyVisibilityMap[enemy.id].lastRefreshedZoomLevel = currentZoomLevel;
                }

                // Write new visible state
                this._enemyVisibilityMap[enemy.id].wasVisible = isVisible;
            }

            this._lastMapMoveDistanceCheckTime = currTime;
        }
    }

    /**
     * Called when the user stops dragging the mouse.
     * @param mouseMoveEvent
     * @private
     */
    _onLeafletMapMoveEnd(mouseMoveEvent) {
        console.assert(this instanceof EnemyVisualManager, 'this is not an EnemyVisualManager!', this);

        // Force a refresh
        this._lastMapMoveDistanceCheckTime = 0;
        this._onLeafletMapMove(mouseMoveEvent);

        this._isMapBeingDragged = false;
    }
}