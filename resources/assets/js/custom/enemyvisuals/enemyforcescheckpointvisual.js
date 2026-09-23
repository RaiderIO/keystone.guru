/**
 * Renders an {@link EnemyForcesCheckpoint}'s pill icon, satellite pill and tooltip. Mirrors the
 * Enemy/EnemyVisual split: this class owns every DOM/Leaflet-facing concern, while
 * EnemyForcesCheckpoint keeps the data accessors (getEnemies, getEnemyForces, getFloorIds, ...) and
 * delegates rendering to an instance of this class.
 */
class EnemyForcesCheckpointVisual {
    constructor(map, checkpoint, layer) {
        console.assert(this instanceof EnemyForcesCheckpointVisual, 'this is not an EnemyForcesCheckpointVisual', this);
        console.assert(checkpoint instanceof EnemyForcesCheckpoint, 'checkpoint was not an EnemyForcesCheckpoint', checkpoint);

        /** @type DungeonMap */
        this.map = map;
        /** @type EnemyForcesCheckpoint */
        this.checkpoint = checkpoint;
        this.layer = layer;

        // Satellite pill for the floor this checkpoint has enemies on, but isn't anchored to.
        this._satelliteLayerGroup = null;
    }

    /**
     * Rebuilds the pill label and, when the current floor holds members but isn't the floor this
     * checkpoint is anchored to, the satellite pill for that floor.
     */
    refreshPill() {
        console.assert(this instanceof EnemyForcesCheckpointVisual, 'this is not an EnemyForcesCheckpointVisual', this);

        let html = this._getPillHtml();

        if (this.layer !== null) {
            this.layer.setIcon(this._buildPillIcon(html));
        }

        this._refreshSatellitePill(html);
        this.checkpoint.rebindTooltip();
    }

    /**
     * Builds the pill's divIcon with an explicit, measured size and a centered anchor. Leaflet cannot
     * center an auto-sized icon itself, and every workaround that touches the rendered element after the
     * fact fails eventually: a CSS transform on the inner pill moves only the painted pill while the
     * marker root - the element leaflet.draw's edit mode puts the dashed border on - stays put
     * (misaligning border from label), and post-hoc inline margins are wiped whenever Leaflet re-creates
     * the element from the icon options (visibility toggles, floor switches). Baking the measured size
     * into iconSize/iconAnchor makes Leaflet itself re-apply the centering on every re-add, and keeps
     * leaflet.draw's _offsetMarker() edit-mode compensation working since that adjusts the same margins.
     * @param html {String}
     * @returns {L.DivIcon}
     * @private
     */
    _buildPillIcon(html) {
        console.assert(this instanceof EnemyForcesCheckpointVisual, 'this is not an EnemyForcesCheckpointVisual', this);

        let size = this._measurePillSize(html);

        return L.divIcon({
            className: 'map_enemy_forces_checkpoint_pill_icon',
            iconSize: size,
            iconAnchor: size !== null ? [size[0] / 2, size[1] / 2] : null,
            html: html,
        });
    }

    /**
     * Renders the pill markup off-screen to learn its size, or null when there is no usable layout
     * (jsdom in tests reports zero sizes - the icon then falls back to Leaflet's uncentered default).
     * @param html {String}
     * @returns {Number[]|null}
     * @private
     */
    _measurePillSize(html) {
        console.assert(this instanceof EnemyForcesCheckpointVisual, 'this is not an EnemyForcesCheckpointVisual', this);

        if (typeof document === 'undefined' || document.body === null) {
            return null;
        }

        // Measure inside the leaflet container, not document.body: the pill's font-size is em-based
        // and leaflet.css sets its own base font size on the container, so a body-context measurement
        // comes out ~20% too large, leaving the icon box too big to hug the label.
        let parent = document.body;
        if (this.map !== null && this.map.leafletMap && typeof this.map.leafletMap.getContainer === 'function') {
            parent = this.map.leafletMap.getContainer();
        }

        let container = document.createElement('div');
        container.style.position = 'absolute';
        container.style.visibility = 'hidden';
        container.innerHTML = html;
        parent.appendChild(container);

        let pill = container.firstElementChild;
        let size = pill === null ? [0, 0] : [pill.offsetWidth, pill.offsetHeight];

        parent.removeChild(container);

        return size[0] > 0 ? size : null;
    }

    /**
     * The pill itself only says how much you need before entering. The checkpoint's name and what it
     * actually holds - the numbers that make that figure interpretable - go in the tooltip.
     */
    bindTooltip() {
        console.assert(this instanceof EnemyForcesCheckpointVisual, 'this is not an EnemyForcesCheckpointVisual', this);

        if (this.map.options.noUI || this.layer === null) {
            return;
        }

        let checkpoint = this.checkpoint;
        let name = checkpoint.name === null || checkpoint.name === '' ? lang.get('js.enemy_forces_checkpoint_unnamed_label') : lang.get(checkpoint.name);
        let enemyForces = checkpoint.getEnemyForces();

        let tooltipText;
        if (getState().getKillZonesNumberStyle() === NUMBER_STYLE_ENEMY_FORCES) {
            tooltipText = lang.get('js.enemy_forces_checkpoint_tooltip_enemy_forces', {name: name, enemyForces: enemyForces});
        } else {
            tooltipText = lang.get('js.enemy_forces_checkpoint_tooltip_percentage', {
                name: name,
                percentage: getFormattedPercentage(enemyForces, this.map.enemyForcesManager.getEnemyForcesRequired()),
            });
        }

        let floorIds = checkpoint.getFloorIds();
        if (floorIds.length > 1) {
            tooltipText += ` ${lang.get('js.enemy_forces_checkpoint_tooltip_spans_floors', {floors: floorIds.length})}`;
        }

        this.layer.bindTooltip(tooltipText, {direction: 'top'});
    }

    /**
     * Builds the pill's markup: how much enemy forces you need before entering this checkpoint, following
     * the "Pull number style" setting - a checkpoint is a group total, like a pull, not a per-enemy number.
     * @returns {String}
     * @private
     */
    _getPillHtml() {
        console.assert(this instanceof EnemyForcesCheckpointVisual, 'this is not an EnemyForcesCheckpointVisual', this);

        let enemyForces = this.checkpoint.getEnemyForces();
        let enemyForcesRequired = this.map.enemyForcesManager.getEnemyForcesRequired();
        // Everything that is NOT in this checkpoint - what you must already have killed when you walk in.
        let requiredBefore = Math.max(0, enemyForcesRequired - enemyForces);

        let value;
        if (getState().getKillZonesNumberStyle() === NUMBER_STYLE_ENEMY_FORCES) {
            value = lang.get('js.enemy_forces_checkpoint_pill_enemy_forces', {enemyForces: requiredBefore});
        } else {
            value = lang.get('js.enemy_forces_checkpoint_pill_percentage', {
                percentage: getFormattedPercentage(requiredBefore, enemyForcesRequired),
            });
        }

        return Handlebars.templates['map_enemy_forces_checkpoint_pill']({value: value});
    }

    /**
     * Draws (or removes) the pill for a floor this checkpoint has enemies on but isn't anchored to.
     * @param html {String}
     * @private
     */
    _refreshSatellitePill(html) {
        console.assert(this instanceof EnemyForcesCheckpointVisual, 'this is not an EnemyForcesCheckpointVisual', this);

        this._removeSatellitePill();

        // The user hid this map object group - the satellite must obey that too.
        if (!this.checkpoint.isMapObjectGroupShown()) {
            return;
        }

        let currentFloorId = getState().getCurrentFloor().id;

        // The anchor itself covers this floor already.
        if (currentFloorId === this.checkpoint.floor_id) {
            return;
        }

        let latLngs = [];
        let enemies = this.checkpoint.getEnemies();
        for (let index in enemies) {
            let enemy = enemies[index];

            if ((enemy.source_floor_id ?? enemy.floor_id) === currentFloorId) {
                latLngs.push({lat: enemy.lat, lng: enemy.lng});
            }
        }

        if (latLngs.length === 0) {
            return;
        }

        let lat = 0, lng = 0;
        for (let index in latLngs) {
            lat += latLngs[index].lat;
            lng += latLngs[index].lng;
        }

        this._satelliteLayerGroup = L.layerGroup().addTo(this.map.leafletMap);

        L.marker([lat / latLngs.length, lng / latLngs.length], {
            icon: this._buildPillIcon(html),
            // Purely informational - never intercept clicks meant for enemies underneath.
            interactive: false,
            keyboard: false,
        }).addTo(this._satelliteLayerGroup);
    }

    /**
     * @private
     */
    _removeSatellitePill() {
        console.assert(this instanceof EnemyForcesCheckpointVisual, 'this is not an EnemyForcesCheckpointVisual', this);

        if (this._satelliteLayerGroup !== null) {
            this.map.leafletMap.removeLayer(this._satelliteLayerGroup);
            this._satelliteLayerGroup = null;
        }
    }

    cleanup() {
        console.assert(this instanceof EnemyForcesCheckpointVisual, 'this is not an EnemyForcesCheckpointVisual', this);

        this._removeSatellitePill();
    }
}

// Guarded export for the test runner (Vitest). This is a no-op in the browser,
// where `module` is undefined, so it does not affect the concatenated bundle.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        EnemyForcesCheckpointVisual,
    };
}
