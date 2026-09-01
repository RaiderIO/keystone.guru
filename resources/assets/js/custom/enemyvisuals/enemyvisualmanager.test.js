// #4414: zoom used to fire one requestAnimationFrame per enemy directly from
// _onZoomLevelChanged (551 of them landed in a single 116.7ms frame on the Black Temple
// facade, since rAF callbacks queued within the same task all run in the next frame). The fix
// batches enemies into a RafWorkQueue (resources/assets/js/custom/rafworkqueue.js) instead, which
// drains them over one or more time-budgeted rAF frames. The queue's own mechanics (budget,
// rescheduling, dedup-by-key) are covered in isolation by rafworkqueue.test.js - these tests only
// cover EnemyVisualManager's integration with it: that tasks are enqueued/deduped keyed by enemy,
// cancelled when an enemy is removed, and that a queued task actually calls buildVisual() vs
// refreshSize() as expected.
//
// EnemyVisualManager is a global-script class with a heavy constructor (wires up mapObjectGroup
// listeners, leaflet event handlers, etc.), so tests build a bare `Object.create(prototype)`
// instance instead - the same recipe used by dungeonmap.test.js - and seed only the fields the
// method under test reads.

global.Signalable = class Signalable {
};

const {RafWorkQueue} = require('../rafworkqueue');
global.RafWorkQueue = RafWorkQueue;

const EnemyVisualManager = require('./enemyvisualmanager');

function createManager() {
    const manager = Object.create(EnemyVisualManager.prototype);
    manager._visualRefreshQueue = new RafWorkQueue({
        run: manager._runVisualRefreshTask.bind(manager),
        frameBudgetMs: 8,
    });

    return manager;
}

/** Controllable requestAnimationFrame queue, mirroring dungeonmap.test.js's createGate(). */
function stubRaf() {
    const rafCallbacks = [];
    window.requestAnimationFrame = vi.fn((callback) => {
        rafCallbacks.push(callback);

        return rafCallbacks.length;
    });

    return rafCallbacks;
}

function makeEnemy({build = false} = {}) {
    return {
        visual: {
            buildVisual: vi.fn(),
            refreshSize: vi.fn(),
        },
        _build: build,
    };
}

describe('EnemyVisualManager._enqueueVisualRefreshTasks', () => {
    it('enqueueVisualRefreshTasks_givenMultipleTasks_schedulesExactlyOneRaf', () => {
        const manager = createManager();
        const rafCallbacks = stubRaf();
        const tasks = [
            {enemy: makeEnemy(), build: false},
            {enemy: makeEnemy(), build: false},
            {enemy: makeEnemy(), build: true},
        ];

        manager._enqueueVisualRefreshTasks(tasks);

        expect(window.requestAnimationFrame).toHaveBeenCalledTimes(1);
        expect(manager._visualRefreshQueue.length).toBe(3);
        expect(rafCallbacks).toHaveLength(1);
    });

    it('enqueueVisualRefreshTasks_givenAlreadyPendingFrame_doesNotScheduleAnotherRaf', () => {
        const manager = createManager();
        stubRaf();

        manager._enqueueVisualRefreshTasks([{enemy: makeEnemy(), build: false}]);
        manager._enqueueVisualRefreshTasks([{enemy: makeEnemy(), build: false}]);

        expect(window.requestAnimationFrame).toHaveBeenCalledTimes(1);
        expect(manager._visualRefreshQueue.length).toBe(2);
    });

    it('enqueueVisualRefreshTasks_givenNoTasks_doesNotScheduleRaf', () => {
        const manager = createManager();
        stubRaf();

        manager._enqueueVisualRefreshTasks([]);

        expect(window.requestAnimationFrame).not.toHaveBeenCalled();
    });

    it('enqueueVisualRefreshTasks_givenEnemyAlreadyQueued_updatesExistingTaskInPlaceInsteadOfDuplicating', () => {
        const manager = createManager();
        stubRaf();
        const enemy = makeEnemy();

        manager._enqueueVisualRefreshTasks([{enemy, build: false}]);
        manager._enqueueVisualRefreshTasks([{enemy, build: false}]);

        expect(manager._visualRefreshQueue.length).toBe(1);
    });

    it('enqueueVisualRefreshTasks_givenEnemyAlreadyQueuedForRefresh_upgradesExistingTaskToBuildOnNewBuildTask', () => {
        const manager = createManager();
        stubRaf();
        const enemy = makeEnemy();

        manager._enqueueVisualRefreshTasks([{enemy, build: false}]);
        manager._enqueueVisualRefreshTasks([{enemy, build: true}]);
        window.performance.now = vi.fn(() => 0);
        manager._visualRefreshQueue._processQueue();

        expect(enemy.visual.buildVisual).toHaveBeenCalledTimes(1);
        expect(enemy.visual.refreshSize).not.toHaveBeenCalled();
    });
});

describe('EnemyVisualManager._runVisualRefreshTask', () => {
    it('runVisualRefreshTask_givenTaskBuildFalse_callsRefreshSizeNotBuildVisual', () => {
        const manager = createManager();
        const enemy = makeEnemy();

        manager._runVisualRefreshTask({enemy, build: false});

        expect(enemy.visual.refreshSize).toHaveBeenCalledTimes(1);
        expect(enemy.visual.buildVisual).not.toHaveBeenCalled();
    });

    it('runVisualRefreshTask_givenTaskBuildTrue_callsBuildVisualNotRefreshSize', () => {
        const manager = createManager();
        const enemy = makeEnemy();

        manager._runVisualRefreshTask({enemy, build: true});

        expect(enemy.visual.buildVisual).toHaveBeenCalledTimes(1);
        expect(enemy.visual.refreshSize).not.toHaveBeenCalled();
    });
});

describe('EnemyVisualManager visual refresh queue integration', () => {
    it('enqueueThenProcess_givenQueueExceedingTimeBudget_stopsAndReschedulesRemainder', () => {
        const manager = createManager();
        const rafCallbacks = stubRaf();
        const first = makeEnemy();
        const second = makeEnemy();

        manager._enqueueVisualRefreshTasks([{enemy: first, build: false}, {enemy: second, build: false}]);
        // _enqueueVisualRefreshTasks() above already scheduled the frame this call simulates
        // running; only assert on what draining the queue itself (re)schedules.
        window.requestAnimationFrame.mockClear();
        rafCallbacks.length = 0;

        // The frame budget is 8ms. The first call computes the deadline (0 + 8 = 8), the second
        // is the loop's pre-task-one check (0, still under budget), and every call after that
        // returns 9 (past the deadline) so it stops right after the first task.
        let callCount = 0;
        window.performance.now = vi.fn(() => (++callCount <= 2 ? 0 : 9));

        manager._visualRefreshQueue._processQueue();

        expect(first.visual.refreshSize).toHaveBeenCalledTimes(1);
        expect(second.visual.refreshSize).not.toHaveBeenCalled();
        expect(manager._visualRefreshQueue.length).toBe(1);
        expect(window.requestAnimationFrame).toHaveBeenCalledTimes(1);

        // Draining the rest happens on the next scheduled frame.
        rafCallbacks[0](20);

        expect(second.visual.refreshSize).toHaveBeenCalledTimes(1);
        expect(manager._visualRefreshQueue.length).toBe(0);
    });

    it('objectDeletedHandler_givenEnemyQueuedForRefresh_cancelsItsQueuedTask', () => {
        const manager = createManager();
        stubRaf();
        const enemy = makeEnemy();
        manager._allEnemies = [enemy];
        manager._visibleEnemies = [];
        manager._hoveredEnemy = null;
        manager._enqueueVisualRefreshTasks([{enemy, build: false}]);

        expect(manager._visualRefreshQueue.length).toBe(1);

        // Mirrors the object:deleted handler registered in the constructor.
        manager._visualRefreshQueue.cancel(enemy);
        window.performance.now = vi.fn(() => 0);
        manager._visualRefreshQueue._processQueue();

        expect(manager._visualRefreshQueue.length).toBe(0);
        expect(enemy.visual.refreshSize).not.toHaveBeenCalled();
    });
});
