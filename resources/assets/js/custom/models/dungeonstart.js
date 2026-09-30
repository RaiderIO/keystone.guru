const DUNGEON_START_ICON_SIZE = 24;

let LeafletDungeonStartIcon = new L.divIcon({
    iconSize: [DUNGEON_START_ICON_SIZE, DUNGEON_START_ICON_SIZE],
    className: 'dungeon_start_icon',
});

let LeafletDungeonStartMarker = L.Marker.extend({
    options: {
        icon: LeafletDungeonStartIcon,
    },
});

L.Draw.DungeonStart = L.Draw.Marker.extend({
    statics: {
        TYPE: 'dungeonstart'
    },
    options: {
        icon: LeafletDungeonStartIcon
    },
    initialize: function (map, options) {
        // Save the type so super can fire, need to do this as cannot do this.TYPE :(
        this.type = L.Draw.DungeonStart.TYPE;
        L.Draw.Feature.prototype.initialize.call(this, map, options);
    }
});

/**
 * Where the party enters the dungeon.
 *
 * @property {Number} floor_id
 * @property {Number|null} target_dungeon_id
 * @property {String|null} comment
 * @property {Number} lat
 * @property {Number} lng
 */
class DungeonStart extends VersionableMapObject {
    constructor(map, layer) {
        super(map, layer, {name: 'dungeonstart', has_route_model_binding: true});

        this.label = 'DungeonStart';

        this.register('object:changed', this, this._refreshVisual.bind(this));
        this.map.register('map:mapstatechanged', this, this._refreshVisual.bind(this));
        getState().register('mapzoomlevel:changed', this, this._refreshVisual.bind(this));
    }

    /**
     * @inheritDoc
     */
    _getAttributes(force = false) {
        console.assert(this instanceof DungeonStart, 'this was not a DungeonStart', this);

        if (this._cachedAttributes !== null && !force) {
            return this._cachedAttributes;
        }

        return this._cachedAttributes = super._getAttributes(force).concat([
            new Attribute({
                name: 'floor_id',
                type: 'int',
                edit: false, // Not directly changeable by user
                default: getState().getCurrentFloor().id,
            }),
            new Attribute({
                name: 'target_dungeon_id',
                type: 'int',
                edit: false,
                save: false,
                default: null,
            }),
            new Attribute({
                name: 'comment',
                type: 'textarea',
                default: '',
            }),
            new Attribute({
                name: 'lat',
                type: 'float',
                edit: false,
                getter: () => this.layer.getLatLng().lat,
            }),
            new Attribute({
                name: 'lng',
                type: 'float',
                edit: false,
                getter: () => this.layer.getLatLng().lng,
            }),
        ]);
    }

    /**
     * @returns {L.DivIcon}
     * @private
     */
    _getLeafletIcon() {
        console.assert(this instanceof DungeonStart, 'this was not a DungeonStart', this);

        let mapState = this.map.getMapState();
        let editModeEnabled = mapState instanceof EditMapState && this.isEditable();
        let deleteModeEnabled = mapState instanceof DeleteMapState && this.isDeletable();
        let selectableMargin = editModeEnabled || deleteModeEnabled ? 8 : 0;

        let size = c.map.mapicon.calculateSize(DUNGEON_START_ICON_SIZE);

        let template = Handlebars.templates['map_map_icon_visual_template'];

        return L.divIcon({
            html: template({
                key: 'dungeon_start',
                icon_url: `${this.map.options.assetsBaseUrl}/images/mapicon/dungeon_start.png`,
                selectedclass: editModeEnabled ? ' leaflet-edit-marker-selected' : (deleteModeEnabled ? ' leaflet-edit-marker-selected delete' : ''),
                outer_width: size + selectableMargin,
                outer_height: size + selectableMargin,
                inner_width: size,
                inner_height: size,
            }),
            iconSize: [size, size],
            tooltipAnchor: [0, -(size / 2)],
            popupAnchor: [0, -(size / 2)],
            className: 'map_icon map_icon_dungeon_start',
        });
    }

    /**
     * @private
     */
    _refreshVisual() {
        console.assert(this instanceof DungeonStart, 'this was not a DungeonStart', this);

        if (this.layer === null) {
            return;
        }

        this.layer.setIcon(this._getLeafletIcon());
        this.bindTooltip();
    }

    /**
     * @returns {String}
     */
    getDisplayText() {
        console.assert(this instanceof DungeonStart, 'this was not a DungeonStart', this);

        return this.comment !== null && this.comment.length > 0 ?
            lang.get(this.comment) :
            lang.get('js.dungeonstart_tooltip');
    }

    /**
     * @inheritDoc
     */
    onLayerInit() {
        console.assert(this instanceof DungeonStart, 'this was not a DungeonStart', this);
        super.onLayerInit();

        this._refreshVisual();
    }

    /**
     * @inheritDoc
     */
    bindTooltip() {
        console.assert(this instanceof DungeonStart, 'this was not a DungeonStart', this);

        if (this.layer === null) {
            return;
        }

        this.unbindTooltip();

        this.layer.bindTooltip(c.map.sanitizeText(this.getDisplayText()), {direction: 'top'});
    }

    /**
     * @inheritDoc
     */
    isEditable() {
        return getState().getMapContext() instanceof MapContextMappingVersionEdit;
    }

    /**
     * @inheritDoc
     */
    isDeletable() {
        return this.isEditable();
    }

    toString() {
        return `Dungeon start ${this.comment === null ? '' : `(${this.comment.substring(0, 50)})`}`;
    }

    /**
     * @inheritDoc
     */
    cleanup() {
        console.assert(this instanceof DungeonStart, 'this was not a DungeonStart', this);
        super.cleanup();

        this.unregister('object:changed', this);
        this.map.unregister('map:mapstatechanged', this);
        getState().unregister('mapzoomlevel:changed', this);
    }
}

// Guarded export for the test runner (Vitest). This is a no-op in the browser,
// where `module` is undefined, so it does not affect the concatenated bundle.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        DungeonStart,
    };
}
