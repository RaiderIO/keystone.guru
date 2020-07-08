class EnemyVisualManager extends Signalable {

    constructor(map) {
        super();
        let self = this;

        this.map = map;
        this._lastMapMoveDistanceCheckTime = 0;
        // Keep track of when we last checked distance between mouse and enemy for each enemy
        this._enemyMouseMoveDistanceCheckTimes = [];
        // Used for storing previous visibility states so we know when to refresh the enemy or not
        this._enemyVisibilityMap = [];

        let enemyMapObjectGroup = self.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_ENEMY);
        enemyMapObjectGroup.register('object:add', this, function (objectAddEvent) {
            self._enemyVisibilityMap[objectAddEvent.data.object.id] = objectAddEvent.data.object.isVisible();
            self._enemyMouseMoveDistanceCheckTimes[objectAddEvent.data.object.id] = 0;
        });

        getState().register('mapzoomlevel:changed', this, this._onZoomLevelChanged.bind(this));
        this.map.register('map:refresh', this, function () {
            self.map.leafletMap.on('mousemove', self._onLeafletMapMouseMove.bind(self));
            self.map.leafletMap.on('move', self._onLeafletMapMove.bind(self));
            self.map.leafletMap.on('moveend', self._onLeafletMapMoveEnd.bind(self));

            // Init the visibility map so we don't have to do isset checks
            for (let i = 0; i < enemyMapObjectGroup.objects.length; i++) {
                let enemy = enemyMapObjectGroup.objects[i];

                self._enemyVisibilityMap[enemy.id] = enemy.isVisibleOnMap();
                self._enemyMouseMoveDistanceCheckTimes[enemy.id] = 0;
            }
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

        let enemyMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_ENEMY);
        for (let i = 0; i < enemyMapObjectGroup.objects.length; i++) {
            let enemy = enemyMapObjectGroup.objects[i];

            // Only refresh what we can see
            if (enemy.isVisibleOnMap()) {
                // console.log(`Refreshing enemy ${enemy.id}`);
                // If we're mouse hovering the visual, just rebuild it entirely. There are a few things which need
                // reworking to support a full refresh of the visual
                if (enemy.visual.isHighlighted()) {
                    window.requestAnimationFrame(enemy.visual.buildVisual.bind(enemy.visual));
                } else {
                    window.requestAnimationFrame(enemy.visual.refreshSize.bind(enemy.visual));
                }
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

        let currTime = (new Date()).getTime();

        // Once every 50 ms, calculation is expensive
        let enemyMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_ENEMY);
        for (let i = 0; i < enemyMapObjectGroup.objects.length; i++) {
            let enemy = enemyMapObjectGroup.objects[i];

            if (currTime - this._enemyMouseMoveDistanceCheckTimes[enemy.id] > 50) {
                if (enemy.isVisible()) {
                    enemy.visual.checkMouseOver(mouseMoveEvent.originalEvent.pageX, mouseMoveEvent.originalEvent.pageY);
                }
            }

            this._lastMouseMoveDistanceCheckTime = currTime;
        }
    }

    /**
     * Called when the mouse has finished moving the leaflet map.
     * @param mouseMoveEvent
     * @private
     */
    _onLeafletMapMoveEnd(mouseMoveEvent) {
        console.assert(this instanceof EnemyVisualManager, 'this is not an EnemyVisualManager!', this);

        // Force a refresh
        this._lastMapMoveDistanceCheckTime = 0;
        this._onLeafletMapMove(mouseMoveEvent);
    }

    /**
     * Called when the mouse has moved over the leaflet map.
     * @param mouseMoveEvent
     * @private
     */
    _onLeafletMapMove(mouseMoveEvent) {
        console.assert(this instanceof EnemyVisualManager, 'this is not an EnemyVisualManager!', this);

        let currTime = (new Date()).getTime();
        // Once every 100 ms, calculation is expensive
        if (currTime - this._lastMapMoveDistanceCheckTime > 100) {
            console.log('viewport checking..');
            let enemyMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_ENEMY);
            for (let i = 0; i < enemyMapObjectGroup.objects.length; i++) {
                let enemy = enemyMapObjectGroup.objects[i];

                let isVisible = enemy.isVisibleOnMap();

                // If panned into view
                if (this._enemyVisibilityMap[enemy.id] === false && isVisible) {
                    console.log(`Refreshing view of enemy ${enemy.id}`);
                    window.requestAnimationFrame(enemy.visual.refreshSize.bind(enemy.visual));
                }

                // Write new visible state
                this._enemyVisibilityMap[enemy.id] = isVisible;
            }

            this._lastMapMoveDistanceCheckTime = currTime;
        }
    }
}