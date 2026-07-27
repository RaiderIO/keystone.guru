// ---------------------------------------------------------------------------
// Covers #3696: the yellow dashed enemy-selection border must hug the enemy
// icon. EnemyVisual.refreshSize() sizes the selection wrapper as icon size
// + 8px, expecting the 4px dashed border to be part of that box (border-box).
// Leaflet.draw ships `.leaflet-edit-marker-selected` with box-sizing:
// content-box, which outranks Bootstrap's universal border-box Reboot rule
// (class > universal selector), so lib/leaflet.css scopes a border-box
// override to the enemy selection wrapper. These tests load the REAL vendor
// and app stylesheets in bundle order and assert the resulting cascade.
// ---------------------------------------------------------------------------

const fs = require('fs');
const path = require('path');

const ROOT = path.resolve(__dirname, '../../..');

const vendorCss = fs.readFileSync(path.join(ROOT, 'node_modules/leaflet-draw/dist/leaflet.draw.css'), 'utf8');
const appCss = fs.readFileSync(path.join(ROOT, 'resources/assets/css/lib/leaflet.css'), 'utf8');

/**
 * Injects the vendor and app stylesheets in the same order the CSS bundle
 * concatenates them (app.scss pulls in leaflet.draw.css; the custom bundle
 * with lib/leaflet.css loads after it), then returns an element carrying the
 * given classes.
 *
 * @param {string} className
 * @returns {HTMLElement}
 */
function createStyledElement(className) {
    document.head.innerHTML = `<style>${vendorCss}</style><style>${appCss}</style>`;
    document.body.innerHTML = `<div id="subject" class="${className}"></div>`;
    return document.querySelector('#subject');
}

describe('enemy selection border box model (#3696)', () => {
    afterEach(() => {
        document.head.innerHTML = '';
        document.body.innerHTML = '';
    });

    test('selectionWrapper_givenBundleCascade_computesBorderBox', () => {
        // enemyvisual.js applies both classes together; the app override must
        // win over leaflet.draw's content-box or the wrapper grows 16px past
        // the icon, leaving the border gapped on the right/bottom.
        const wrapper = createStyledElement('selected_enemy_icon leaflet-edit-marker-selected');

        expect(getComputedStyle(wrapper).boxSizing).toBe('border-box');
    });

    test('editModeMarker_givenBundleCascade_keepsContentBox', () => {
        // Leaflet.draw's own edit-mode markers get only the vendor class and
        // self-compensate their position assuming content-box (its
        // _offsetMarker(icon, 4)); the override must not leak onto them.
        const marker = createStyledElement('leaflet-edit-marker-selected');

        expect(getComputedStyle(marker).boxSizing).toBe('content-box');
    });

    test('vendorStylesheet_givenCurrentLeafletDraw_stillDeclaresTheAssumedBoxModel', () => {
        // If a leaflet-draw update ever drops the content-box declaration or
        // changes the 4px border, the app override and the +8 compensation in
        // EnemyVisual.refreshSize() must be revisited together.
        const rule = vendorCss.match(/\.leaflet-edit-marker-selected\{[^}]*}/)[0];

        expect(rule).toContain('box-sizing:content-box');
        expect(rule).toContain('border:4px dashed');
    });

    test('enemyVisual_givenSelectableSizing_compensatesBothBorderSides', () => {
        // The CSS override and this +8 (2 x 4px dashed border) are two halves
        // of one contract; guard the JS half so they cannot drift apart
        // silently.
        const enemyVisualJs = fs.readFileSync(path.join(ROOT, 'resources/assets/js/custom/enemyvisuals/enemyvisual.js'), 'utf8');

        expect(enemyVisualJs).toContain('outerWidth + 8');
        expect(enemyVisualJs).toContain('outerHeight + 8');
    });
});
