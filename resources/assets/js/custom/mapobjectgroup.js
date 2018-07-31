class MapObjectGroup {


    constructor(map, name, className) {
        this.map = map;
        this.name = name;

        this.objects = [];
        this.layerGroup = null;
    }

    _create(layer) {
        switch (this.routeClassName) {
            default:
                return new Route(this, layer);
        }
    }

    createNew(layer){
        console.assert(this instanceof DungeonMap, this, 'this is not a DungeonMap');

        let route = this._createRoute(layer);
        this.routes.push(route);
        this.mapObjects.push(route);
        // layer.addTo(this.leafletMap);
        this.routeLayerGroup.addLayer(layer);

        route.onLayerInit();

        this.signal('route:add', {route: route});

        return route;
    }

    getObjectName() {
        console.assert(this instanceof MapObjectGroup, 'this was not a MapObjectGroup');
        return this.name;
    }

    isShown() {
        console.assert(this instanceof MapObjectGroup, 'this was not a MapObjectGroup');
        return this.map.leafletMap.hasLayer(this.layerGroup);
    }

    setVisibility(visible) {
        console.assert(this instanceof MapObjectGroup, 'this was not a MapObjectGroup');
        if (!this.isShown() && visible) {
            this.map.leafletMap.addLayer(this.layerGroup);
        } else if (this.isShown() && !visible) {
            this.map.leafletMap.removeLayer(this.layerGroup);
        }
    }

}