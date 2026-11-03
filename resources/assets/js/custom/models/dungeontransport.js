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

L.Draw.DungeonTransportPath = L.Draw.Polyline.extend({
    statics: {
        TYPE: 'dungeontransportpath'
    },
    initialize: function (map, options) {
        L.Draw.Polyline.prototype.initialize.call(this, map, options);
        this.type = L.Draw.DungeonTransportPath.TYPE;
    },
    /**
     * Hands the drawn vertices to options.onCreated instead of firing draw:created, which would add the line to the
     * map as a new map object.
     */
    _fireCreatedEvent: function () {
        this.options.onCreated(this._poly.getLatLngs());
    }
});

/**
 * One end of a portal, boat, shortcut or other transport. Clicking it takes the viewer to its linked partner.
 *
 * @property {Number|null} linked_dungeon_transport_id
 * @property {Number|null} target_dungeon_id
 * @property {String|null} link_key
 * @property {String|null} path_vertices_json [{lat, lng, floor_id}], from this transport towards its partner
 * @property {String|null} facade_path_vertices_json The same path on the facade, when the map shows the facade floor next
 *                                                   to the floors the path is stored on
 */
class DungeonTransport extends Icon {
    constructor(map, layer) {
        super(map, layer, {name: 'dungeontransport', has_route_model_binding: true});

        this.label = 'DungeonTransport';
        this.mouseOver = false;
        /** @type {L.FeatureGroup|null} Lives outside the decorator, since a path can cross floors neither end is on */
        this.pathLayer = null;
        this.setMapIconType(getState().getMapContext().getMapIconTypeByKey(DUNGEON_TRANSPORT_DEFAULT_MAP_ICON_TYPE_KEY), false);

        // Either end of a pair may finish loading or be shown last, and only one of them draws the line between them
        this.register(['object:initialized', 'object:changed', 'shown', 'hidden'], this, this._rebuildPairLines.bind(this));
        this.register('object:deleted', this, this._onDeleted.bind(this));
        getState().register('floorid:changed', this, this._rebuildPathLayer.bind(this));
        this._getMapObjectGroup().register('visibility:changed', this, this._rebuildPathLayer.bind(this));
    }

    /**
     * @inheritDoc
     */
    _getAttributes(force = false) {
        console.assert(this instanceof DungeonTransport, 'this was not a DungeonTransport', this);

        if (this._cachedAttributes !== null && !force) {
            return this._cachedAttributes;
        }

        let attributes = super._getAttributes(force);
        let factionAttribute = attributes.find(attribute => attribute.name === 'faction');
        factionAttribute.values = factionAttribute.values.map(faction => faction.id === 'any' ?
            {id: faction.id, name: lang.get('js.dungeontransport_faction_any_option')} : faction);

        return this._cachedAttributes = attributes.concat([
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
            new Attribute({
                name: 'path_vertices_json',
                type: 'text',
                edit: false,
                default: null,
            }),
            new Attribute({
                name: 'facade_path_vertices_json',
                type: 'text',
                edit: false,
                save: false,
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

        let linkedDungeonTransport = this._getMapObjectGroup().findMapObjectById(this.linked_dungeon_transport_id);

        return linkedDungeonTransport === null || linkedDungeonTransport._deleted ? null : linkedDungeonTransport;
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
     * Of a two-way pair only the lower id draws the line between them.
     *
     * @param linkedDungeonTransport {DungeonTransport}
     * @returns {Boolean}
     */
    isPairLineOwner(linkedDungeonTransport) {
        return !this.isTwoWayWith(linkedDungeonTransport) || this.id < linkedDungeonTransport.id;
    }

    /**
     * @param onFacade {Boolean} The path as it runs on the facade, where the map holds that next to the stored path.
     * @returns {{lat: Number, lng: Number, floor_id: Number}[]}
     */
    getPathVertices(onFacade = false) {
        let pathVerticesJson = onFacade && this.facade_path_vertices_json ? this.facade_path_vertices_json : this.path_vertices_json;

        return pathVerticesJson ? JSON.parse(pathVerticesJson) : [];
    }

    /**
     * Either end of a two-way pair may hold the pair's path.
     *
     * @param onFacade {Boolean} The path as it runs on the facade, where the map holds that next to the stored path.
     * @returns {{lat: Number, lng: Number, floor_id: Number}[]} The pair's path, running from this transport to its partner.
     */
    getPairPathVertices(onFacade = false) {
        let pathVertices = this.getPathVertices(onFacade);
        let linkedDungeonTransport = this.getLinkedDungeonTransport();

        if (pathVertices.length === 0 && linkedDungeonTransport !== null && this.isTwoWayWith(linkedDungeonTransport)) {
            return linkedDungeonTransport.getPathVertices(onFacade).reverse();
        }

        return pathVertices;
    }

    /**
     * On the facade, a vertex on a floor the facade does not hold keeps that floor's coordinates and is left out too.
     *
     * @param floor {{id: Number, facade: Boolean}}
     * @returns {Number[][][]} Each run of the pair's consecutive vertices on the floor.
     */
    getPathLatLngRuns(floor) {
        let runs = [];
        let run = [];

        for (let vertex of this.getPairPathVertices(!!floor.facade)) {
            if (vertex.floor_id === floor.id) {
                run.push([vertex.lat, vertex.lng]);
            } else {
                runs.push(run);
                run = [];
            }
        }
        runs.push(run);

        return runs.filter(latLngs => latLngs.length >= 2);
    }

    /**
     * @private
     */
    _rebuildPairLines() {
        let linkedDungeonTransport = this.getLinkedDungeonTransport();
        if (linkedDungeonTransport !== null) {
            if (linkedDungeonTransport.isVisible()) {
                linkedDungeonTransport._rebuildDecorator();
            }
            linkedDungeonTransport._rebuildPathLayer();
        }

        if (this.isVisible()) {
            this._rebuildDecorator();
        }
        this._rebuildPathLayer();
    }

    /**
     * @returns {Object} The line's options, coloured by the transport's faction.
     */
    getConnectionPolylineOptions() {
        let factionColors = c.map.dungeontransport.factionColors;

        return $.extend({}, c.map.dungeontransport.connectionPolylineOptions, {
            color: factionColors[this.faction] ?? factionColors.any,
        });
    }

    /**
     * @param linkedDungeonTransport {DungeonTransport|null}
     * @returns {Object}
     * @private
     */
    _getConnectionPolylineOptions(linkedDungeonTransport) {
        let options = this.getConnectionPolylineOptions();
        if (this.mouseOver || linkedDungeonTransport?.mouseOver) {
            options = $.extend({}, options, c.map.dungeontransport.connectionPolylineMouseoverOptions);
        }

        return options;
    }

    /**
     * @param latLngs {Array}
     * @param linkedDungeonTransport {DungeonTransport|null}
     * @returns {L.Layer[]} The line, with an arrowhead halfway when the link is one-way.
     * @private
     */
    _getConnectionLayers(latLngs, linkedDungeonTransport) {
        let options = this._getConnectionPolylineOptions(linkedDungeonTransport);
        let polyline = L.polyline(latLngs, options);
        let layers = [polyline];

        if (linkedDungeonTransport !== null && !this.isTwoWayWith(linkedDungeonTransport)) {
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

        return layers;
    }

    /**
     * @inheritDoc
     */
    _getDecorator() {
        let linkedDungeonTransport = this.getLinkedDungeonTransport();

        if (this.layer === null || linkedDungeonTransport === null || linkedDungeonTransport.layer === null ||
            !this.isVisible() || !linkedDungeonTransport.isVisible() || !this.isPairLineOwner(linkedDungeonTransport) ||
            this.getPairPathVertices().length > 0) {
            return null;
        }

        return L.featureGroup(this._getConnectionLayers(
            [this.layer.getLatLng(), linkedDungeonTransport.layer.getLatLng()],
            linkedDungeonTransport,
        ));
    }

    /**
     * A transport not linked to another one on this map, such as one linked to another dungeon by its link key, draws
     * its own path. One linked to a transport that is gone draws nothing.
     *
     * @returns {L.FeatureGroup|null} The pair's path on the current floor, when this transport draws the pair's line.
     * @private
     */
    _getPathLayer() {
        let linkedDungeonTransport = this.getLinkedDungeonTransport();
        let drawsThePath = linkedDungeonTransport === null ?
            !(this.linked_dungeon_transport_id > 0) : this.isPairLineOwner(linkedDungeonTransport);

        if (!drawsThePath || !this._getMapObjectGroup().isShown()) {
            return null;
        }

        let runs = this.getPathLatLngRuns(getState().getCurrentFloor());
        if (runs.length === 0) {
            return null;
        }

        return L.featureGroup(runs.flatMap(latLngs => this._getConnectionLayers(latLngs, linkedDungeonTransport)));
    }

    /**
     * @private
     */
    _rebuildPathLayer() {
        this._cleanPathLayer();

        this.pathLayer = this._getPathLayer();
        if (this.pathLayer !== null) {
            this.pathLayer.addTo(this.map.leafletMap);
        }
    }

    /**
     * The transports that drew their line to this one have nothing left to draw it to.
     *
     * @private
     */
    _onDeleted() {
        this._cleanPathLayer();

        Object.values(this._getMapObjectGroup().objects)
            .filter(dungeonTransport => dungeonTransport !== this && dungeonTransport.linked_dungeon_transport_id === this.id)
            .forEach(dungeonTransport => dungeonTransport._rebuildPairLines());
    }

    /**
     * @private
     */
    _cleanPathLayer() {
        if (this.pathLayer !== null) {
            this.map.leafletMap.removeLayer(this.pathLayer);
            this.pathLayer = null;
        }
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
                this._rebuildPairLines();
            })
            .on('mouseout', () => {
                this.mouseOver = false;
                this._rebuildPairLines();
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

        this._cleanPathLayer();
        this.unregister(['object:initialized', 'object:changed', 'shown', 'hidden', 'object:deleted'], this);
        getState().unregister('floorid:changed', this);
        this._getMapObjectGroup().unregister('visibility:changed', this);
    }
}

// Guarded export for the test runner (Vitest). This is a no-op in the browser,
// where `module` is undefined, so it does not affect the concatenated bundle.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        DungeonTransport,
    };
}
