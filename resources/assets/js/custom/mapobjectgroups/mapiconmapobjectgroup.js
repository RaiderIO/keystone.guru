class MapIconMapObjectGroup extends MapObjectGroup {
    constructor(manager, editable) {
        super(manager, MAP_OBJECT_GROUP_MAPICON, 'mapicon', editable);

        let self = this;

        this.title = 'Hide/show map icons';
        this.fa_class = 'fa-icons';

        if (this.manager.map.options.echo) {
            window.Echo.join('route-edit.' + getState().getDungeonRoute().publicKey)
                .listen('.mapicon-changed', (e) => {
                    self._restoreObject(e.mapicon, e.user);
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
        let mapIcon = this.findMapObjectById(remoteMapObject.id);

        // Only create a new one if it's new for us
        if (mapIcon === null) {
            // Find the layer we should display on the map
            let layer = new LeafletMapIconMarker();
            layer.setLatLng(L.latLng(remoteMapObject.lat, remoteMapObject.lng));

            // Pass the map icon type here so layer initialization can take the type into account
            mapIcon = this.createNew(layer, {mapIconType: getState().getMapIconType(remoteMapObject.map_icon_type_id)});
        }

        mapIcon.id = remoteMapObject.id;
        mapIcon.floor_id = remoteMapObject.floor_id;
        mapIcon.map_icon_type_id = remoteMapObject.map_icon_type_id;
        mapIcon.has_dungeon_route = remoteMapObject.has_dungeon_route;
        mapIcon.comment = remoteMapObject.comment;

        // We just downloaded the map icon, it's synced alright!
        mapIcon.setSynced(true);

        // Show echo notification or not
        this._showReceivedFromEcho(mapIcon, username);
    }
}