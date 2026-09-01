// The "changes you made may not be saved" browser dialog used to fire for _any_ active MapState on an
// editable map (#4365) - having a pull selected was enough, which is the normal state while shift+clicking
// pulls together. Users concluded the editor had no save button and was throwing their work away.
//
// `shouldWarnUnsavedChanges()` is the opt-out, and its default must stay `true` so a state never loses its
// warning by accident. `EditKillZoneEnemySelection` is the one state that knows better: it queues changed
// pulls in `_changedKillZoneIds` and only flushes them to the server in `stop()`.
//
// Follows the global-script recipe from enemyselection.tooltips.test.js: stub the collaborators the class
// bodies touch at LOAD time, then require the sources.

// Only `Signalable` is needed at load time (for `extends`); everything else these classes touch lives
// inside constructors/methods that these tests never run.
global.Signalable = class Signalable {
};
global.LeafletKillZoneIconEditMode = {};

// The real inheritance chain, so the inherited default under test is the real one.
const {MapState} = require('../mapstate');
global.MapState = MapState;
const {MapObjectMapState} = require('../mapobjectmapstate');
global.MapObjectMapState = MapObjectMapState;

const {EnemySelection} = require('./enemyselection');
// The subclasses reference their base as a bare global (they are concatenated into one bundle).
global.EnemySelection = EnemySelection;

const {EditKillZoneEnemySelection} = require('./editkillzoneenemyselection');

/**
 * Calls `shouldWarnUnsavedChanges()` off the prototype against a hand-built `this`, so no constructor
 * (and none of its Leaflet/DOM/map wiring) has to run.
 */
function shouldWarnUnsavedChanges(cls, self = {}) {
    return cls.prototype.shouldWarnUnsavedChanges.call(self);
}

describe('MapState unsaved changes warning (#4365)', () => {
    it.each([
        ['MapState', MapState],
        ['MapObjectMapState', MapObjectMapState],
        ['EnemySelection', EnemySelection],
    ])('shouldWarnUnsavedChanges_givenAStateThatDoesNotOptOut_returnsTrue (%s)', (name, cls) => {
        // Arrange / Act
        const result = shouldWarnUnsavedChanges(cls);

        // Assert
        // Warning is the safe default - a state has to know its changes are synced to opt out.
        expect(result).toBe(true);
    });

    it.each([
        ['MapObjectMapState', MapObjectMapState],
        ['EnemySelection', EnemySelection],
    ])('shouldWarnUnsavedChanges_givenAnIntermediateBaseClass_isNotOverridden (%s)', (name, cls) => {
        // Arrange / Act
        const ownProperty = Object.prototype.hasOwnProperty.call(cls.prototype, 'shouldWarnUnsavedChanges');

        // Assert
        // Every enemy selection extends these, so an override here would silently take the warning away
        // from states that have no idea whether they are in sync.
        expect(ownProperty).toBe(false);
    });

    it('shouldWarnUnsavedChanges_givenQueuedKillZoneChanges_returnsTrue', () => {
        // Arrange
        const self = {_changedKillZoneIds: [5], getMapObject: () => ({synced: true})};

        // Act
        const result = shouldWarnUnsavedChanges(EditKillZoneEnemySelection, self);

        // Assert
        // Queued ids are only saved in stop(), so they are genuinely unsaved right now.
        expect(result).toBe(true);
    });

    it('shouldWarnUnsavedChanges_givenNoChangesAndASyncedKillZone_returnsFalse', () => {
        // Arrange
        const self = {_changedKillZoneIds: [], getMapObject: () => ({synced: true})};

        // Act
        const result = shouldWarnUnsavedChanges(EditKillZoneEnemySelection, self);

        // Assert
        // This is the reported case: a pull is selected, everything is saved, no dialog.
        expect(result).toBe(false);
    });

    it('shouldWarnUnsavedChanges_givenNoChangesAndAnUnsyncedKillZone_returnsTrue', () => {
        // Arrange
        const self = {_changedKillZoneIds: [], getMapObject: () => ({synced: false})};

        // Act
        const result = shouldWarnUnsavedChanges(EditKillZoneEnemySelection, self);

        // Assert
        // createNewPull() calls save() _before_ this map state is constructed, so a brand new pull has an
        // empty queue while its create request is still in flight (or after it failed). Dropping the
        // warning here would lose the pull on a shift+click followed by an immediate navigation.
        expect(result).toBe(true);
    });

    it('shouldWarnUnsavedChanges_givenNoSourceKillZone_returnsFalse', () => {
        // Arrange
        const self = {_changedKillZoneIds: [], getMapObject: () => null};

        // Act
        const result = shouldWarnUnsavedChanges(EditKillZoneEnemySelection, self);

        // Assert
        // MapObjectMapState explicitly allows a null source map object - nothing to lose.
        expect(result).toBe(false);
    });
});

describe('MapState._onBeforeUnload (#4365)', () => {
    /**
     * A stand-in for the BeforeUnloadEvent, recording whether the navigation was actually blocked.
     */
    function makeEvent() {
        return {
            preventDefaultCalled: false,
            preventDefault() {
                this.preventDefaultCalled = true;
            },
        };
    }

    it('_onBeforeUnload_givenTheStateWantsToWarn_blocksTheNavigation', () => {
        // Arrange
        const event = makeEvent();

        // Act
        MapState.prototype._onBeforeUnload.call({shouldWarnUnsavedChanges: () => true}, event);

        // Assert
        expect(event.preventDefaultCalled).toBe(true);
        // Chrome only shows the dialog when returnValue is set as well.
        expect(event.returnValue).toBe('');
    });

    it('_onBeforeUnload_givenTheStateDoesNotWantToWarn_leavesTheEventAlone', () => {
        // Arrange
        const event = makeEvent();

        // Act
        MapState.prototype._onBeforeUnload.call({shouldWarnUnsavedChanges: () => false}, event);

        // Assert
        expect(event.preventDefaultCalled).toBe(false);
        expect(event.returnValue).toBeUndefined();
    });
});
