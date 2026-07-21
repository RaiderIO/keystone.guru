// ---------------------------------------------------------------------------
// Covers the Tom Select adapter behind the project's `.selectpicker`
// convention (selectpicker.js, #3420): initialization from the bootstrap-select
// era markup, and refreshSelectPickers() semantics that the inline scripts
// rely on (init new pickers, adopt external value/disabled changes, rebuild
// the option list on repopulation without duplicating or reordering options).
// ---------------------------------------------------------------------------

const {getOptionSetFingerprint, getSelectPickerSettings, refreshSelectPickers} = require('./selectpicker');

/**
 * @param {string} attributes
 * @param {string} options
 * @returns {HTMLSelectElement}
 */
function createSelect(attributes = '', options = `
        <option value="1">One</option>
        <option value="2" selected>Two</option>
        <option value="3">Three</option>`) {
    document.body.innerHTML = `<select class="form-control selectpicker" ${attributes}>${options}</select>`;

    return document.querySelector('select.selectpicker');
}

describe('refreshSelectPickers', () => {
    test('refreshSelectPickers_givenUninitializedSelect_initializesTomSelect', () => {
        const select = createSelect();

        refreshSelectPickers();

        expect(select.tomselect).toBeDefined();
        expect(Object.keys(select.tomselect.options)).toEqual(['1', '2', '3']);
        expect(select.tomselect.getValue()).toBe('2');
    });

    test('refreshSelectPickers_givenRepeatedCalls_doesNotDuplicateOptions', () => {
        const select = createSelect();

        refreshSelectPickers();
        refreshSelectPickers();
        refreshSelectPickers();

        expect(Object.keys(select.tomselect.options).length).toBe(3);
        expect(select.tomselect.getValue()).toBe('2');
    });

    test('refreshSelectPickers_givenRepopulatedOptions_rebuildsOptionList', () => {
        const select = createSelect();
        refreshSelectPickers();

        select.innerHTML = `
            <option value="4">Four</option>
            <option value="5" selected>Five</option>`;
        refreshSelectPickers();

        expect(Object.keys(select.tomselect.options).sort()).toEqual(['4', '5']);
        expect(select.tomselect.getValue()).toBe('5');
    });

    test('refreshSelectPickers_givenExternalValueChange_adoptsValue', () => {
        const select = createSelect();
        refreshSelectPickers();

        // Mimics the inline scripts doing $select.val(3) followed by refreshSelectPickers()
        select.value = '3';
        refreshSelectPickers();

        expect(select.tomselect.getValue()).toBe('3');
    });

    test('refreshSelectPickers_givenExternallyDisabledSelect_togglesInstanceDisabledState', () => {
        const select = createSelect();
        refreshSelectPickers();

        select.disabled = true;
        refreshSelectPickers();
        expect(select.tomselect.wrapper.classList.contains('disabled')).toBe(true);

        select.disabled = false;
        refreshSelectPickers();
        expect(select.tomselect.wrapper.classList.contains('disabled')).toBe(false);
    });

    test('refreshSelectPickers_givenUnchangedOptionSet_keepsOptionOrder', () => {
        const select = createSelect();
        refreshSelectPickers();
        const instance = select.tomselect;

        // Selecting moves the chosen <option> to the end of the underlying <select>
        // (Tom Select's updateOriginalInput); a refresh must not adopt that scrambled order
        instance.addItem('1');
        refreshSelectPickers();

        const orderedValues = Object.values(instance.options)
            .sort((a, b) => a.$order - b.$order)
            .map((option) => option.value);
        expect(orderedValues).toEqual(['1', '2', '3']);
        expect(instance.getValue()).toBe('1');
    });

    test('refreshSelectPickers_givenDataContentOption_exposesHtmlContent', () => {
        const select = createSelect('', `
            <option value="1" data-content="&lt;img src='icon.png'/&gt; One">One</option>
            <option value="2" selected>Two</option>`);

        refreshSelectPickers();

        expect(select.tomselect.options['1'].content).toBe(`<img src='icon.png'/> One`);
    });

    test('refreshSelectPickers_givenGroupedSingleSelectAfterSelection_keepsOptionsGrouped', () => {
        // The dungeon picker is a single select whose options are grouped into optgroups. Tom Select's
        // updateOriginalInput moves each selected <option> out of its <optgroup> to the top level of the
        // <select>; a fingerprint-triggered rebuild used to re-import that mangled DOM and permanently
        // strip the options of their grouping. A group-insensitive fingerprint keeps the groups intact.
        const select = createSelect('', `
            <optgroup label="Season 1"><option value="1">One</option><option value="2">Two</option></optgroup>
            <optgroup label="Season 2"><option value="3">Three</option></optgroup>`);
        refreshSelectPickers();
        const instance = select.tomselect;

        // Mimic the real flow: user picks a dungeon, then the difficulty/start/affixes cascade fires
        // refreshSelectPickers(). Repeat across several dungeons.
        instance.addItem('1');
        refreshSelectPickers();
        instance.addItem('3');
        refreshSelectPickers();
        instance.addItem('2');
        refreshSelectPickers();

        // Every option must still render under its optgroup header - none relocated to the top level.
        instance.open();
        instance.refreshOptions(true);
        const topLevelOptions = Array.from(instance.dropdown_content.children)
            .filter((child) => child.matches('[data-selectable]'));
        expect(topLevelOptions).toEqual([]);
        // Options 1 and 2 stay under the first optgroup, option 3 under the second.
        expect(instance.options['1'].optgroup).toBe(instance.options['2'].optgroup);
        expect(instance.options['3'].optgroup).not.toBe(instance.options['1'].optgroup);
    });
});

describe('initSelectPicker focus retention inside Leaflet popups', () => {
    /**
     * Leaflet popups call L.DomEvent.disableClickPropagation, which stops `mousedown` before it
     * reaches document - defeating Tom Select's own document-level focus-retention handler. Reproduce
     * that here by swallowing mousedown at the container, then initialize a picker inside it.
     *
     * @param {string} containerClass
     * @returns {HTMLElement} the initialized `.ts-control`
     */
    function initInContainerThatSwallowsMousedown(containerClass) {
        document.body.innerHTML = `<div class="${containerClass}">
            <select class="form-control selectpicker">
                <option value="1">One</option>
                <option value="2">Two</option>
            </select>
        </div>`;
        const container = document.querySelector(`.${containerClass}`);
        container.addEventListener('mousedown', (event) => event.stopPropagation());

        refreshSelectPickers();

        return container.querySelector('.ts-control');
    }

    test('initSelectPicker_givenSelectInLeafletPopup_retainsFocusOnControlMousedown', () => {
        const control = initInContainerThatSwallowsMousedown('leaflet-popup');

        const event = new MouseEvent('mousedown', {bubbles: true, cancelable: true});
        control.dispatchEvent(event);

        // The wrapper-level handler runs before the popup swallows the event, so the control keeps
        // focus and the dropdown no longer collapses on a single click.
        expect(event.defaultPrevented).toBe(true);
    });

    test('initSelectPicker_givenSelectOutsidePopup_doesNotAddPopupFocusHandler', () => {
        // Same swallowed mousedown but no `.leaflet-popup` ancestor: the fix must not attach itself,
        // so the control mousedown is left to Tom Select's (here suppressed) document handler.
        const control = initInContainerThatSwallowsMousedown('not-a-popup');

        const event = new MouseEvent('mousedown', {bubbles: true, cancelable: true});
        control.dispatchEvent(event);

        expect(event.defaultPrevented).toBe(false);
    });
});

describe('multi select dropdown toggle deselect', () => {
    function openMulti() {
        const select = createSelect('multiple', `
            <option value="1" selected>One</option>
            <option value="2">Two</option>`);
        refreshSelectPickers();
        const instance = select.tomselect;
        instance.open();
        instance.refreshOptions(true);
        return instance;
    }

    function optionFor(instance, value) {
        return Array.from(instance.dropdown_content.querySelectorAll('[data-selectable]'))
            .find((el) => el.dataset.value === value) ?? null;
    }

    function clickOption(instance, value) {
        optionFor(instance, value).dispatchEvent(new MouseEvent('click', {bubbles: true, cancelable: true}));
    }

    test('refreshSelectPickers_givenMultiSelect_keepsSelectedOptionsInDropdown', () => {
        // hideSelected:false so a selected option can still be deselected from the dropdown
        const instance = openMulti();

        expect(optionFor(instance, '1')).not.toBeNull();
    });

    test('refreshSelectPickers_givenClickOnSelectedOption_deselectsIt', () => {
        const instance = openMulti();

        clickOption(instance, '1');

        expect(instance.items).not.toContain('1');
    });

    test('refreshSelectPickers_givenClickOnUnselectedOption_stillSelectsIt', () => {
        const instance = openMulti();

        clickOption(instance, '2');

        expect(instance.items).toContain('2');
    });
});

describe('selected count summary (data-selected-text-format)', () => {
    const COUNT_ATTRS = 'multiple data-selected-text-format="count > 1" data-count-selected-text="{0} attributes"';

    test('refreshSelectPickers_givenSelectionAboveThreshold_collapsesToCountSummary', () => {
        const select = createSelect(COUNT_ATTRS, `
            <option value="1" selected>One</option>
            <option value="2" selected>Two</option>
            <option value="3" selected>Three</option>`);

        refreshSelectPickers();

        const control = select.tomselect.control;
        expect(control.classList.contains('ts-count-summary')).toBe(true);
        expect(control.dataset.countSummary).toBe('3 attributes');
    });

    test('refreshSelectPickers_givenSelectionAtThreshold_keepsTags', () => {
        const select = createSelect(COUNT_ATTRS, `
            <option value="1" selected>One</option>
            <option value="2">Two</option>`);

        refreshSelectPickers();

        const control = select.tomselect.control;
        expect(control.classList.contains('ts-count-summary')).toBe(false);
        expect(control.dataset.countSummary).toBeUndefined();
    });

    test('refreshSelectPickers_givenSelectionCrossingThreshold_updatesSummaryOnChange', () => {
        const select = createSelect(COUNT_ATTRS, `
            <option value="1" selected>One</option>
            <option value="2">Two</option>`);
        refreshSelectPickers();
        const instance = select.tomselect;

        instance.addItem('2');

        expect(instance.control.classList.contains('ts-count-summary')).toBe(true);
        expect(instance.control.dataset.countSummary).toBe('2 attributes');
    });

    test('refreshSelectPickers_givenSingleSelectWithFormat_doesNotCollapse', () => {
        const select = createSelect('data-selected-text-format="count > 1"', `
            <option value="1" selected>One</option>
            <option value="2">Two</option>`);

        refreshSelectPickers();

        expect(select.tomselect.control.classList.contains('ts-count-summary')).toBe(false);
    });
});

describe('getSelectPickerSettings', () => {
    test('getSelectPickerSettings_givenLiveSearch_enablesDropdownInputPlugin', () => {
        const select = createSelect('data-live-search="true"');

        const settings = getSelectPickerSettings(select);

        expect(settings.plugins).toContain('dropdown_input');
        expect(settings.controlInput).toBeUndefined();
    });

    test('getSelectPickerSettings_givenNoLiveSearch_disablesControlInput', () => {
        const select = createSelect();

        const settings = getSelectPickerSettings(select);

        expect(settings.plugins).not.toContain('dropdown_input');
        expect(settings.controlInput).toBeNull();
    });

    test('getSelectPickerSettings_givenMultipleSelect_enablesRemoveButtonPlugin', () => {
        const select = createSelect('multiple');

        const settings = getSelectPickerSettings(select);

        expect(settings.plugins).toContain('remove_button');
    });

    test('getSelectPickerSettings_givenTitleAttribute_usesItAsPlaceholder', () => {
        const select = createSelect('title="Select something"');

        expect(getSelectPickerSettings(select).placeholder).toBe('Select something');
    });

    test('getSelectPickerSettings_givenEmptyValueOption_allowsEmptyOption', () => {
        const select = createSelect('', `
            <option value="">None</option>
            <option value="1">One</option>`);

        expect(getSelectPickerSettings(select).allowEmptyOption).toBe(true);
    });
});

describe('getOptionSetFingerprint', () => {
    test('getOptionSetFingerprint_givenOptionMovedBetweenOptgroups_returnsSameFingerprint', () => {
        // Tom Select relocates a selected <option> out of its <optgroup> to the top level of the
        // <select>; the fingerprint must ignore that so it does not trigger a destructive rebuild.
        const select = createSelect('', `
            <optgroup label="Season 1"><option value="1">One</option><option value="2">Two</option></optgroup>
            <optgroup label="Season 2"><option value="3">Three</option></optgroup>`);
        const before = getOptionSetFingerprint(select, false);

        // Move option "1" out of its optgroup to the top level of the select (what Tom Select does).
        select.appendChild(select.querySelector('option[value="1"]'));

        expect(getOptionSetFingerprint(select, false)).toBe(before);
    });

    test('getOptionSetFingerprint_givenChangedOptionValues_returnsDifferentFingerprint', () => {
        // Guardrail: a genuine repopulation (the difficulty/start child selects) still changes the
        // fingerprint so the rebuild path keeps working.
        const select = createSelect();
        const before = getOptionSetFingerprint(select, false);

        select.innerHTML = `
            <option value="4">Four</option>
            <option value="5" selected>Five</option>`;

        expect(getOptionSetFingerprint(select, false)).not.toBe(before);
    });
});
