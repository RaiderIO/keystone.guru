const DUNGEON_TRANSPORT_DEFAULT_MAP_ICON_TYPE_KEY = 'portal_blue';

L.Draw.DungeonTransport = L.Draw.Marker.extend({
    statics: {
        TYPE: 'dungeontransport'
    },
    options: {
        icon: LeafletIconUnknown
    },
    initialize: function (map, options) {
        // Save the type so super can fire, need to do this as cannot do this.TYPE :(
        this.type = L.Draw.DungeonTransport.TYPE;
        L.Draw.Feature.prototype.initialize.call(this, map, options);
    }
});

/**
 * One end of a portal, boat, shortcut or other transport. Clicking it takes the viewer to its linked partner.
 *
 * @property {Number|null} linked_dungeon_transport_id
 * @property {Number|null} target_dungeon_id
 * @property {String|null} link_key
 */
class DungeonTransport extends Icon {
    constructor(map, layer) {
        super(map, layer, {name: 'dungeontransport', has_route_model_binding: true});

        this.label = 'DungeonTransport';
        this.mouseOver = false;
        this.setMapIconType(getState().getMapContext().getMapIconTypeByKey(DUNGEON_TRANSPORT_DEFAULT_MAP_ICON_TYPE_KEY), false);

        // Either end of a pair may finish loading last, and only one of them draws the line between them
        this.register(['object:initialized', 'object:changed'], this, this._rebuildPartnerDecorator.bind(this));
    }

    /**
     * @inheritDoc
     */
    _getAttributes(force = false) {
        console.assert(this instanceof DungeonTransport, 'this was not a DungeonTransport', this);

        if (this._cachedAttributes !== null && !force) {
            return this._cachedAttributes;
        }

        return this._cachedAttributes = super._getAttributes(force).concat([
            new Attribute({
                name: 'linked_dungeon_transport_id',
                type: 'select',
                values: this._getLinkedDungeonTransportSelectValues.bind(this),
                live_search: true,
                default: null,
            }),
            new Attribute({
                name: 'target_dungeon_id',
                type: 'select',
                values: () => getState().getMapContext().getDungeonSelectValues(false),
                live_search: true,
                default: null,
            }),
            new Attribute({
                name: 'link_key',
                type: 'text',
                default: null,
            }),
        ]);
    }

    /**
     * @returns {{id: Number, name: String}[]}
     * @private
     */
    _getLinkedDungeonTransportSelectValues() {
        let mapContext = getState().getMapContext();

        return Object.values(this._getMapObjectGroup().objects)
            .filter(dungeonTransport => dungeonTransport.id !== this.id && dungeonTransport.id > 0)
            .map(dungeonTransport => {
                let floor = mapContext.getFloorById(dungeonTransport.floor_id);

                return {
                    id: dungeonTransport.id,
                    name: lang.get('js.dungeontransport_linked_dungeon_transport_id_option', {
                        id: dungeonTransport.id,
                        text: lang.get(dungeonTransport.getName()),
                        floor: floor === false ? '?' : lang.get(floor.name),
                    }),
                };
            });
    }

    /**
     * @returns {DungeonTransportMapObjectGroup}
     * @private
     */
    _getMapObjectGroup() {
        return this.map.mapObjectGroupManager.getDungeonTransportMapObjectGroup();
    }

    /**
     * @returns {DungeonTransport|null}
     */
    getLinkedDungeonTransport() {
        if (this.linked_dungeon_transport_id === null || this.linked_dungeon_transport_id <= 0) {
            return null;
        }

        return this._getMapObjectGroup().findMapObjectById(this.linked_dungeon_transport_id);
    }

    /**
     * Of a two-way pair only one end draws the line between them.
     *
     * @param linkedDungeonTransport {DungeonTransport}
     * @returns {Boolean}
     */
    isTwoWayWith(linkedDungeonTransport) {
        return linkedDungeonTransport.linked_dungeon_transport_id === this.id;
    }

    /**
     * @private
     */
    _rebuildPartnerDecorator() {
        let linkedDungeonTransport = this.getLinkedDungeonTransport();
        if (linkedDungeonTransport !== null && linkedDungeonTransport.isVisible()) {
            linkedDungeonTransport._rebuildDecorator();
        }

        if (this.isVisible()) {
            this._rebuildDecorator();
        }
    }

    /**
     * @inheritDoc
     */
    _getDecorator() {
        let linkedDungeonTransport = this.getLinkedDungeonTransport();

        if (this.layer === null || linkedDungeonTransport === null || linkedDungeonTransport.layer === null ||
            !this.isVisible() || !linkedDungeonTransport.isVisible()) {
            return null;
        }

        let twoWay = this.isTwoWayWith(linkedDungeonTransport);
        if (twoWay && this.id > linkedDungeonTransport.id) {
            return null;
        }

        let options = c.map.dungeontransport.connectionPolylineOptions;
        if (this.mouseOver || linkedDungeonTransport.mouseOver) {
            options = $.extend({}, options, c.map.dungeontransport.connectionPolylineMouseoverOptions);
        }

        let polyline = L.polyline([this.layer.getLatLng(), linkedDungeonTransport.layer.getLatLng()], options);
        let layers = [polyline];

        if (!twoWay) {
            layers.push(L.polylineDecorator(polyline, {
                patterns: [{
                    offset: '50%',
                    repeat: 0,
                    symbol: L.Symbol.arrowHead({
                        pixelSize: 12,
                        pathOptions: {fillOpacity: options.opacity, weight: 0, color: options.color},
                    }),
                }],
            }));
        }

        return L.featureGroup(layers);
    }

    /**
     * Takes the viewer to the linked transport: pans to it when it is on the floor being viewed, switches floors otherwise.
     */
    travel() {
        let linkedDungeonTransport = this.getLinkedDungeonTransport();
        if (linkedDungeonTransport === null || linkedDungeonTransport.layer === null) {
            return;
        }

        let state = getState();
        let latLng = linkedDungeonTransport.layer.getLatLng();

        if (linkedDungeonTransport.floor_id === state.getCurrentFloor().id) {
            this.map.leafletMap.panTo(latLng);
        } else {
            state.setFloorId(linkedDungeonTransport.floor_id, [latLng.lat, latLng.lng]);
        }
    }

    /**
     * @inheritDoc
     */
    onLayerInit() {
        console.assert(this instanceof DungeonTransport, 'this is not a DungeonTransport', this);
        super.onLayerInit();

        this.layer.on('click', this.travel.bind(this))
            .on('mouseover', () => {
                this.mouseOver = true;
                this._rebuildPartnerDecorator();
            })
            .on('mouseout', () => {
                this.mouseOver = false;
                this._rebuildPartnerDecorator();
            });
    }

    /**
     * @returns {String} The comment, or the icon's name when there is no comment.
     */
    getName() {
        return super.getDisplayText();
    }

    /**
     * @inheritDoc
     */
    getDisplayText() {
        console.assert(this instanceof DungeonTransport, 'this is not a DungeonTransport', this);

        let text = this.getName();
        let linkedDungeonTransport = this.getLinkedDungeonTransport();
        let state = getState();

        if (linkedDungeonTransport !== null && linkedDungeonTransport.floor_id !== this.floor_id) {
            let floor = state.getMapContext().getFloorById(linkedDungeonTransport.floor_id);

            if (floor !== false) {
                return lang.get('js.dungeontransport_to_floor_label', {text: lang.get(text), floor: lang.get(floor.name)});
            }
        }

        return text;
    }

    /**
     * @inheritDoc
     */
    isEditable() {
        return getState().getMapContext() instanceof MapContextMappingVersionEdit;
    }

    toString() {
        return `Transport (${this.comment === null ? '' : this.comment.substring(0, 25)})`;
    }

    /**
     * @inheritDoc
     */
    cleanup() {
        super.cleanup();

        this.unregister(['object:initialized', 'object:changed'], this);
    }
}

// Guarded export for the test runner (Vitest). This is a no-op in the browser,
// where `module` is undefined, so it does not affect the concatenated bundle.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        DungeonTransport,
    };
}
