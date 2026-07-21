// ---------------------------------------------------------------------------
// These files are concatenated into a bundle in the browser, so they reference
// collaborators as bare globals. The inlinemanager.js uses `eval(className)` to
// instantiate classes dynamically from the bladePath. To exercise InlineManager
// in isolation:
//
//   1. Require inlinecode.js first; its guarded export makes InlineCode available,
//      and we also put it on globalThis so inlinemanager.js can reference it as a
//      bare global (as it does in the browser bundle).
//   2. Require inlinemanager.js; its guarded export makes InlineManager available.
//   3. Define minimal stub classes on globalThis for each bladePath used in tests,
//      so that eval(className) inside init() can find them.
// ---------------------------------------------------------------------------

const {InlineCode}    = require('./inlinecode');
globalThis.InlineCode = InlineCode;

const {InlineManager} = require('./inlinemanager');

// Stub classes for bladePaths used across tests.
// bladePath -> className: split by '/', capitalize first letter of each segment, join.
// e.g. 'some/page' -> 'SomePage', 'dungeonroute/table' -> 'DungeonrouteTable'
globalThis.SomePage          = class SomePage          extends InlineCode {};
globalThis.ParentPage        = class ParentPage        extends InlineCode {};
globalThis.ChildPage         = class ChildPage         extends InlineCode {};
globalThis.DungeonrouteTable = class DungeonrouteTable extends InlineCode {};
globalThis.ProfileView       = class ProfileView       extends InlineCode {};
globalThis.SourcePage        = class SourcePage        extends InlineCode {};
globalThis.FirstChild        = class FirstChild        extends InlineCode {};
globalThis.SecondChild       = class SecondChild       extends InlineCode {};

beforeEach(() => {
    vi.spyOn(console, 'log').mockImplementation(() => {});
    vi.spyOn(console, 'warn').mockImplementation(() => {});
    vi.spyOn(console, 'error').mockImplementation(() => {});
});

afterEach(() => {
    vi.restoreAllMocks();
});

// ---------------------------------------------------------------------------

describe('InlineManager.init', () => {
    it('init_givenValidBladePath_returnsInlineCodeInstance', () => {
        const manager = new InlineManager();

        const result = manager.init('id1', 'some/page', {});

        expect(result).toBeInstanceOf(InlineCode);
    });

    it('init_givenPathDependency_registersDependencyMapping', () => {
        const manager = new InlineManager();

        manager.init('id-parent', 'parent/page', {});
        manager.init('id-child', 'child/page', {dependencies: ['parent/page']});

        // Verify that the dependency mapping was recorded: parent/page -> [child/page]
        expect(manager._dependencies['parent/page']).toContain('child/page');
    });
});

// ---------------------------------------------------------------------------

describe('InlineManager.getInlineCode', () => {
    it('getInlineCode_givenBladePath_returnsSingleInstance', () => {
        const manager = new InlineManager();
        manager.init('id1', 'some/page', {});

        const result = manager.getInlineCode('some/page');

        expect(result).toBeInstanceOf(InlineCode);
        expect(Array.isArray(result)).toBe(false);
    });

    it('getInlineCode_givenBladePathWithMultipleInstances_returnsArray', () => {
        const manager = new InlineManager();
        manager.init('id1', 'some/page', {});
        manager.init('id2', 'some/page', {});

        const result = manager.getInlineCode('some/page');

        expect(Array.isArray(result)).toBe(true);
        expect(result).toHaveLength(2);
    });

    it('getInlineCode_givenUnknownBladePath_returnsEmptyArray', () => {
        const manager = new InlineManager();

        const result = manager.getInlineCode('unknown/path');

        expect(result).toEqual([]);
    });
});

// ---------------------------------------------------------------------------

describe('InlineManager.getInlineCodeById', () => {
    it('getInlineCodeById_givenKnownId_returnsInstance', () => {
        const manager = new InlineManager();
        manager.init('abc123', 'some/page', {});

        const result = manager.getInlineCodeById('abc123');

        expect(result).toBeInstanceOf(InlineCode);
        expect(result.id).toBe('abc123');
    });

    it('getInlineCodeById_givenUnknownId_returnsUndefined', () => {
        const manager = new InlineManager();

        const result = manager.getInlineCodeById('nonexistent');

        expect(result).toBeUndefined();
    });
});

// ---------------------------------------------------------------------------

describe('InlineManager.activate', () => {
    it('activate_givenNoDependencies_activatesCode', () => {
        const manager = new InlineManager();
        manager.init('id1', 'some/page', {});

        manager.activate('id1');

        expect(manager.getInlineCodeById('id1').isActivated()).toBe(true);
    });

    it('activate_givenUnknownId_doesNotThrow', () => {
        const manager = new InlineManager();

        expect(() => manager.activate('nonexistent')).not.toThrow();
    });

    it('activate_givenPathDependencyNotYetActivated_doesNotActivateChild', () => {
        const manager = new InlineManager();
        manager.init('id-parent', 'parent/page', {});
        manager.init('id-child', 'child/page', {dependencies: ['parent/page']});

        // Attempt to activate child before the parent is activated
        manager.activate('id-child');

        expect(manager.getInlineCodeById('id-child').isActivated()).toBe(false);
    });

    it('activate_givenPathDependencyActivated_cascadesActivationToChild', () => {
        // Regression test: before the fix in inlinemanager.js, the cascade passed
        // the child's bladePath to activate() instead of its UUID ID, causing
        // getInlineCodeById(bladePath) to return undefined and the child to never activate.
        const manager = new InlineManager();
        manager.init('id-table', 'dungeonroute/table', {});
        manager.init('id-view', 'profile/view', {dependencies: ['dungeonroute/table']});

        manager.activate('id-table');

        expect(manager.getInlineCodeById('id-table').isActivated()).toBe(true);
        expect(manager.getInlineCodeById('id-view').isActivated()).toBe(true);
    });

    it('activate_givenIdDependencyNotYetActivated_doesNotActivateChild', () => {
        const manager = new InlineManager();
        manager.init('id-parent', 'parent/page', {});
        manager.init('id-child', 'child/page', {dependenciesById: ['id-parent']});

        // Attempt to activate child before its ID dependency is met
        manager.activate('id-child');

        expect(manager.getInlineCodeById('id-child').isActivated()).toBe(false);
    });

    it('activate_givenIdDependencyActivated_cascadesActivationToChildById', () => {
        const manager = new InlineManager();
        manager.init('id-parent', 'parent/page', {});
        manager.init('id-child', 'child/page', {dependenciesById: ['id-parent']});

        manager.activate('id-parent');

        expect(manager.getInlineCodeById('id-parent').isActivated()).toBe(true);
        expect(manager.getInlineCodeById('id-child').isActivated()).toBe(true);
    });

    it('activate_givenMultipleChildrenWaitingOnSameParent_activatesAllChildren', () => {
        const manager = new InlineManager();
        manager.init('id-source', 'source/page', {});
        manager.init('id-first',  'first/child',  {dependencies: ['source/page']});
        manager.init('id-second', 'second/child', {dependencies: ['source/page']});

        manager.activate('id-source');

        expect(manager.getInlineCodeById('id-source').isActivated()).toBe(true);
        expect(manager.getInlineCodeById('id-first').isActivated()).toBe(true);
        expect(manager.getInlineCodeById('id-second').isActivated()).toBe(true);
    });
});
