---
name: admin-batch-page
description: >
  Use when adding a new admin tools page that runs a long operation as chunked AJAX
  requests with a progress bar, start/pause/stop controls, elapsed timer, and log output.
  Examples: pruning database columns by criteria, backfilling data, bulk-updating rows.
---

# Admin Batch Page

A skill for creating admin tools pages that execute a long-running operation as a series
of AJAX batch requests. The user sees a progress bar, elapsed timer, activity log, and
start/pause/stop buttons. No page reload is needed; work continues until the server
returns `remaining: 0`.

## How it works

1. The user configures options on the page (e.g. checks season checkboxes) and clicks Start.
2. The JS subclass (extends `InlineCodeAjaxBatchProcessor`) sends a POST to the batch endpoint.
3. The PHP endpoint processes one chunk (e.g. 500 rows) and returns `{ pruned, remaining }`.
4. The JS updates the progress bar and calls itself again until `remaining === 0`.
5. Pause/Stop interrupt the loop; Resume restarts it with the same parameters.

## File layout

```
app/Http/Controllers/AdminTools/AdminTools{Name}Controller.php
app/Http/Requests/AdminTools{Name}BatchRequest.php
resources/views/admin/tools/{group}/{slug}.blade.php
resources/assets/js/custom/inline/admin/tools/{group}/{slug}.js
routes/web.php                         ← GET + POST batch routes
routes/breadcrumbs.php                 ← breadcrumb definition
lang/en_US/view_admin.php              ← view strings
lang/en_US/breadcrumbs.php             ← breadcrumb label
```

---

## 1 — PHP Controller

Two public methods only: a fast `index()` and a JSON `batch()`.

```php
namespace App\Http\Controllers\AdminTools;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminTools{Name}BatchRequest;
use App\Models\SomeModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminTools{Name}Controller extends Controller
{
    public function index(): View
    {
        // Keep this query light — never touch large text/blob columns here.
        // Use covering indexes (e.g. filter/group on indexed VARCHAR columns only).
        $stats = DB::table('some_table')
            ->select([DB::raw('some_column'), DB::raw('COUNT(*) AS total')])
            ->groupBy('some_column')
            ->orderByDesc('some_column')
            ->get();

        return view('admin.tools.{group}.{slug}', compact('stats'));
    }

    public function batch(AdminTools{Name}BatchRequest $request): JsonResponse
    {
        $batchSize = 500;
        // Pull parameters from the validated request
        $param = $request->validated('param');

        $ids = SomeModel::query()
            ->where('needs_processing', true)
            ->where('some_column', $param)
            ->orderByDesc('id')   // process newest first → test rows are always in batch 1
            ->limit($batchSize)
            ->pluck('id');

        $processed = 0;
        if ($ids->isNotEmpty()) {
            $processed = SomeModel::query()
                ->whereIn('id', $ids)
                ->update(['some_column' => '']);
        }

        $remaining = SomeModel::query()
            ->where('needs_processing', true)
            ->where('some_column', $param)
            ->count();

        return response()->json([
            'processed' => $processed,
            'remaining' => $remaining,
        ]);
    }
}
```

**Important:** `index()` must not read large blob/text columns — even a `COUNT(*)` that
triggers a full row scan on a 2 GB table causes a gateway timeout. Use covering index
queries (only reference indexed columns).

---

## 2 — Form Request

```php
namespace App\Http\Requests;

use App\Models\Laratrust\Role;
use Auth;
use Illuminate\Foundation\Http\FormRequest;

class AdminTools{Name}BatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::user()->hasRole(Role::ROLE_ADMIN);
    }

    public function rules(): array
    {
        return [
            'param'   => ['required', 'string', 'max:255'],
            // add more as needed
        ];
    }
}
```

---

## 3 — Routes

Add inside the existing `role:admin` middleware group in `routes/web.php`:

```php
use App\Http\Controllers\AdminTools\AdminTools{Name}Controller;

// inside Route::prefix('admin/tools')->group(...)
Route::get('{group}/{slug}',       new AdminTools{Name}Controller()->index(...))->name('admin.tools.{group}.{slug}');
Route::post('{group}/{slug}/batch', new AdminTools{Name}Controller()->batch(...))->name('admin.tools.{group}.{slug}.batch');
```

---

## 4 — Breadcrumb

`routes/breadcrumbs.php`:
```php
Breadcrumbs::for('admin.tools.{group}.{slug}', static function (Generator $trail) {
    $trail->parent('admin.tools.list');
    $trail->push(__('breadcrumbs.home.admin.tools.{name_key}'), route('admin.tools.{group}.{slug}'));
});
```

`lang/en_US/breadcrumbs.php` (inside the `tools` array):
```php
'{name_key}' => 'My batch operation',
```

---

## 5 — Blade view

Reference: `resources/views/admin/tools/combatlog/rundata.blade.php`

```blade
@extends('layouts.sitepage', ['showAds' => false, 'title' => __('view_admin.tools.{group}.{slug}.title')])

@section('header-title', __('view_admin.tools.{group}.{slug}.header'))

@section('content')
    <p class="text-muted">{{ __('view_admin.tools.{group}.{slug}.description') }}</p>

    {{-- Config UI: checkboxes, selects, or other inputs the user adjusts before starting --}}
    @foreach($stats as $stat)
        <label>
            <input type="checkbox" class="my-option-checkbox" value="{{ $stat->some_column }}">
            {{ $stat->some_column }} ({{ number_format($stat->total) }} rows)
        </label>
    @endforeach

    {{-- Warning --}}
    <div class="alert alert-warning mt-3">
        <i class="fas fa-exclamation-triangle"></i>
        {{ __('view_admin.tools.{group}.{slug}.warning') }}
    </div>

    {{-- Progress bar --}}
    <div class="mb-2 mt-3">
        <div class="progress mb-1">
            <div id="batch_progress_bar"
                 class="progress-bar progress-bar-striped progress-bar-animated"
                 role="progressbar" style="width: 0"
                 aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
        </div>
        <div class="d-flex justify-content-between">
            <small class="text-muted" id="batch_progress_label">–</small>
            <small class="text-muted">
                Remaining: <span id="batch_remaining">–</span>
                &nbsp;&nbsp;Elapsed: <span id="batch_timer">00:00:00</span>
            </small>
        </div>
    </div>

    {{-- Buttons --}}
    <div class="mb-3" style="display: flex; gap: 8px;">
        <button id="batch_start" class="btn btn-danger">
            <i class="fas fa-play"></i> Start
        </button>
        <button id="batch_pause" class="btn btn-warning d-none">
            <i class="fas fa-pause"></i> Pause
        </button>
        <button id="batch_resume" class="btn btn-success d-none">
            <i class="fas fa-play"></i> Resume
        </button>
        <button id="batch_stop" class="btn btn-secondary d-none">
            <i class="fas fa-stop"></i> Stop
        </button>
    </div>

    {{-- Log --}}
    <pre id="batch_log"
         class="bg-dark text-light p-3 rounded"
         style="height: 300px; overflow-y: scroll; white-space: pre-wrap; word-break: break-all;"></pre>
@endsection

@include('common.general.inline', ['path' => 'admin/tools/{group}/{slug}', 'options' => [
    'batchUrl'              => route('admin.tools.{group}.{slug}.batch'),
    'optionSelector'        => '.my-option-checkbox:checked',
    'progressBarSelector'   => '#batch_progress_bar',
    'progressLabelSelector' => '#batch_progress_label',
    'logSelector'           => '#batch_log',
    'startBtnSelector'      => '#batch_start',
    'pauseBtnSelector'      => '#batch_pause',
    'resumeBtnSelector'     => '#batch_resume',   // omit this key to hide Resume
    'stopBtnSelector'       => '#batch_stop',
    'timerSelector'         => '#batch_timer',
    'remainingCountSelector'=> '#batch_remaining',
    // 'etaSelector'        => '#batch_eta',       // add this + a <span> to show ETA
]])
```

**Naming convention:** The `path` passed to `common.general.inline` determines the JS
class name via `InlineManager`. `admin/tools/{group}/{slug}` → class
`AdminTools{Group}{Slug}`. The JS file must live at
`resources/assets/js/custom/inline/admin/tools/{group}/{slug}.js` and the class name
must match exactly (PascalCase, no separators).

---

## 6 — JS subclass

The class name is derived from the blade path: each `/`-separated segment is uppercased
and joined. `admin/tools/combatlog/rundata` → `AdminToolsCombatlogRundata`.

Reference: `resources/assets/js/custom/inline/admin/tools/combatlog/rundata.js`

```js
/**
 * @typedef {Object} AdminTools{Group}{Slug}Options
 * @property {string} batchUrl
 * @property {string} optionSelector
 * @property {string} progressBarSelector
 * @property {string} progressLabelSelector
 * @property {string} logSelector
 * @property {string} startBtnSelector
 * @property {string} pauseBtnSelector
 * @property {string} resumeBtnSelector   // optional
 * @property {string} stopBtnSelector
 * @property {string} timerSelector
 * @property {string} remainingCountSelector
 */

/**
 * @property {AdminTools{Group}{Slug}Options} options
 */
class AdminTools{Group}{Slug} extends InlineCodeAjaxBatchProcessor {

    activate() {
        this._processedSoFar = 0;
        this._totalRows      = 0;
        super.activate();
    }

    _start() {
        let params = this._getParams();
        if (!params) return; // validation failed, already logged

        this._state          = 'running';
        this._processedSoFar = 0;
        this._totalRows      = 0;
        this._setButtonState('running');
        this._startTimerSegment();
        this._appendLog('Starting...\n');
        this._runBatch(params);
    }

    _resume() {
        let params = this._getParams();
        super._resume();
        this._runBatch(params);
    }

    _complete() {
        this._pauseTimerSegment();
        this._setProgress(100, this._processedSoFar.toLocaleString() + ' processed');
        this._setButtonState('completed');
        this._appendLog('\nDone.');
    }

    /** @returns {Object|null} validated params, or null if invalid */
    _getParams() {
        let selected = $(this.options.optionSelector).map(function () {
            return $(this).val();
        }).get();

        if (selected.length === 0) {
            this._appendLog('Nothing selected — choose at least one option.\n');
            return null;
        }

        return {param: selected[0]}; // adjust shape to match your FormRequest rules
    }

    /**
     * @param {Object} params
     * @private
     */
    _runBatch(params) {
        if (this._state !== 'running') return;

        let self = this;

        $.ajax({
            type: 'POST',
            url: this.options.batchUrl,
            dataType: 'json',
            data: params,
            success: function (response) {
                self._processedSoFar += response.processed;

                let remaining = response.remaining;

                // Learn the total from the first response.
                if (self._totalRows === 0) {
                    self._totalRows = self._processedSoFar + remaining;
                }

                let percent = self._totalRows > 0
                    ? Math.min(100, Math.round(((self._totalRows - remaining) / self._totalRows) * 100))
                    : 100;

                self._setProgress(percent, self._processedSoFar.toLocaleString() + ' processed');
                self._setRemainingCount(remaining);
                self._appendLog('Processed ' + response.processed + ' — ' + remaining.toLocaleString() + ' remaining.\n');

                if (remaining === 0) {
                    self._complete();
                } else {
                    self._runBatch(params);
                }
            },
            error: function (xhr) {
                let msg = xhr.responseJSON ? (xhr.responseJSON.message || xhr.responseText) : xhr.responseText;
                self._onError(msg);
            }
        });
    }
}
```

### Base class API (`InlineCodeAjaxBatchProcessor`)

| Method | Override? | Purpose |
|---|---|---|
| `activate()` | Call `super.activate()` after own init | Binds buttons, initialises state |
| `_start()` | **Required** | Called on Start click |
| `_resume()` | Optional — call `super._resume()` first | Called on Resume click |
| `_complete()` | Optional | Called when work is done |
| `_computeEtaSeconds(elapsedMs)` | Optional | Return seconds for ETA display or `null` |
| `_pause()` | No | Pauses timer, updates buttons/log |
| `_stop()` | No | Stops run, updates buttons/log |
| `_onError(message)` | No | Auto-pauses with error in log |
| `_setButtonState(state)` | No | Handles all button visibility |
| `_setProgress(percent, label)` | No | Updates progress bar + label |
| `_setRemainingCount(n)` | No | Updates `remainingCountSelector` display |
| `_appendLog(text)` | No | Appends to log without O(n²) DOM thrash |
| `_startTimerSegment()` | No | Starts elapsed-time tracking |
| `_pauseTimerSegment()` | No | Pauses elapsed-time tracking |

**Standard options** every subclass blade must pass:
`progressBarSelector`, `progressLabelSelector`, `logSelector`,
`startBtnSelector`, `pauseBtnSelector`, `stopBtnSelector`, `timerSelector`

**Optional options:**
- `resumeBtnSelector` — include to wire up a Resume button
- `etaSelector` — include to display ETA (requires `_computeEtaSeconds` override)
- `remainingCountSelector` — include to display a live remaining-count number

---

## 7 — Language strings

`lang/en_US/view_admin.php` (inside the `tools.{group}` array):

```php
'{slug}' => [
    'title'       => 'My batch page',
    'header'      => 'My batch page',
    'description' => 'Select options then click Start.',
    'warning'     => 'This operation is irreversible.',
    'start'       => 'Start',
    'pause'       => 'Pause',
    'resume'      => 'Resume',
    'stop'        => 'Stop',
    'remaining'   => 'Remaining',
    'elapsed'     => 'Elapsed',
],
```

Link it from `resources/views/admin/tools/list.blade.php` inside the relevant card:

```blade
<li class="list-group-item">
    <a href="{{ route('admin.tools.{group}.{slug}') }}">{{ __('view_admin.tools.list.{key}') }}</a>
    <small class="text-muted d-block">{{ __('view_admin.tools.list.{key}_description') }}</small>
</li>
```

---

## 8 — Checklist

1. Controller `index()` uses a covering-index query — no blob/text columns touched
2. Controller `batch()` returns `{ processed, remaining }` JSON
3. Batch endpoint uses `->orderByDesc('id')` so newest rows (test rows) are in batch 1
4. JS class name matches blade path exactly (PascalCase, all segments joined)
5. Breadcrumb defined for the GET route name
6. Linked from `admin/tools/list.blade.php`
7. Run `docker compose exec -T app composer run fix`
8. Run `docker compose exec -T app composer run analyse`
9. Write a `#[SlowTest]` PHPUnit test — the test DB has real data so it runs slowly
10. Stage all new/modified files with `git add`
