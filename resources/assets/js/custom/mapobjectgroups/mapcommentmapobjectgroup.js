class MapCommentMapObjectGroup extends MapObjectGroup {
    constructor(map, name, classname, editable) {
        super(map, name, editable);

        this.classname = classname;
        this.title = 'Hide/show map comments';
        this.fa_class = 'fa-comment';
    }

    _createObject(layer) {
        console.assert(this instanceof MapCommentMapObjectGroup, 'this is not an MapCommentMapObjectGroup');

        switch (this.classname) {
            case "AdminMapComment":
                return new AdminMapComment(this.map, layer);
            default:
                return new MapComment(this.map, layer);
        }
    }


    fetchFromServer(floor) {
        // no super call required
        console.assert(this instanceof MapCommentMapObjectGroup, this, 'this is not a MapCommentMapObjectGroup');

        let self = this;
        // No network traffic if this is enabled!
        if (!this.map.isTryModeEnabled()) {
            $.ajax({
                type: 'GET',
                url: '/ajax/mapcomments',
                dataType: 'json',
                data: {
                    dungeonroute: this.map.getDungeonRoute().publicKey, // defined in map.blade.php
                    floor_id: floor.id
                },
                success: function (json) {
                    // Now draw the patrols on the map
                    for (let index in json) {
                        if (json.hasOwnProperty(index)) {
                            let remoteMapComment = json[index];

                            let layer = new LeafletMapCommentMarker();
                            layer.setLatLng(L.latLng(remoteMapComment.lat, remoteMapComment.lng));

                            /** @var MapComment mapComment */
                            let mapComment = self.createNew(layer);
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

                    self.signal('fetchsuccess');
                }
            });
        } else {
            self.signal('fetchsuccess');
        }
    }
}