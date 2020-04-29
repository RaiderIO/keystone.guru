class CommonMapsKillzonessidebar extends InlineCode {


    constructor(options) {
        super(options);
        this.sidebar = new Sidebar(options);
        this.sidebar.activate();
    }

    /**
     * Adds a killzone to the sidebar.
     * @param killZone
     * @private
     */
    _addKillZone(killZone) {
        let template = Handlebars.templates['map_killzonessidebar_killzone_row_template'];

        let data = $.extend({
            color: killZone.color
        }, getHandlebarsDefaultVariables());

        $(this.options.killZonesContainerSelector).append(
            $(template(data))
        );
    }

    /**
     *
     */
    activate() {
        let self = this;

        let killZoneMapObjectGroup = getState().getDungeonMap().mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_KILLZONE);
        killZoneMapObjectGroup.register('object:add', this, function(killZoneAddedEvent){
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