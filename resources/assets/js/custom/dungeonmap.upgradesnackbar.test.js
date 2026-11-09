// Renders the real mapping version upgrade snackbar template that DungeonMap adds for an outdated
// route, so the upgrade action is asserted against the markup that ships.

global.$ = global.jQuery = require('jquery');

const fs = require('fs');
const path = require('path');
const HandlebarsRuntime = require('handlebars');

const upgradeSnackbarTemplate = HandlebarsRuntime.compile(
    fs.readFileSync(
        path.join(__dirname, '../handlebars/map_controls_snackbar_mapping_version_upgrade.handlebars'),
        'utf8'
    )
);

test('template_givenUpgradeUrl_submitsAPostFormWithTheCsrfToken', () => {
    // Arrange / Act
    document.body.innerHTML = upgradeSnackbarTemplate({
        upgrade_url: 'https://example.com/route/upgrade',
        csrf_token:  'test-csrf-token',
    });

    // Assert
    const $form = $('form');
    expect($form).toHaveLength(1);
    expect($form.attr('method')).toBe('POST');
    expect($form.attr('action')).toBe('https://example.com/route/upgrade');
    expect($form.find('input[name="_token"]').val()).toBe('test-csrf-token');
    expect($form.find('button[type="submit"]')).toHaveLength(1);
    expect($('a[href="https://example.com/route/upgrade"]')).toHaveLength(0);
});
