class MapIconMapObjectGroup extends MapObjectGroup {
    constructor(manager, editable) {
        super(manager, [MAP_OBJECT_GROUP_MAPICON, MAP_OBJECT_GROUP_MAPICON_AWAKENED_OBELISK], editable);

        this.title = 'Hide/show map icons';
        this.fa_class = 'fa-icons';

        // Defined in mapicon.js, need to fix this somehow
        initAwakenedObeliskGatewayIcon();
    }

    /**
     * @inheritDoc
     **/
    _getRawObjects() {
        return getState().getMapContext().getMapIcons();
    }

    /**
     * @inheritDoc
     */
    _getOptions(remoteMapObject) {
        return {mapIconType: getState().getMapContext().getMapIconType(remoteMapObject.map_icon_type_id)};
    }

    /**
     * @inheritDoc
     */
    _createLayer(remoteMapObject) {
        let layer = new LeafletIconMarker();
        layer.setLatLng(L.latLng(remoteMapObject.lat, remoteMapObject.lng));
        return layer;
    }

    _createMapObject(layer, options = {}) {
        console.assert(this instanceof MapIconMapObjectGroup, 'this is not an MapIconMapObjectGroup', this);

        let mapIcon;
        if (getState().isMapAdmin()) {
            mapIcon = new AdminMapIcon(this.manager.map, layer);
        }
        // If we're actively placing the obelisk, make sure we create the correct map icon, or if we're restoring the gateway
        else if (options.hasOwnProperty('mapIconType') && options.mapIconType.isAwakenedObelisk()) {
            mapIcon = new MapIconAwakenedObelisk(this.manager.map, layer);
        } else {
            mapIcon = new MapIcon(this.manager.map, layer);
        }
        // // Pass the map icon type here so layer initialization can take the type into account
        // if (typeof options !== 'undefined' && typeof options.mapIconType !== 'undefined') {
        //     mapIcon._setMapIconType(options.mapIconType);
        // }

        return mapIcon;
    }
}