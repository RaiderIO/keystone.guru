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
    }

    loadRemoteMapObject(remoteMapObject, parentAttribute = null) {
        this.options.save_url = `/ajax/${getState().getMapContext().getPublicKey()}/pridefulenemy/${remoteMapObject.id}`;
        this.options.delete_url = `/ajax/${getState().getMapContext().getPublicKey()}/pridefulenemy/${remoteMapObject.id}`

        super.loadRemoteMapObject(remoteMapObject, parentAttribute);
    }

    isDeletable() {
        return true;
    }

    isEditable() {
        return true;
    }

    /**
     * Checks if this enemy is possibly selectable when selecting enemies.
     * @returns {*}
     */
    isSelectable() {
        return this.selectable && this.visual !== null;
    }

    toString() {
        return 'PridefulEnemy-' + this.id;
    }
}