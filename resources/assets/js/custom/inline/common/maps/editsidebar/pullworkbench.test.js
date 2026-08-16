// Regression tests for https://github.com/RaiderIO/keystone.guru/issues/4026: internal
// "refresh after add/remove kill area" calls into editPull() must not be mistaken for the
// external "click the same pull's edit button twice to close it" interaction, which would hide
// the workbench and null out `killZone` right after a re-render was requested.

global.MAP_OBJECT_GROUP_KILLZONE = 'killzone';

// editPull() only touches jQuery for the toggle-button selectors and the header text - none of
// which exist in this test's fake DOM - so a chainable no-op stub is enough.
function chainable() {
    const obj = {};
    ['each', 'show', 'hide', 'text', 'attr', 'val', 'on', 'unbind', 'bind'].forEach((method) => {
        obj[method] = vi.fn(() => obj);
    });
    return obj;
}

global.$ = vi.fn(() => chainable());
global.bootstrap = {Button: {getOrCreateInstance: () => ({toggle: () => {}})}};

const {Signalable} = require('../../../../signalable');
global.Signalable = Signalable;

const {PullWorkBench} = require('./pullworkbench');

function makeFakeKillZone(id) {
    return {id, index: id};
}

function makeWorkBench(killZonesById) {
    const killZoneGroup = {findMapObjectById: (id) => killZonesById[id] ?? null};
    const workBench = new PullWorkBench({
        map: {mapObjectGroupManager: {getByName: () => killZoneGroup}},
    });

    workBench.$workbench = chainable();
    // The real render sub-steps are DOM/lang heavy and irrelevant to the toggle logic under test.
    workBench._initDescriptionButton = vi.fn();
    workBench._initKillAreaButton = vi.fn();
    workBench._initColorPickerButton = vi.fn();
    workBench._initDeletePullButton = vi.fn();

    return workBench;
}

describe('PullWorkBench.editPull', () => {
    it('given the external double-click-to-close interaction, closes and clears the killZone', () => {
        const killZoneA = makeFakeKillZone(5);
        const workBench = makeWorkBench({5: killZoneA});
        workBench.killZone = killZoneA;

        workBench.editPull(5);

        expect(workBench.killZone).toBeNull();
        expect(workBench.$workbench.hide).toHaveBeenCalled();
    });

    it('given an internal refresh call for the same pull with forceReopen, keeps the workbench open', () => {
        const killZoneA = makeFakeKillZone(5);
        const workBench = makeWorkBench({5: killZoneA});
        workBench.killZone = killZoneA;

        workBench.editPull(5, {forceReopen: true});

        expect(workBench.killZone).toBe(killZoneA);
        expect(workBench.$workbench.hide).not.toHaveBeenCalled();
        expect(workBench.$workbench.show).toHaveBeenCalled();
    });

    it('given forceReopen, still re-renders the workbench sub-sections', () => {
        const killZoneA = makeFakeKillZone(5);
        const workBench = makeWorkBench({5: killZoneA});
        workBench.killZone = killZoneA;

        workBench.editPull(5, {forceReopen: true});

        expect(workBench._initDescriptionButton).toHaveBeenCalled();
        expect(workBench._initKillAreaButton).toHaveBeenCalled();
        expect(workBench._initColorPickerButton).toHaveBeenCalled();
        expect(workBench._initDeletePullButton).toHaveBeenCalled();
    });

    it('given a different pull id, switches to it without needing forceReopen', () => {
        const killZoneA = makeFakeKillZone(5);
        const killZoneB = makeFakeKillZone(9);
        const workBench = makeWorkBench({5: killZoneA, 9: killZoneB});
        workBench.killZone = killZoneA;

        workBench.editPull(9);

        expect(workBench.killZone).toBe(killZoneB);
        expect(workBench.$workbench.hide).not.toHaveBeenCalled();
        expect(workBench.$workbench.show).toHaveBeenCalled();
    });

    it('given an unknown killZoneId, hides the workbench and clears the killZone', () => {
        const killZoneA = makeFakeKillZone(5);
        const workBench = makeWorkBench({5: killZoneA});
        workBench.killZone = killZoneA;

        workBench.editPull(404);

        expect(workBench.killZone).toBeNull();
        expect(workBench.$workbench.hide).toHaveBeenCalled();
    });
});
