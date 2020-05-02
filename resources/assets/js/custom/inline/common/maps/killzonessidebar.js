class CommonMapsKillzonessidebar extends InlineCode {


    constructor(options) {
        super(options);
        this.sidebar = new Sidebar(options);
        this.sidebar.activate();
    }

    _selectKillZone() {
        $('.map_killzonessidebar_killzone').removeClass('selected');

        let map = getState().getDungeonMap();

        // Get the currently selected killzone ID, if any
        let currentlySelectedKillZoneId = 0;
        let currentMapState = map.getMapState();
        if (currentMapState !== null && currentMapState instanceof KillZoneEnemySelection) {
            currentlySelectedKillZoneId = currentMapState.getMapObject().id;
        }

        let selectedKillZoneId = parseInt($(this).data('id'));
        if (selectedKillZoneId !== currentlySelectedKillZoneId) {
            $(this).addClass('selected');
        } else {
            selectedKillZoneId = 0;
        }

        let newMapState = null;

        // Find the killzone and if found, switch our map to a selection for that killzone
        if (selectedKillZoneId > 0) {
            let killZoneMapObjectGroup = map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_KILLZONE);
            let killZone = killZoneMapObjectGroup.findMapObjectById(selectedKillZoneId);
            if (killZone !== null) {
                newMapState = new KillZoneEnemySelection(map, killZone);
            }
        }

        // Either de-select, or add a new state to the map
        map.setMapState(newMapState);
    }

    /**
     * Adds a killzone to the sidebar.
     * @param killZone
     * @private
     */
    _addKillZone(killZone) {
        console.assert(this instanceof CommonMapsKillzonessidebar, 'this is not a CommonMapsKillzonessidebar', this);
        let template = Handlebars.templates['map_killzonessidebar_killzone_row_template'];

        let enemyForcesPercent = (killZone.getEnemyForces() / this.map.getEnemyForcesRequired()) * 100;
        enemyForcesPercent = Math.floor(enemyForcesPercent * 100) / 100;
        let data = $.extend({
            'id': killZone.id,
            'color': killZone.color,
            'text': `${killZone.enemies.length} enemies, ${enemyForcesPercent}%`,
            'text-class': isColorDark(killZone.color) ? 'text-white' : 'text-dark'
        }, getHandlebarsDefaultVariables());

        $(this.options.killZonesContainerSelector).append(
            $(template(data))
        );

        $('#map_killzonessidebar_killzone_' + killZone.id).bind('click', this._selectKillZone);
    }

    /**
     *
     */
    activate() {
        console.assert(this instanceof CommonMapsKillzonessidebar, 'this is not a CommonMapsKillzonessidebar', this);
        this.map = getState().getDungeonMap();

        let self = this;

        let killZoneMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_KILLZONE);
        killZoneMapObjectGroup.register('object:add', this, function (killZoneAddedEvent) {
            self._addKillZone(killZoneAddedEvent.data.object);
        });

        $(this.options.newKillZoneSelector).bind('click', function () {
            console.log('new pull!');
        });
    }

    /**
     *
     */
    cleanup() {
        console.assert(this instanceof CommonMapsKillzonessidebar, 'this is not a CommonMapsKillzonessidebar', this);

        let killZoneMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_KILLZONE);
        killZoneMapObjectGroup.unregister('object:add', this);
    }
}