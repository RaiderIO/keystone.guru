class MapObjectGroup extends Signalable {

    constructor(map, name) {
        super();
        this.map = map;
        this.name = name;

        this.objects = [];
        this.layerGroup = null;

        let self = this;

        // Whenever the map refreshes, we need to add ourselves to the map again
        this.map.register('map:refresh', (function (data) {
            // Rebuild the layer group
            self.layerGroup = new L.LayerGroup();

            // Set it to be visible if it was
            // @todo self.isShown(), currently the layer will ALWAYS show regardless of MapControl status
            self.setVisibility(true);
        }).bind(this));
    }

    /**
     * Removes all objects' layer from the map layer.
     * @protected
     */
    _removeObjectsFromLayer(){
        console.assert(this instanceof MapObjectGroup, this, 'this is not a MapObjectGroup');

        // Remove any layers that were added before
        for (let i = 0; i < this.objects.length; i++) {
            let enemyPack = this.objects[i];
            // Remove all layers
            this.map.leafletMap.removeLayer(enemyPack.layer);
        }
    }

    /**
     * @param layer
     * @protected
     * @return MapObject
     */
    _createObject(layer) {
        console.error('override the _createObject function!');
    }

    /**
     * Called whenever an object has deleted itself.
     * @param data
     * @private
     */
    _onObjectDeleted(data){
        console.assert(this instanceof MapObjectGroup, this, 'this is not a MapObjectGroup');

        this.layerGroup.removeLayer(data.context.layer);
        this.map.leafletMap.removeLayer(data.context.layer);

        let object = data.context;

        // Remove it from our records
        let newObjects = [];
        for (let i = 0; i < this.objects.length; i++) {
            let objectCandidate = this.objects[i];
            if (objectCandidate.id !== object.id) {
                newObjects.push(objectCandidate);
            }
        }
        this.objects = newObjects;
    }

    /**
     *
     * @param layer
     * @return MapObject
     */
    createNew(layer) {
        console.assert(this instanceof MapObjectGroup, this, 'this is not a MapObjectGroup');

        let object = this._createObject(layer);
        this.objects.push(object);
        this.layerGroup.addLayer(layer);

        object.onLayerInit();

        object.register('object:deleted', (this._onObjectDeleted).bind(this));
        this.signal('object:add', {object: object});

        return object;
    }

    getObjectName() {
        console.assert(this instanceof MapObjectGroup, this, 'this was not a MapObjectGroup');
        return this.name;
    }

    isShown() {
        console.assert(this instanceof MapObjectGroup, this, 'this was not a MapObjectGroup');
        return this.map.leafletMap.hasLayer(this.layerGroup);
    }

    setVisibility(visible) {
        console.assert(this instanceof MapObjectGroup, this, 'this was not a MapObjectGroup');
        if (!this.isShown() && visible) {
            this.map.leafletMap.addLayer(this.layerGroup);
        } else if (this.isShown() && !visible) {
            this.map.leafletMap.removeLayer(this.layerGroup);
        }
    }

    /**
     * Refreshes the objects that are displayed on the map based on the current dungeon & selected floor.
     */
    fetchFromServer(floor) {
        console.warn('call to empty fetchFromServer()');
    }
}