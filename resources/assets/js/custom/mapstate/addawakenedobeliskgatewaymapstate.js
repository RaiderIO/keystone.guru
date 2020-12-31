class AddAwakenedObeliskGatewayMapState extends MapObjectMapState {
    constructor(map, sourceMapObject) {
        super(map, sourceMapObject);
        console.assert(sourceMapObject instanceof MapIcon, 'sourceMapObject is not a MapIcon', sourceMapObject);
        console.assert(sourceMapObject.getMapIconType().isAwakenedObelisk(), 'sourceMapObject is not an Awakened Obelisk!', sourceMapObject);

        let mapIconManager = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_MAPICON_AWAKENED_OBELISK);
        mapIconManager.register('save:success', this, this._onSaveSuccess.bind(this));
    }

    getName() {
        return 'AddAwakenedObeliskGatewayMapState';
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
        mapIconManager.unregister('save:success', this);
    }

    /**
     *
     * @param saveSuccessEvent
     * @private
     */
    _onSaveSuccess(saveSuccessEvent) {
        console.assert(this instanceof AddAwakenedObeliskGatewayMapState, 'this is not a AddAwakenedObeliskGatewayMapState', this);
        let addedGateway = saveSuccessEvent.data.object;

        // Find the gateway that was potentially already there
        let mapIconManager = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_MAPICON);
        for (let i = 0; i < mapIconManager.objects.length; i++) {
            let mapIconCandidate = mapIconManager.objects[i];

            // Found a match..
            if (mapIconCandidate.linked_awakened_obelisk_id === this.sourceMapObject.id) {
                // Get rid of it, we've made a new one
                mapIconCandidate.delete();

                // Hide it right away, otherwise we get some brief overlap with 2 lines
                mapIconCandidate.localDelete();
            }
        }

        // Find the path that was potentially already there
        let pathManager = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_PATH);
        for (let i = 0; i < pathManager.objects.length; i++) {
            let pathCandidate = pathManager.objects[i];

            // Found a match..
            if (pathCandidate.linked_awakened_obelisk_id === this.sourceMapObject.id) {
                // Get rid of it, we've made a new one
                pathCandidate.delete();

                // Hide it right away, otherwise we get some brief overlap with 2 lines
                pathCandidate.localDelete();
            }
        }

        // Link the gateway to the obelisk
        addedGateway.setMapIconTypeId(getState().getMapContext().getAwakenedObeliskGatewayMapIconType().id);
        addedGateway.linked_awakened_obelisk_id = this.sourceMapObject.id;
        addedGateway.comment = this.sourceMapObject.getDisplayText();
        addedGateway.save();

        let addedPath = pathManager.createNewPath([{
            lat: this.sourceMapObject.layer.getLatLng().lat,
            lng: this.sourceMapObject.layer.getLatLng().lng
        }, {
            lat: addedGateway.layer.getLatLng().lat,
            lng: addedGateway.layer.getLatLng().lng,
        }], {linked_awakened_obelisk_id: this.sourceMapObject.id});

        // Reset map state to null so this map state ends
        this.map.setMapState(null);
    }
}