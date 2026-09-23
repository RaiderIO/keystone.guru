// The satellite pill is drawn for a floor a checkpoint has members on but is not anchored to. It is
// added straight to the leaflet map rather than to the owning MapObjectGroup's layer group, so
// `MapObjectGroup.setVisibility(false)` (the "Enemy forces checkpoints" entry in the Map Elements
// dropdown) cannot reach it - it only ever removes each map object's own `layer`.
//
// Without that gate, hiding the group leaves an orphan satellite on the map that `refreshPill()`
// re-creates on every floor switch, and the choice is persisted in the `hidden_map_object_groups`
// cookie - so it comes back on every page load with no way to get rid of it.
//
// Follows the global-script recipe from killzone.test.js: stub the collaborators the class body
// touches at LOAD time, then require the source.

global.EnemyForcesCheckpoint = class EnemyForcesCheckpoint {
};
global.NUMBER_STYLE_ENEMY_FORCES = 'enemy_forces';
global.NUMBER_STYLE_PERCENTAGE = 'percentage';

// Leaflet bits used at load time are not needed here (unlike the model file, this one has no
// top-level L.divIcon()/L.Marker.extend() calls), but the code under test calls into L at runtime.
global.L = {
    divIcon: function (options) {
        return {options};
    },
};

const {EnemyForcesCheckpointVisual} = require('./enemyforcescheckpointvisual');

/**
 * Builds a visual with just the collaborators `_refreshSatellitePill` reaches for, backed by a fake
 * checkpoint carrying the two enemies that make it want a satellite on floor 2.
 *
 * @param options {{groupIsShown?: Boolean, currentFloorId?: Number}}
 */
function createVisual({groupIsShown = true, currentFloorId = 2} = {}) {
    const removedLayers = [];
    const addedLayers = [];

    const enemies = [
        {id: 1, floor_id: 2, source_floor_id: null, lat: 10, lng: 20},
        {id: 2, floor_id: 2, source_floor_id: null, lat: 30, lng: 40},
    ];

    const checkpoint = Object.create(EnemyForcesCheckpoint.prototype);
    checkpoint.id = 55;
    // Anchored on floor 1, but the members above live on floor 2 - so floor 2 wants a satellite.
    checkpoint.floor_id = 1;
    checkpoint.isMapObjectGroupShown = () => groupIsShown;
    checkpoint.getEnemies = () => enemies;

    const map = {
        leafletMap: {
            removeLayer: (layer) => removedLayers.push(layer),
        },
    };

    const visual = new EnemyForcesCheckpointVisual(map, checkpoint, null);

    global.getState = () => ({
        getCurrentFloor: () => ({id: currentFloorId}),
    });

    global.L.layerGroup = () => ({
        addTo: function () {
            addedLayers.push(this);

            return this;
        },
    });
    global.L.marker = () => ({
        addTo: (layerGroup) => layerGroup,
    });

    return {visual, removedLayers, addedLayers};
}

describe('EnemyForcesCheckpointVisual._refreshSatellitePill', () => {
    it('refreshSatellitePill_givenVisibleMapObjectGroup_drawsTheSatellite', () => {
        // Arrange
        const {visual} = createVisual({groupIsShown: true});

        // Act
        visual._refreshSatellitePill('<div>10%</div>');

        // Assert
        expect(visual._satelliteLayerGroup).not.toBeNull();
    });

    it('refreshSatellitePill_givenHiddenMapObjectGroup_drawsNothing', () => {
        // Arrange
        const {visual} = createVisual({groupIsShown: false});

        // Act
        visual._refreshSatellitePill('<div>10%</div>');

        // Assert
        expect(visual._satelliteLayerGroup).toBeNull();
    });

    it('refreshSatellitePill_givenMapObjectGroupHiddenAfterBeingShown_removesTheExistingSatellite', () => {
        // Arrange
        const shown = createVisual({groupIsShown: true});
        shown.visual._refreshSatellitePill('<div>10%</div>');
        const drawnSatellite = shown.visual._satelliteLayerGroup;

        const hidden = createVisual({groupIsShown: false});
        hidden.visual._satelliteLayerGroup = drawnSatellite;

        // Act
        hidden.visual._refreshSatellitePill('<div>10%</div>');

        // Assert
        // A floor switch while the group is hidden must not leave (or re-create) an orphan pill.
        expect(hidden.visual._satelliteLayerGroup).toBeNull();
        expect(hidden.removedLayers).toContain(drawnSatellite);
    });

    it('refreshSatellitePill_givenAnchorFloorIsTheCurrentFloor_drawsNothing', () => {
        // Arrange
        // The checkpoint's own marker already covers its anchor floor.
        const {visual} = createVisual({groupIsShown: true, currentFloorId: 1});

        // Act
        visual._refreshSatellitePill('<div>10%</div>');

        // Assert
        expect(visual._satelliteLayerGroup).toBeNull();
    });
});

/**
 * Builds a visual with just the collaborators the pill and the tooltip reach for.
 *
 * The two number style settings are deliberately given OPPOSING values, so a test can tell which of
 * the two was consulted: a checkpoint is a group total (like a pull), so it must follow "Pull number
 * style" and never "Enemy number style" - which is about the numbers drawn on individual enemies.
 *
 * @param options {{killZonesNumberStyle: String, mapNumberStyle: String}}
 */
function createVisualForNumberStyle({killZonesNumberStyle, mapNumberStyle}) {
    const checkpoint = Object.create(EnemyForcesCheckpoint.prototype);
    checkpoint.id = 55;
    checkpoint.floor_id = 1;
    checkpoint.name = 'Corridor';
    checkpoint.getEnemyForces = () => 20;
    checkpoint.getFloorIds = () => [1];

    const layer = {bindTooltip: (text) => (visual._boundTooltipText = text)};
    const map = {
        options: {noUI: false},
        enemyForcesManager: {
            getEnemyForcesForEnemies: () => 20,
            getEnemyForcesRequired: () => 100,
        },
    };

    const visual = new EnemyForcesCheckpointVisual(map, checkpoint, layer);

    global.getState = () => ({
        getCurrentFloor: () => ({id: 1}),
        getKillZonesNumberStyle: () => killZonesNumberStyle,
        getMapNumberStyle: () => mapNumberStyle,
    });

    // Echo the key back with its parameters, so a test can assert both which branch ran and the value.
    global.lang = {get: (key, params = {}) => `${key}:${JSON.stringify(params)}`};
    global.getFormattedPercentage = (amount, total) => `${Math.round((amount / total) * 100)}%`;
    global.Handlebars = {templates: {map_enemy_forces_checkpoint_pill: ({value}) => `<div>${value}</div>`}};

    return visual;
}

describe('EnemyForcesCheckpointVisual number style', () => {
    it('getPillHtml_givenPullNumberStyleIsEnemyForces_rendersRawEnemyForces', () => {
        // Arrange
        const visual = createVisualForNumberStyle({
            killZonesNumberStyle: NUMBER_STYLE_ENEMY_FORCES,
            mapNumberStyle: NUMBER_STYLE_PERCENTAGE,
        });

        // Act
        const html = visual._getPillHtml();

        // Assert
        // 100 required - 20 held by this checkpoint = 80 needed before entering.
        expect(html).toContain('js.enemy_forces_checkpoint_pill_enemy_forces');
        expect(html).toContain('80');
    });

    it('getPillHtml_givenPullNumberStyleIsPercentage_rendersAPercentage', () => {
        // Arrange
        const visual = createVisualForNumberStyle({
            killZonesNumberStyle: NUMBER_STYLE_PERCENTAGE,
            mapNumberStyle: NUMBER_STYLE_ENEMY_FORCES,
        });

        // Act
        const html = visual._getPillHtml();

        // Assert
        // The opposing "Enemy number style" value must not be able to reach the pill.
        expect(html).toContain('js.enemy_forces_checkpoint_pill_percentage');
        expect(html).toContain('80%');
    });

    it('bindTooltip_givenPullNumberStyleIsEnemyForces_rendersRawEnemyForces', () => {
        // Arrange
        const visual = createVisualForNumberStyle({
            killZonesNumberStyle: NUMBER_STYLE_ENEMY_FORCES,
            mapNumberStyle: NUMBER_STYLE_PERCENTAGE,
        });

        // Act
        visual.bindTooltip();

        // Assert
        expect(visual._boundTooltipText).toContain('js.enemy_forces_checkpoint_tooltip_enemy_forces');
    });

    it('bindTooltip_givenPullNumberStyleIsPercentage_rendersAPercentage', () => {
        // Arrange
        const visual = createVisualForNumberStyle({
            killZonesNumberStyle: NUMBER_STYLE_PERCENTAGE,
            mapNumberStyle: NUMBER_STYLE_ENEMY_FORCES,
        });

        // Act
        visual.bindTooltip();

        // Assert
        expect(visual._boundTooltipText).toContain('js.enemy_forces_checkpoint_tooltip_percentage');
        expect(visual._boundTooltipText).toContain('20%');
    });

    it('bindTooltip_givenNoUI_bindsNothing', () => {
        // Arrange
        const visual = createVisualForNumberStyle({
            killZonesNumberStyle: NUMBER_STYLE_ENEMY_FORCES,
            mapNumberStyle: NUMBER_STYLE_PERCENTAGE,
        });
        visual.map.options.noUI = true;

        // Act
        visual.bindTooltip();

        // Assert
        expect(visual._boundTooltipText).toBeUndefined();
    });
});
