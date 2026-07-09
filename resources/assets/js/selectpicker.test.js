// ---------------------------------------------------------------------------
// Covers the Tom Select adapter behind the project's `.selectpicker`
// convention (selectpicker.js, #3420): initialization from the bootstrap-select
// era markup, and refreshSelectPickers() semantics that the inline scripts
// rely on (init new pickers, adopt external value/disabled changes, rebuild
// the option list on repopulation without duplicating or reordering options).
// ---------------------------------------------------------------------------

const {getSelectPickerSettings, refreshSelectPickers} = require('./selectpicker');

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
