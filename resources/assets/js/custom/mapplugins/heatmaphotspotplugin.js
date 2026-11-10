/**
 * Draws the deadly-NPC hotspots that come with an open-world heatmap: per hotspot its area as a polygon and the most
 * deadly NPC's portrait at its centre, with a breakdown of the area's deadliest NPCs on hover or click.
 */
class HeatmapHotspotPlugin extends MapPlugin {
    constructor(map) {
        super(map);

        let self = this;

        /** @type {L.LayerGroup|null} */
        this.layerGroup = null;
        /** @type {Object<Number, Object[]>} The hotspots per floor, keyed by floor id */
        this.hotspotsByFloorId = {};
        this.visible = true;

        getState().register('floorid:changed', this, function (floorIdChangedEvent) {
            self._applyHotspotsForFloor(floorIdChangedEvent.data.floorId);
        });
    }

    /**
     * @returns {Boolean}
     */
    isEnabled() {
        return getState().getMapContext() instanceof MapContextDungeonExplore;
    }

    addToMap() {
        console.assert(this instanceof HeatmapHotspotPlugin, 'this is not an instance of HeatmapHotspotPlugin', this);

        if (!this.isEnabled()) {
            return;
        }

        this.layerGroup = L.layerGroup().addTo(this.map.leafletMap);

        // Hotspots may have arrived while the map deferred loading its plugins
        this._applyHotspotsForFloor(getState().getCurrentFloor().id);
    }

    removeFromMap() {
        console.assert(this instanceof HeatmapHotspotPlugin, 'this is not an instance of HeatmapHotspotPlugin', this);

        if (this.layerGroup !== null) {
            this.map.leafletMap.removeLayer(this.layerGroup);
            this.layerGroup = null;
        }
    }

    /**
     * @param enabled {Boolean}
     */
    toggle(enabled) {
        console.assert(this instanceof HeatmapHotspotPlugin, 'this is not an instance of HeatmapHotspotPlugin', this);

        this.visible = enabled;

        this._applyHotspotsForFloor(getState().getCurrentFloor().id);
    }

    /**
     * @param hotspotsPerFloor {Object[]|null|undefined} The response's hotspots: [{floor_id, hotspots}], absent when
     * the response carried none
     */
    setHotspotsPerFloor(hotspotsPerFloor) {
        console.assert(this instanceof HeatmapHotspotPlugin, 'this is not an instance of HeatmapHotspotPlugin', this);

        this.hotspotsByFloorId = {};
        for (let floorHotspots of (hotspotsPerFloor ?? [])) {
            this.hotspotsByFloorId[floorHotspots.floor_id] = floorHotspots.hotspots;
        }

        this._applyHotspotsForFloor(getState().getCurrentFloor().id);
    }

    /**
     * @param hotspot {Object}
     * @returns {Object} The data the breakdown template renders
     */
    getBreakdownTemplateData(hotspot) {
        return {
            title: lang.get('js.heatmap_hotspot_breakdown_title'),
            deaths_label: lang.get('js.heatmap_hotspot_deaths_label', {count: hotspot.deaths}),
            npcs: hotspot.npcs.map(npc => ({
                name: npc.name ?? lang.get('js.heatmap_hotspot_unknown_npc_label', {id: npc.npc_id}),
                portrait_url: this._getPortraitUrl(npc),
                percentage: npc.percentage,
            })),
        };
    }

    /**
     * @param npc {Object}
     * @returns {String}
     * @private
     */
    _getPortraitUrl(npc) {
        return npc.portrait_url === null ?
            `${this.map.options.assetsBaseUrl}/images/enemyportraits/unknown.png` :
            `${this.map.options.assetsBaseUrl}/${npc.portrait_url}`;
    }

    /**
     * @param floorId {Number}
     * @private
     */
    _applyHotspotsForFloor(floorId) {
        if (this.layerGroup === null) {
            return;
        }

        this.layerGroup.clearLayers();

        if (!this.visible) {
            return;
        }

        let hotspots = this.hotspotsByFloorId[floorId] ?? [];
        for (let index = 0; index < hotspots.length; index++) {
            this._addHotspot(hotspots[index], index);
        }
    }

    /**
     * @param hotspot {Object}
     * @param index {Number}
     * @private
     */
    _addHotspot(hotspot, index) {
        let breakdownHtml = Handlebars.templates['map_heatmap_hotspot_breakdown_template'](
            $.extend({}, getHandlebarsDefaultVariables(), this.getBreakdownTemplateData(hotspot))
        );

        let polygon = L.polygon(hotspot.lat_lngs, c.map.heatmapHotspot.polygonOptions);

        let portraitHtml = Handlebars.templates['map_enemy_visual_enemy_portrait_template']({
            id: `heatmap_hotspot_${index}`,
            enemy_portrait_url: this._getPortraitUrl(hotspot.npcs[0]),
        });
        let iconSize = c.map.heatmapHotspot.iconSize;
        let marker = L.marker(hotspot.center, {
            icon: L.divIcon({
                html: `<div class="outer enemy_icon_npc_enemy_portrait aggressive heatmap_hotspot_portrait">${portraitHtml}</div>`,
                className: 'heatmap_hotspot_marker',
                iconSize: [iconSize, iconSize],
            }),
        });

        for (let layer of [polygon, marker]) {
            layer.bindTooltip(breakdownHtml, {direction: 'top', className: 'heatmap_hotspot_tooltip'});
            layer.bindPopup(breakdownHtml, {className: 'heatmap_hotspot_popup'});
            layer.on('popupopen', () => layer.closeTooltip());
            this.layerGroup.addLayer(layer);
        }
    }
}

// Guarded export for the test runner (Vitest). This is a no-op in the browser,
// where `module` is undefined, so it does not affect the concatenated bundle.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = HeatmapHotspotPlugin;
}
