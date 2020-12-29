class EnemyVisual extends Signalable {
    constructor(map, enemy, layer) {
        super();
        console.assert(map instanceof DungeonMap, 'map was not a DungeonMap', map);
        console.assert(enemy instanceof Enemy, 'enemy was not an Enemy', enemy);
        console.assert(layer instanceof L.Layer, 'layer is not an L.Layer', layer);

        /** @type DungeonMap */
        this.map = map;
        /** @type Enemy */
        this.enemy = enemy;
        this.layer = layer;

        /** Override for showing fade or not **/
        this._highlighted = false;
        /** Used for managing mouse overs over our enemy pack. If one enemy is mouse overed, all are mouse overed */
        this._managedBy = this.enemy.id;

        this._divIcon = null;
        this._modifiers = [];
        this._$mainVisual = null;
        this._$mainVisualOuter = null;
        this._$mainVisualInner = null;

        this.visualType = '';
        /** @type EnemyVisualMain */
        this.mainVisual = null;

        this._circleMenu = null;

        // Default visual (after modifiers!)
        this.setVisualType(getState().getEnemyDisplayType());

        let self = this;
        // Build and/or destroy the visual based on visibility
        this.enemy.register(['shown', 'hidden'], this, function (shownHiddenEvent) {
            if (shownHiddenEvent.data.visible && enemy.shouldBeVisible()) {
                if (self._divIcon === null) {
                    self.buildVisual();
                } else {
                    self.refreshJQuerySelectors();
                    self.refreshSize();
                }
            } else {
                // When an object is hidden, its layer is removed from the parent, effectively rendering its display nil.
                // We don't need to do anything since if the visual is added again, we're going to re-create it anyways
            }
        });

        // If it changed, refresh the entire visual
        this.enemy.register(['enemy:set_raid_marker'], this, this.buildVisual.bind(this));
        this.enemy.register('killzone:attached', this, function (killZoneAttachedEvent) {
            // If the killzone we're attached to gets refreshed, register for its changes and rebuild our visual
            let killZone = self.enemy.getKillZone();
            killZone.register('object:changed', self, self.buildVisual.bind(self));
            killZone.register('object:deleted', self, self.buildVisual.bind(self));

            // Check if we can shortcut by updating just the border
            // if ((killZoneAttachedEvent.data.previousKillZone instanceof KillZone && !(killZone instanceof KillZone)) ||
            //     (!(killZoneAttachedEvent.data.previousKillZone instanceof KillZone) && killZone instanceof KillZone)) {
            //     // We cannot
            //     self.buildVisual();
            // } else {
            // From killzone to killzone we can, otherwise we can't
            self._updateBorder(killZone.color);
            // }
        });
        // Cleanup if it's detached
        this.enemy.register('killzone:detached', this, function (event) {
            // Only if it was attached to something
            if (event.data.previous instanceof KillZone) {
                event.data.previous.unregister('object:deleted', self);
                event.data.previous.unregister('object:changed', self);
            }
            self._updateBorder('white');
        });
        this.map.register('map:mapstatechanged', this, function (mapStateChangedEvent) {
            if (mapStateChangedEvent.data.previousMapState instanceof EditMapState ||
                mapStateChangedEvent.data.newMapState instanceof EditMapState ||
                mapStateChangedEvent.data.previousMapState instanceof DeleteMapState ||
                mapStateChangedEvent.data.newMapState instanceof DeleteMapState) {
                self.buildVisual();
            }
        });
    }

    /**
     * @protected
     */
    _mouseOver() {
        console.assert(this instanceof EnemyVisual, 'this is not an EnemyVisual', this);
        if (this._managedBy === this.enemy.id) {
            let visuals = [this];

            // Add all the enemies in said pack to the toggle display (may be empty if not part of a pack)
            let packBuddies = this.enemy.getPackBuddies();
            packBuddies.push(this.enemy);
            $.each(packBuddies, function (index, enemy) {
                if (enemy.visual !== null) {
                    visuals.push(enemy.visual);
                }
            });

            for (let i = 0; i < visuals.length; i++) {
                visuals[i]._managedBy = this.enemy.id;
                visuals[i]._highlighted = true;
                visuals[i].setVisualType('enemy_forces');
            }

            getState().setFocusedEnemy(this.enemy);
        }
    }

    /**
     * @protected
     */
    _mouseOut() {
        console.assert(this instanceof EnemyVisual, 'this is not an EnemyVisual', this);
        // Only actually when we're highlighted do we want to undo ourselves of it
        if (this._highlighted && this._managedBy === this.enemy.id) {
            if (this._circleMenu === null) {
                let visuals = [this];

                // Add all the enemies in said pack to the toggle display (may be empty if enemy not part of a pack)
                let packBuddies = this.enemy.getPackBuddies();
                packBuddies.push(this.enemy);
                $.each(packBuddies, function (index, enemy) {
                    // Some packs may contain Teeming enemies which may be hidden
                    if (enemy.isVisible() && enemy.visual !== null) {
                        visuals.push(enemy.visual);
                    }
                });

                for (let i = 0; i < visuals.length; i++) {
                    let visual = visuals[i];
                    // Return management state to their own enemy
                    visual._managedBy = visual.enemy.id;
                    visual._highlighted = false;
                    visual.setVisualType(getState().getEnemyDisplayType());
                }

                getState().setFocusedEnemy(null);
            }
        }

        this.layer.closeTooltip();
    }

    /**
     * Refreshes the modifier's visibility based on current map zoom level.
     * @param width {float}
     * @param height {float}
     * @param margin {float}
     * @private
     */
    _refreshModifierVisibility(width, height, margin) {
        let zoomLevel = getState().getMapZoomLevel();
        for (let i = 0; i < this._modifiers.length; i++) {
            this._modifiers[i].updateVisibility(zoomLevel, width, height, margin);
        }
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
            if (this.enemy.npc.classification_id !== 1) {
                modifiers.push(new EnemyVisualModifierClassification(this, 1));
            }

            // Truesight marker
            if (this.enemy.npc.truesight === 1) {
                modifiers.push(new EnemyVisualModifierTruesight(this, 2));
            }

            // Awakened marker
            if (this.enemy.seasonal_type === ENEMY_SEASONAL_TYPE_AWAKENED) {
                modifiers.push(new EnemyVisualModifierAwakened(this, 3));
            }
            // Inspiring marker
            else if (this.enemy.seasonal_type === ENEMY_SEASONAL_TYPE_INSPIRING) {
                modifiers.push(new EnemyVisualModifierInspiring(this, 3));
            }
        }

        if (this.enemy.teeming === 'visible') {
            modifiers.push(new EnemyVisualModifierTeeming(this, 4));
        }

        // For each active aura, add a new modifier
        for(let i = 0; i < this.enemy.active_auras.length; i++ ){
            modifiers.push(new EnemyVisualModifierActiveAura(this, 5 + i, this.enemy.active_auras[i]));
        }

        return modifiers;
    }

    /**
     * Called whenever the root visual object was clicked
     * @private
     */
    _visualRightClicked() {
        console.assert(this instanceof EnemyVisual, 'this is not an EnemyVisual!', this);
        let self = this;

        // Some exclusions as to when the menu should not pop up
        if (self.map.options.edit &&
            self.map.getMapState() === null &&
            !(self.enemy instanceof AdminEnemy)) {

            if (self._circleMenu === null) {
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
                self._circleMenu = $circleMenu.circleMenu({
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

                        self.map.setMapState(new RaidMarkerSelectMapState(self.map, self.enemy));
                    },
                    close: function () {
                        // Unassigned when opened
                        self.enemy.bindTooltip();

                        // Delete ourselves again
                        self._cleanupCircleMenu();
                    },
                    select: function (evt, index) {
                        // Unassigned when opened
                        self.enemy.bindTooltip();

                        // Assign the selected raid marker
                        self.enemy.assignRaidMarker($(index).data('name'));

                        // Delete ourselves again
                        self._cleanupCircleMenu();
                    }
                });

                // Force open the menu
                self._circleMenu.circleMenu('open');

                // Unbind this function so we don't get repeat clicks
                $enemyDiv.unbind('click');
                // Give the user a way to close the menu by clicking the enemy again
                $enemyDiv.bind('click', function () {
                    self._circleMenu.circleMenu('close', false);
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
            self._circleMenu = null;

            // Slight hack to restore the state we were in prior to selecting the icon (otherwise we may get stuck in mouse over state)
            self._mouseOut();

            // Re-bind this function
            $enemyDiv.unbind('contextmenu');
            $enemyDiv.bind('contextmenu', self._visualRightClicked.bind(self));

            // Only stop the map state at this point
            self.map.setMapState(null);
            self.signal('circlemenu:close');
        });
    }

    /**
     * Constructs the structure for the visuals and re-fetches the main visual's and modifier's data to re-apply to
     * the interface.
     */
    buildVisual() {
        console.assert(this instanceof EnemyVisual, 'this is not an EnemyVisual', this);

        // If the object is invisible, don't build the visual
        let enemyMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_ENEMY);
        // Determine which modifiers the visual should have
        // console.warn(`building visual`, enemyMapObjectGroup.isMapObjectVisible(this.enemy));

        if (enemyMapObjectGroup.isMapObjectVisible(this.enemy)) {

            let template = Handlebars.templates['map_enemy_visual_template'];

            // Set a default color which may be overridden by any visuals
            let data = {};

            let isDeletable = this.map.getMapState() instanceof DeleteMapState && this.enemy.isDeletable();
            let isSelectable = (this.map.getMapState() instanceof MDTEnemySelection && this.enemy.isSelectable()) ||
                (this.map.getMapState() instanceof EditMapState && this.enemy.isEditable()) || isDeletable;

            // Either no border or a solid border in the color of the killzone
            let border = `${getState().getMapZoomLevel()}px solid white`;
            if (this.enemy.getKillZone() instanceof KillZone) {
                border = `${getState().getMapZoomLevel()}px solid ${this.enemy.getKillZone().color}`;
            } else if (!this._highlighted && !this.enemy.is_mdt && !isSelectable) {
                // If not selected in a killzone, fade the enemy
                data.root_classes = 'map_enemy_visual_fade';
            }

            if (this.enemy.unskippable) {
                data.root_classes += ' unskippable';
            }

            data.outer_border = border;

            if (isSelectable) {
                data.selection_classes_base += ' leaflet-edit-marker-selected selected_enemy_icon';
            }

            if (isDeletable) {
                data.selection_classes_base += ' delete';
            }

            data = $.extend(data, this.mainVisual._getTemplateData());

            let size = this.mainVisual.getSize();

            let width = size.iconSize[0];
            let height = size.iconSize[1];

            let margin = c.map.enemy.calculateMargin(width);

            data.id = this.enemy.id;

            data.margin = margin;

            // Build modifiers object
            data.modifiers = [];
            // Fetch the modifiers we're displaying on our visual
            this._modifiers = this._createModifiers();
            for (let i = 0; i < this._modifiers.length; i++) {
                data.modifiers.push(this._modifiers[i]._getTemplateData(width, height, margin));
            }

            // Create a new div icon (the entire structure)
            this._divIcon = new L.divIcon({
                html: template(data),
                iconSize: [width + (margin * 2), height + (margin * 2)],
                tooltipAnchor: [0, ((height * -.5) - margin)],
                popupAnchor: [0, ((height * -.5) - margin)]
            });

            // Set the structure as HTML for the layer
            this.layer.setIcon(this._divIcon);

            this.refreshJQuerySelectors();

            // Apply current size to the icon
            this.refreshSize(false);

            // $(`#map_enemy_visual_${data.id}`).mouseover(this._mouseOver.bind(this));
            // $(`#map_enemy_visual_${data.id}`).mouseout(this._mouseOut.bind(this));

            // When the visual exists, bind a click method to it (to increase performance)
            let $enemyIcon = $('#map_enemy_visual_' + this.enemy.id).find('.enemy_icon');
            $enemyIcon.unbind('contextmenu');
            $enemyIcon.bind('contextmenu', this._visualRightClicked.bind(this));

            this.signal('enemyvisual:builtvisual', {});
        }
    }

    /**
     * Updates the color of the border for this visual
     * @param color string
     * @private
     */
    _updateBorder(color) {
        console.assert(this instanceof EnemyVisual, 'this is not an EnemyVisual', this);
        $('#map_enemy_visual_' + this.enemy.id).find('.outer').css('border-color', color);
    }

    /**
     * True if the visual was highlighted by the user, false if it was not
     * @returns {boolean}
     */
    isHighlighted() {
        return this._highlighted;
    }

    /**
     * Refreshes all _$xxxVisual variables.
     */
    refreshJQuerySelectors() {
        this._$mainVisual = $(`#map_enemy_visual_${this.enemy.id}`);
        this._$mainVisualOuter = $(`#map_enemy_visual_${this.enemy.id}_outer`);
        this._$mainVisualInner = $(`#map_enemy_visual_${this.enemy.id}_inner`);
        this._$mainVisualParent = $(this._$mainVisual.closest('.leaflet-div-icon'));
    }

    /**
     *
     */
    refreshSize(adjustParent = true) {
        console.assert(this instanceof EnemyVisual, 'this is not an EnemyVisual', this);

        // if (adjustParent) {
        //     console.warn(`refreshing size`);
        // }

        if (this._$mainVisual.length === 0) {
            console.warn('Unable to refresh size of visual that no longer exists');
            return;
        }

        let size = this.mainVisual.getSize();

        let width = size.iconSize[0];
        let height = size.iconSize[1];

        let margin = c.map.enemy.calculateMargin(width);
        let marginStr = `${margin}px`;

        let outerWidth = (width + (margin * 2));
        let outerHeight = (height + (margin * 2));

        let outerWidthStr = `${outerWidth}px`;
        let outerHeightStr = `${outerHeight}px`;

        let innerSizeStr = `calc(100% - ${(margin * 2)}px)`;

        // Compensate for a 2px border on the inner, 2x border on the outer
        this._$mainVisual[0].style.width = outerWidthStr;
        this._$mainVisual[0].style.height = outerHeightStr;

        this._$mainVisualOuter[0].style.width = outerWidthStr;
        this._$mainVisualOuter[0].style.height = outerHeightStr;
        this._$mainVisualOuter[0].style.borderWidth = `${getState().getMapZoomLevel()}px`;

        this._$mainVisualInner[0].style.width = innerSizeStr;
        this._$mainVisualInner[0].style.height = innerSizeStr;
        this._$mainVisualInner[0].style.margin = marginStr;

        // this.$mainVisual.css('width', `${outerWidth}px`).css('height', `${outerHeight}px`);
        // this.$mainVisualOuter.css('width', `${outerWidth}px`).css('height', `${outerHeight}px`);
        // this.$mainVisualInner.css('width', innerWidth).css('height', innerHeight).css('margin', margin);

        if (adjustParent) {
            let parentMargin = outerWidth * -0.5;
            let parentMarginStr = `${parentMargin}px`;

            this._$mainVisualParent[0].style.marginLeft = parentMarginStr;
            this._$mainVisualParent[0].style.marginTop = parentMarginStr;
            this._$mainVisualParent[0].style.width = outerWidthStr;
            this._$mainVisualParent[0].style.height = outerHeightStr;

            // this._$mainVisualParent.css('margin-left', `${parentMargin}px`).css('margin-top', `${parentMargin}px`)
            //     .css('width', `${outerWidth}px`).css('height', `${outerHeight}px`);
        }

        // Hide/show modifiers based on zoom level
        this._refreshModifierVisibility(outerWidth, outerHeight, margin);
    }

    /**
     * Checks if we should be showing our mouse over state or not
     * @param mouseX {int}
     * @param mouseY {int}
     *
     * @return float Squared distance from the enemy to the mouse
     */
    checkMouseOver(mouseX, mouseY) {
        console.assert(this instanceof EnemyVisual, 'this is not an EnemyVisual', this);

        // Sensible default for distance (approx 75% of the full map distance)
        let result = 1000000;

        if (this._$mainVisual !== null && this._$mainVisual.length > 0 && this._managedBy === this.enemy.id) {

            let offset = this._$mainVisual.offset();
            let iconSize = this.mainVisual.getSize();
            let size = iconSize.iconSize[0];
            let margin = c.map.enemy.calculateMargin(size);
            let halfSize = (size / 2) + margin;

            result = getDistanceSquared([offset.left + halfSize, offset.top + halfSize], [mouseX, mouseY]);

            if (result < halfSize * halfSize) {
                this._mouseOver();
            } else {
                this._mouseOut();
            }
        }

        return result;
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
                    case 'npc_type':
                        this.mainVisual = new EnemyVisualMainNpcType(this);
                        break;
                    case 'enemy_forces':
                        this.mainVisual = new EnemyVisualMainEnemyForces(this);
                        break;
                    case 'enemy_portrait':
                        this.mainVisual = new EnemyVisualMainEnemyPortrait(this);
                        break;
                    default:
                        this.mainVisual = new EnemyVisualMainEnemyClass(this);
                        break;
                }
            }

            // Don't do this on page load - it's pointless since show/hidden events will do this again
            if (this.enemy.isVisible()) {
                this.buildVisual();
            }

            this.visualType = name;
        }
    }

    cleanup() {
        this.enemy.unregister('killzone:detached', this);
        this.enemy.unregister('killzone:attached', this);
        this.enemy.unregister('enemy:set_raid_marker', this);
        this.map.unregister('map:mapstatechanged', this);

        if (this._circleMenu !== null) {
            this._cleanupCircleMenu();
        }
    }
}