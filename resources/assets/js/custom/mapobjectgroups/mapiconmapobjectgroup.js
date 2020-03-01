const MAP_ICON_COMMENT = 'mapcomment';

class MapIconMapObjectGroup extends MapObjectGroup {
    constructor(manager, name, editable) {
        super(manager, name, editable);

        let self = this;

        this.title = 'Hide/show map icons';
        this.fa_class = 'fa-icons';

        if (this.manager.map.options.echo) {
            window.Echo.join('route-edit.' + this.manager.map.getDungeonRoute().publicKey)
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

    _createObject(layer) {
        console.assert(this instanceof MapIconMapObjectGroup, 'this is not an MapIconMapObjectGroup', this);

        if (isMapAdmin) {
            return new AdminMapIcon(this.manager.map, layer);
        } else {
            return new MapIcon(this.manager.map, layer);
        }
    }

    _restoreObject(remoteMapObject, username = null) {
        console.assert(this instanceof MapIconMapObjectGroup, 'this is not a MapIconMapObjectGroup', this);
        // Fetch the existing map comment if it exists
        let mapIcon = this.findMapObjectById(remoteMapObject.id);

        // Only create a new one if it's new for us
        if (mapIcon === null) {
            let layer = null;

            switch(remoteMapObject.icon_type){
                case MAP_ICON_COMMENT:
                    layer = new LeafletMapIconCommentMarker();
                    break;
                default:
                    layer = new LeafletMapIconUnknownMarker();
            }
            layer.setLatLng(L.latLng(remoteMapObject.lat, remoteMapObject.lng));

            /** @var KillZone killzone */
            mapIcon = this.createNew(layer);
        }

        mapIcon.id = remoteMapObject.id;
        mapIcon.floor_id = remoteMapObject.floor_id;
        mapIcon.comment = remoteMapObject.comment;

        // We just downloaded the kill zone, it's synced alright!
        mapIcon.setSynced(true);

        // Show echo notification or not
        this._showReceivedFromEcho(mapIcon, username);
    }

    _fetchSuccess(response) {
        super._fetchSuccess(response);
        // no super call required
        console.assert(this instanceof MapIconMapObjectGroup, 'this is not a MapIconMapObjectGroup', this);

        let mapIcons = response.mapicon;

        // Now draw the patrols on the map
        for (let index in mapIcons) {
            if (mapIcons.hasOwnProperty(index)) {
                this._restoreObject(mapIcons[index]);
            }
        }
    }
}