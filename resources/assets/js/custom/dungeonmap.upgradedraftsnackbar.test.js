// Renders the real upgrade draft snackbar template that DungeonMap adds for an upgrade draft, so the
// spacing between the draft notice and its buttons is asserted against the markup that ships.

global.$ = global.jQuery = require('jquery');

const fs = require('fs');
const path = require('path');
const HandlebarsRuntime = require('handlebars');

const upgradeDraftSnackbarTemplate = HandlebarsRuntime.compile(
    fs.readFileSync(
        path.join(__dirname, '../handlebars/map_controls_snackbar_mapping_version_upgrade_draft.handlebars'),
        'utf8'
    )
);

/**
 * @param {Object} data
 * @returns {jQuery} The column holding the "What changed?" button.
 */
function renderDiffButtonColumn(data) {
    document.body.innerHTML = upgradeDraftSnackbarTemplate(data);

    return $('[data-bs-target="#mapping_version_upgrade_diff_modal"]').parent();
}

test('template_givenUpgradeDraft_padsDiffButtonAwayFromNotice', () => {
    // Arrange / Act
    const $diffButtonColumn = renderDiffButtonColumn({});

    // Assert
    expect($diffButtonColumn.hasClass('col-auto')).toBe(true);
    expect($diffButtonColumn.hasClass('ps-2')).toBe(true);
    expect($diffButtonColumn.prev().hasClass('col')).toBe(true);
    expect($diffButtonColumn.prev().find('a')).toHaveLength(0);
});

test('template_givenUpgradeOfUrl_stillPadsDiffButtonAwayFromNoticeLink', () => {
    // Arrange / Act
    const $diffButtonColumn = renderDiffButtonColumn({
        upgrade_of_url: 'https://example.com/route/edit',
    });

    // Assert
    expect($diffButtonColumn.prev().find('a').attr('href')).toBe('https://example.com/route/edit');
    expect($diffButtonColumn.hasClass('ps-2')).toBe(true);
});
