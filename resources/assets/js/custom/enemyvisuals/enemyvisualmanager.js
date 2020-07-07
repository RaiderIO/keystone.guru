class EnemyVisualManager extends Signalable {

    constructor(map) {
        super();
        let self = this;

        this.map = map;

        getState().register('mapzoomlevel:changed', this, this._onZoomLevelChanged.bind(this));

        this._lastDistanceCheckTime = 0;
        this.map.register('map:refresh', this, function () {
            self.map.leafletMap.on('mousemove', self._onLeafletMapMouseMove.bind(self));
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
            if (enemy.isVisible()) {
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
        // Once every 100 ms, calculation is expensive
        if (currTime - this._lastDistanceCheckTime > 50) {
            let enemyMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_ENEMY);
            for (let i = 0; i < enemyMapObjectGroup.objects.length; i++) {
                let enemy = enemyMapObjectGroup.objects[i];

                if (enemy.isVisible()) {
                    enemy.visual.checkMouseOver(mouseMoveEvent.originalEvent.pageX, mouseMoveEvent.originalEvent.pageY);
                }
            }

            this._lastDistanceCheckTime = currTime;
        }
    }
}