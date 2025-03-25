class PullWorkBench extends Signalable {

    /**
     * @param killZonesSidebar {CommonMapsKillzonessidebar}
     */
    constructor(killZonesSidebar) {
        super();

        this.killZonesSidebar = killZonesSidebar;
        /** @type {KillZone|null} */
        this.killZone = null;
        /** @type Pickr|null */
        this.colorPicker = null;
    }

    activate() {
        console.assert(this instanceof PullWorkBench, 'this is not a PullWorkBench', this);

        let self = this;

        this.$workbench = $('#pull_sidebar_workbench');

        this.colorPicker = this._initColorPicker();

        // If we finished adding a killzone (kill area) we refresh the workbench to update tooltips
        getState().getDungeonMap().register('map:mapstatechanged', this, function (mapStateChangedEvent) {
            if (mapStateChangedEvent.data.previousMapState instanceof AddKillZoneMapState) {
                self.editPull(self.killZone.id);
            }
        });
    }

    /**
     * Initializes a color picker.
     * @returns {*}
     * @private
     */
    _initColorPicker() {
        console.assert(this instanceof PullWorkBench, 'this is not a PullWorkBench', this);

        let self = this;

        // Simple example, see optional options for more configuration.
        return Pickr.create($.extend(c.map.colorPickerDefaultOptions, {
            el: `#map_killzonessidebar_killzone_color`,
            default: '#000', // self.killZone.color
        })).on('save', (color, instance) => {
            // Apply the new color
            let newColor = '#' + color.toHEXA().join('');
            // Only save when the color is valid
            if (self.killZone.color !== newColor && newColor.length === 7) {
                self.killZone.color = newColor;
                self.killZone.save();
            }

            // Reset ourselves
            instance.hide();
        });
    }

    /**
     *
     * @param killZoneId
     */
    editPull(killZoneId) {
        console.assert(this instanceof PullWorkBench, 'this is not a PullWorkBench', this);

        // Depress a toggle button
        let oldKillZoneId = null;
        if (this.killZone !== null) {
            $(`#map_killzonessidebar_killzone_${this.killZone.id}_edit`).button('toggle');
            oldKillZoneId = this.killZone.id;
        }

        /** @type KillZoneMapObjectGroup */
        let killZoneMapObjectGroup = this.killZonesSidebar.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_KILLZONE);

        this.killZone = killZoneMapObjectGroup.findMapObjectById(killZoneId);
        if (this.killZone === null) {
            console.warn(`Unable to find killzone ${killZoneId}!`);
            this.$workbench.hide();
            return;
        } else if (oldKillZoneId === this.killZone.id) {
            // Toggle it off again since our keypress turned it on just now
            $(`#map_killzonessidebar_killzone_${this.killZone.id}_edit`).button('toggle');
            this.killZone = null;
            this.$workbench.hide();
            return;
        } else {
            this.$workbench.show();
        }

        $(`#pull_sidebar_workbench_header`).text(
            lang.get('messages.pull_workbench_header_label', {index: this.killZone.index})
        );

        this._initDescriptionButton();
        this._initKillAreaButton();
        this._initColorPickerButton();
        this._initDeletePullButton();
    }

    /**
     *
     * @private
     */
    _initDescriptionButton() {
        console.assert(this instanceof PullWorkBench, 'this is not a PullWorkBench', this);

        let self = this;

        $(`#map_killzonessidebar_killzone_description_modal_label`).text(
            lang.get('messages.pull_workbench_pull_description_label', {index: this.killZone.index})
        );
        $(`#map_killzonessidebar_killzone_description_modal_supported_html_tags`).text(
            lang.get('messages.pull_workbench_pull_supported_tags_label', {tags: c.map.sanitizeTextDefaultAllowedTags.join(', ')})
        );
        let $supportedDomains = $(`#map_killzonessidebar_killzone_description_modal_supported_domains`).attr(
            'title',
            c.map.sanitizeTextDefaultAllowedDomains.join('<br>')
        );
        refreshTooltips($supportedDomains);

        $(`#map_killzonessidebar_killzone_description_modal_textarea`).val(
            this.killZone.description ?? ''
        ).on('keydown', this._descriptionKeyDown);

        $(`#map_killzonessidebar_killzone_spells_modal_select`).val(
            this.killZone.spellIds
        );

        this._descriptionKeyDown();
        refreshSelectPickers();
        $(`#map_killzonessidebar_killzone_description_modal_save`).unbind('click').bind('click', function () {
            self.killZone.description = $(`#map_killzonessidebar_killzone_description_modal_textarea`).val();
            self.killZone.save();
        });
        $(`#map_killzonessidebar_killzone_spells_modal_save`).unbind('click').bind('click', function () {
            self.killZone.setSpells($(`#map_killzonessidebar_killzone_spells_modal_select`).val());
            self.killZone.save();
        });
    }

    _descriptionKeyDown() {
        // Show or hide the warning
        let description = $(`#map_killzonessidebar_killzone_description_modal_textarea`).val();

        $(`#map_killzonessidebar_killzone_description_modal_remaining_characters`).toggle(
            description.length > c.map.editsidebar.pullsWorkbench.description.maxLength *
            c.map.editsidebar.pullsWorkbench.description.warningThreshold
        ).text(
            lang.get('messages.pull_workbench_pull_description_length', {
                current: description.length,
                max: c.map.editsidebar.pullsWorkbench.description.maxLength
            })
        );
    }

    /**
     *
     * @private
     */
    _initKillAreaButton() {
        console.assert(this instanceof PullWorkBench, 'this is not a PullWorkBench', this);

        let self = this;

        /**
         * Code to prevent calling refreshTooltips too often
         */
        let $killAreaLabel = $(`#map_killzonessidebar_killzone_kill_area_label`);

        let resultMessage;
        // Set and is currently 0
        if (this.killZone.hasKillArea()) {
            // It was not, update it
            resultMessage = lang.get('messages.pull_workbench_remove_kill_area_label');
        } else {
            // Default
            resultMessage = lang.get('messages.pull_workbench_add_kill_area_label');
        }

        $killAreaLabel.attr('title', resultMessage).refreshTooltips();

        let $hasKillZone = $(`#map_killzonessidebar_killzone_has_killzone`).unbind('click').bind('click', function () {
            if (self.killZone.layer === null) {
                getState().getDungeonMap().setMapState(
                    new AddKillZoneMapState(getState().getDungeonMap(), self.killZone)
                );
            } else {
                // @TODO This entire piece of code is hacky, should be done differently eventually
                getState().getDungeonMap().drawnLayers.removeLayer(self.killZone.layer);
                getState().getDungeonMap().editableLayers.removeLayer(self.killZone.layer);

                let killZoneMapObjectGroup = getState().getDungeonMap().mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_KILLZONE);
                // It's been removed; unset it
                killZoneMapObjectGroup.setLayerToMapObject(null, self.killZone);

                self.killZone.floor_id = null;
                // Update its visuals
                self.killZone.redrawConnectionsToEnemies();
                self.killZone.save();

                // Re-init the workbench
                self.editPull(self.killZone.id);
            }
        });

        // If we have a killzone layer
        if (self.killZone.hasKillArea()) {
            // Was inactive (always starts inactive), is active now
            $hasKillZone.button('toggle');
        }
    }

    /**
     *
     * @private
     */
    _initColorPickerButton() {
        console.assert(this instanceof PullWorkBench, 'this is not a PullWorkBench', this);

        if (this.colorPicker !== null) {
            // SetColor is slow, check if we really need to set it
            let oldColor = '#' + this.colorPicker.getColor().toHEXA().join('');
            if (oldColor !== this.killZone.color) {
                this.colorPicker.setColor(this.killZone.color);
            }
        } else {
            console.warn('Color picker not found!', this.killZone, this.killZone.id);
        }
    }

    /**
     *
     * @private
     */
    _initDeletePullButton() {
        $(`#map_killzonessidebar_killzone_delete`).unbind('click').bind('click', this._deleteKillZoneClicked.bind(this));
    }

    /**
     * Called whenever the trash icon is clicked and the killzone should be deleted
     * @private
     */
    _deleteKillZoneClicked() {
        let self = this;

        let trashIcon = 'fa-trash';
        let loadingIcon = 'fa-circle-notch fa-spin';

        let $self = $(`#map_killzonessidebar_killzone_delete`);

        // Prevent double deletes if user presses the button twice in a row
        if ($self.find('i').hasClass(trashIcon)) {
            $self.find('i').removeClass(trashIcon).addClass(loadingIcon);

            self.killZone.register('object:deleted', '123123', function () {
                showSuccessNotification(lang.get('messages.object.deleted'));

                // Bit hacky?
                if (self.killZone.isKillAreaVisible()) {
                    getState().getDungeonMap().drawnLayers.removeLayer(self.killZone.layer);
                    getState().getDungeonMap().editableLayers.removeLayer(self.killZone.layer);
                }

                self.killZone.unregister('object:deleted', '123123');

                $self.find('i').addClass(trashIcon).removeClass(loadingIcon);
                self.$workbench.hide();

            });
            self.killZone.register('object:changed', '123123', function () {
                if (!self.killZone.synced) {
                    // Failed to delete
                    $self.find('i').addClass(trashIcon).removeClass(loadingIcon)
                }

                self.killZone.unregister('object:changed', '123123');
            });

            self.killZone.delete();
        }
    }
}
