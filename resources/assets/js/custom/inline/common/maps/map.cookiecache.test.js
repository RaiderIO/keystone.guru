// #4409: StateManager caches the map display settings it reads out of document.cookie, so anything
// that writes one of those cookies without going through a StateManager setter has to drop that
// cache itself. _initDefaults() is exactly such a writer - it fills in every map cookie with
// Cookies.set directly - and it is also where the cross-tab escape hatch is wired up.
//
// Follows the global-script recipe from map.favorite.test.js: stub what the class body touches at
// load time, then require the source.

globalThis.$ = globalThis.jQuery = require('jquery');

const {InlineCode} = require('../../inlinecode');
globalThis.InlineCode = InlineCode;

globalThis.SettingsTabMap = class SettingsTabMap {
};
globalThis.SettingsTabPull = class SettingsTabPull {
};

const {CommonMapsMap} = require('./map');

globalThis.ENVIRONMENT_LOCAL = 'local';

describe('CommonMapsMap._initDefaults cookie cache handling', () => {
    let invalidateCookieCache;
    let cookieJar;

    beforeEach(() => {
        cookieJar = new Map();
        invalidateCookieCache = vi.fn();

        globalThis.cookieDefaultAttributes = undefined;
        globalThis.Cookies = {
            get: (key) => cookieJar.get(key),
            set: (key, value) => cookieJar.set(key, `${value}`),
            withAttributes: () => undefined,
        };

        globalThis.getState = () => ({
            getMapContext: () => ({getEnvironment: () => ENVIRONMENT_LOCAL}),
            invalidateCookieCache: invalidateCookieCache,
        });
    });

    afterEach(() => {
        vi.clearAllMocks();
    });

    /**
     * _initDefaults() only needs `this` for nothing at all, but calling it off the prototype keeps
     * the constructor (and its sidebar collaborators) out of the way.
     */
    function initDefaults() {
        CommonMapsMap.prototype._initDefaults.call({});
    }

    test('_initDefaults_givenAFreshPage_invalidatesTheCookieCacheAfterWritingTheDefaults', () => {
        // Without this the very first read of a setting could be cached from before the defaults
        // were written, and would then be served for the rest of the session.
        let cookieAtInvalidation;
        invalidateCookieCache.mockImplementation(() => {
            cookieAtInvalidation = cookieJar.get('map_enemy_dangerous_border');
        });

        initDefaults();

        expect(invalidateCookieCache).toHaveBeenCalledTimes(1);
        expect(cookieAtInvalidation).toBe('0');
    });

    test('_initDefaults_givenAFreshPage_writesTheSameDefaultsAsTheMapSettingsForm', () => {
        // mapsettings.blade.php renders its checkboxes from these cookies with its own fallbacks;
        // the two must agree or the form contradicts the map on first load.
        initDefaults();

        expect(cookieJar.get('map_enemy_dangerous_border')).toBe('0');
        expect(cookieJar.get('map_number_style')).toBe('enemy_forces');
    });

    test('_initDefaults_givenExistingCookies_keepsTheUsersChoice', () => {
        cookieJar.set('map_enemy_dangerous_border', '1');
        cookieJar.set('map_number_style', 'percentage');

        initDefaults();

        expect(cookieJar.get('map_enemy_dangerous_border')).toBe('1');
        expect(cookieJar.get('map_number_style')).toBe('percentage');
    });

    test('_initDefaults_givenAFreshPage_hidesMountableAreasOnce', () => {
        // Arrange: no cookies at all

        // Act
        initDefaults();

        // Assert
        expect(JSON.parse(cookieJar.get('hidden_map_object_groups'))).toEqual(['mountablearea']);
        expect(cookieJar.get('hidden_map_object_groups_added_mountablearea')).toBe('1');
    });

    test('_initDefaults_givenHiddenGroupsFromBeforeMountableAreas_hidesMountableAreasOnce', () => {
        cookieJar.set('hidden_map_object_groups', '["enemypatrol"]');

        initDefaults();

        expect(JSON.parse(cookieJar.get('hidden_map_object_groups'))).toEqual(['enemypatrol', 'mountablearea']);
        expect(cookieJar.get('hidden_map_object_groups_added_mountablearea')).toBe('1');
    });

    test('_initDefaults_givenMountableAreasAlreadyHandled_keepsTheUsersHiddenGroups', () => {
        cookieJar.set('hidden_map_object_groups', '["enemypatrol"]');
        cookieJar.set('hidden_map_object_groups_added_mountablearea', '1');

        initDefaults();

        expect(JSON.parse(cookieJar.get('hidden_map_object_groups'))).toEqual(['enemypatrol']);
    });

    test('_initDefaults_givenAnEmptyHiddenGroupsCookie_refillsIt', () => {
        cookieJar.set('hidden_map_object_groups', '');
        cookieJar.set('hidden_map_object_groups_added_mountablearea', '1');

        initDefaults();

        expect(cookieJar.get('hidden_map_object_groups')).toBe('["mountablearea"]');
    });

    test('_initDefaults_givenTheTabIsFocusedAgain_invalidatesTheCookieCache', () => {
        // Another tab may have changed a display setting in the shared cookie while this one was in
        // the background; cookies have no change event, so focus is the trigger.
        initDefaults();
        invalidateCookieCache.mockClear();

        window.dispatchEvent(new Event('focus'));

        expect(invalidateCookieCache).toHaveBeenCalledTimes(1);
    });
});

describe('CommonMapsMap._mapObjectGroupVisibilityChanged hidden groups cookie', () => {
    let cookieJar;

    /**
     * @param {String} name
     * @param {Boolean} userToggleable
     * @param {Boolean} shown
     */
    function fakeGroup(name, userToggleable = true, shown = true) {
        let group = new globalThis.MapObjectGroup();
        group.name = name;
        group.isUserToggleable = () => userToggleable;
        group.isShown = () => shown;
        group.setVisibility = vi.fn();
        return group;
    }

    /**
     * @param {Object} groupsOnThisMap Map object groups available on the current map, keyed by name
     * @param {Array} hiddenInDropdown Names of the groups the user has toggled off in the dropdown
     */
    function visibilityChanged(groupsOnThisMap, hiddenInDropdown) {
        let $dropdown = $('<div>', {id: 'map_map_object_group_visibility_dropdown'});
        for (let name in groupsOnThisMap) {
            $dropdown.append($('<a>', {
                class: hiddenInDropdown.includes(name) ? 'dropdown-item' : 'dropdown-item active',
                'data-group': name,
            }));
        }
        $('body').append($dropdown);

        globalThis.getMapObjectGroup = (name) => groupsOnThisMap[name];

        CommonMapsMap.prototype._mapObjectGroupVisibilityChanged.call({});
    }

    beforeEach(() => {
        cookieJar = new Map();

        globalThis.cookieDefaultAttributes = undefined;
        globalThis.Cookies = {
            get: (key) => cookieJar.get(key),
            set: (key, value) => cookieJar.set(key, `${value}`),
        };
        globalThis.MapObjectGroup = class MapObjectGroup {
        };
        globalThis.MAP_OBJECT_GROUP_NAMES = ['killzone', 'brushline', 'mountablearea'];
    });

    afterEach(() => {
        $('#map_map_object_group_visibility_dropdown').remove();
    });

    test('_mapObjectGroupVisibilityChanged_givenANewlyHiddenGroup_keepsTheHiddenSettingOfAGroupNotOnThisMap', () => {
        // Arrange
        cookieJar.set('hidden_map_object_groups', '["brushline","mountablearea"]');
        let groupsOnThisMap = {killzone: fakeGroup('killzone'), brushline: fakeGroup('brushline')};

        // Act
        visibilityChanged(groupsOnThisMap, ['killzone', 'brushline']);

        // Assert
        expect(JSON.parse(cookieJar.get('hidden_map_object_groups'))).toEqual(['killzone', 'brushline', 'mountablearea']);
    });

    test('_mapObjectGroupVisibilityChanged_givenAGroupShownAgain_removesItFromTheCookie', () => {
        // Arrange
        cookieJar.set('hidden_map_object_groups', '["brushline","mountablearea"]');
        let groupsOnThisMap = {killzone: fakeGroup('killzone'), brushline: fakeGroup('brushline')};

        // Act
        visibilityChanged(groupsOnThisMap, []);

        // Assert
        expect(JSON.parse(cookieJar.get('hidden_map_object_groups'))).toEqual(['mountablearea']);
        expect(groupsOnThisMap.brushline.setVisibility).toHaveBeenCalledWith(true);
    });

    test('_mapObjectGroupVisibilityChanged_givenAHiddenGroupNotToggleableByTheUser_storesItAsHidden', () => {
        // Arrange
        let groupsOnThisMap = {killzone: fakeGroup('killzone', false, false), brushline: fakeGroup('brushline')};

        // Act
        visibilityChanged(groupsOnThisMap, []);

        // Assert
        expect(JSON.parse(cookieJar.get('hidden_map_object_groups'))).toEqual(['killzone']);
        expect(groupsOnThisMap.killzone.setVisibility).toHaveBeenCalledWith(false);
    });
});
