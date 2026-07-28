// Enemy tooltip suppression must stay scoped to the selection flows that actually want it.
//
// `disablesTooltips()` used to live on the shared `EnemySelection` base, which silently also silenced
// tooltips during ordinary pull-building in the (non-admin) route editor - `EditKillZoneEnemySelection`
// and `SelectKillZoneEnemySelectionOverpull` extend the same base. A router needs the npc name, health
// and enemy forces in that tooltip to decide whether to click an enemy at all, so these tests pin the
// override down onto the admin-side selection states only.
//
// Follows the global-script recipe from models/killzone.test.js: stub the collaborators the class
// bodies touch at LOAD time, then require the sources.

// Only `Signalable` is needed at load time (for `extends`); everything else these classes touch lives
// inside constructors/methods that these tests never run.
global.Signalable = class Signalable {
};
global.LeafletKillZoneIconEditMode = {};
global.MDTEnemyIconSelected = {};

// The real inheritance chain, so the default `disablesTooltips()` under test is the real one.
const {MapState} = require('../mapstate');
global.MapState = MapState;
const {MapObjectMapState} = require('../mapobjectmapstate');
global.MapObjectMapState = MapObjectMapState;

const {EnemySelection} = require('./enemyselection');
// The subclasses reference their base as a bare global (they are concatenated into one bundle).
global.EnemySelection = EnemySelection;

const {EnemyPatrolEnemySelection} = require('./enemypatrolenemyselection');
const {MDTEnemySelection} = require('./mdtenemyselection');
const {EnemyForcesCheckpointEnemySelection} = require('./enemyforcescheckpointenemyselection');
const {EditKillZoneEnemySelection} = require('./editkillzoneenemyselection');
const {SelectKillZoneEnemySelectionOverpull} = require('./selectkillzoneenemyselectionoverpull');

/**
 * Calls `disablesTooltips()` off the prototype, so no constructor (and none of its Leaflet/DOM wiring)
 * has to run.
 */
function disablesTooltips(cls) {
    return cls.prototype.disablesTooltips.call({});
}

describe('EnemySelection tooltip suppression scope', () => {
    it('disablesTooltips_givenSharedEnemySelectionBase_isNotOverridden', () => {
        // Arrange / Act
        const ownProperty = Object.prototype.hasOwnProperty.call(EnemySelection.prototype, 'disablesTooltips');

        // Assert
        // The whole point: subclasses used in the route editor must not inherit suppression from here.
        expect(ownProperty).toBe(false);
    });

    it.each([
        ['EnemyPatrolEnemySelection', EnemyPatrolEnemySelection],
        ['MDTEnemySelection', MDTEnemySelection],
        ['EnemyForcesCheckpointEnemySelection', EnemyForcesCheckpointEnemySelection],
    ])('disablesTooltips_givenAdminSelectionState_returnsTrue (%s)', (name, cls) => {
        // Arrange / Act
        const result = disablesTooltips(cls);

        // Assert
        expect(result).toBe(true);
        expect(Object.prototype.hasOwnProperty.call(cls.prototype, 'disablesTooltips')).toBe(true);
    });

    it.each([
        ['EditKillZoneEnemySelection', EditKillZoneEnemySelection],
        ['SelectKillZoneEnemySelectionOverpull', SelectKillZoneEnemySelectionOverpull],
    ])('disablesTooltips_givenRouteEditorPullBuildingState_returnsFalse (%s)', (name, cls) => {
        // Arrange / Act
        const result = disablesTooltips(cls);

        // Assert
        // Hovering an enemy while building a pull must keep showing npc name / health / enemy forces.
        expect(result).toBe(false);
    });
});

// `drawsEnemyEditBorder()` replaced EnemyVisual's per-call-site instanceof chains (MDT/patrol/
// checkpoint), so which selection states render selectable enemies with the dashed edit border is now
// decided here. Same scoping story as the tooltips above: admin selections yes, pull-building no.
describe('EnemySelection edit border scope', () => {
    it('drawsEnemyEditBorder_givenSharedEnemySelectionBase_returnsFalse', () => {
        // Arrange / Act
        const result = EnemySelection.prototype.drawsEnemyEditBorder.call({});

        // Assert
        // The default must be opt-in, or every new selection state would silently get the border.
        expect(result).toBe(false);
    });

    it.each([
        ['EnemyPatrolEnemySelection', EnemyPatrolEnemySelection],
        ['MDTEnemySelection', MDTEnemySelection],
        ['EnemyForcesCheckpointEnemySelection', EnemyForcesCheckpointEnemySelection],
    ])('drawsEnemyEditBorder_givenAdminSelectionState_returnsTrue (%s)', (name, cls) => {
        // Arrange / Act
        const result = cls.prototype.drawsEnemyEditBorder.call({});

        // Assert
        expect(result).toBe(true);
    });

    it.each([
        ['EditKillZoneEnemySelection', EditKillZoneEnemySelection],
        ['SelectKillZoneEnemySelectionOverpull', SelectKillZoneEnemySelectionOverpull],
    ])('drawsEnemyEditBorder_givenRouteEditorPullBuildingState_returnsFalse (%s)', (name, cls) => {
        // Arrange / Act
        const result = cls.prototype.drawsEnemyEditBorder.call({});

        // Assert
        // Pull-building has its own selection visuals; the dashed border is an admin mapping cue.
        expect(result).toBe(false);
    });
});
