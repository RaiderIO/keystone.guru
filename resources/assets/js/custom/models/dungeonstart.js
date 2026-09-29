L.Draw.DungeonStart = L.Draw.Marker.extend({
    statics: {
        TYPE: 'dungeonstart'
    },
    options: {
        icon: LeafletIconUnknown
    },
    initialize: function (map, options) {
        // Save the type so super can fire, need to do this as cannot do this.TYPE :(
        this.type = L.Draw.DungeonStart.TYPE;
        L.Draw.Feature.prototype.initialize.call(this, map, options);
    }
});

/**
 * Where the party enters the dungeon. Rendered with the dungeon_start map icon type's visual.
 */
class DungeonStart extends Icon {
    constructor(map, layer) {
        super(map, layer, {name: 'dungeonstart', has_route_model_binding: true});

        this.label = 'DungeonStart';
        this.setMapIconType(getState().getMapContext().getMapIconType(MAP_ICON_TYPE_DUNGEON_START_ID), false);
    }

    /**
     * @inheritDoc
     */
    _getAttributes(force = false) {
        console.assert(this instanceof DungeonStart, 'this was not a DungeonStart', this);

        if (this._cachedAttributes !== null && !force) {
            return this._cachedAttributes;
        }

        let superAttributes = super._getAttributes(force);
        for (let i = 0; i < superAttributes.length; i++) {
            let attribute = superAttributes[i];
            if (attribute.options.name === 'map_icon_type_id') {
                attribute.options.edit = false;
                attribute.options.save = false;
                attribute.options.default = MAP_ICON_TYPE_DUNGEON_START_ID;
            }
        }

        return this._cachedAttributes = superAttributes;
    }

    /**
     * @inheritDoc
     */
    isEditable() {
        return super.isEditable() && getState().getMapContext() instanceof MapContextMappingVersionEdit;
    }

    toString() {
        return `Dungeon start ${this.comment === null ? '' : `(${this.comment.substring(0, 50)})`}`;
    }
}

// Guarded export for the test runner (Vitest). This is a no-op in the browser,
// where `module` is undefined, so it does not affect the concatenated bundle.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        DungeonStart,
    };
}
