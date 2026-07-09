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
 * @param {HTMLSelectElement} select
 * @returns {TomSelect}
 */
function initSelectPicker(select) {
    const instance = new TomSelect(select, getSelectPickerSettings(select));
    instance.selectpickerFingerprint = getOptionSetFingerprint(select, instance.settings.allowEmptyOption);

    if (select.dataset.width) {
        instance.wrapper.style.width = select.dataset.width;
    }

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
