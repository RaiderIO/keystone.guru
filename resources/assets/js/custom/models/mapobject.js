/**
 * @property id int
 * @property faction string
 * @property teeming string
 */
class MapObject extends Signalable {

    /**
     *
     * @param map
     * @param layer {L.layer}
     */
    constructor(map, layer) {
        super();
        console.assert(map instanceof DungeonMap, 'Passed map is not a DungeonMap!', map);
        let self = this;

        this._defaultVisible = true;
        /** @type {object} */
        this._cachedAttributes = null;
        this.synced = false;
        /** @type DungeonMap */
        this.map = map;
        /** @type L.Layer|null */
        this.layer = layer;

        this.id = 0;
        this.faction = 'any'; // sensible default
        this.teeming = null; // visible, hidden, null
        this.label = 'default label';
        this.decorator = null;

        this.register('synced', this, function () {
            self._rebuildDecorator();
        });
        this.register('object:deleted', this, function () {
            self._cleanDecorator();
        });
        this.map.register('map:beforerefresh', this, function () {
            self._cleanDecorator();
        });

        this.register(['shown', 'hidden'], this, function (event) {
            if (event.data.visible) {
                self._rebuildDecorator();
            } else {
                self._cleanDecorator();
            }
        });

        // Set the defaults for our attributes so we don't get any undefined errors
        let attributes = this._getAttributes();

        for (let property in attributes) {
            if (attributes.hasOwnProperty(property)) {
                let attribute = attributes[property];

                if (typeof attribute.default !== 'undefined') {
                    this[property] = attribute.default;
                }
            }
        }
    }

    /**
     * Get the attributes that belong to this map object and how to represent them.
     * @param force {boolean} True to force a rebuild of the attributes.
     * @returns {object}
     * @protected
     */
    _getAttributes(force = false) {
        console.assert(this instanceof MapObject, 'this is not a MapObject', this);

        let selectFactions = [];
        let factions = this.map.options.factions;

        for (let index in factions) {
            if (factions.hasOwnProperty(index)) {
                let faction = factions[index];
                selectFactions.push({
                    id: faction.key,
                    name: faction.description
                });
            }
        }

        let selectTeemingOptions = [];
        let teemingOptions = this.map.options.teemingOptions;

        for (let index in teemingOptions) {
            if (teemingOptions.hasOwnProperty(index)) {
                let teemingOption = teemingOptions[index];
                selectTeemingOptions.push({
                    id: teemingOption.key,
                    name: teemingOption.description
                });
            }
        }

        return {
            id: new Attribute({
                type: 'int', // Not changeable by user
                edit: false, // Not directly changeable by user
            }),
            faction: new Attribute({
                type: 'select',
                values: selectFactions,
                admin: true,
                show_default: false
            }),
            teeming: new Attribute({
                type: 'select',
                values: selectTeemingOptions,
                admin: true,
                show_default: false
            })
        };
    }

    /**
     * Gets the value of a property using either the getter or directly.
     * @param property {string}
     * @param attribute {Attribute}
     * @returns {*}
     * @private
     */
    _getValue(property, attribute) {
        console.assert(attribute instanceof Attribute, 'attribute is not an Attribute', this);
        return attribute.hasOwnProperty('getter') ? attribute.getter() : this[property];
    }

    /**
     * Sets the value of a property using either the setter or directly.
     * @param attribute {Attribute}
     * @param property {string}
     * @param value {*}
     * @private
     */
    _setValue(attribute, property, value) {
        console.assert(attribute instanceof Attribute, 'attribute is not an Attribute', this);

        // Use setter if supplied
        if (attribute.hasOwnProperty('setter')) {
            attribute.setter(value);
        } else {
            // Assign variable back to us
            this[property] = value;
        }
    }

    /**
     * Get the name of the current object (MapObject) and returns a snake cased version (map_object).
     * @returns {string}
     * @private
     */
    _getSnakeCaseName() {
        return toSnakeCase(this.constructor.name);
    }

    /**
     * Get the last part of the URL we're using to edit this map object.
     * @returns {string}
     * @protected
     */
    _getRouteSuffix() {
        return this.constructor.name.toLowerCase();
    }

    /**
     * Assigns the popup to this map object
     * @protected
     */
    _assignPopup() {
        console.assert(this instanceof MapObject, 'this is not a MapObject', this);

        let self = this;

        if (this.layer !== null && this.isEditable()) {
            this.layer.unbindPopup();
            this.layer.bindPopup(this._getPopupHtml(), {
                'maxWidth': '400',
                'minWidth': '300',
                'className': 'popupCustom'
            });

            // Popup trigger function, needs to be outside the synced function to prevent multiple bindings
            // This also cannot be a private function since that'll apparently give different signatures as well
            // (and thus trigger the submit function multiple times when clicked once)
            let popupOpenFn = function (event) {
                let mapObjectName = self._getSnakeCaseName();

                // Prevent multiple binds to click
                let $submitBtn = $(`#map_${mapObjectName}_edit_popup_submit_${self.id}`);
                $submitBtn.unbind('click');
                $submitBtn.bind('click', self._popupSubmitClicked.bind(self));

                self._initPopup();
            };

            this.layer.off('popupopen');
            this.layer.on('popupopen', popupOpenFn);
        }
    }

    /**
     * Initializes the popup
     * @returns {*}
     * @private
     */
    _initPopup() {
        console.assert(this instanceof MapObject, 'this is not a MapObject', this);

        let self = this;

        refreshSelectPickers();

        let mapObjectName = this._getSnakeCaseName();
        let attributes = this._getAttributes();

        for (let property in attributes) {
            if (attributes.hasOwnProperty(property)) {
                let attribute = attributes[property];

                if (attribute.type === 'color') {
                    // Clean up the previous instance if any
                    if (typeof attribute._pickr !== 'undefined') {
                        // Unset it after to be sure to clear it for the next time
                        attributes[property]._tempPickrColor = null;
                        attributes[property]._pickr.destroyAndRemove();
                    }
                    //
                    attribute._pickr = Pickr.create($.extend(c.map.colorPickerDefaultOptions, {
                        el: `#map_${mapObjectName}_edit_popup_${property}_btn_${this.id}`,
                        default: this._getValue(property, attribute)
                    })).on('save', (color, instance) => {
                        // Apply the new color
                        let newColor = '#' + color.toHEXA().join('');
                        // Only save when the color is valid
                        if (self._getValue(property, attribute) !== newColor && newColor.length === 7) {
                            $(`#map_${mapObjectName}_edit_popup_${property}_${this.id}`).val(newColor);
                        }

                        // Reset ourselves
                        instance.hide();
                    });
                }
            }
        }
    }

    _popupSubmitClicked() {
        console.assert(this instanceof MapObject, 'this was not a MapObject', this);
        let mapObjectName = this._getSnakeCaseName();
        let attributes = this._getAttributes();

        for (let property in attributes) {
            if (attributes.hasOwnProperty(property)) {
                let attribute = attributes[property];
                // Color is already set by Pickr
                if (attribute.isEditable()) {
                    let $element = $(`#map_${mapObjectName}_edit_popup_${property}_${this.id}`);
                    console.assert($element.length > 0, 'Element must be found!', attribute);

                    // Read the value differently based on its type
                    let val = $element.val();

                    // Do post processing if necessary
                    switch (attribute.type) {
                        case 'select':
                            // If the string contains a numeric value, parse it as such
                            let intVal = parseInt(val);
                            if ((intVal + '') === val) {
                                val = intVal;
                            }
                            break;
                        case 'bool':
                            val = $element.is(':checked') ? 1 : 0;
                            break;
                        case 'int':
                            val = parseInt(val);
                            break;
                        case 'float':
                            val = parseFloat(val);
                            break;
                        case 'double':
                            val = parseFloat(val);
                            break;
                    }

                    this._setValue(attribute, property, val);
                    // Let anyone else know it's changed so they may act upon it
                    this.signal('property:changed', {property: property, value: val});
                }
            }
        }

        this.save();
        // this.bindTooltip();
    }

    /**
     * Get the html for the popup as defined by the attributes of this map object
     * @returns {jQuery}
     * @private
     */
    _getPopupHtml() {
        console.assert(this instanceof MapObject, 'this is not a MapObject', this);
        let result = '';

        let mapObjectName = this._getSnakeCaseName();
        let attributes = this._getAttributes();

        for (let property in attributes) {
            if (attributes.hasOwnProperty(property)) {
                let attribute = attributes[property];

                if (attribute.isEditable()) {
                    let handlebarsString = '';

                    switch (attribute.type) {
                        case 'select':
                            handlebarsString = 'map_popup_type_select_template';
                            console.assert(attribute.hasOwnProperty('values'), `Attribute must have 'values' property if you choose 'select'!`, attribute);
                            break;
                        case 'textarea':
                            handlebarsString = 'map_popup_type_textarea_template';
                            break;
                        case 'bool':
                            handlebarsString = 'map_popup_type_bool_template';
                            break;
                        case 'color':
                            handlebarsString = 'map_popup_type_color_template';
                            break;
                        case 'string':
                        case 'text':
                        case 'int':
                        case 'float':
                        case 'double':
                        default:
                            handlebarsString = 'map_popup_type_text_template';
                            break;
                    }

                    let typeTemplate = Handlebars.templates[handlebarsString];

                    result += typeTemplate($.extend({}, getHandlebarsDefaultVariables(), {
                        id: this.id,
                        property: property,
                        map_object_name: mapObjectName,
                        label: lang.get(`messages.${mapObjectName}_${property}_label`),
                        value: this._getValue(property, attribute),
                        values: attribute.hasOwnProperty('values') ? attribute.values : [],
                        select_default_label: attribute.type === 'select' ? lang.get(`messages.${mapObjectName}_${property}_select_default_label`) : '',
                        show_default: attribute.hasOwnProperty('show_default') ? attribute.show_default : true,
                        live_search: attribute.hasOwnProperty('live_search') ? attribute.live_search : false
                    }));
                }
            }
        }

        let popupTemplate = Handlebars.templates['map_popup_template'];

        return popupTemplate($.extend({}, getHandlebarsDefaultVariables(), {
            id: this.id,
            html: result,
            map_object_name: mapObjectName
        }));
    }

    /**
     * Cleans up the decorator of this route, removing it from the map.
     * @private
     */
    _cleanDecorator() {
        console.assert(this instanceof MapObject, 'this is not a MapObject', this);

        if (this.decorator !== null) {
            this.map.leafletMap.removeLayer(this.decorator);
        }
    }

    /**
     * Rebuild the decorators for this route (directional arrows etc).
     * @private
     */
    _rebuildDecorator() {
        console.assert(this instanceof MapObject, 'this is not a MapObject', this);

        this._cleanDecorator();

        this.decorator = this._getDecorator();
        // Only if set after the getter finished
        if (this.decorator !== null) {
            this.decorator.addTo(this.map.leafletMap);
        }
    }

    _getDecorator() {
        return null;
    }

    _updateContextMenuOptions() {
        return {
            contextmenuWidth: 140,
            // Handled by loop in onLayerInit(), we want to refresh the list on every click
            // contextmenuItems: this.getContextMenuItems()
        };
    }

    /**
     * Populates this map object with a remote map object.
     * @param remoteMapObject {object} As received from the server
     */
    loadRemoteMapObject(remoteMapObject) {
        let attributes = this._getAttributes();

        for (let property in attributes) {
            if (attributes.hasOwnProperty(property)) {
                let attribute = attributes[property];
                if (remoteMapObject.hasOwnProperty(property)) {
                    // Assign the attributes from the object
                    if (typeof attribute.setter === 'function') {
                        attribute.setter(remoteMapObject[property]);
                    } else {
                        this[property] = remoteMapObject[property];
                    }
                } else {
                    // @TODO MapIcons don't have Teeming and Faction properties so this gets thrown a lot
                    // console.error(`Wanted to load attribute '${property}' from remoteMapObject but it didn't have this property!`);
                }
            }
        }
    }

    /**
     * Deletes this object locally; removing it from the screen and everywhere else.
     */
    localDelete() {
        console.assert(this instanceof MapObject, 'this is not a MapObject', this);
        this.signal('object:deleted');
    }

    getContextMenuItems() {
        return [
            //     {
            //     text: this.label + ' (synced: ' + this.synced + ')',
            //     disabled: true
            // }
        ];
    }

    /**
     * Gets if this map object is editable, default is true. May be overridden.
     * @returns {boolean}
     */
    isEditable() {
        return true;
    }

    /**
     * Gets if this map object is deleteable, default is true. May be overridden.
     * @returns {boolean}
     */
    isDeletable() {
        return true;
    }

    /**
     * Sets this enemy to be visible by default or not. Note: only read/used at initial load in!
     * @param value boolean
     */
    setDefaultVisible(value) {
        console.assert(this instanceof MapObject, 'this is not a MapObject', this);
        this._defaultVisible = value;
    }

    /**
     * Checks if this object is visible by default.
     * @returns {boolean}
     */
    isDefaultVisible() {
        console.assert(this instanceof MapObject, 'this is not a MapObject', this);
        return this._defaultVisible;
    }

    /**
     * Unbinds the tooltip from this map object.
     */
    unbindTooltip() {
        console.assert(this instanceof MapObject, 'this is not a MapObject', this);
        this.layer.unbindTooltip();
    }

    /**
     * Applies the tooltip to this map object if applicable.
     */
    bindTooltip() {

    }

    /**
     * Sets the colors to use for a map object, if applicable.
     * @param colors object The colors object as found in the constants.js file.
     */
    setColors(colors) {
        console.assert(this instanceof MapObject, 'this is not a MapObject', this);
        this.colors = colors;
    }

    /**
     * Sets the synced state of the map object. Will adjust the colors of the layer if colors are set.
     * @param value bool True to set the status to synced, false to unsynced.
     * @todo Somehow this does not work when trying to set edited colors. Very strange, couldn't get it to work
     */
    setSynced(value) {
        console.assert(this instanceof MapObject, 'this is not a MapObject', this);

        // Only if the colors object was ever set by a parent
        if (typeof this.colors !== 'undefined' && this.layer !== null && typeof this.layer.setStyle === 'function') {
            // Now synced
            if (value) {
                this.layer.setStyle({
                    fillColor: this.colors.saved,
                    color: this.colors.savedBorder
                });
            }
            // No longer synced when it was synced
            else if (!value && this.synced) {
                this.layer.setStyle({
                    fillColor: this.colors.edited,
                    color: this.colors.editedBorder
                });
            }
            // No longer synced, possibly wasn't in the first place, so unsaved
            else if (!value) {
                this.layer.setStyle({
                    fillColor: this.colors.unsaved,
                    color: this.colors.unsavedBorder
                });
            }
            this.layer.redraw();
        }

        // If we're synced, trigger the synced event
        if (value) {
            // Refresh the tooltip
            this.bindTooltip();
            this._assignPopup();

            this.signal('synced');
        }

        this.synced = value;
    }

    /**
     * Called when the layer should be initialized.
     */
    onLayerInit() {
        let self = this;
        console.assert(this instanceof MapObject, 'this is not a MapObject', this);

        self.layer.bindContextMenu(self._updateContextMenuOptions());
        self.layer.on('contextmenu', function () {
            let items = self.getContextMenuItems();
            self.map.leafletMap.contextmenu.removeAllItems();

            $.each(items, function (index, value) {
                self.map.leafletMap.contextmenu.addItem(value);
            });
            return true;
        });
        self.layer.on('draw:edited', function () {
            // Changed = gone out of sync
            self.setSynced(false);
        });
    }

    /**
     * Saves thiss map object to the server.
     */
    save() {
        let self = this;
        console.assert(this instanceof MapObject, 'this was not a MapObject', this);

        // Construct the
        let data = {};
        let mapObjectName = this._getSnakeCaseName();
        let attributes = this._getAttributes();

        for (let property in attributes) {
            if (attributes.hasOwnProperty(property)) {
                let attribute = attributes[property];

                if (attribute.isSaveable() && attribute.isEditableAdmin()) {
                    data[property] = this._getValue(property, attribute);
                }
            }
        }

        $.ajax({
            type: 'POST',
            url: `/ajax/${getState().getDungeonRoute().publicKey}/${this._getRouteSuffix()}`,
            dataType: 'json',
            data: data,
            beforeSend: function () {
                $(`#map_${mapObjectName}_edit_popup_submit_${self.id}`).attr('disabled', 'disabled');
                self.signal('save:beforesend');
            },
            success: function (json) {
                self.id = json.id;

                self.setSynced(true);
                self.map.leafletMap.closePopup();
                // ID may have changed - refresh it
                self._assignPopup();

                self.signal('save:success', {json: json});
            },
            complete: function () {
                $(`#map_${mapObjectName}_edit_popup_submit_${self.id}`).removeAttr('disabled');
                self.signal('save:complete');
            },
            error: function (xhr, textStatus, errorThrown) {
                // Even if we were synced, make sure user knows it's no longer / an error occurred
                self.setSynced(false);

                defaultAjaxErrorFn(xhr, textStatus, errorThrown);
                self.signal('save:error');
            }
        });
    }

    delete() {
        let self = this;
        console.assert(this instanceof MapObject, 'this was not a MapObject', this);

        $.ajax({
            type: 'POST',
            url: `/ajax/${getState().getDungeonRoute().publicKey}/${this._getRouteSuffix()}/${this.id}`,
            dataType: 'json',
            data: {
                _method: 'DELETE'
            },
            success: function (json) {
                self.localDelete();
            },
            error: function (xhr, textStatus, errorThrown) {
                // Even if we were synced, make sure user knows it's no longer / an error occurred
                self.setSynced(false);

                defaultAjaxErrorFn(xhr, textStatus, errorThrown);
            }
        });
    }

    toString() {
        return 'MapObject-' + this.id;
    }

    cleanup() {
        this.map.unregister('map:beforerefresh', this);

        this._cleanupSignals();
        this._cleanDecorator();

        super.cleanup();
    }
}