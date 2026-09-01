// #4414 review follow-up: the time-budgeted rAF batching originally embedded in
// EnemyVisualManager was extracted into this generic class so it can be tested in isolation and
// reused anywhere else in the codebase that needs to smear a batch of recalculation over
// multiple frames.

const {RafWorkQueue} = require('./rafworkqueue');

/** Controllable requestAnimationFrame queue, mirroring enemyvisualmanager.test.js's stubRaf(). */
function stubRaf() {
    const rafCallbacks = [];
    window.requestAnimationFrame = vi.fn((callback) => {
        rafCallbacks.push(callback);

        return rafCallbacks.length;
    });

    return rafCallbacks;
}

describe('RafWorkQueue.enqueue', () => {
    it('enqueue_givenMultipleTasks_schedulesExactlyOneRaf', () => {
        stubRaf();
        const run = vi.fn();
        const queue = new RafWorkQueue({run});

        queue.enqueue('a', {value: 1});
        queue.enqueue('b', {value: 2});
        queue.enqueue('c', {value: 3});

        expect(window.requestAnimationFrame).toHaveBeenCalledTimes(1);
        expect(queue.length).toBe(3);
    });

    it('enqueue_givenAlreadyPendingFrame_doesNotScheduleAnotherRaf', () => {
        stubRaf();
        const queue = new RafWorkQueue({run: vi.fn()});

        queue.enqueue('a', {value: 1});
        queue.enqueue('b', {value: 2});

        expect(window.requestAnimationFrame).toHaveBeenCalledTimes(1);
        expect(queue.length).toBe(2);
    });

    it('enqueue_givenKeyAlreadyQueued_defaultMergeReplacesTaskInsteadOfDuplicating', () => {
        stubRaf();
        const queue = new RafWorkQueue({run: vi.fn()});

        queue.enqueue('a', {value: 1});
        queue.enqueue('a', {value: 2});

        expect(queue.length).toBe(1);
    });

    it('enqueue_givenKeyAlreadyQueuedWithCustomMerge_usesMergeResultInsteadOfIncomingTask', () => {
        stubRaf();
        const runCalls = [];
        const queue = new RafWorkQueue({run: (task) => runCalls.push(task)});
        const merge = (existingTask, incomingTask) => ({value: existingTask.value + incomingTask.value});

        queue.enqueue('a', {value: 1}, merge);
        queue.enqueue('a', {value: 2}, merge);
        window.performance.now = vi.fn(() => 0);
        queue._processQueue();

        expect(runCalls).toEqual([{value: 3}]);
    });
});

describe('RafWorkQueue.cancel', () => {
    it('cancel_givenQueuedKey_removesItWithoutRunningIt', () => {
        stubRaf();
        const run = vi.fn();
        const queue = new RafWorkQueue({run});

        queue.enqueue('a', {value: 1});
        queue.cancel('a');
        window.performance.now = vi.fn(() => 0);
        queue._processQueue();

        expect(queue.length).toBe(0);
        expect(run).not.toHaveBeenCalled();
    });

    it('cancel_givenUnknownKey_doesNothing', () => {
        stubRaf();
        const queue = new RafWorkQueue({run: vi.fn()});

        queue.enqueue('a', {value: 1});
        queue.cancel('does-not-exist');

        expect(queue.length).toBe(1);
    });
});

describe('RafWorkQueue._processQueue', () => {
    it('processQueue_givenTasksWithinBudget_runsAllOfThemAndDoesNotReschedule', () => {
        stubRaf();
        const runCalls = [];
        const queue = new RafWorkQueue({run: (task) => runCalls.push(task)});
        queue.enqueue('a', {value: 1});
        queue.enqueue('b', {value: 2});
        window.performance.now = vi.fn(() => 0);
        // enqueue() above already scheduled the frame this call simulates running; only assert
        // on what _processQueue() itself schedules.
        window.requestAnimationFrame.mockClear();

        queue._processQueue();

        expect(runCalls).toEqual([{value: 1}, {value: 2}]);
        expect(queue.length).toBe(0);
        expect(window.requestAnimationFrame).not.toHaveBeenCalled();
    });

    it('processQueue_givenQueueExceedingTimeBudget_stopsAndReschedulesRemainder', () => {
        const rafCallbacks = stubRaf();
        const runCalls = [];
        const queue = new RafWorkQueue({run: (task) => runCalls.push(task), frameBudgetMs: 8});
        queue.enqueue('a', {value: 1});
        queue.enqueue('b', {value: 2});
        window.requestAnimationFrame.mockClear();
        rafCallbacks.length = 0;

        // The frame budget is 8ms. The first call computes the deadline (0 + 8 = 8), the second
        // is the loop's pre-task-one check (0, still under budget), and every call after that
        // returns 9 (past the deadline) so it stops right after the first task.
        let callCount = 0;
        window.performance.now = vi.fn(() => (++callCount <= 2 ? 0 : 9));

        queue._processQueue();

        expect(runCalls).toEqual([{value: 1}]);
        expect(queue.length).toBe(1);
        expect(window.requestAnimationFrame).toHaveBeenCalledTimes(1);

        // Draining the rest happens on the next scheduled frame.
        rafCallbacks[0](20);

        expect(runCalls).toEqual([{value: 1}, {value: 2}]);
        expect(queue.length).toBe(0);
    });

    it('processQueue_givenCallbackStartedLateInTheFrame_stillProcessesAtLeastOneTask', () => {
        // If other work queued ahead of this callback in the same frame delayed it past the
        // frame's nominal start, a deadline computed from that stale start time would already be
        // in the past - the loop would process zero tasks and just reschedule forever. The
        // deadline must be measured from when this callback itself starts running.
        stubRaf();
        const runCalls = [];
        const queue = new RafWorkQueue({run: (task) => runCalls.push(task)});
        queue.enqueue('a', {value: 1});
        window.performance.now = vi.fn(() => 1000);

        queue._processQueue();

        expect(runCalls).toEqual([{value: 1}]);
        expect(queue.length).toBe(0);
    });

    it('processQueue_givenQueueDrainedWithinBudget_doesNotRescheduleAnotherFrame', () => {
        stubRaf();
        const queue = new RafWorkQueue({run: vi.fn()});
        queue.enqueue('a', {value: 1});
        window.performance.now = vi.fn(() => 0);
        window.requestAnimationFrame.mockClear();

        queue._processQueue();

        expect(queue.length).toBe(0);
        expect(window.requestAnimationFrame).not.toHaveBeenCalled();
    });
});
