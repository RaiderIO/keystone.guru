// Default icon; placeholder while placing a new enemy. This can't really use the Visual system, it'd require
// too much rewrites. Better to just make a small placeholder like this and assign it to the below constructs.
let DefaultPridefulEnemyIcon = new L.divIcon({className: 'enemy_icon'});

let LeafletPridefulEnemyMarker = L.Marker.extend({
    options: {
        icon: DefaultPridefulEnemyIcon
    }
});

L.Draw.PridefulEnemy = L.Draw.Marker.extend({
    statics: {
        TYPE: 'pridefulenemy'
    },
    options: {
        icon: DefaultPridefulEnemyIcon
    },
    initialize: function (map, options) {
        // Save the type so super can fire, need to do this as cannot do this.TYPE :(
        this.type = L.Draw.PridefulEnemy.TYPE;

        L.Draw.Feature.prototype.initialize.call(this, map, options);
    }
});

/**
 * @inheritDoc
 */
class PridefulEnemy extends Enemy {
    constructor(map, layer) {
        super(map, layer, {name: 'pridefulenemy'});

        // Whether we have been assigned to a position by the user or not
        this.assigned = false;
    }

    loadRemoteMapObject(remoteMapObject, parentAttribute = null) {
        this.options.delete_url = `/ajax/${getState().getMapContext().getPublicKey()}/pridefulenemy/${remoteMapObject.id}`

        super.loadRemoteMapObject(remoteMapObject, parentAttribute);
    }

    shouldBeVisible() {
        if (!this.isAssigned()) {
            return false;
        }

        return super.shouldBeVisible();
    }

    isDeletable() {
        return true;
    }

    isEditable() {
        return true;
    }

    isEditableByPopup() {
        return false;
    }

    /**
     * Checks if this prideful enemy has been placed by the user somewhere
     * @returns {boolean}
     */
    isAssigned() {
        return this.assigned;
    }

    /**
     *
     * @param lat {float}
     * @param lng {float}
     * @param floorId {int}
     */
    setAssignedLocation(lat, lng, floorId) {
        this.lat = lat;
        this.lng = lng;

        this.layer.setLatLng(L.latLng(lat, lng));
        this.floor_id = floorId;
        this.assigned = true;
    }

    /**
     *
     */
    unsetAssignedLocation() {
        this.assigned = false;
    }

    save() {
        let self = this;

        $.ajax({
            type: 'POST',
            url: `/ajax/${getState().getMapContext().getPublicKey()}/pridefulenemy/${this.id}`,
            dataType: 'json',
            data: {
                floor_id: this.floor_id,
                lat: this.layer.getLatLng().lat,
                lng: this.layer.getLatLng().lng
            },
            success: function (json) {
                this.floor_id = json.floor_id;
                this.lat = json.lat;
                this.lng = json.lng;

                self.setSynced(true);
                self.map.leafletMap.closePopup();
                // ID may have changed - refresh it
                self._assignPopup();

                self.signal('save:success', {json: json});

                self.onSaveSuccess(json);
            }
        });
    }

    toString() {
        return 'PridefulEnemy-' + this.id;
    }
}