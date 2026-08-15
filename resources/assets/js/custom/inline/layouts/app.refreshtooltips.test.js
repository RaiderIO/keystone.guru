// Regression coverage for #3967: the pull workbench's kill-area button tooltip text getting
// permanently stuck on whichever title it showed first (e.g. "Add kill area"), never updating to
// "Remove kill area" and back no matter how many times _initKillAreaButton() re-sets the `title`
// attribute and calls refreshTooltips().
//
// Real jQuery and the real `bootstrap` package against jsdom, like
// enemyvisual.circlemenu.test.js - the bug lives entirely inside Bootstrap 5's own
// Tooltip#dispose()/_fixTitle() interaction, which a hand-rolled Tooltip stub would not exercise.
global.$ = global.jQuery = require('jquery');
global.bootstrap = require('bootstrap');
global.isMobile = () => false;
global.InlineCode = require('../inlinecode').InlineCode;

const {refreshTooltips} = require('./app');

describe('refreshTooltips (#3967)', () => {
    afterEach(() => {
        document.body.innerHTML = '';
    });

    test('refreshTooltips_givenTheTitleChangedSinceTheLastRefresh_showsTheNewTitle', () => {
        // Arrange: an element with a tooltip already initialized once, exactly like
        // _initKillAreaButton() does on the first editPull() for a page.
        document.body.innerHTML = '<div id="target" data-bs-toggle="tooltip" title="Add kill area"></div>';
        const $target = $('#target');
        refreshTooltips($target);
        expect(bootstrap.Tooltip.getInstance($target[0])._getTitle()).toBe('Add kill area');

        // Act: the kill zone now has a kill area, so _initKillAreaButton() sets the new title and
        // refreshes again
        $target.attr('title', 'Remove kill area');
        refreshTooltips($target);

        // Assert: the tooltip must reflect the new title, not stay frozen on the first one it ever saw
        expect(bootstrap.Tooltip.getInstance($target[0])._getTitle()).toBe('Remove kill area');
    });

    test('refreshTooltips_givenTheTitleTogglesBackAndForth_alwaysShowsTheCurrentTitle', () => {
        // Arrange
        document.body.innerHTML = '<div id="target" data-bs-toggle="tooltip" title="Add kill area"></div>';
        const $target = $('#target');

        // Act / Assert: add -> remove -> add, mirroring repeatedly toggling the kill area button
        refreshTooltips($target);
        expect(bootstrap.Tooltip.getInstance($target[0])._getTitle()).toBe('Add kill area');

        $target.attr('title', 'Remove kill area');
        refreshTooltips($target);
        expect(bootstrap.Tooltip.getInstance($target[0])._getTitle()).toBe('Remove kill area');

        $target.attr('title', 'Add kill area');
        refreshTooltips($target);
        expect(bootstrap.Tooltip.getInstance($target[0])._getTitle()).toBe('Add kill area');
    });
});
