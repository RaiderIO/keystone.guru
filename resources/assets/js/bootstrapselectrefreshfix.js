/**
 * bootstrap-select 1.14.0-beta3's buildData() appends to selectpicker.main.data without ever
 * clearing it, so every .selectpicker('refresh') duplicates all options in the menu and in the
 * selected-count label. Reset the built data first so refresh rebuilds it from the <select>'s
 * current DOM.
 *
 * Only applies to DOM-sourced pickers; with options.source.data the data is not rebuilt on
 * refresh, so resetting it would empty the menu instead.
 *
 * @param {Object} Selectpicker The bootstrap-select constructor ($.fn.selectpicker.Constructor).
 */
function applyBootstrapSelectRefreshFix(Selectpicker) {
    const originalRefresh = Selectpicker.prototype.refresh;
    Selectpicker.prototype.refresh = function () {
        if (!this.options.source.data) {
            this.selectpicker.main.data = [];
            this.selectpicker.search.data = [];
        }
        return originalRefresh.call(this);
    };
}

// Guarded export so vitest can require this file; the browser bundle applies it via bootstrap.js.
if (typeof module !== 'undefined' && typeof module.exports !== 'undefined') {
    module.exports = {applyBootstrapSelectRefreshFix};
}
