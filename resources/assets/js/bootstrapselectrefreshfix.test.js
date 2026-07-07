// ---------------------------------------------------------------------------
// bootstrap-select 1.14.0-beta3 appends to selectpicker.main.data on every
// buildData() call, so each .selectpicker('refresh') duplicates all options.
// The first test documents that upstream bug (it doubles as a canary: when a
// future bootstrap-select release fixes it, the test fails and the patch in
// bootstrapselectrefreshfix.js can be removed). The remaining tests verify
// the patch. Note the tests must run in declaration order: applying the
// prototype patch is global and cannot be undone within the test run.
// ---------------------------------------------------------------------------

window.$ = window.jQuery = require('jquery');
window.bootstrap = require('bootstrap');
require('bootstrap-select');

const {applyBootstrapSelectRefreshFix} = require('./bootstrapselectrefreshfix');

const OPTION_COUNT = 3;

function createSelectpicker() {
    document.body.innerHTML = `
        <select class="selectpicker" multiple>
            <option value="1">One</option>
            <option value="2" selected>Two</option>
            <option value="3">Three</option>
        </select>`;
    const $select = $('.selectpicker');
    $select.selectpicker();

    return $select;
}

describe('applyBootstrapSelectRefreshFix', () => {
    test('refresh_givenUnpatchedSelectpicker_duplicatesOptionData', () => {
        const $select = createSelectpicker();
        expect($select.data('selectpicker').selectpicker.main.data.length).toBe(OPTION_COUNT);

        $select.selectpicker('refresh');
        $select.selectpicker('refresh');

        // Upstream bug: every refresh appends another copy of all options
        expect($select.data('selectpicker').selectpicker.main.data.length).toBe(OPTION_COUNT * 3);
    });

    test('refresh_givenPatchedSelectpicker_keepsOptionDataStable', () => {
        applyBootstrapSelectRefreshFix($.fn.selectpicker.Constructor);

        const $select = createSelectpicker();
        expect($select.data('selectpicker').selectpicker.main.data.length).toBe(OPTION_COUNT);

        $select.selectpicker('refresh');
        $select.selectpicker('refresh');

        expect($select.data('selectpicker').selectpicker.main.data.length).toBe(OPTION_COUNT);
    });

    test('refresh_givenPatchedSelectpickerAndAddedOption_reflectsNewOption', () => {
        const $select = createSelectpicker();
        $select.append('<option value="4">Four</option>');

        $select.selectpicker('refresh');

        expect($select.data('selectpicker').selectpicker.main.data.length).toBe(OPTION_COUNT + 1);
    });
});
