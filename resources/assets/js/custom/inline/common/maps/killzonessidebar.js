class CommonMapsKillzonessidebar extends InlineCode {


    constructor(options) {
        super(options);
        this.sidebar = new Sidebar(options);
        this.sidebar.activate();
    }

    _newPull() {
        let killZoneMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_KILLZONE);
        let killZone = killZoneMapObjectGroup.createNewPull();

        // this._addKillZone(killZone);
    }

    _selectKillZone() {
        $('.map_killzonessidebar_killzone').removeClass('selected bg-primary');

        let map = getState().getDungeonMap();

        // Get the currently selected killzone ID, if any
        let currentlySelectedKillZoneId = 0;
        let currentMapState = map.getMapState();
        if (currentMapState !== null && currentMapState instanceof KillZoneEnemySelection) {
            currentlySelectedKillZoneId = currentMapState.getMapObject().id;
        }

        let selectedKillZoneId = parseInt($(this).data('id'));
        if (selectedKillZoneId !== currentlySelectedKillZoneId) {
            $(this).addClass('selected bg-primary');
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

    _initColorPicker() {
        // Simple example, see optional options for more configuration.
        const pickr = Pickr.create({
            el: '.color-picker',
            theme: 'classic', // or 'monolith', or 'nano'

            swatches: [
                'rgba(244, 67, 54, 1)',
                'rgba(233, 30, 99, 0.95)',
                'rgba(156, 39, 176, 0.9)',
                'rgba(103, 58, 183, 0.85)',
                'rgba(63, 81, 181, 0.8)',
                'rgba(33, 150, 243, 0.75)',
                'rgba(3, 169, 244, 0.7)',
                'rgba(0, 188, 212, 0.7)',
                'rgba(0, 150, 136, 0.75)',
                'rgba(76, 175, 80, 0.8)',
                'rgba(139, 195, 74, 0.85)',
                'rgba(205, 220, 57, 0.9)',
                'rgba(255, 235, 59, 0.95)',
                'rgba(255, 193, 7, 1)'
            ],

            components: {

                // Main components
                preview: true,
                opacity: true,
                hue: true,

                // Input / output Options
                interaction: {
                    hex: true,
                    rgba: true,
                    hsla: true,
                    hsva: true,
                    cmyk: true,
                    input: true,
                    clear: true,
                    save: true
                }
            }
        });
    }

    /**
     * Adds a killzone to the sidebar.
     * @param killZone
     * @private
     */
    _addKillZone(killZone) {
        console.assert(this instanceof CommonMapsKillzonessidebar, 'this is not a CommonMapsKillzonessidebar', this);
        let template = Handlebars.templates['map_killzonessidebar_killzone_row_template'];

        let data = $.extend({
            'id': killZone.id,
            'text-class': 'text-white'
        }, getHandlebarsDefaultVariables());

        $(this.options.killZonesContainerSelector).append(
            $(template(data))
        );

        $('#map_killzonessidebar_killzone_' + killZone.id).bind('click', this._selectKillZone);
        // Set some additional properties
        this._refreshKillZone(killZone);
    }

    /**
     * Removes a killzone from the sidebar.
     * @param killZone
     * @private
     */
    _removeKillZone(killZone) {
        $('#map_killzonessidebar_killzone_' + killZone.id).remove();
    }

    /**
     * Should be called whenever something's changed in the killzone that warrants a UI update
     * @param killZone
     * @private
     */
    _refreshKillZone(killZone) {
        console.log('refreshing!');

        let enemyForcesPercent = (killZone.getEnemyForces() / this.map.getEnemyForcesRequired()) * 100;
        enemyForcesPercent = Math.floor(enemyForcesPercent * 100) / 100;

        $(`#map_killzonessidebar_killzone_${killZone.id}_text`)
            .text(`${killZone.enemies.length} enemies, ${enemyForcesPercent}%`);

        $(`#map_killzonessidebar_killzone_${killZone.id}_color`)
            .css('background-color', killZone.color);
    }

    /**
     *
     */
    activate() {
        console.assert(this instanceof CommonMapsKillzonessidebar, 'this is not a CommonMapsKillzonessidebar', this);
        this.map = getState().getDungeonMap();

        let self = this;

        // Setup new pull button

        let $newPullBtn = $(this.options.newKillZoneSelector);
        $newPullBtn.bind('click', this._newPull.bind(this));

        let killZoneMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_KILLZONE);
        killZoneMapObjectGroup.register('object:add', this, function (killZoneAddedEvent) {
            // Add the killzone to our list
            self._addKillZone(killZoneAddedEvent.data.object);
            // Listen to changes in the killzone
            killZoneAddedEvent.data.object.register(['killzone:enemyadded', 'killzone:enemyremoved'], self, function (killZoneChangedEvent) {
                self._refreshKillZone(killZoneChangedEvent.context);
            });
        });
        // If the killzone was deleted, get rid of our display too
        killZoneMapObjectGroup.register('object:deleted', this, function (killZoneDeletedEvent) {
            // Add the killzone to our list
            self._removeKillZone(killZoneDeletedEvent.data.object);
            // Stop listening to changes in the killzone
            killZoneDeletedEvent.data.object.unregister(['killzone:enemyadded', 'killzone:enemyremoved'], self);
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