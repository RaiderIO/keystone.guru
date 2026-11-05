// Box-selecting enemies on the mapping page and editing them through one popup. The enemies are real MapObjects,
// so the popup is the one MapObject#_getPopupHtml() renders from the real .handlebars sources, and the map state is
// the real EnemyMultiEditEnemySelection.
const fs = require('fs');
const path = require('path');
const Handlebars = require('handlebars');

global.L = require('leaflet');
global.window.L = global.L;
require('leaflet-draw');

global.$ = require('jquery');
global.$.fn.refreshTooltips = function () {
    return this;
};
global._ = require('lodash');

Handlebars.registerHelper('select', function (value, options) {
    let $el = global.$('<select />').html(options.fn(this));
    $el.find('[value="' + value + '"]').attr({'selected': 'selected'});
    return $el.html();
});
Handlebars.registerHelper('ifCond', function (v1, operator, v2, options) {
    return v1 === v2 ? options.fn(this) : options.inverse(this);
});

/**
 * @param name {String}
 * @returns {Function}
 */
function compileTemplate(name) {
    return Handlebars.compile(fs.readFileSync(path.join(__dirname, `../../handlebars/${name}.handlebars`), 'utf8'));
}

global.Handlebars = {
    templates: Object.fromEntries([
        'map_popup_template',
        'map_popup_type_category_template',
        'map_popup_type_select_template',
        'map_popup_type_bool_template',
        'map_popup_type_number_template',
        'map_popup_type_text_template',
    ].map((name) => [name, compileTemplate(name)])),
};

global.isUserAdmin = true;
global.csrfToken = 'token';
global.getHandlebarsDefaultVariables = require('../util').getHandlebarsDefaultVariables;
global.refreshSelectPickers = () => {
};
global.showInfoNotification = vi.fn();
global.Cookies = {get: () => undefined, set: vi.fn()};
global.bootstrap = {Collapse: {getOrCreateInstance: () => ({show: vi.fn(), hide: vi.fn()})}};
global.getState = () => ({isMapAdmin: () => true});
global.lang = {
    get: (key, replacements = {}) => Object.keys(replacements).length > 0 ? `${key} ${JSON.stringify(replacements)}` : key,
};

global.Signalable = require('../signalable').Signalable;
global.DungeonMap = class DungeonMap extends global.Signalable {
};
global.Attribute = require('../models/attribute').Attribute;
global.MapObject = require('../models/mapobject').MapObject;
global.MapState = require('../mapstate/mapstate').MapState;
global.MapObjectMapState = require('../mapstate/mapobjectmapstate').MapObjectMapState;
global.EnemySelection = require('../mapstate/enemyselection/enemyselection').EnemySelection;
global.EnemyMultiEditEnemySelection =
    require('../mapstate/enemyselection/enemymultieditenemyselection').EnemyMultiEditEnemySelection;

const {AdminEnemyMultiEdit, ENEMY_MULTI_SELECT_DRAWN_EVENT} = require('./adminenemymultiedit');

class TestEnemy extends global.MapObject {
    constructor(map, {id, latLng, values = {}, editable = true, visibleOnMap = true}) {
        super(map, L.marker(latLng), {name: 'enemy'});

        this.id = id;
        this.editable = editable;
        this.visibleOnMap = visibleOnMap;
        this.selectable = false;
        Object.assign(this, values);
        this.save = vi.fn();
    }

    _getAttributes(force = false) {
        if (this._cachedAttributes !== null && !force) {
            return this._cachedAttributes;
        }

        return this._cachedAttributes = super._getAttributes(force).concat([
            new Attribute({
                name: 'npc_id',
                type: 'select',
                admin: true,
                values: [{id: 1, name: 'Npc one'}, {id: 2, name: 'Npc two'}],
                default: null,
            }),
            new Attribute({name: 'skippable', type: 'bool', admin: true, default: false}),
            new Attribute({name: 'enemy_forces_override', type: 'int', admin: true, default: null}),
            new Attribute({name: 'mdt_id', type: 'int', admin: true, bulk_edit: false, default: null}),
        ]);
    }

    isEditable() {
        return this.editable;
    }

    isSelectable() {
        return this.selectable;
    }

    setSelectable(value) {
        this.selectable = value;
    }
}

/**
 * @returns {DungeonMap} With a real Leaflet map, and DungeonMap's map state switching
 */
function makeMap() {
    let map = new global.DungeonMap();
    let container = document.createElement('div');
    document.body.appendChild(container);

    map.options = {
        edit: false,
        readonly: false,
        factions: [{key: 'any', description: 'Any'}, {key: 'horde', description: 'Horde'}],
        teemingOptions: [],
    };
    map.leafletMap = L.map(container, {crs: L.CRS.Simple}).setView([0, 0], 0);
    map.editableLayers = {getLayers: () => []};
    map.enemies = [];
    map.mapObjectGroupManager = {
        getEnemyMapObjectGroup: () => ({
            objects: map.enemies,
            isMapObjectVisible: (enemy) => enemy.visibleOnMap,
        }),
    };
    map.mapState = null;
    map.getMapState = () => map.mapState;
    map.setMapState = function (mapState) {
        if (this.mapState !== null && this.mapState.isStarted() && !this.mapState.isStopped()) {
            this.mapState.stop();
        }

        let previousMapState = this.mapState;
        this.mapState = mapState;
        this.signal('map:mapstatechanged', {previousMapState: previousMapState, newMapState: mapState});
        if (mapState !== null) {
            mapState.start();
        }
    };

    return map;
}

/**
 * @param map {DungeonMap}
 * @param enemies {Object[]} TestEnemy options
 * @returns {TestEnemy[]}
 */
function addEnemies(map, enemies) {
    map.enemies.push(...enemies.map((enemy) => new TestEnemy(map, enemy)));

    return map.enemies;
}

/**
 * @param name {String}
 * @returns {jQuery}
 */
function popupField(name) {
    return $(`#map_enemy_edit_popup_${name}_multi`);
}

describe('AdminEnemyMultiEdit', () => {
    let map;
    let multiEdit;

    beforeEach(() => {
        document.body.innerHTML = '';
        global.showInfoNotification.mockClear();
        map = makeMap();
        multiEdit = new AdminEnemyMultiEdit(map);
    });

    afterEach(() => {
        multiEdit.cleanup();
        map.leafletMap.remove();
    });

    test('findEnemiesInBounds_givenEnemiesInAndOutsideTheBox_returnsOnlyVisibleEditableEnemiesInside', () => {
        // Arrange
        let [inside, , , , insideToo] = addEnemies(map, [
            {id: 1, latLng: [10, 10]},
            {id: 2, latLng: [50, 50]},
            {id: 3, latLng: [12, 12], editable: false},
            {id: 4, latLng: [14, 14], visibleOnMap: false},
            {id: 5, latLng: [18, 18]},
        ]);

        // Act
        let result = multiEdit.findEnemiesInBounds(L.latLngBounds([0, 0], [20, 20]));

        // Assert
        expect(result).toEqual([inside, insideToo]);
    });

    test('drawnEvent_givenABox_opensThePopupForTheEnemiesInsideIt', () => {
        // Arrange
        let [inside, outside] = addEnemies(map, [
            {id: 1, latLng: [10, 10]},
            {id: 2, latLng: [50, 50]},
        ]);

        // Act
        map.leafletMap.fire(ENEMY_MULTI_SELECT_DRAWN_EVENT, {bounds: L.latLngBounds([0, 0], [20, 20])});

        // Assert
        expect(multiEdit.enemies).toEqual([inside]);
        expect(map.getMapState()).toBeInstanceOf(EnemyMultiEditEnemySelection);
        expect(inside.isSelectable()).toBe(true);
        expect(outside.isSelectable()).toBe(false);
        expect(document.body.textContent).toContain('js.enemy_multi_edit_title {"count":1}');
    });

    test('select_givenNoEnemies_showsANotificationInsteadOfAPopup', () => {
        // Act
        multiEdit.select([], L.latLng(0, 0));

        // Assert
        expect(global.showInfoNotification).toHaveBeenCalledWith('js.enemy_multi_edit_no_enemies_selected');
        expect(map.getMapState()).toBeNull();
        expect($('#map_enemy_edit_popup_submit_multi')).toHaveLength(0);
    });

    test('select_givenAnAttributeThatIsNotBulkEditable_leavesItOutOfThePopup', () => {
        // Arrange
        let enemies = addEnemies(map, [{id: 1, latLng: [10, 10]}, {id: 2, latLng: [11, 11]}]);

        // Act
        multiEdit.select(enemies, L.latLng(10, 10));

        // Assert
        expect(popupField('npc_id')).toHaveLength(1);
        expect(popupField('mdt_id')).toHaveLength(0);
    });

    test('submit_givenOneChangedField_appliesOnlyThatFieldToEverySelectedEnemy', () => {
        // Arrange
        let enemies = addEnemies(map, [
            {id: 1, latLng: [10, 10], values: {npc_id: 1, faction: 'horde', enemy_forces_override: 3}},
            {id: 2, latLng: [11, 11], values: {npc_id: 1, faction: 'any', enemy_forces_override: 5}},
        ]);
        multiEdit.select(enemies, L.latLng(10, 10));

        // Act
        popupField('npc_id').val('2');
        $('#map_enemy_edit_popup_submit_multi').trigger('click');

        // Assert
        expect(enemies.map((enemy) => enemy.npc_id)).toEqual([2, 2]);
        expect(enemies.map((enemy) => enemy.faction)).toEqual(['horde', 'any']);
        expect(enemies.map((enemy) => enemy.enemy_forces_override)).toEqual([3, 5]);
        expect(enemies.map((enemy) => enemy.save.mock.calls.length)).toEqual([1, 1]);
    });

    test('submit_givenNothingChanged_savesNoEnemy', () => {
        // Arrange
        let enemies = addEnemies(map, [
            {id: 1, latLng: [10, 10], values: {npc_id: 1, skippable: true}},
            {id: 2, latLng: [11, 11], values: {npc_id: 2, skippable: false}},
        ]);
        multiEdit.select(enemies, L.latLng(10, 10));

        // Act
        multiEdit.submit();

        // Assert
        expect(enemies.map((enemy) => enemy.save.mock.calls.length)).toEqual([0, 0]);
        expect(enemies.map((enemy) => enemy.npc_id)).toEqual([1, 2]);
    });

    test('select_givenEnemiesThatShareAValue_showsThatValue', () => {
        // Arrange
        let enemies = addEnemies(map, [
            {id: 1, latLng: [10, 10], values: {npc_id: 2, enemy_forces_override: 7}},
            {id: 2, latLng: [11, 11], values: {npc_id: 2, enemy_forces_override: 7}},
        ]);

        // Act
        multiEdit.select(enemies, L.latLng(10, 10));

        // Assert
        expect(popupField('npc_id').val()).toBe('2');
        expect(popupField('enemy_forces_override').val()).toBe('7');
        expect(document.body.textContent).not.toContain('js.enemy_multi_edit_mixed_values_description');
    });

    test('select_givenEnemiesThatDifferInASelect_showsItAsMixed', () => {
        // Arrange
        let enemies = addEnemies(map, [
            {id: 1, latLng: [10, 10], values: {npc_id: 1, faction: 'horde'}},
            {id: 2, latLng: [11, 11], values: {npc_id: 2, faction: 'horde'}},
        ]);

        // Act
        multiEdit.select(enemies, L.latLng(10, 10));

        // Assert
        expect(popupField('npc_id').find('option:selected').text()).toBe('js.enemy_multi_edit_mixed_values_label');
        expect(popupField('faction').val()).toBe('horde');
        expect($('#map_enemy_edit_popup_npc_id_multi_description').text()).toContain('js.enemy_multi_edit_mixed_values_description');
    });

    test('submit_givenAMixedCheckboxTheMapperTouched_appliesTheValueItShowsToEveryEnemy', () => {
        // Arrange
        let enemies = addEnemies(map, [
            {id: 1, latLng: [10, 10], values: {skippable: true}},
            {id: 2, latLng: [11, 11], values: {skippable: false}},
        ]);
        multiEdit.select(enemies, L.latLng(10, 10));
        let $skippable = popupField('skippable');

        // Act
        $skippable.prop('checked', true).trigger('change');
        $skippable.prop('checked', false).trigger('change');
        multiEdit.submit();

        // Assert
        expect(enemies.map((enemy) => enemy.skippable)).toEqual([0, 0]);
        expect(enemies.map((enemy) => enemy.save.mock.calls.length)).toEqual([1, 1]);
    });

    test('submit_givenAnUnmixedCheckboxTheMapperToggledBack_savesNoEnemy', () => {
        // Arrange
        let enemies = addEnemies(map, [
            {id: 1, latLng: [10, 10], values: {skippable: true}},
            {id: 2, latLng: [11, 11], values: {skippable: true}},
        ]);
        multiEdit.select(enemies, L.latLng(10, 10));
        let $skippable = popupField('skippable');

        // Act
        $skippable.prop('checked', false).trigger('change');
        $skippable.prop('checked', true).trigger('change');
        multiEdit.submit();

        // Assert
        expect(enemies.map((enemy) => enemy.save.mock.calls.length)).toEqual([0, 0]);
    });

    test('select_givenAMixedCheckbox_showsItIndeterminate', () => {
        // Arrange
        let enemies = addEnemies(map, [
            {id: 1, latLng: [10, 10], values: {skippable: true}},
            {id: 2, latLng: [11, 11], values: {skippable: false}},
        ]);

        // Act
        multiEdit.select(enemies, L.latLng(10, 10));

        // Assert
        expect(popupField('skippable').prop('indeterminate')).toBe(true);
    });

    test('closingThePopup_givenAnOpenSelection_endsTheMapStateAndDeselectsTheEnemies', () => {
        // Arrange
        let enemies = addEnemies(map, [{id: 1, latLng: [10, 10]}]);
        multiEdit.select(enemies, L.latLng(10, 10));

        // Act
        map.leafletMap.closePopup();

        // Assert
        expect(map.getMapState()).toBeNull();
        expect(enemies[0].isSelectable()).toBe(false);
    });

    test('mapStateChange_givenAnOpenSelection_closesThePopup', () => {
        // Arrange
        let enemies = addEnemies(map, [{id: 1, latLng: [10, 10]}]);
        multiEdit.select(enemies, L.latLng(10, 10));

        // Act
        map.setMapState(null);

        // Assert
        expect($('#map_enemy_edit_popup_submit_multi')).toHaveLength(0);
    });

    test('select_givenAnotherSelectionWasStillOpen_keepsTheNewSelectionActive', () => {
        // Arrange
        let [first, second] = addEnemies(map, [{id: 1, latLng: [10, 10]}, {id: 2, latLng: [50, 50]}]);
        multiEdit.select([first], L.latLng(10, 10));

        // Act
        multiEdit.select([second], L.latLng(50, 50));

        // Assert
        expect(map.getMapState()).toBeInstanceOf(EnemyMultiEditEnemySelection);
        expect(map.getMapState().enemies).toEqual([second]);
        expect($('#map_enemy_edit_popup_submit_multi')).toHaveLength(1);
        expect(first.isSelectable()).toBe(false);
        expect(second.isSelectable()).toBe(true);

        map.setMapState(null);
        expect($('#map_enemy_edit_popup_submit_multi')).toHaveLength(0);
    });
});

describe('L.Draw.EnemyMultiSelect', () => {
    test('fireCreatedEvent_givenADrawnBox_handsOnItsBoundsWithoutCreatingALayer', () => {
        // Arrange
        let container = document.createElement('div');
        document.body.appendChild(container);
        let leafletMap = L.map(container, {crs: L.CRS.Simple}).setView([0, 0], 0);
        let handler = new L.Draw.EnemyMultiSelect(leafletMap, {});
        let bounds = L.latLngBounds([0, 0], [20, 20]);
        handler._shape = L.rectangle(bounds);
        let drawnListener = vi.fn();
        let createdListener = vi.fn();
        leafletMap.on(ENEMY_MULTI_SELECT_DRAWN_EVENT, drawnListener);
        leafletMap.on(L.Draw.Event.CREATED, createdListener);

        // Act
        handler._fireCreatedEvent();

        // Assert
        expect(handler.type).toBe('enemymultiselect');
        expect(drawnListener).toHaveBeenCalledTimes(1);
        expect(drawnListener.mock.calls[0][0].bounds.equals(bounds)).toBe(true);
        expect(createdListener).not.toHaveBeenCalled();

        leafletMap.remove();
    });
});
