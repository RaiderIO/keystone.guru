class CommonMapsKillzonessidebar extends InlineCode {


    constructor(options) {
        super(options);
        this.sidebar = new Sidebar(options);
        this.sidebar.activate();
    }

    _selectKillZone() {
        $('.map_killzonessidebar_killzone').removeClass('selected');
        $(this).addClass('selected');

        getState().getDungeonMap().setSelectedKillZoneId($(this).data('id'));
    }

    /**
     * Adds a killzone to the sidebar.
     * @param killZone
     * @private
     */
    _addKillZone(killZone) {
        let template = Handlebars.templates['map_killzonessidebar_killzone_row_template'];

        let enemyForcesPercent = (killZone.getEnemyForces() / getState().getDungeonMap().getEnemyForcesRequired()) * 100;
        enemyForcesPercent = Math.floor(enemyForcesPercent * 100) / 100;
        let data = $.extend({
            id: killZone.id,
            color: killZone.color,
            text: `${killZone.enemies.length} enemies, ${enemyForcesPercent}%`
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
        let self = this;

        let killZoneMapObjectGroup = getState().getDungeonMap().mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_KILLZONE);
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
        let killZoneMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_KILLZONE);
        killZoneMapObjectGroup.unregister('object:add', this);
    }
}