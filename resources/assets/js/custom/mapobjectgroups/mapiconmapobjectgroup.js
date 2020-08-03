class MapIconMapObjectGroup extends MapObjectGroup {
    constructor(manager, editable) {
        super(manager, [MAP_OBJECT_GROUP_MAPICON, MAP_OBJECT_GROUP_MAPICON_AWAKENED_OBELISK], 'mapicon', editable);

        this.title = 'Hide/show map icons';
        this.fa_class = 'fa-icons';
    }

    _createMapObject(layer, options = {}) {
        console.assert(this instanceof MapIconMapObjectGroup, 'this is not an MapIconMapObjectGroup', this);

        let mapIcon;
        if (getState().isMapAdmin()) {
            mapIcon = new AdminMapIcon(this.manager.map, layer);
        }
        // If we're actively placing the obelisk, make sure we create the correct map icon, or if we're restoring the gateway
        else if (typeof options !== 'undefined' && options.mapIconType.isAwakenedObelisk()) {
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

    /**
     * @inheritDoc
     */
    _restoreObject(remoteMapObject, username = null) {
        console.assert(this instanceof MapIconMapObjectGroup, 'this is not a MapIconMapObjectGroup', this);
        // Fetch the existing map icon if it exists
        /** @type {MapIcon} */
        let mapIcon = this.findMapObjectById(remoteMapObject.id);
        let createdNew;

        // Only create a new one if it's new for us
        if (createdNew = (mapIcon === null)) {
            // Find the layer we should display on the map
            let layer = new LeafletMapIconMarker();
            layer.setLatLng(L.latLng(remoteMapObject.lat, remoteMapObject.lng));

            // Pass the map icon type here so layer initialization can take the type into account
            mapIcon = this.createNew(layer, {mapIconType: getState().getMapIconType(remoteMapObject.map_icon_type_id)});
        } else {
            // Update position if it already existed
            mapIcon.layer.setLatLng(L.latLng(remoteMapObject.lat, remoteMapObject.lng));
        }

        mapIcon.loadRemoteMapObject(remoteMapObject);

        // When in admin mode, show all map icons
        if (!(this.manager.map instanceof AdminDungeonMap) && (mapIcon.seasonal_index !== null && getState().getSeasonalIndex() !== mapIcon.seasonal_index)) {
            // Hide this enemy by default
            mapIcon.setDefaultVisible(false);
        }

        // We just downloaded the map icon, it's synced alright!
        mapIcon.setSynced(true);
        // Refresh the tooltip; it may have been permanent before and no longer, or vice versa
        mapIcon.bindTooltip();

        // Show echo notification or not
        this._showReceivedFromEcho(mapIcon, username);

        return mapIcon;
    }
}