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

    beforeEach(() => {
        previousLang = global.lang;
        previousGetState = global.getState;
        global.lang = {
            get: (key, params = {}) => params.hotkey ? `${key}(${params.hotkey})` : key,
            messages: {},
        };
        global.getState = () => ({
            addSnackbar: () => 'snackbar',
            removeSnackbar: () => {
            },
        });

        document.body.innerHTML = '<div id="edit_route_draw_container"></div><div id="outside"></div>';
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
        jQuery(document).off('.drawtoolgroups');
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
        expect($polyline.find('[data-bs-toggle="tooltip"]').attr('title')).toBe('js.polyline_title(1 / P)');
        expect($polyline.attr('aria-label')).toBe('js.polyline');
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
});
