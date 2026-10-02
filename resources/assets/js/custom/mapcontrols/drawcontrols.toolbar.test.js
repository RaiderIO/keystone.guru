// The draw toolbar is generated from the editor's tool list: these tests feed DrawControls a tool list of
// Leaflet.draw's own handlers and check the Leaflet.draw control, the rail and the group flyouts it renders.
const fs = require('fs');
const path = require('path');
const Handlebars = require('handlebars');

global.L = require('leaflet');
global.window.L = global.L;
require('leaflet-draw');

const jQuery = require('jquery');
global.$ = jQuery;

Handlebars.registerHelper('ifCond', function (v1, operator, v2, options) {
    return (v1 != v2) === (operator === '!=') ? options.fn(this) : options.inverse(this);
});

/**
 * @param name {String}
 * @returns {Function}
 */
function compileTemplate(name) {
    return Handlebars.compile(fs.readFileSync(path.join(__dirname, `../../handlebars/${name}.handlebars`), 'utf8'));
}

global.Handlebars = {
    templates: {
        map_controls_route_edit_button_template: compileTemplate('map_controls_route_edit_button_template'),
        map_controls_draw_tool_group_template: compileTemplate('map_controls_draw_tool_group_template'),
        map_controls_draw_tool_flyout_item_template: compileTemplate('map_controls_draw_tool_flyout_item_template'),
        map_controls_draw_tool_status_template: compileTemplate('map_controls_draw_tool_status_template'),
    },
};

const {Signalable} = require('../signalable');
global.Signalable = Signalable;
const {Hotkeys} = require('../hotkeys');
global.Hotkeys = Hotkeys;

global.MapControl = class MapControl {
    cleanup() {
    }
};
global.DungeonMap = class DungeonMap {
};
global.PatherMapState = class PatherMapState {
};

const {DrawControls} = require('./drawcontrols');

/** @type {DrawTool[]} */
const TOOLS = [{
    id: 'polyline',
    icon: 'fa-route',
    label: 'js.polyline',
    title: 'js.polyline_title',
    keys: ['1', 'p'],
    handler: L.Draw.Polyline,
    options: {},
}, {
    id: 'marker',
    group: 'markers',
    icon: 'fa-icons',
    label: 'js.marker',
    title: 'js.marker_title',
    keys: ['2'],
    handler: L.Draw.Marker,
    options: {},
}, {
    id: 'rectangle',
    hidden: true,
    handler: L.Draw.Rectangle,
    options: {},
}, {
    id: 'polygon',
    group: 'markers',
    icon: 'fa-draw-polygon',
    label: 'js.polygon',
    title: 'js.polygon_title',
    keys: ['shift+u'],
    handler: L.Draw.Polygon,
    options: {},
}, {
    id: 'edit',
    kind: 'edit',
    icon: 'fa-edit',
    label: 'js.edit',
    title: 'js.edit_title',
    keys: ['5'],
}, {
    id: 'delete',
    kind: 'remove',
    icon: 'fa-trash',
    label: 'js.delete',
    title: 'js.delete_title',
    keys: ['6'],
    btnType: 'btn-danger',
}];

describe('DrawControls toolbar generated from the tool list', () => {
    let previousLang;
    let previousGetState;
    let leafletMap;
    let controls;
    let $rail;
    /** @type {String[]} */
    let snackbars;

    beforeEach(() => {
        previousLang = global.lang;
        previousGetState = global.getState;
        global.lang = {
            get: (key, params = {}) => params.hotkey ? `${key}(${params.hotkey})` : key,
            messages: {},
        };
        snackbars = [];
        global.getState = () => ({
            addSnackbar: (html) => {
                snackbars.push(html);
                return 'snackbar';
            },
            removeSnackbar: () => {
            },
        });

        document.body.innerHTML = '<div class="route_manipulation_tools"><div id="edit_route_draw_container"></div></div><div id="outside"></div>';
        const container = document.createElement('div');
        Object.defineProperty(container, 'clientWidth', {value: 800});
        Object.defineProperty(container, 'clientHeight', {value: 600});
        document.body.appendChild(container);
        leafletMap = L.map(container, {center: [0, 0], zoom: 2});

        controls = Object.create(DrawControls.prototype);
        controls.map = {
            leafletMap: leafletMap,
            togglePather: () => {
            },
            getMapState: () => null,
        };
        controls.editableItemsLayer = new L.FeatureGroup();
        controls._mapControl = null;
        controls._getTools = () => TOOLS;

        // Act (shared): build the toolbar
        controls.addControl();
        $rail = jQuery(jQuery('#edit_route_draw_container > div').children()[0]);
    });

    afterEach(() => {
        controls.cleanup();
        leafletMap.remove();
        global.lang = previousLang;
        global.getState = previousGetState;
        document.body.innerHTML = '';
    });

    test('addControl_givenToolList_rendersRailInToolListOrder', () => {
        // Assert
        const rail = $rail.children('a, .draw_tool_group').toArray().map((element) => {
            return element.dataset.drawTool ?? `group:${element.dataset.drawToolGroup}`;
        });
        expect(rail).toEqual(['polyline', 'group:markers', 'rectangle', 'edit', 'delete']);
        expect($rail.children('[data-draw-tool="rectangle"]').hasClass('d-none')).toBe(true);
    });

    test('addControl_givenGroupedTools_rendersThemInTheGroupFlyout', () => {
        // Assert
        const $flyout = $rail.find('[data-draw-tool-group="markers"] .draw_tool_group_flyout');
        const flyoutTools = $flyout.children('a').toArray().map((element) => element.dataset.drawTool);
        expect(flyoutTools).toEqual(['marker', 'polygon']);
        expect($flyout.find('[data-draw-tool="polygon"] .draw_tool_keycap').text().trim()).toBe('Shift+U');
        expect($flyout.find('[data-draw-tool="marker"]').hasClass('leaflet-draw-draw-marker')).toBe(true);
        expect($flyout.is(':visible')).toBe(false);
    });

    test('addControl_givenToolKeys_rendersFirstKeyAsKeycapAndAllKeysInTitle', () => {
        // Assert
        const $polyline = $rail.children('[data-draw-tool="polyline"]');
        expect($polyline.find('.draw_tool_keycap').text().trim()).toBe('1');
        expect($polyline.attr('data-bs-toggle')).toBe('tooltip');
        expect($polyline.attr('data-bs-title')).toBe('js.polyline_title(1 / P)');
        expect($rail.children('[data-draw-tool="delete"]').find('.btn-danger').length).toBe(1);
    });

    test('getModeHandlers_givenToolList_createsOneHandlerPerDrawTool', () => {
        // Act
        const handlers = controls._mapControl._toolbars.draw.getModeHandlers(leafletMap);

        // Assert
        expect(handlers.map((modeHandler) => modeHandler.handler.type)).toEqual(['polyline', 'marker', 'rectangle', 'polygon']);
        expect(handlers[0].title).toBe('js.polyline_title(1 / P)');
    });

    test('groupButton_givenClickedThenOutsideClicked_opensAndClosesFlyout', () => {
        // Arrange
        const $group = $rail.find('[data-draw-tool-group="markers"]');
        const $flyout = $group.find('.draw_tool_group_flyout');

        // Act
        $group.find('.draw_tool_group_button').trigger('click');
        const openAfterClick = $flyout.css('display') !== 'none';
        jQuery('#outside').trigger('click');

        // Assert
        expect(openAfterClick).toBe(true);
        expect($flyout.css('display')).toBe('none');
    });

    test('groupButton_givenEscapePressed_closesFlyout', () => {
        // Arrange
        const $group = $rail.find('[data-draw-tool-group="markers"]');
        const $flyout = $group.find('.draw_tool_group_flyout');
        $group.find('.draw_tool_group_button').trigger('click');

        // Act
        jQuery(document.body).trigger(jQuery.Event('keydown', {key: 'Escape'}));

        // Assert
        expect($flyout.css('display')).toBe('none');
    });

    test('flyoutTool_givenClicked_activatesToolAndMarksItsGroup', () => {
        // Arrange
        const $group = $rail.find('[data-draw-tool-group="markers"]');
        $group.find('.draw_tool_group_button').trigger('click');
        const button = $group.find('[data-draw-tool="polygon"]')[0];

        // Act
        button.dispatchEvent(new MouseEvent('click', {bubbles: true, cancelable: true}));

        // Assert
        expect(button.classList.contains('leaflet-draw-toolbar-button-enabled')).toBe(true);
        expect($group.find('.draw_tool_group_button').hasClass('leaflet-draw-toolbar-button-enabled')).toBe(true);
        expect($group.find('.draw_tool_group_icon').hasClass('fa-draw-polygon')).toBe(true);
        expect($group.find('.draw_tool_group_flyout').css('display')).toBe('none');
    });

    test('flyoutTool_givenToolClosed_unmarksItsGroup', () => {
        // Arrange
        const $group = $rail.find('[data-draw-tool-group="markers"]');
        $group.find('[data-draw-tool="marker"]')[0].dispatchEvent(new MouseEvent('click', {bubbles: true}));

        // Act
        controls._mapControl._toolbars.draw.disable();

        // Assert
        expect($group.find('.draw_tool_group_button').hasClass('leaflet-draw-toolbar-button-enabled')).toBe(false);
    });
    test('addControl_givenToolList_givesEveryButtonANameWithItsHotkeysAndNoNativeTitle', () => {
        // Assert
        const $polyline = $rail.children('[data-draw-tool="polyline"]');
        expect($polyline.attr('role')).toBe('button');
        expect($polyline.attr('aria-label')).toBe('js.draw_tool_aria_label(1 / P)');
        expect($polyline.attr('aria-keyshortcuts')).toBe('1 P');
        expect($rail.find('[data-draw-tool="polygon"]').attr('aria-keyshortcuts')).toBe('Shift+U');
        expect($rail.children('[data-draw-tool="edit"]').attr('aria-label')).toBe('js.draw_tool_aria_label(5)');
        expect($rail.children('[data-draw-tool="delete"]').attr('aria-label')).toBe('js.draw_tool_aria_label(6)');
        expect($rail.find('[title]').length).toBe(0);
    });

    test('editToolbar_givenLayersAddedAndRemoved_syncsAriaDisabledWithoutNativeTitle', () => {
        // Arrange
        const $edit = $rail.children('[data-draw-tool="edit"]');
        const $delete = $rail.children('[data-draw-tool="delete"]');
        const disabledWhenEmpty = [$edit.attr('aria-disabled'), $delete.attr('aria-disabled')];
        const marker = L.marker([0, 0]);

        // Act
        controls.editableItemsLayer.addLayer(marker);
        const disabledWithLayer = [$edit.attr('aria-disabled'), $delete.attr('aria-disabled')];
        const titlesWithLayer = [$edit.attr('title'), $delete.attr('title')];
        controls.editableItemsLayer.removeLayer(marker);

        // Assert
        expect(disabledWhenEmpty).toEqual(['true', 'true']);
        expect(disabledWithLayer).toEqual(['false', 'false']);
        expect(titlesWithLayer).toEqual([undefined, undefined]);
        expect([$edit.attr('aria-disabled'), $delete.attr('aria-disabled')]).toEqual(['true', 'true']);
        expect([$edit.attr('title'), $delete.attr('title')]).toEqual([undefined, undefined]);
    });

    test('drawTool_givenActivatedThenClosed_togglesAriaPressed', () => {
        // Arrange
        const polyline = $rail.children('[data-draw-tool="polyline"]')[0];
        const polygon = $rail.find('[data-draw-tool="polygon"]')[0];
        const pressedBefore = polyline.getAttribute('aria-pressed');

        // Act
        polyline.dispatchEvent(new MouseEvent('click', {bubbles: true, cancelable: true, detail: 1}));
        const pressedWhileActive = [polyline.getAttribute('aria-pressed'), polygon.getAttribute('aria-pressed')];
        controls._mapControl._toolbars.draw.disable();

        // Assert
        expect(pressedBefore).toBe('false');
        expect(pressedWhileActive).toEqual(['true', 'false']);
        expect(polyline.getAttribute('aria-pressed')).toBe('false');
    });

    test('drawTool_givenActivated_namesTheToolInTheSnackbar', () => {
        // Arrange
        const polygon = $rail.find('[data-draw-tool="polygon"]')[0];

        // Act
        polygon.dispatchEvent(new MouseEvent('click', {bubbles: true, cancelable: true, detail: 1}));

        // Assert
        expect(snackbars.length).toBe(1);
        const $snackbar = jQuery(`<div>${snackbars[0]}</div>`);
        const $status = $snackbar.find('.draw_tool_snackbar > .draw_tool_status[role="status"]');
        expect($status.length).toBe(1);
        expect($status.find('.visually-hidden').text().trim()).toBe('js.draw_tool_status');
        expect($status.find('.draw_tool_status_label').text().trim()).toBe('js.polygon');
        expect($status.find('.fa-draw-polygon').length).toBe(1);
        expect($status.find('.draw_tool_keycap').text().trim()).toBe('Shift+U');
    });

    test('drawTool_givenHiddenToolActivated_rendersSnackbarWithoutStatus', () => {
        // Act
        new L.Draw.Rectangle(leafletMap, controls.drawControlOptions.draw.rectangle);
        $rail.children('[data-draw-tool="rectangle"]')[0].dispatchEvent(new MouseEvent('click', {bubbles: true, cancelable: true, detail: 1}));

        // Assert
        expect(snackbars.length).toBe(1);
        const $snackbar = jQuery(`<div>${snackbars[0]}</div>`);
        expect($snackbar.find('.draw_tool_snackbar').length).toBe(1);
        expect($snackbar.find('.draw_tool_status').length).toBe(0);
    });

    test('groupButton_givenKeyboardClick_opensFlyoutAndFocusesFirstTool', () => {
        // Arrange
        const $group = $rail.find('[data-draw-tool-group="markers"]');
        const groupButton = $group.find('.draw_tool_group_button')[0];

        // Act
        groupButton.dispatchEvent(new MouseEvent('click', {bubbles: true, cancelable: true, detail: 0}));

        // Assert
        expect($group.find('.draw_tool_group_flyout').css('display')).not.toBe('none');
        expect(groupButton.getAttribute('aria-expanded')).toBe('true');
        expect(document.activeElement).toBe($group.find('[data-draw-tool="marker"]')[0]);
    });

    test('groupButton_givenMouseClick_opensFlyoutWithoutMovingFocus', () => {
        // Arrange
        const $group = $rail.find('[data-draw-tool-group="markers"]');
        const groupButton = $group.find('.draw_tool_group_button')[0];
        groupButton.focus();

        // Act
        groupButton.dispatchEvent(new MouseEvent('click', {bubbles: true, cancelable: true, detail: 1}));

        // Assert
        expect($group.find('.draw_tool_group_flyout').css('display')).not.toBe('none');
        expect(document.activeElement).toBe(groupButton);
    });

    test('groupButton_givenArrowRight_opensFlyoutAndFocusesActiveTool', () => {
        // Arrange
        const $group = $rail.find('[data-draw-tool-group="markers"]');
        const polygon = $group.find('[data-draw-tool="polygon"]')[0];
        polygon.dispatchEvent(new MouseEvent('click', {bubbles: true, cancelable: true, detail: 1}));
        const groupButton = $group.find('.draw_tool_group_button')[0];

        // Act
        jQuery(groupButton).trigger(jQuery.Event('keydown', {key: 'ArrowRight'}));

        // Assert
        expect($group.find('.draw_tool_group_flyout').css('display')).not.toBe('none');
        expect(document.activeElement).toBe(polygon);
    });

    test('groupFlyout_givenArrowKeys_movesFocusAndWraps', () => {
        // Arrange
        const $group = $rail.find('[data-draw-tool-group="markers"]');
        const marker = $group.find('[data-draw-tool="marker"]')[0];
        const polygon = $group.find('[data-draw-tool="polygon"]')[0];
        $group.find('.draw_tool_group_button')[0].dispatchEvent(new MouseEvent('click', {bubbles: true, detail: 0}));
        const pressKey = (key) => jQuery(document.activeElement).trigger(jQuery.Event('keydown', {key: key}));

        // Act
        pressKey('ArrowDown');
        const afterDown = document.activeElement;
        pressKey('ArrowDown');
        const afterWrapDown = document.activeElement;
        pressKey('ArrowUp');
        const afterWrapUp = document.activeElement;
        pressKey('Home');
        const afterHome = document.activeElement;
        pressKey('End');

        // Assert
        expect(afterDown).toBe(polygon);
        expect(afterWrapDown).toBe(marker);
        expect(afterWrapUp).toBe(polygon);
        expect(afterHome).toBe(marker);
        expect(document.activeElement).toBe(polygon);
    });

    test('groupFlyout_givenEscape_closesFlyoutAndFocusesGroupButtonOnly', () => {
        // Arrange
        const $group = $rail.find('[data-draw-tool-group="markers"]');
        const groupButton = $group.find('.draw_tool_group_button')[0];
        groupButton.dispatchEvent(new MouseEvent('click', {bubbles: true, detail: 0}));
        let escapeReachedDocument = false;
        jQuery(document).on('keydown.escapeprobe', (keyEvent) => {
            escapeReachedDocument = keyEvent.key === 'Escape';
        });

        // Act
        jQuery(document.activeElement).trigger(jQuery.Event('keydown', {key: 'Escape'}));
        jQuery(document).off('.escapeprobe');

        // Assert
        expect($group.find('.draw_tool_group_flyout').css('display')).toBe('none');
        expect(groupButton.getAttribute('aria-expanded')).toBe('false');
        expect(document.activeElement).toBe(groupButton);
        expect(escapeReachedDocument).toBe(false);
    });

    test('groupFlyout_givenFocusLeavesTheGroup_closesFlyout', () => {
        // Arrange
        const $group = $rail.find('[data-draw-tool-group="markers"]');
        $group.find('.draw_tool_group_button')[0].dispatchEvent(new MouseEvent('click', {bubbles: true, detail: 0}));
        const focusInsideStaysOpen = (() => {
            $group.find('[data-draw-tool="polygon"]')[0].focus();
            return $group.find('.draw_tool_group_flyout').css('display') !== 'none';
        })();

        // Act
        $rail.children('[data-draw-tool="edit"]')[0].focus();

        // Assert
        expect(focusInsideStaysOpen).toBe(true);
        expect($group.find('.draw_tool_group_flyout').css('display')).toBe('none');
    });

    test('toolButton_givenSpacePressed_activatesTool', () => {
        // Arrange
        const polyline = $rail.children('[data-draw-tool="polyline"]')[0];

        // Act
        jQuery(polyline).trigger(jQuery.Event('keydown', {key: ' '}));

        // Assert
        expect(polyline.classList.contains('leaflet-draw-toolbar-button-enabled')).toBe(true);
        expect(polyline.getAttribute('aria-pressed')).toBe('true');
    });
    test('groupButton_givenOpened_positionsFlyoutBesideTheButton', () => {
        // Arrange
        const $group = $rail.find('[data-draw-tool-group="markers"]');
        $group.find('.draw_tool_group_button')[0].getBoundingClientRect = () => ({top: 120, right: 64, bottom: 168, left: 0});

        // Act
        $group.find('.draw_tool_group_button').trigger('click');

        // Assert
        const $flyout = $group.find('.draw_tool_group_flyout');
        expect($flyout.css('top')).toBe('120px');
        expect($flyout.css('left')).toBe('64px');
    });

    test('groupButton_givenOpenedNearTheBottom_keepsFlyoutOnScreen', () => {
        // Arrange
        const $group = $rail.find('[data-draw-tool-group="markers"]');
        const $flyout = $group.find('.draw_tool_group_flyout');
        $group.find('.draw_tool_group_button')[0].getBoundingClientRect = () => ({top: window.innerHeight - 50, right: 64, bottom: window.innerHeight, left: 0});
        $flyout[0].getBoundingClientRect = () => ({top: 0, right: 0, bottom: 200, left: 0, width: 240, height: 200});

        // Act
        $group.find('.draw_tool_group_button').trigger('click');

        // Assert
        expect($flyout.css('top')).toBe(`${window.innerHeight - 200}px`);
    });

    test('rail_givenScrolled_closesOpenFlyout', () => {
        // Arrange
        const $group = $rail.find('[data-draw-tool-group="markers"]');
        $group.find('.draw_tool_group_button').trigger('click');
        const openBeforeScroll = $group.find('.draw_tool_group_flyout').css('display') !== 'none';

        // Act
        jQuery('.route_manipulation_tools').trigger('scroll');

        // Assert
        expect(openBeforeScroll).toBe(true);
        expect($group.find('.draw_tool_group_flyout').css('display')).toBe('none');
        expect($group.find('.draw_tool_group_button').attr('aria-expanded')).toBe('false');
    });
});
