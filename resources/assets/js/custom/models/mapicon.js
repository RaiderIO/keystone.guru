// $(function () {
L.Draw.MapIcon = L.Draw.Marker.extend({
    statics: {
        TYPE: 'mapicon'
    },
    options: {
        icon: LeafletIconUnknown
    },
    initialize: function (map, options) {
        // Save the type so super can fire, need to do this as cannot do this.TYPE :(
        this.type = L.Draw.MapIcon.TYPE;
        L.Draw.Feature.prototype.initialize.call(this, map, options);
    }
});

/**
 * @property {Number} floor_id
 * @property {Number} map_icon_type_id
 * @property {Number} linked_awakened_obelisk_id
 * @property {Number} permanent_tooltip
 * @property {Number} seasonal_index
 */
class MapIcon extends Icon {
    constructor(map, layer) {
        super(map, layer, {name: 'map_icon', route_suffix: 'mapicon'});

        this.label = 'MapIcon';
    }

    /**
     * @inheritDoc
     */
    _getAttributes(force) {
        console.assert(this instanceof MapIcon, 'this is not a MapIcon', this);

        if (this._cachedAttributes !== null && !force) {
            return this._cachedAttributes;
        }

        let self = this;

        return this._cachedAttributes = super._getAttributes(force).concat([
            new Attribute({
                // Reads team_id, stores as show_across_team
                name: 'team_id',
                type: 'bool',
                default: false,
                edit: getState().getMapContext().getTeamId() >= 1,
                setter: function (value) {
                    // If team_id is not null, we show this across the entire team
                    this.show_across_team = value;
                },
                getter: function () {
                    return this.show_across_team ? getState().getMapContext().getTeamId() : null;
                }
            }),
            new Attribute({
                name: 'permanent_tooltip',
                type: 'bool',
                default: false
            }),
            new Attribute({
                name: 'linked_awakened_obelisk_id',
                type: 'int',
                edit: false, // Not directly changeable by user
                default: null
            }),
            new Attribute({
                name: 'is_admin',
                type: 'bool',
                edit: false,
                save: false
            }),
            new Attribute({
                name: 'seasonal_index',
                type: 'select',
                admin: true,
                default: null,
                values: [
                    {id: 0, name: 'Week 1'},
                    {id: 1, name: 'Week 2'},
                    {id: 2, name: 'Week 3'},
                    {id: 3, name: 'Week 4'},
                    {id: 4, name: 'Week 5'}
                ],
                setter: function (value) {
                    // NaN check
                    if (value === -1 || value === '' || value !== value) {
                        value = null;
                    }
                    self.seasonal_index = value;
                }
            }),
        ]);
    }

    /**
     * @inheritDoc
     */
    loadRemoteMapObject(remoteMapObject, parentAttribute = null) {
        super.loadRemoteMapObject(remoteMapObject, parentAttribute);

        // When in admin mode, show all map icons
        if (!getState().isMapAdmin() && (this.seasonal_index !== null && getState().getMapContext().getSeasonalIndex() !== this.seasonal_index)) {
            // Hide this enemy by default
            this.setDefaultVisible(false);
        }
    }

    /**
     *
     * @returns {{permanent}}
     */
    getTooltipOptions() {
        return {
            // Disable permanent tooltips if no UI should be shown
            permanent: this.map.options.noUI ? false : this.permanent_tooltip
        };
    }

    /**
     * @inheritDoc
     */
    isEditable() {
        console.assert(this instanceof MapIcon, 'this is not a MapIcon', this);
        // Admin may edit everything, but not useful when editing a dungeonroute
        return super.isEditable() && this.linked_awakened_obelisk_id === null &&
            getState().isMapAdmin() === this.is_admin;
    }

    /**
     * @inheritDoc
     */
    isDeletable() {
        return this.isEditable();
    }

    toString() {
        return `Map icon (${this.comment.substring(0, 25)})`;
    }
}
