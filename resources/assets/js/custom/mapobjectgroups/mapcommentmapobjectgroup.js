class MapCommentMapObjectGroup extends MapObjectGroup {
    constructor(manager, name, classname, editable) {
        super(manager, name, editable);

        this.classname = classname;
        this.title = 'Hide/show map comments';
        this.fa_class = 'fa-comment';
    }

    _createObject(layer) {
        console.assert(this instanceof MapCommentMapObjectGroup, 'this is not an MapCommentMapObjectGroup');

        switch (this.classname) {
            case "AdminMapComment":
                return new AdminMapComment(this.manager.map, layer);
            default:
                return new MapComment(this.manager.map, layer);
        }
    }

    _fetchSuccess(response) {
        super._fetchSuccess(response);
        // no super call required
        console.assert(this instanceof MapCommentMapObjectGroup, this, 'this is not a MapCommentMapObjectGroup');

        let mapComments = response.mapcomment;

        // Now draw the patrols on the map
        for (let index in mapComments) {
            if (mapComments.hasOwnProperty(index)) {
                let remoteMapComment = mapComments[index];

                let layer = new LeafletMapCommentMarker();
                layer.setLatLng(L.latLng(remoteMapComment.lat, remoteMapComment.lng));

                /** @var MapComment mapComment */
                let mapComment = this.createNew(layer);
                mapComment.id = remoteMapComment.id;
                mapComment.floor_id = remoteMapComment.floor_id;
                mapComment.always_visible = remoteMapComment.always_visible;
                mapComment.comment = remoteMapComment.comment;
                // if( remoteMapComment.gameIcon !== null ) {
                //     mapComment.setGameIcon(remoteMapComment.gameIcon);
                // }

                // We just downloaded the kill zone, it's synced alright!
                mapComment.setSynced(true);
            }
        }
    }
}