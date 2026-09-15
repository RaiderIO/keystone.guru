// Regression tests for https://github.com/RaiderIO/keystone.guru/issues/4026: internal
// "refresh after add/remove kill area" calls into editPull() must not be mistaken for the
// external "click the same pull's edit button twice to close it" interaction, which would hide
// the workbench and null out `killZone` right after a re-render was requested.

global.MAP_OBJECT_GROUP_KILLZONE = 'killzone';

// `$` backed by the real jsdom document, so `.each()` really iterates matched elements and
// `bootstrap.Button.getOrCreateInstance(el).toggle()` calls are observable on real nodes -
// needed to verify the toggle-button guards (both the "depress the previous button" branch and
// `_initKillAreaButton`'s pressed-state sync) actually skip/run as intended.
function chainable(elements) {
    const obj = {
        length: elements.length,
        each: vi.fn(function (cb) {
            elements.forEach((el) => cb.call(el));
            return this;
        }),
    };
    ['show', 'hide', 'text', 'attr', 'val', 'on', 'unbind', 'bind', 'refreshTooltips', 'removeClass', 'addClass'].forEach((method) => {
        obj[method] = vi.fn(() => obj);
    });
    return obj;
}

global.$ = vi.fn((selector) => chainable(typeof selector === 'string' ? Array.from(document.querySelectorAll(selector)) : []));

const toggleSpy = vi.fn();
global.bootstrap = {Button: {getOrCreateInstance: () => ({toggle: toggleSpy})}};

const {Signalable} = require('../../../../signalable');
global.Signalable = Signalable;

const {PullWorkBench} = require('./pullworkbench');

function makeFakeKillZone(id, hasKillArea = false) {
    return {id, index: id, hasKillArea: () => hasKillArea};
}

function makeWorkBench(killZonesById) {
    const killZoneGroup = {findMapObjectById: (id) => killZonesById[id] ?? null};
    const workBench = new PullWorkBench({
        map: {mapObjectGroupManager: {getByName: () => killZoneGroup}},
    });

    workBench.$workbench = chainable([]);
    // The description/color-picker/delete render sub-steps are DOM/lang heavy and irrelevant to
    // the toggle logic under test; `_initKillAreaButton` is left real, see below.
    workBench._initDescriptionButton = vi.fn();
    workBench._initColorPickerButton = vi.fn();
    workBench._initDeletePullButton = vi.fn();

    return workBench;
}

beforeEach(() => {
    document.body.innerHTML = '';
    toggleSpy.mockClear();
    global.$.mockClear();
});

describe('PullWorkBench.editPull', () => {
    it('given the external double-click-to-close interaction, closes and clears the killZone', () => {
        document.body.innerHTML = `<button id="map_killzonessidebar_killzone_5_edit"></button>`;
        const killZoneA = makeFakeKillZone(5);
        const workBench = makeWorkBench({5: killZoneA});
        workBench.killZone = killZoneA;

        workBench.editPull(5);

        expect(workBench.killZone).toBeNull();
        expect(workBench.$workbench.hide).toHaveBeenCalled();
        // Depressed once when the panel first realizes it's the same id, then toggled back once
        // more in the close branch - net visual state unchanged, but both toggle() calls happen.
        expect(toggleSpy).toHaveBeenCalledTimes(2);
    });

    it('given an internal refresh call for the same pull with forceReopen, keeps the workbench open and never touches the edit-toggle button', () => {
        document.body.innerHTML = `<button id="map_killzonessidebar_killzone_5_edit"></button>`;
        const killZoneA = makeFakeKillZone(5);
        const workBench = makeWorkBench({5: killZoneA});
        workBench.killZone = killZoneA;

        workBench.editPull(5, {forceReopen: true});

        expect(workBench.killZone).toBe(killZoneA);
        expect(workBench.$workbench.hide).not.toHaveBeenCalled();
        expect(workBench.$workbench.show).toHaveBeenCalled();
        // The whole point of forceReopen: the close-on-same-id toggle dance must not run.
        expect(toggleSpy).not.toHaveBeenCalled();
    });

    it('given forceReopen, still re-renders the workbench sub-sections', () => {
        document.body.innerHTML = `<button id="map_killzonessidebar_killzone_5_edit"></button>`;
        const killZoneA = makeFakeKillZone(5);
        const workBench = makeWorkBench({5: killZoneA});
        workBench.killZone = killZoneA;
        workBench._initKillAreaButton = vi.fn();

        workBench.editPull(5, {forceReopen: true});

        expect(workBench._initDescriptionButton).toHaveBeenCalled();
        expect(workBench._initKillAreaButton).toHaveBeenCalled();
        expect(workBench._initColorPickerButton).toHaveBeenCalled();
        expect(workBench._initDeletePullButton).toHaveBeenCalled();
    });

    it('given a different pull id, switches to it and depresses the previously open pull\'s button', () => {
        document.body.innerHTML = `
            <button id="map_killzonessidebar_killzone_5_edit"></button>
            <button id="map_killzonessidebar_killzone_9_edit"></button>
        `;
        const killZoneA = makeFakeKillZone(5);
        const killZoneB = makeFakeKillZone(9);
        const workBench = makeWorkBench({5: killZoneA, 9: killZoneB});
        workBench.killZone = killZoneA;

        workBench.editPull(9);

        expect(workBench.killZone).toBe(killZoneB);
        expect(workBench.$workbench.hide).not.toHaveBeenCalled();
        expect(workBench.$workbench.show).toHaveBeenCalled();
        // The old pull's (5) button must still be depressed even though this isn't a close.
        expect(toggleSpy).toHaveBeenCalledTimes(1);
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

describe('PullWorkBench._initKillAreaButton', () => {
    it('given the toggle button already reflects hasKillArea(), does not toggle it again', () => {
        document.body.innerHTML = `
            <span id="map_killzonessidebar_killzone_kill_area_label"></span>
            <button id="map_killzonessidebar_killzone_has_killzone" class="active"></button>
            <i id="map_killzonessidebar_killzone_has_killzone_icon"></i>
        `;
        const killZoneA = makeFakeKillZone(5, true);
        const workBench = makeWorkBench({5: killZoneA});
        workBench.killZone = killZoneA;

        workBench._initKillAreaButton();

        expect(toggleSpy).not.toHaveBeenCalled();
    });

    it('given the toggle button does not yet reflect hasKillArea(), toggles it once', () => {
        document.body.innerHTML = `
            <span id="map_killzonessidebar_killzone_kill_area_label"></span>
            <button id="map_killzonessidebar_killzone_has_killzone"></button>
            <i id="map_killzonessidebar_killzone_has_killzone_icon"></i>
        `;
        const killZoneA = makeFakeKillZone(5, true);
        const workBench = makeWorkBench({5: killZoneA});
        workBench.killZone = killZoneA;

        workBench._initKillAreaButton();

        expect(toggleSpy).toHaveBeenCalledTimes(1);
    });

    it('given it runs twice in a row (as happens on a forceReopen refresh), only toggles once total', () => {
        document.body.innerHTML = `
            <span id="map_killzonessidebar_killzone_kill_area_label"></span>
            <button id="map_killzonessidebar_killzone_has_killzone"></button>
            <i id="map_killzonessidebar_killzone_has_killzone_icon"></i>
        `;
        const killZoneA = makeFakeKillZone(5, true);
        const workBench = makeWorkBench({5: killZoneA});
        workBench.killZone = killZoneA;

        workBench._initKillAreaButton();
        // Simulate the real bootstrap.Button behavior: toggling flips the 'active' class.
        document.getElementById('map_killzonessidebar_killzone_has_killzone').classList.add('active');
        workBench._initKillAreaButton();

        expect(toggleSpy).toHaveBeenCalledTimes(1);
    });
});
