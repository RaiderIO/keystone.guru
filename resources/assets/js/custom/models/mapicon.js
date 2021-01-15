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
 * @property floor_id int
 * @property map_icon_type_id int
 * @property linked_awakened_obelisk_id int
 * @property permanent_tooltip int
 * @property seasonal_index int
 * @property comment string
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
    getTooltipOptions(){
        return {
            permanent: this.permanent_tooltip
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

    cleanup() {
        super.cleanup();

        getState().unregister('mapzoomlevel:changed', this);
        this.map.unregister('map:mapstatechanged', this);
        this.unregister('object:changed', this);
    }
}