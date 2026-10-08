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
 * @property {Boolean} raid
 * @property {Number|null} min_suggested_level
 * @property {Number|null} max_suggested_level
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
                type: 'select',
                values: () => getState().getMapContext().getDungeonSelectValues(false),
                live_search: true,
                default: null,
            }),
            new Attribute({
                name: 'comment',
                type: 'textarea',
                default: '',
            }),
            new Attribute({
                name: 'raid',
                type: 'bool',
                edit: false,
                save: false,
                default: false,
            }),
            new Attribute({
                name: 'min_suggested_level',
                type: 'int',
                edit: false,
                save: false,
                default: null,
            }),
            new Attribute({
                name: 'max_suggested_level',
                type: 'int',
                edit: false,
                save: false,
                default: null,
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
        let key = this.raid ? 'raid_start' : 'dungeon_start';

        return L.divIcon({
            html: template({
                key: key,
                icon_url: `${this.map.options.assetsBaseUrl}/images/mapicon/${key}.png`,
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

        let navigation = this.getNavigation();
        if (navigation !== null) {
            return lang.get(
                navigation.backLink ? 'js.dungeonstart_back_to_label' : 'js.dungeonstart_go_to_label',
                {dungeon: lang.get(navigation.dungeonName)},
            );
        }

        return this.comment !== null && this.comment.length > 0 ?
            lang.get(this.comment) :
            lang.get(this.raid ? 'js.dungeonstart_raid_tooltip' : 'js.dungeonstart_tooltip');
    }

    /**
     * The suggested level range of the dungeon this start leads into, or null when it has none or leads back out.
     *
     * @returns {String|null}
     */
    getSuggestedLevelText() {
        console.assert(this instanceof DungeonStart, 'this was not a DungeonStart', this);

        let navigation = this.getNavigation();
        if (navigation !== null && navigation.backLink) {
            return null;
        }

        let min = this.min_suggested_level ?? null;
        let max = this.max_suggested_level ?? null;

        if (min !== null && max !== null) {
            return min === max ?
                lang.get('js.dungeonstart_suggested_level', {level: min}) :
                lang.get('js.dungeonstart_suggested_level_range', {min: min, max: max});
        } else if (min !== null) {
            return lang.get('js.dungeonstart_suggested_level_min', {min: min});
        } else if (max !== null) {
            return lang.get('js.dungeonstart_suggested_level_max', {max: max});
        }

        return null;
    }

    /**
     * Where clicking this start leads, or null when it leads nowhere.
     *
     * @returns {{backLink: Boolean, dungeonName: String, url: String}|null}
     */
    getNavigation() {
        console.assert(this instanceof DungeonStart, 'this was not a DungeonStart', this);

        return getState().getMapContext().getDungeonStartNavigation(this.id);
    }

    /**
     * @inheritDoc
     */
    onLayerInit() {
        console.assert(this instanceof DungeonStart, 'this was not a DungeonStart', this);
        super.onLayerInit();

        this.layer.on('click', () => {
            let navigation = this.getNavigation();
            if (navigation !== null) {
                window.location.href = navigation.url;
            }
        });

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

        let suggestedLevelText = this.getSuggestedLevelText();
        let tooltipText = suggestedLevelText === null ?
            this.getDisplayText() :
            lang.get('js.dungeonstart_tooltip_with_suggested_level', {text: this.getDisplayText(), level: suggestedLevelText});

        this.layer.bindTooltip(c.map.sanitizeText(tooltipText), {direction: 'top'});
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
