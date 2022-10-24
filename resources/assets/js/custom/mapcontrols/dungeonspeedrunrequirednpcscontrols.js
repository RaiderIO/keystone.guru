class DungeonSpeedrunRequiredNpcsControls extends MapControl {
    constructor(map) {
        super(map);
        console.assert(this instanceof DungeonSpeedrunRequiredNpcsControls, 'this is not DungeonSpeedrunRequiredNpcsControls', this);

        let self = this;

        this.loaded = false;
        this.map = map;

        // On route load, this will also fill the enemy forces to the value they should be as the route is loaded
        let killZoneMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_KILLZONE);
        // May be null in admin setting where there's no kill zones
        if (killZoneMapObjectGroup !== false) {
            killZoneMapObjectGroup.register('killzone:enemyadded', this, function (addEvent) {
                self._onEnemySelectionChanged(addEvent);
            });
            killZoneMapObjectGroup.register('killzone:enemyremoved', this, function (removedEvent) {
                self._onEnemySelectionChanged(removedEvent);
            });
            // killZoneMapObjectGroup.register('object:add', this, function (addEvent) {
            //     addEvent.data.object.register('killzone:changed', self, self._onKillZoneChanged.bind(self));
            // });
        }


        this.mapControlOptions = {
            onAdd: function (leafletMap) {
                let template = Handlebars.templates['map_dungeon_speedrun_required_npcs_template'];

                // Build the status bar from the template
                self.statusbar = $(template({}));

                self.statusbar = self.statusbar[0];

                self.refreshUI();

                return self.statusbar;
            }
        };

        $('#edit_route_dungeon_speedrun_required_npcs_collapse').on('show.bs.collapse', function () {
              getState().setDungeonSpeedrunRequiredNpcsShowAllEnabled(true);
        }).on('hide.bs.collapse', function () {
              getState().setDungeonSpeedrunRequiredNpcsShowAllEnabled(false);
        });

        this.loaded = true;
    }

    /**
     * Triggers the change of a selected enemy, triggers UI update
     * @param changedEvent {Object}
     * @private
     */
    _onEnemySelectionChanged(changedEvent) {
        console.assert(this instanceof DungeonSpeedrunRequiredNpcsControls, 'this is not DungeonSpeedrunRequiredNpcsControls', this);

        this.refreshUI();
    }

    /**
     *
     * @param npcId {Number}
     * @private
     * @return {Number}
     */
    _getKilledEnemiesByNpcId(npcId) {
        let result = 0;

        let killZoneMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_KILLZONE);
        let enemyMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_ENEMY);
        for (let killZoneKey in killZoneMapObjectGroup.objects) {
            let killZone = killZoneMapObjectGroup.objects[killZoneKey];
            for (let enemyIndex in killZone.enemies) {
                let enemyId = killZone.enemies[enemyIndex];
                if (enemyMapObjectGroup.findMapObjectById(enemyId).npc.id === npcId) {
                    result++;
                }
            }
        }

        return result;
    }

    /**
     * Refreshes the UI to reflect the current enemy forces state
     */
    refreshUI() {
        console.assert(this instanceof DungeonSpeedrunRequiredNpcsControls, 'this is not DungeonSpeedrunRequiredNpcsControls', this);

        // Remove all existing required npcs
        let $dungeonSpeedrunRequiredNpcs = $('#map_dungeon_speedrun_required_npcs');
        $dungeonSpeedrunRequiredNpcs.empty();

        let $dungeonSpeedrunRequiredNpcsOverflow = $('#edit_route_dungeon_speedrun_required_npcs_container_overflow');
        $dungeonSpeedrunRequiredNpcsOverflow.empty();

        let currentFloorId = getState().getCurrentFloor().id;
        let mapContext = getState().getMapContext();
        let requiredNpcs = mapContext.getDungeonSpeedrunRequiredNpcs();
        for (let index in requiredNpcs) {
            let $targetContainer = $dungeonSpeedrunRequiredNpcs;
            let requiredNpc = requiredNpcs[index];

            // Insert an npc on a different floor to the overflow container
            if (requiredNpc.floor_id !== currentFloorId) {
                $targetContainer = $dungeonSpeedrunRequiredNpcsOverflow;
            }

            let template = Handlebars.templates['map_dungeon_speedrun_required_npcs_row_template'];

            let npcs = [
                requiredNpc.npc_id,
                requiredNpc.npc2_id,
                requiredNpc.npc3_id,
                requiredNpc.npc4_id,
                requiredNpc.npc5_id,
            ];

            let killedCount = 0;
            let npcNames = [];
            for (let index in npcs) {
                let npcId = npcs[index];
                if (npcId !== null) {
                    npcNames.push({
                        name: mapContext.findNpcById(npcId).name
                    });

                    killedCount += this._getKilledEnemiesByNpcId(npcId);
                }
            }

            $targetContainer.append(
                $(template({
                    id: requiredNpc.npc_id,
                    npcNames: npcNames,
                    count: requiredNpc.count,
                    killed_count: killedCount
                }))
            );
        }
    }

    /**
     * Adds the Control to the current LeafletMap
     */
    addControl() {
        console.assert(this instanceof DungeonSpeedrunRequiredNpcsControls, 'this is not DungeonSpeedrunRequiredNpcsControls', this);

        // Code for the statusbar
        L.Control.Statusbar = L.Control.extend(this.mapControlOptions);

        L.control.statusbar = function (opts) {
            return new L.Control.Statusbar(opts);
        };

        this._mapControl = L.control.statusbar({position: 'bottomhorizontalcenter'}).addTo(this.map.leafletMap);

        // Add the leaflet draw control to the sidebar
        let container = this._mapControl.getContainer();
        let $targetContainer = $('#edit_route_dungeon_speedrun_required_npcs_container');
        $targetContainer.append(container);

        // Fix for Edge prioritizing float: left; from leaflet-control, leading to the div having 1 pixel width rather
        // than the full width. Removing the leaflet-control class fixes this.
        let $dungeonSpeedrunRequiredNpcs = $('#map_dungeon_speedrun_required_npcs');
        $dungeonSpeedrunRequiredNpcs.removeClass('leaflet-control');

        // Show the default values
        this.refreshUI();
    }

    cleanup() {
        console.assert(this instanceof DungeonSpeedrunRequiredNpcsControls, 'this is not DungeonSpeedrunRequiredNpcsControls', this);
        super.cleanup();
    }

}
