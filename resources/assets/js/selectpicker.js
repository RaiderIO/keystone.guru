// ---------------------------------------------------------------------------
// Tom Select backing for the project's `.selectpicker` convention (#3420).
//
// Blades and handlebars templates keep the bootstrap-select-era markup — a
// `<select class="selectpicker">` with data attributes — and this module
// interprets those attributes when driving Tom Select:
// - data-live-search="true"          -> search input inside the dropdown
// - data-content="<html>" on options -> HTML rendering of options/items
// - title / data-none-selected-text  -> placeholder
// - data-width                       -> explicit width on the wrapper
// - multiple                         -> tags UI with remove buttons
//
// refreshSelectPickers() initializes any new `.selectpicker` elements and
// syncs already-initialized ones with their underlying <select>. The option
// list is only rebuilt when the option set in the DOM actually changed:
// Tom Select moves selected <option> elements to the end of the <select> on
// every selection (updateOriginalInput), so blindly re-importing on each
// refresh would slowly scramble the dropdown order.
// ---------------------------------------------------------------------------

// The CJS dist exposes the constructor as the `default` export
const TomSelect = require('tom-select').default;

const FIELD_SEPARATOR = '\u001f';
const OPTION_SEPARATOR = '\u001e';

/**
 * Order-insensitive fingerprint of the select's options, mirroring Tom
 * Select's own DOM import rules (empty-value options are skipped unless
 * allowEmptyOption).
 *
 * @param {HTMLSelectElement} select
 * @param {boolean} allowEmptyOption
 * @returns {string}
 */
function getOptionSetFingerprint(select, allowEmptyOption) {
    const parts = [];

    for (const option of select.options) {
        if (option.value === '' && !allowEmptyOption) {
            continue;
        }

        const group = option.parentElement instanceof HTMLOptGroupElement ? option.parentElement.label : '';
        parts.push([option.value, option.text, option.dataset.content ?? '', option.disabled ? '1' : '0', group]
            .join(FIELD_SEPARATOR));
    }

    return parts.sort().join(OPTION_SEPARATOR);
}

/**
 * Translates the bootstrap-select-era markup of a `.selectpicker` element
 * into Tom Select settings.
 *
 * @param {HTMLSelectElement} select
 * @returns {Object}
 */
function getSelectPickerSettings(select) {
    const liveSearch = select.dataset.liveSearch === 'true';

    const plugins = [];
    if (liveSearch) {
        plugins.push('dropdown_input');
    }
    if (select.multiple) {
        plugins.push('remove_button');
    }

    const settings = {
        plugins: plugins,
        // Keep selected options in the dropdown (bootstrap-select parity) instead of Tom Select's
        // default of removing them: it's the only way to deselect once the count summary collapses
        // the removable tags, and initSelectPicker() makes clicking a selected option toggle it off.
        hideSelected: select.multiple ? false : undefined,
        // The default (50) truncates large lists such as the NPC selects
        maxOptions: null,
        allowEmptyOption: select.querySelector('option[value=""]') !== null,
        placeholder: select.getAttribute('title') ?? select.dataset.noneSelectedText ?? undefined,
        render: {
            // data-content is server-generated HTML (image/icon options), never user input
            option: (data, escape) => `<div>${data.content ?? escape(data.text)}</div>`,
            item: (data, escape) => `<div>${data.content ?? escape(data.text)}</div>`,
        },
    };

    if (!liveSearch) {
        // Without live search there is nothing to type - render a plain dropdown
        settings.controlInput = null;
    }

    return settings;
}

/**
 * Restores bootstrap-select's `data-selected-text-format="count > N"` behaviour for multi selects:
 * once more than N options are selected, the individual tags are replaced by a single "N <label>"
 * summary (from `data-count-selected-text`, with `{0}` as the count) so a large selection cannot
 * bloat the control's height - the routes filter defaults its whole attributes list to selected.
 * Below the threshold the normal tags are shown. The collapse is a class + data attribute the
 * stylesheet renders via `::after`; the updater is stored on the instance so refreshSelectPicker()
 * - which sets values silently, bypassing the `change` event - can re-run it.
 *
 * @param {TomSelect} instance
 * @param {HTMLSelectElement} select
 */
function setupSelectedCountSummary(instance, select) {
    const format = select.dataset.selectedTextFormat;
    if (!select.multiple || !format) {
        return;
    }

    const thresholdMatch = /count(?:\s*>\s*(\d+))?/.exec(format);
    if (thresholdMatch === null) {
        return;
    }
    const threshold = thresholdMatch[1] !== undefined ? parseInt(thresholdMatch[1], 10) : 0;
    const countTemplate = select.dataset.countSelectedText ?? '{0}';

    const update = () => {
        const collapsed = instance.items.length > threshold;
        instance.control.classList.toggle('ts-count-summary', collapsed);
        if (collapsed) {
            instance.control.dataset.countSummary = countTemplate.replace('{0}', instance.items.length);
        } else {
            delete instance.control.dataset.countSummary;
        }
    };

    instance.selectpickerUpdateCountSummary = update;
    instance.on('change', update);
    update();
}

/**
 * With `hideSelected: false`, a selected option stays in the dropdown but Tom Select only ever ADDS
 * on click (onOptionSelect -> addItem), so it can never be unselected there. Make a click on an
 * already-selected option toggle it OFF instead - the deselect path bootstrap-select had, and the
 * only way to unselect once the count summary hides the removable tags. Runs in the capture phase so
 * it pre-empts Tom Select's own bubble-phase click -> addItem for the deselect case only.
 *
 * @param {TomSelect} instance
 */
function setupToggleDeselect(instance) {
    instance.dropdown_content.addEventListener('click', (evt) => {
        const optionEl = evt.target.closest('[data-selectable]');
        if (optionEl === null || !instance.items.includes(optionEl.dataset.value)) {
            return;
        }
        instance.removeItem(optionEl.dataset.value);
        instance.refreshOptions(instance.isOpen);
        evt.preventDefault();
        evt.stopImmediatePropagation();
    }, true);
}

/**
 * @param {HTMLSelectElement} select
 * @returns {TomSelect}
 */
function initSelectPicker(select) {
    const instance = new TomSelect(select, getSelectPickerSettings(select));
    instance.selectpickerFingerprint = getOptionSetFingerprint(select, instance.settings.allowEmptyOption);

    if (select.dataset.width) {
        instance.wrapper.style.width = select.dataset.width;
    }

    // Leaflet popups (the map's enemy/object edit popups) call L.DomEvent.disableClickPropagation on
    // their content, which stops `mousedown` before it bubbles to document. Tom Select retains focus
    // via a document-level mousedown handler that preventDefaults; suppressed, the control focuses on
    // mousedown and the ensuing click toggles the just-opened dropdown shut - so a single click can't
    // open it (you'd have to hold and drag to select). Re-assert that focus retention on the wrapper,
    // which lives inside the popup and runs before Leaflet stops the event. Mirrors Tom Select's own
    // doc_mousedown: keep the caret-positioning exception for an open search input.
    if (select.closest('.leaflet-popup')) {
        instance.wrapper.addEventListener('mousedown', (evt) => {
            if (evt.target === instance.control_input && instance.isOpen) {
                evt.stopPropagation();
            } else {
                evt.preventDefault();
                evt.stopPropagation();
            }
        });
    }

    if (select.multiple) {
        setupToggleDeselect(instance);
    }
    setupSelectedCountSummary(instance, select);

    return instance;
}

/**
 * Syncs an initialized instance with its underlying <select>: value and
 * disabled state always, the option list only when it actually changed.
 *
 * @param {HTMLSelectElement} select
 */
function refreshSelectPicker(select) {
    const instance = select.tomselect;

    const fingerprint = getOptionSetFingerprint(select, instance.settings.allowEmptyOption);
    if (fingerprint !== instance.selectpickerFingerprint) {
        // The <select>'s contents were replaced - rebuild the option list from the DOM.
        // sync() only ever adds options (setupOptions -> addOptions), so clear everything
        // first. clear() rewrites the <select>'s selection (removeItem -> updateOriginalInput),
        // so capture it up front and restore it after the rebuild.
        const selectedValues = Array.from(select.selectedOptions, (option) => option.value);
        instance.clear(true);
        instance.clearOptions(() => false);
        instance.clearOptionGroups();
        instance.sync();
        instance.setValue(selectedValues, true);
        instance.selectpickerFingerprint = fingerprint;
    } else {
        // Same option set - still adopt external value/disabled changes ($select.val(), .prop('disabled'))
        instance.setValue(Array.from(select.selectedOptions, (option) => option.value), true);
        if (select.disabled) {
            instance.disable();
        } else {
            instance.enable();
        }
    }

    // setValue() above is silent (no change event), so refresh the count summary explicitly.
    instance.selectpickerUpdateCountSummary?.();
}

/**
 * Initializes all new `.selectpicker` selects on-screen and refreshes all existing ones
 **/
function refreshSelectPickers() {
    document.querySelectorAll('select.selectpicker').forEach((select) => {
        if (select.tomselect) {
            refreshSelectPicker(select);
        } else {
            initSelectPicker(select);
        }
    });
}

module.exports = {getOptionSetFingerprint, getSelectPickerSettings, initSelectPicker, refreshSelectPicker, refreshSelectPickers};
