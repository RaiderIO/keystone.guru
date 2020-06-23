class AddAwakenedObeliskGatewayMapState extends MapObjectMapState {
    constructor(map, sourceMapObject) {
        super(map, sourceMapObject);
        console.assert(sourceMapObject instanceof MapIcon, 'sourceMapObject is not a MapIcon', sourceMapObject);
        console.assert(sourceMapObject.getMapIconType().isAwakenedObelisk(), 'sourceMapObject is not an Awakened Obelisk!', sourceMapObject);

        let mapIconManager = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_MAPICON_AWAKENED_OBELISK);
        mapIconManager.register('object:add', this, this._onObjectAdded.bind(this));
    }

    start() {
        console.assert(this instanceof AddAwakenedObeliskGatewayMapState, 'this is not a AddAwakenedObeliskGatewayMapState', this);
        super.start();

        // Start drawing an obelisk gateway
        $('.leaflet-draw-draw-awakenedobeliskgatewaymapicon')[0].click();
    }

    stop() {
        console.assert(this instanceof AddAwakenedObeliskGatewayMapState, 'this is not a AddAwakenedObeliskGatewayMapState', this);
        super.stop();

        this.sourceMapObject.floor_id = getState().getCurrentFloor().id;

        let mapIconManager = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_MAPICON_AWAKENED_OBELISK);
        mapIconManager.unregister('object:add', this);
    }

    /**
     *
     * @param objectAddedEvent
     * @private
     */
    _onObjectAdded(objectAddedEvent) {
        console.assert(this instanceof AddAwakenedObeliskGatewayMapState, 'this is not a AddAwakenedObeliskGatewayMapState', this);
        let addedGateway = objectAddedEvent.data.object;

        // Find the gateway that was potentially already there
        let mapIconManager = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_MAPICON);
        for(let i = 0; i < mapIconManager.objects.length; i++ ){
            let mapIconCandidate = mapIconManager.objects[i];

            // Found a match..
            if( mapIconCandidate.linked_awakened_obelisk_id === this.sourceMapObject.id ){
                // Get rid of it, we've made a new one
                mapIconCandidate.delete();

                // Hide it right away, otherwise we get some brief overlap with 2 lines
                mapIconCandidate.localDelete();
            }
        }

        // Link the gateway to the obelisk
        addedGateway.setMapIconTypeId(getState().getAwakenedObeliskGatewayMapIconType().id);
        addedGateway.setLinkedAwakenedObeliskId(this.sourceMapObject.id);
        addedGateway.save();

        this.stop();
    }
}