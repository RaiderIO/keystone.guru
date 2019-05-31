class MapCommentMapObjectGroup extends MapObjectGroup {
    constructor(manager, name, editable) {
        super(manager, name, editable);

        let self = this;

        this.title = 'Hide/show map comments';
        this.fa_class = 'fa-comment';

        window.Echo.join('route-edit.' + this.manager.map.getDungeonRoute().publicKey)
            .listen('.mapcomment-changed', (e) => {
                self._restoreObject(e.mapcomment);
            })
            .listen('.mapcomment-deleted', (e) => {
                let mapObject = self.findMapObjectById(e.id);
                if (mapObject !== null) {
                    mapObject.localDelete();
                }
            });
    }

    _createObject(layer) {
        console.assert(this instanceof MapCommentMapObjectGroup, 'this is not an MapCommentMapObjectGroup', this);

        if (isMapAdmin) {
            return new AdminMapComment(this.manager.map, layer);
        } else {
            return new MapComment(this.manager.map, layer);
        }
    }

    _restoreObject(remoteMapObject) {
        console.assert(this instanceof MapCommentMapObjectGroup, 'this is not a MapCommentMapObjectGroup', this);
        // Fetch the existing map comment if it exists
        let mapComment = this.findMapObjectById(remoteMapObject.id);

        // Only create a new one if it's new for us
        if (mapComment === null) {
            let layer = new LeafletMapCommentMarker();
            layer.setLatLng(L.latLng(remoteMapObject.lat, remoteMapObject.lng));

            /** @var KillZone killzone */
            mapComment = this.createNew(layer);
        }

        mapComment.id = remoteMapObject.id;
        mapComment.floor_id = remoteMapObject.floor_id;
        mapComment.always_visible = remoteMapObject.always_visible;
        mapComment.comment = remoteMapObject.comment;
        // if( remoteMapComment.gameIcon !== null ) {
        //     mapComment.setGameIcon(remoteMapComment.gameIcon);
        // }

        // We just downloaded the kill zone, it's synced alright!
        mapComment.setSynced(true);
    }

    _fetchSuccess(response) {
        super._fetchSuccess(response);
        // no super call required
        console.assert(this instanceof MapCommentMapObjectGroup, 'this is not a MapCommentMapObjectGroup', this);

        let mapComments = response.mapcomment;

        // Now draw the patrols on the map
        for (let index in mapComments) {
            if (mapComments.hasOwnProperty(index)) {
                this._restoreObject(mapComments[index]);
            }
        }
    }
}