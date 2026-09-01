// Regression coverage for #4422: the enemy_skippable template rendered an element id ending in
// `_enemy_forces` (copy-pasted from the enemy_forces template), while refreshSize() selects
// `..._enemy_skippable`. The mismatch meant refreshSize() silently updated nothing. This test
// renders the real .handlebars source and calls the real refreshSize() against it, so a
// reintroduced id/selector mismatch fails here instead of only failing silently in the browser.

// Real jQuery, replacing the minimal stub from test/setup.js.
global.$ = global.jQuery = require('jquery');

const fs = require('fs');
const path = require('path');
const HandlebarsRuntime = require('handlebars');

global.Signalable = class Signalable {
};
global.EnemyVisual = class EnemyVisual {
};
global.EnemyVisualIcon = class EnemyVisualIcon extends Signalable {
    refreshSize() {
    }
};
global.EnemyVisualMain = class EnemyVisualMain extends EnemyVisualIcon {
};

const {EnemyVisualMainEnemySkippable} = require('./enemyvisualmainenemyskippable');

const skippableTemplate = HandlebarsRuntime.compile(
    fs.readFileSync(
        path.join(__dirname, '../../handlebars/map_enemy_visual_enemy_skippable_template.handlebars'),
        'utf8'
    )
);

test('refreshSize_givenRenderedTemplateMarkup_updatesItsFontSize', () => {
    const enemyId = 42;
    document.body.innerHTML = skippableTemplate({id: enemyId, width: 10, display_text: 'Y'});

    const fakeThis = {
        enemyvisual: {enemy: {id: enemyId}},
        _getTextWidth: () => 22,
    };

    EnemyVisualMainEnemySkippable.prototype.refreshSize.call(fakeThis);

    expect($(`#map_enemy_visual_${enemyId}_enemy_skippable`).css('font-size')).toBe('22px');
});
