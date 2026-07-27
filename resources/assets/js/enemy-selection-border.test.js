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

/**
 * Same as createStyledElement, but nests the subject inside a marker-root
 * element (as Leaflet renders it), for the rules keyed on the root's state.
 *
 * @param {string} rootClassName
 * @param {string} className
 * @returns {HTMLElement}
 */
function createNestedStyledElement(rootClassName, className) {
    document.head.innerHTML = `<style>${vendorCss}</style><style>${appCss}</style>`;
    document.body.innerHTML = `<div class="${rootClassName}"><div id="subject" class="${className}"></div></div>`;
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

    test('mapIconVisual_givenBundleCascade_computesBorderBox', () => {
        // getLeafletIcon() renders map_map_icon_visual_template with the
        // selection class on an inner div sized outer_width/outer_height
        // (icon + 8px) — the same border-box contract as the enemy wrapper.
        const visual = createStyledElement('map_icon dungeon_start leaflet-edit-marker-selected');

        expect(getComputedStyle(visual).boxSizing).toBe('border-box');
    });

    test('mapIconMarkerRoot_givenBundleCascade_keepsContentBox', () => {
        // The marker ROOT also carries map_icon (icon.js className) and gets
        // the selection class from leaflet.draw's edit toggle; the
        // :not(.leaflet-marker-icon) guard must keep the override off it.
        const root = createStyledElement('map_icon leaflet-edit-marker-selected leaflet-marker-icon');

        expect(getComputedStyle(root).boxSizing).toBe('content-box');
    });

    test('editModeMarker_givenBundleCascade_keepsContentBox', () => {
        // Leaflet.draw's own edit-mode markers get only the vendor class and
        // self-compensate their position assuming content-box (its
        // _offsetMarker(icon, 4)); the override must not leak onto them.
        const marker = createStyledElement('leaflet-edit-marker-selected');

        expect(getComputedStyle(marker).boxSizing).toBe('content-box');
    });

    test('selectionVisual_givenPlainMarkerRoot_recentersByBorderWidth', () => {
        // App-driven states (couple/select/delete) put the class only on the
        // inner visual; the icon would sit 4px right/down of its anchor
        // without the pull-back (measured in #3701).
        const wrapper = createNestedStyledElement('leaflet-marker-icon', 'map_icon dungeon_start leaflet-edit-marker-selected');

        const cs = getComputedStyle(wrapper);
        expect(cs.marginTop).toBe('-4px');
        expect(cs.marginLeft).toBe('-4px');
    });

    test('selectionVisual_givenLeafletDrawSelectedRoot_doesNotRecenter', () => {
        // In leaflet.draw's edit mode the ROOT also carries the class and
        // leaflet.draw re-centers the marker itself (_offsetMarker(icon, 4));
        // compensating there too would double-shift the icon.
        const wrapper = createNestedStyledElement('leaflet-marker-icon leaflet-edit-marker-selected', 'map_icon dungeon_start leaflet-edit-marker-selected');

        const cs = getComputedStyle(wrapper);
        // jsdom serializes the unset margin as '0'; a browser would say '0px'
        expect(['0', '0px']).toContain(cs.marginTop);
        expect(['0', '0px']).toContain(cs.marginLeft);
    });

    test('vendorStylesheet_givenCurrentLeafletDraw_stillDeclaresTheAssumedBoxModel', () => {
        // If a leaflet-draw update ever drops the content-box declaration or
        // changes the 4px border, the app override and the +8 compensation in
        // EnemyVisual.refreshSize() must be revisited together.
        const rule = vendorCss.match(/\.leaflet-edit-marker-selected\s*\{[^}]*\}/);

        expect(rule).not.toBeNull();
        expect(rule[0]).toMatch(/box-sizing:\s*content-box/);
        expect(rule[0]).toContain('border:4px dashed');
    });

    test('enemyVisual_givenSelectableSizing_compensatesBothBorderSides', () => {
        // The CSS override and this +8 (2 x 4px dashed border) are two halves
        // of one contract; guard the JS half so they cannot drift apart
        // silently.
        const enemyVisualJs = fs.readFileSync(path.join(ROOT, 'resources/assets/js/custom/enemyvisuals/enemyvisual.js'), 'utf8');

        expect(enemyVisualJs).toMatch(/outerWidth\s*\+\s*8/);
        expect(enemyVisualJs).toMatch(/outerHeight\s*\+\s*8/);
    });
});
