// #4414: zoom used to fire one requestAnimationFrame per enemy directly from
// _onZoomLevelChanged (551 of them landed in a single 116.7ms frame on the Black Temple
// facade, since rAF callbacks queued within the same task all run in the next frame). These
// tests cover the replacement: enemies are queued and drained by a single self-rescheduling
// rAF loop that stops once it has spent its per-frame time budget.
//
// EnemyVisualManager is a global-script class with a heavy constructor (wires up mapObjectGroup
// listeners, leaflet event handlers, etc.), so tests build a bare `Object.create(prototype)`
// instance instead - the same recipe used by dungeonmap.test.js - and seed only the fields the
// method under test reads.

global.Signalable = class Signalable {
};

const EnemyVisualManager = require('./enemyvisualmanager');

function createManager() {
    const manager = Object.create(EnemyVisualManager.prototype);
    manager._visualRefreshQueue = [];
    manager._visualRefreshQueueRafHandle = null;

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
        expect(manager._visualRefreshQueue).toEqual(tasks);
        expect(rafCallbacks).toHaveLength(1);
    });

    it('enqueueVisualRefreshTasks_givenAlreadyPendingFrame_doesNotScheduleAnotherRaf', () => {
        const manager = createManager();
        stubRaf();

        manager._enqueueVisualRefreshTasks([{enemy: makeEnemy(), build: false}]);
        manager._enqueueVisualRefreshTasks([{enemy: makeEnemy(), build: false}]);

        expect(window.requestAnimationFrame).toHaveBeenCalledTimes(1);
        expect(manager._visualRefreshQueue).toHaveLength(2);
    });

    it('enqueueVisualRefreshTasks_givenNoTasks_doesNotScheduleRaf', () => {
        const manager = createManager();
        stubRaf();

        manager._enqueueVisualRefreshTasks([]);

        expect(window.requestAnimationFrame).not.toHaveBeenCalled();
    });
});

describe('EnemyVisualManager._processVisualRefreshQueue', () => {
    it('processVisualRefreshQueue_givenTaskBuildFalse_callsRefreshSizeNotBuildVisual', () => {
        const manager = createManager();
        stubRaf();
        const enemy = makeEnemy();
        manager._visualRefreshQueue = [{enemy, build: false}];
        window.performance.now = vi.fn(() => 0);

        manager._processVisualRefreshQueue(0);

        expect(enemy.visual.refreshSize).toHaveBeenCalledTimes(1);
        expect(enemy.visual.buildVisual).not.toHaveBeenCalled();
    });

    it('processVisualRefreshQueue_givenTaskBuildTrue_callsBuildVisualNotRefreshSize', () => {
        const manager = createManager();
        stubRaf();
        const enemy = makeEnemy();
        manager._visualRefreshQueue = [{enemy, build: true}];
        window.performance.now = vi.fn(() => 0);

        manager._processVisualRefreshQueue(0);

        expect(enemy.visual.buildVisual).toHaveBeenCalledTimes(1);
        expect(enemy.visual.refreshSize).not.toHaveBeenCalled();
    });

    it('processVisualRefreshQueue_givenQueueExceedingTimeBudget_stopsAndReschedulesRemainder', () => {
        const manager = createManager();
        const rafCallbacks = stubRaf();
        const first = makeEnemy();
        const second = makeEnemy();
        manager._visualRefreshQueue = [{enemy: first, build: false}, {enemy: second, build: false}];

        // The frame budget is 8ms (ENEMY_VISUAL_REFRESH_FRAME_BUDGET_MS); the loop checks
        // performance.now() before processing each task, so returning 0 on the first check and
        // 9 (past the deadline) on every check after makes it stop right after the first task.
        let callCount = 0;
        window.performance.now = vi.fn(() => (++callCount === 1 ? 0 : 9));

        manager._processVisualRefreshQueue(0);

        expect(first.visual.refreshSize).toHaveBeenCalledTimes(1);
        expect(second.visual.refreshSize).not.toHaveBeenCalled();
        expect(manager._visualRefreshQueue).toEqual([{enemy: second, build: false}]);
        expect(window.requestAnimationFrame).toHaveBeenCalledTimes(1);
        expect(manager._visualRefreshQueueRafHandle).not.toBeNull();

        // Draining the rest happens on the next scheduled frame.
        rafCallbacks[0](20);

        expect(second.visual.refreshSize).toHaveBeenCalledTimes(1);
        expect(manager._visualRefreshQueue).toEqual([]);
        expect(manager._visualRefreshQueueRafHandle).toBeNull();
    });

    it('processVisualRefreshQueue_givenQueueDrainedWithinBudget_doesNotRescheduleAnotherFrame', () => {
        const manager = createManager();
        stubRaf();
        const enemy = makeEnemy();
        manager._visualRefreshQueue = [{enemy, build: false}];
        window.performance.now = vi.fn(() => 0);

        manager._processVisualRefreshQueue(0);

        expect(manager._visualRefreshQueue).toEqual([]);
        expect(window.requestAnimationFrame).not.toHaveBeenCalled();
        expect(manager._visualRefreshQueueRafHandle).toBeNull();
    });
});
