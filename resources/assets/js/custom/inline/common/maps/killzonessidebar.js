class CommonMapsKillzonessidebar extends InlineCode {


    constructor(options) {
        super(options);
        this.sidebar = new Sidebar(options);
        this.sidebar.activate();

        this._colorPickers = [];
    }

    _newPull() {
        let killZoneMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_KILLZONE);
        let killZone = killZoneMapObjectGroup.createNewPull();

        this._selectKillZone.bind($(``))
        // this._addKillZone(killZone);
    }

    _selectKillZone() {
        // Deselect all killzones
        $('#killzones_container .selected').removeClass('selected bg-primary');

        let map = getState().getDungeonMap();

        // Get the currently selected killzone ID, if any (so we may deselect it)
        let currentlySelectedKillZoneId = 0;
        let currentMapState = map.getMapState();
        if (currentMapState !== null && currentMapState instanceof KillZoneEnemySelection) {
            currentlySelectedKillZoneId = currentMapState.getMapObject().id;
        }

        let selectedKillZoneId = parseInt($(this).closest('.map_killzonessidebar_killzone').data('id'));
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

    /**
     * Initializes a color picker.
     * @param killZone
     * @returns {*}
     * @private
     */
    _initColorPicker(killZone) {
        // Simple example, see optional options for more configuration.
        return Pickr.create({
            el: `#map_killzonessidebar_killzone_${killZone.id}_color`,
            theme: 'nano', // 'classic' or 'monolith', or 'nano'

            default: killZone.color,

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
        let self = this;

        let template = Handlebars.templates['map_killzonessidebar_killzone_row_template'];

        let data = $.extend({
            'id': killZone.id,
            'text-class': 'text-white'
        }, getHandlebarsDefaultVariables());

        $(this.options.killZonesContainerSelector).append(
            $(template(data))
        );

        $(`#map_killzonessidebar_killzone_${killZone.id} .selectable`).bind('click', this._selectKillZone);
        $(`#map_killzonessidebar_killzone_${killZone.id}_color`).bind('click', function(){
            self._colorPickers[killZone.id].show();
        });
        $(`#map_killzonessidebar_killzone_${killZone.id}_delete`).bind('click', this._deleteKillZone);
        this._colorPickers[killZone.id] = this._initColorPicker(killZone);

        // Set some additional properties
        this._refreshKillZone(killZone);
    }

    /**
     *
     * @private
     */
    _deleteKillZone() {
        let selectedKillZoneId = parseInt($(this).closest('.map_killzonessidebar_killzone').data('id'));
        let killZoneMapObjectGroup = getState().getDungeonMap().mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_KILLZONE);
        let killZone = killZoneMapObjectGroup.findMapObjectById(selectedKillZoneId);

        killZone.register('object:deleted', '123123', function () {
            showSuccessNotification(lang.get('messages.object.deleted'));
            $('#map_killzonessidebar_killzone_' + killZone.id).remove();

            // Bit hacky?
            if (killZone.layer !== null) {
                getState().getDungeonMap().drawnLayers.removeLayer(killZone.layer);
                getState().getDungeonMap().editableLayers.removeLayer(killZone.layer);
            }

            killZone.unregister('object:deleted', '123123');
            killZone.cleanup();
        });
        killZone.delete();
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
            let killZone = killZoneAddedEvent.data.object;
            // Add the killzone to our list
            self._addKillZone(killZone);
            // Listen to changes in the killzone
            killZone.register(['killzone:enemyadded', 'killzone:enemyremoved'], self, function (killZoneChangedEvent) {
                self._refreshKillZone(killZoneChangedEvent.context);
            });
        });
        // If the killzone was deleted, get rid of our display too
        killZoneMapObjectGroup.register('object:deleted', this, function (killZoneDeletedEvent) {
            let killZone = killZoneAddedEvent.data.object;
            // Add the killzone to our list
            self._removeKillZone(killZone);
            // Stop listening to changes in the killzone
            killZone.unregister(['killzone:enemyadded', 'killzone:enemyremoved'], self);
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