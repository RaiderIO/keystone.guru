// Renders the real snackbar template that DungeonMap adds for a draft created from an MDT string.

global.$ = global.jQuery = require('jquery');

const fs = require('fs');
const path = require('path');
const HandlebarsRuntime = require('handlebars');

const mdtImportDraftSnackbarTemplate = HandlebarsRuntime.compile(
    fs.readFileSync(
        path.join(__dirname, '../handlebars/map_controls_snackbar_mdt_import_draft.handlebars'),
        'utf8'
    )
);

const MESSAGES = {
    'js.mdt_import_draft_label': 'You are reviewing an MDT import draft.',
    'js.mdt_import_draft_of_label': 'View the route it replaces',
    'js.mapping_version_upgrade_apply_label': 'Apply',
    'js.mapping_version_upgrade_discard_label': 'Discard',
};

let previousLang;

beforeEach(() => {
    previousLang = globalThis.lang;
    globalThis.lang = {get: (key) => MESSAGES[key] ?? key};
});

afterEach(() => {
    globalThis.lang = previousLang;
});

/**
 * @param {Object} data
 */
function render(data) {
    document.body.innerHTML = mdtImportDraftSnackbarTemplate(data);
}

test('template_givenMdtImportDraft_offersApplyAndDiscard', () => {
    // Arrange / Act
    render({});

    // Assert
    expect($('.upgrade_draft_apply').length).toBe(1);
    expect($('.upgrade_draft_discard').length).toBe(1);
    expect(document.body.textContent).toContain('You are reviewing an MDT import draft.');
});

test('template_givenMdtImportDraft_hasNoMappingUpgradeDiffButton', () => {
    // Arrange / Act
    render({});

    // Assert
    expect($('[data-bs-target="#mapping_version_upgrade_diff_modal"]').length).toBe(0);
});

test('template_givenUpgradeOfUrl_linksToReplacedRoute', () => {
    // Arrange / Act
    render({upgrade_of_url: 'https://example.com/route/edit'});

    // Assert
    expect($('a[href="https://example.com/route/edit"]').text()).toBe('View the route it replaces');
});
