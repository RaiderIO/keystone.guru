class EnemyVisual extends Signalable {
    constructor(map, enemy, layer) {
        super();
        console.assert(map instanceof DungeonMap, 'map was not a DungeonMap', map);
        console.assert(enemy instanceof Enemy, 'enemy was not an Enemy', enemy);

        /** Override for showing fade or not **/
        this._hideFade = false;

        /** @type DungeonMap */
        this.map = map;
        /** @type Enemy */
        this.enemy = enemy;
        this.layer = layer;
        this.divIcon = null;

        this.visualType = '';
        /** @type EnemyVisualMain */
        this.mainVisual = null;

        this.circleMenu = null;

        // Default visual (after modifiers!)
        this.setVisualType(getState().getEnemyDisplayType());

        let self = this;
        // Build and/or destroy the visual based on visibility
        this.enemy.register(['shown', 'hidden'], this, function (event) {
            if (event.data.visible) {
                self._buildVisual();
            } else {
                // When an object is hidden, its layer is removed from the parent, effectively rendering its display nil.
                // We don't need to do anything since if the visual is added again, we're going to re-create it anyways
            }
        });

        // If it changed, refresh the entire visual
        this.enemy.register(['enemy:set_raid_marker', 'synced'], this, this._buildVisual.bind(this));
        this.enemy.register('killzone:attached', this, function () {
            // If the killzone we're attached to gets refreshed, register for its changes and rebuild our visual
            let killZone = self.enemy.getKillZone();
            killZone.register('killzone:changed', self, self._buildVisual.bind(self));
            killZone.register('object:deleted', self, self._buildVisual.bind(self));
            self._buildVisual();
        });
        // Cleanup if it's detached
        this.enemy.register('killzone:detached', this, function (event) {
            // Only if it was attached to something
            if (event.data.previous instanceof KillZone) {
                event.data.previous.unregister('object:deleted', self);
                event.data.previous.unregister('killzone:changed', self);
            }
            self._buildVisual();
        });
        this.map.register('map:mapstatechanged', this, function (mapStateChangedEvent) {
            if (mapStateChangedEvent.data.previousMapState instanceof EditMapState ||
                mapStateChangedEvent.data.newMapState instanceof EditMapState) {
                self._buildVisual();
            }
        });

        // this.layer.on('mouseover', function () {
        //     self._mouseOver();
        // });
        // this.layer.on('mouseout', function () {
        //     self._mouseOut();
        // });

    }

    /**
     *
     * @protected
     */
    _mouseOver() {
        console.assert(this instanceof EnemyVisual, 'this is not an EnemyVisual', this);
        let visuals = [this];

        // Add all the enemies in said pack to the toggle display (may be empty if not part of a pack)
        let packBuddies = this.enemy.getPackBuddies();
        packBuddies.push(this.enemy);
        $.each(packBuddies, function (index, enemy) {
            visuals.push(enemy.visual);
        });

        for (let i = 0; i < visuals.length; i++) {
            visuals[i]._hideFade = true;
            visuals[i].setVisualType('enemy_forces');
        }
    }

    /**
     *
     * @protected
     */
    _mouseOut() {
        console.assert(this instanceof EnemyVisual, 'this is not an EnemyVisual', this);
        if (this.circleMenu === null) {
            let visuals = [this];

            // Add all the enemies in said pack to the toggle display (may be empty if enemy not part of a pack)
            let packBuddies = this.enemy.getPackBuddies();
            packBuddies.push(this.enemy);
            $.each(packBuddies, function (index, enemy) {
                visuals.push(enemy.visual);
            });

            for (let i = 0; i < visuals.length; i++) {
                visuals[i]._hideFade = false;
                visuals[i].setVisualType(getState().getEnemyDisplayType());
            }
        }

        this.layer.closeTooltip();
    }

    /**
     * Creates modifiers that alter the display of the visual
     * @returns {Array}
     * @private
     */
    _createModifiers() {
        console.assert(this instanceof EnemyVisual, 'this is not an EnemyVisual', this);

        let modifiers = [];
        // Only add the modifiers if they're necessary; otherwise don't waste resources on adding hidden items
        if (typeof this.enemy.raid_marker_name === 'string' && this.enemy.raid_marker_name !== '') {
            modifiers.push(new EnemyVisualModifierRaidMarker(this, 0));
        }

        // Only for elite enemies
        if (this.enemy.npc !== null) {
            if (this.enemy.npc.classification_id !== 1 &&
                getState().getMapZoomLevel() > c.map.enemy.classification_display_zoom) {
                modifiers.push(new EnemyVisualModifierClassification(this, 1));
            }

            // Truesight marker
            if (this.enemy.npc.truesight === 1 &&
                getState().getMapZoomLevel() > c.map.enemy.truesight_display_zoom) {
                modifiers.push(new EnemyVisualModifierTruesight(this, 2));
            }

            // Awakened marker
            if (this.enemy.npc.dungeon_id === -1 &&
                getState().getMapZoomLevel() > c.map.enemy.awakened_display_zoom) {
                modifiers.push(new EnemyVisualModifierAwakened(this, 3));
            }
        }

        if (this.enemy.teeming === 'visible' &&
            getState().getMapZoomLevel() > c.map.enemy.teeming_display_zoom) {
            modifiers.push(new EnemyVisualModifierTeeming(this, 4));
        }

        return modifiers;
    }

    /**
     * Called whenever the root visual object was clicked
     * @private
     */
    _visualClicked() {
        console.assert(this instanceof EnemyVisual, 'this is not an EnemyVisual!', this);
        let self = this;

        // Some exclusions as to when the menu should not pop up
        if (self.map.options.edit &&
            self.map.getMapState() === null &&
            self.enemy.constructor.name !== 'AdminEnemy') {

            if (self.circleMenu === null) {
                let template = Handlebars.templates['map_enemy_raid_marker_template'];
                let id = self.enemy.id;

                let data = $.extend({}, getHandlebarsDefaultVariables(), {
                    id: id
                });

                let $container = $('#map_enemy_visual_' + id);
                $container.append(template(data));

                let $circleMenu = $('#map_enemy_raid_marker_radial_' + id);

                let $enemyDiv = $container.find('.enemy_icon');

                let size = self.mainVisual.getSize().iconSize[0];
                let margin = c.map.enemy.calculateMargin(size);

                // Force the circle menu to appear in the center of the enemy visual
                $circleMenu.css('position', 'absolute');
                // Compensate of the 24x24 square
                $circleMenu.css('left', ((size / 2) + margin - 12) + 'px');
                $circleMenu.css('top', ((size / 2) + margin - 12) + 'px');

                // Init circle menu and open it
                self.circleMenu = $circleMenu.circleMenu({
                    direction: 'full',
                    step_in: 5,
                    step_out: 0,
                    trigger: 'click',
                    transition_function: 'linear',
                    // Radius
                    circle_radius: size + margin,
                    // Positioning
                    item_diameter: 24,
                    speed: 300,
                    init: function () {
                        refreshTooltips();
                    },
                    open: function () {
                        self.enemy.unbindTooltip();
                        self.signal('circlemenu:open');
                    },
                    close: function () {
                        // Unassigned when opened
                        self.enemy.bindTooltip();

                        // Delete ourselves again
                        self._cleanupCircleMenu();
                        self.signal('circlemenu:close');
                    },
                    select: function (evt, index) {
                        // Unassigned when opened
                        self.enemy.bindTooltip();

                        // Assign the selected raid marker
                        self.enemy.assignRaidMarker($(index).data('name'));

                        // Delete ourselves again
                        self._cleanupCircleMenu();
                        self.signal('circlemenu:close');
                    }
                });

                // Force open the menu
                self.circleMenu.circleMenu('open');

                // Unbind this function so we don't get repeat clicks
                $enemyDiv.unbind('click');
                // Give the user a way to close the menu by clicking the enemy again
                $enemyDiv.bind('click', function () {
                    self.circleMenu.circleMenu('close', false);
                    // Prevent multiple clicks triggering the close
                    $enemyDiv.unbind('click');
                });
            }
        }
    }

    /**
     * Cleans up the circle menu, removing it from the object completely.
     * @private
     */
    _cleanupCircleMenu() {
        console.assert(this instanceof EnemyVisual, 'this is not an EnemyVisual!', this);

        let self = this;
        let id = self.enemy.id;
        let $enemyDiv = $('#map_enemy_visual_' + id).find('.enemy_icon');

        // Clear any stray tooltips
        refreshTooltips();

        // Delay it by 500 ms so the animations have a chance to complete
        $('#map_enemy_raid_marker_radial_' + id).delay(500).queue(function () {
            $(this).remove().dequeue();
            self.circleMenu = null;

            // Slight hack to restore the state we were in prior to selecting the icon (otherwise we may get stuck in mouse over state)
            self._mouseOut();

            // Re-bind this function
            $enemyDiv.unbind('contextmenu');
            $enemyDiv.bind('contextmenu', self._visualClicked.bind(self));
        });
    }

    /**
     * Constructs the structure for the visuals and re-fetches the main visual's and modifier's data to re-apply to
     * the interface.
     * @private
     */
    _buildVisual() {
        console.assert(this instanceof EnemyVisual, 'this is not an EnemyVisual', this);

        // Determine which modifiers the visual should have

        // If the object is invisible, don't build the visual
        let enemyMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_ENEMY);
        if (enemyMapObjectGroup.isMapObjectVisible(this.enemy)) {
            let template = Handlebars.templates['map_enemy_visual_template'];

            // Set a default color which may be overridden by any visuals
            let data = {};

            let isSelectable = (this.map.getMapState() instanceof MDTEnemySelection && this.enemy.isSelectable()) ||
                (this.map.getMapState() instanceof EditMapState && this.enemy.isEditable()) ||
                (this.map.getMapState() instanceof DeleteMapState && this.enemy.isDeletable());

            // Either no border or a solid border in the color of the killzone
            let border = `${getState().getMapZoomLevel()}px solid white`;
            if (this.enemy.getKillZone() instanceof KillZone) {
                border = `${getState().getMapZoomLevel()}px solid ${this.enemy.getKillZone().color}`;
            } else if (!this._hideFade && !this.enemy.is_mdt && !isSelectable) {
                // If not selected in a killzone, fade the enemy
                data.root_classes = 'map_enemy_visual_fade';
            }

            data.outer_border = border;

            if (isSelectable) {
                data.selection_classes_base += ' leaflet-edit-marker-selected selected_enemy_icon';
            }

            data = $.extend(data, this.mainVisual._getTemplateData());

            let size = this.mainVisual.getSize();

            let width = size.iconSize[0];
            let height = size.iconSize[1];

            let margin = c.map.enemy.calculateMargin(width);

            data.id = this.enemy.id;
            // Compensate for a 2px border on the inner, 2x border on the outer
            data.inner_width = 'calc(100% - ' + (margin * 2) + 'px)';
            data.inner_height = 'calc(100% - ' + (margin * 2) + 'px)';

            data.outer_width = (width + (margin * 2)) + 'px';
            data.outer_height = (height + (margin * 2)) + 'px';

            data.margin = margin;

            // Build modifiers object
            data.modifiers = [];
            // Fetch the modifiers we're displaying on our visual
            let modifiers = this._createModifiers();
            for (let i = 0; i < modifiers.length; i++) {
                data.modifiers.push(modifiers[i]._getTemplateData(width, height, margin));
            }

            // Create a new div icon (the entire structure)
            this.divIcon = new L.divIcon({
                html: template(data),
                iconSize: [width + (margin * 2), height + (margin * 2)],
                tooltipAnchor: [0, ((height * -.5) - margin)],
                popupAnchor: [0, ((height * -.5) - margin)]
            });

            // Set the structure as HTML for the layer
            this.layer.setIcon(this.divIcon);

            $(`#map_enemy_visual_${data.id}`).hover(
                this._mouseOver.bind(this),
                this._mouseOut.bind(this)
            );

            // When the visual exists, bind a click method to it (to increase performance)
            let $enemyIcon = $('#map_enemy_visual_' + this.enemy.id).find('.enemy_icon');
            $enemyIcon.unbind('contextmenu');
            $enemyIcon.bind('contextmenu', this._visualClicked.bind(this));

            this.signal('enemyvisual:builtvisual', {});
        }
    }

    // @TODO Listen to killzone selectable changed event
    refresh() {
        console.assert(this instanceof EnemyVisual, 'this is not an EnemyVisual', this);

        // Refresh the visual completely
        this.setVisualType(getState().getEnemyDisplayType(), true);
    }

    /**
     * Sets the visual type for this enemy.
     * @param name
     * @param force Force the recreation of the visual
     */
    setVisualType(name, force = false) {
        // Only when actually changed
        if (this.visualType !== name || force) {
            // Let them clean up their mess
            if (this.mainVisual !== null) {
                this.mainVisual.cleanup();
            }

            if (this.enemy.is_mdt) {
                this.mainVisual = new EnemyVisualMainMDT(this);
            } else {
                switch (name) {
                    case 'npc_class':
                        this.mainVisual = new EnemyVisualMainEnemyClass(this);
                        break;
                    case 'npc_type':
                        this.mainVisual = new EnemyVisualMainNpcType(this);
                        break;
                    case 'enemy_forces':
                        this.mainVisual = new EnemyVisualMainEnemyForces(this);
                        break;
                    case 'enemy_portrait':
                        this.mainVisual = new EnemyVisualMainEnemyPortrait(this);
                        break;
                }
            }

            this._buildVisual();

            this.visualType = name;
        }
    }

    cleanup() {
        // this.layer.off('mouseover');
        // this.layer.off('mouseout');

        this.enemy.unregister('killzone:detached', this);
        this.enemy.unregister('killzone:attached', this);
        this.enemy.unregister('enemy:set_raid_marker', this);
        this.map.unregister('map:mapstatechanged', this);

        this._cleanupCircleMenu();
    }
}