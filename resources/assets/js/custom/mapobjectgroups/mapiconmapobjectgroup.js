class MapIconMapObjectGroup extends MapObjectGroup {
    constructor(manager, editable) {
        super(manager, MAP_OBJECT_GROUP_MAPICON, 'mapicon', editable);

        let self = this;

        this.title = 'Hide/show map icons';
        this.fa_class = 'fa-icons';

        if (this.manager.map.options.echo) {
            window.Echo.join(this.manager.map.options.appType + '-route-edit.' + getState().getDungeonRoute().publicKey)
                .listen('.mapicon-changed', (e) => {
                    if (e.mapicon.floor_id === getState().getCurrentFloor().id) {
                        self._restoreObject(e.mapicon, e.user);
                    }
                })
                .listen('.mapicon-deleted', (e) => {
                    let mapObject = self.findMapObjectById(e.id);
                    if (mapObject !== null) {
                        mapObject.localDelete();
                        self._showDeletedFromEcho(mapObject, e.user);
                    }
                });
        }
    }

    _createObject(layer, options) {
        console.assert(this instanceof MapIconMapObjectGroup, 'this is not an MapIconMapObjectGroup', this);

        let mapIcon;
        if (getState().isMapAdmin()) {
            mapIcon = new AdminMapIcon(this.manager.map, layer);
        } else {
            mapIcon = new MapIcon(this.manager.map, layer);
        }
        // Pass the map icon type here so layer initialization can take the type into account
        if (typeof options !== 'undefined' && typeof options.mapIconType !== 'undefined') {
            mapIcon.setMapIconType(options.mapIconType);
        }

        return mapIcon;
    }

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
        }

        mapIcon.loadRemoteMapObject(remoteMapObject);

        // If refreshed from Echo
        if (!createdNew) {
            mapIcon.setMapIconType(getState().getMapIconType(mapIcon.map_icon_type_id));
        }

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
    }
}