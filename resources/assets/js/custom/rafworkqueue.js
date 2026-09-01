/**
 * A generic, reusable queue that spreads a batch of arbitrary tasks over one or more
 * time-budgeted requestAnimationFrame callbacks instead of firing one rAF callback per task
 * (which all run back to back in the same frame regardless of how many are queued).
 *
 * Extracted from EnemyVisualManager's zoom-refresh batching (#4414) so any other part of the
 * codebase that triggers a whole batch of expensive recalculation can smear it over multiple
 * frames the same way, without depending on enemy visuals.
 *
 * Callers supply:
 * - `run(task)`: how to execute a single queued task.
 * - a dedup key per `enqueue()` call, so a rapid run of triggering events (e.g. fast scroll
 *   wheel zoom) updates the already-queued task for that key in place instead of piling up one
 *   task per event - without this the queue could grow unboundedly faster than the time budget
 *   can drain it.
 */
class RafWorkQueue {

    /**
     * @param run {function(*): void} Called with a queued task's data when it's its turn to run.
     * @param frameBudgetMs {number} Time budget (ms) for a single frame of processing before
     *        re-queueing itself for the next frame. Chosen to leave headroom in a 16.7ms (60fps)
     *        frame for everything else the browser needs to do (layout, paint, input handling).
     */
    constructor({run, frameBudgetMs = 8}) {
        console.assert(typeof run === 'function', 'run should be a function', run);

        this._run = run;
        this._frameBudgetMs = frameBudgetMs;
        this._queue = [];
        // key -> the queued entry, so an already-queued key can be found/updated/cancelled in
        // O(1) instead of scanning the queue
        this._queuedKeys = new Map();
        this._rafHandle = null;
    }

    /**
     * Number of tasks currently queued (including one that may be mid-frame).
     * @returns {number}
     */
    get length() {
        return this._queue.length;
    }

    /**
     * Queues a task under `key`, scheduling a rAF callback to drain the queue if one isn't
     * already pending. If a task is already queued for `key`, `merge` decides what replaces it
     * instead of a second entry being added.
     * @param key {*} Dedup key. Whatever uniquely identifies the thing this task recalculates
     *        (an enemy, an id, ...).
     * @param task {*} Arbitrary task data, passed to `run()` verbatim.
     * @param merge {function(*, *): *} Given (existingTask, incomingTask), returns the task to
     *        keep queued for `key`. Defaults to replacing the existing task with the incoming one.
     */
    enqueue(key, task, merge = (existingTask, incomingTask) => incomingTask) {
        let existingEntry = this._queuedKeys.get(key);
        if (existingEntry) {
            existingEntry.task = merge(existingEntry.task, task);
        } else {
            let entry = {key: key, task: task};
            this._queue.push(entry);
            this._queuedKeys.set(key, entry);
        }

        if (this._queue.length > 0 && this._rafHandle === null) {
            this._rafHandle = window.requestAnimationFrame(this._processQueue.bind(this));
        }
    }

    /**
     * Removes any task queued for `key` without running it, e.g. when the thing it would have
     * recalculated no longer exists.
     * @param key {*}
     */
    cancel(key) {
        let existingEntry = this._queuedKeys.get(key);
        if (existingEntry) {
            let index = this._queue.indexOf(existingEntry);
            if (index > -1) {
                this._queue.splice(index, 1);
            }
            this._queuedKeys.delete(key);
        }
    }

    /**
     * Processes queued tasks until the per-frame time budget is spent, then re-queues itself for
     * the next frame if work remains. Measures the budget from when this callback actually
     * starts running rather than from the rAF frameStartTime argument - other work queued ahead
     * of it in the same frame can delay that start past the frame's nominal deadline, which would
     * otherwise make the loop process zero tasks and just reschedule, indefinitely, if that keeps
     * happening every frame.
     * @private
     */
    _processQueue() {
        let deadline = window.performance.now() + this._frameBudgetMs;

        while (this._queue.length > 0 && window.performance.now() < deadline) {
            let entry = this._queue.shift();
            this._queuedKeys.delete(entry.key);
            this._run(entry.task);
        }

        if (this._queue.length > 0) {
            this._rafHandle = window.requestAnimationFrame(this._processQueue.bind(this));
        } else {
            this._rafHandle = null;
        }
    }
}

// Guarded export for the test runner (Vitest). This is a no-op in the browser,
// where `module` is undefined, so it does not affect the concatenated bundle.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        RafWorkQueue,
    };
}
