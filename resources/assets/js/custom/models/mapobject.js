/**
 * @property id int
 * @property faction string
 * @property teeming string
 * @property local bool
 */
class MapObject extends Signalable {

    /**
     *
     * @param map
     * @param layer {L.layer}
     * @param options {Object}
     */
    constructor(map, layer, options) {
        super();
        console.assert(map instanceof DungeonMap, 'Passed map is not a DungeonMap!', map);
        console.assert(typeof options === 'object', 'options must be set and an object!', options);
        console.assert(typeof options.name === 'string', 'options.name must be set!', options);

        // Set default options, no need to repeat ourselves a lot of times
        if (typeof options.route_suffix === 'undefined') {
            options.route_suffix = options.name;
        }
        console.assert(typeof options.route_suffix === 'string', 'options.route_suffix must be set!', options);

        let self = this;

        // Visible by default
        this._visible = false;
        this._defaultVisible = true;
        /** @type {Array} */
        this._cachedAttributes = null;
        this.synced = false;
        /** @type DungeonMap */
        this.map = map;
        /** @type L.Layer|null */
        this.layer = layer;

        this.options = options;

        this.id = 0;
        this.faction = 'any'; // sensible default
        this.teeming = null; // visible, hidden, null
        this.local = false;
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
        /** @type {Array} */
        let attributes = this._getAttributes();

        for (let index in attributes) {
            if (attributes.hasOwnProperty(index)) {
                let attribute = attributes[index];
                let name = attribute.name;

                if (typeof attribute.default !== 'undefined') {
                    this[name] = attribute.default;
                }
            }
        }
    }

    /**
     * Get the attributes that belong to this map object and how to represent them.
     * @param force {boolean} True to force a rebuild of the attributes.
     * @returns {Array}
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

        return [
            new Attribute({
                name: 'id',
                type: 'int', // Not changeable by user
                edit: false, // Not directly changeable by user
            }),
            new Attribute({
                name: 'faction',
                type: 'select',
                values: selectFactions,
                admin: true,
                show_default: false
            }),
            new Attribute({
                name: 'teeming',
                type: 'select',
                values: selectTeemingOptions,
                admin: true,
                show_default: false
            })
        ];
    }

    /**
     * Finds an attribute by the name of the attribute.
     * @param name string
     * @returns {Attribute}|{null}
     * @private
     */
    _findAttributeByName(name) {
        console.assert(name !== null, `Name must be a string ${name}`, name);

        let attribute = null;
        let attributes = this._getAttributes();
        for (let index in attributes) {
            if (attributes.hasOwnProperty(index)) {
                let attributeCandidate = attributes[index];
                if (attributeCandidate.name === name) {
                    attribute = attributeCandidate;
                    break;
                }
            }
        }

        return attribute;
    }

    /**
     * Gets the value of a property using either the getter or directly.
     * @param name {string}
     * @returns {*}
     * @private
     */
    _getValue(name) {
        let attribute = this._findAttributeByName(name);
        let attributes = this._getAttributes();
        console.assert(attribute !== null, `Unable to find attribute with name ${name}`, attributes);
        if (attribute === null) {
            return false;
        } else {
            return attribute.hasOwnProperty('getter') ? attribute.getter() : this[name];
        }
    }

    /**
     * Sets the value of a property using either the setter or directly.
     * @param name {string}
     * @param value {*}
     * @private
     */
    _setValue(name, value) {
        let attribute = this._findAttributeByName(name);
        let attributes = this._getAttributes();
        console.assert(attribute !== null, `Unable to find attribute with name ${name}`, attributes);

        // Use setter if supplied
        if (attribute.hasOwnProperty('setter')) {
            attribute.setter(value);
        } else {
            // Assign variable back to us
            this[name] = value;
        }
    }

    /**
     * Assigns the popup to this map object
     * @protected
     */
    _assignPopup(layer = null) {
        console.assert(this instanceof MapObject, 'this is not a MapObject', this);

        if (layer === null) {
            layer = this.layer;
        }

        let self = this;

        if (layer !== null && this.map.options.edit && this.isEditable() && this.isEditableByPopup()) {
            layer.unbindPopup();
            layer.bindPopup(this._getPopupHtml(), {
                'maxWidth': '400',
                'minWidth': '300',
                'className': 'popupCustom'
            });

            // Popup trigger function, needs to be outside the synced function to prevent multiple bindings
            // This also cannot be a private function since that'll apparently give different signatures as well
            // (and thus trigger the submit function multiple times when clicked once)
            let popupOpenFn = function (event) {
                // Prevent multiple binds to click
                let $submitBtn = $(`#map_${self.options.name}_edit_popup_submit_${self.id}`);
                $submitBtn.unbind('click');
                $submitBtn.bind('click', self._popupSubmitClicked.bind(self));

                self._initPopup();
            };

            layer.off('popupopen');
            layer.on('popupopen', popupOpenFn);
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

        let mapObjectName = this.options.name;
        let attributes = this._getAttributes();

        for (let index in attributes) {
            if (attributes.hasOwnProperty(index)) {
                let attribute = attributes[index];
                let name = attribute.name;

                if (attribute.isEditable() && attribute.type === 'color') {
                    // Clean up the previous instance if any
                    if (typeof attribute._pickr !== 'undefined') {
                        // Unset it after to be sure to clear it for the next time
                        attributes[index]._tempPickrColor = null;
                        attributes[index]._pickr.destroyAndRemove();
                    }
                    //
                    attribute._pickr = Pickr.create($.extend(c.map.colorPickerDefaultOptions, {
                        el: `#map_${mapObjectName}_edit_popup_${name}_btn_${this.id}`,
                        default: this._getValue(name)
                    })).on('save', (color, instance) => {
                        // Apply the new color
                        let newColor = '#' + color.toHEXA().join('');
                        // Only save when the color is valid
                        if (self._getValue(name) !== newColor && newColor.length === 7) {
                            $(`#map_${mapObjectName}_edit_popup_${name}_${self.id}`).val(newColor);
                        }

                        // Reset ourselves
                        instance.hide();
                    });
                }
            }
        }
    }

    /**
     * Called when the popup submit button was clicked
     * @private
     */
    _popupSubmitClicked() {
        console.assert(this instanceof MapObject, 'this was not a MapObject', this);

        let mapObjectName = this.options.name;
        let attributes = this._getAttributes();

        for (let index in attributes) {
            if (attributes.hasOwnProperty(index)) {
                let attribute = attributes[index];
                let name = attribute.name;

                // Color is already set by Pickr
                if (attribute.isEditable()) {
                    let $element = $(`#map_${mapObjectName}_edit_popup_${name}_${this.id}`);
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

                    this._setValue(name, val);
                    // Let anyone else know it's changed so they may act upon it
                    this.signal('property:changed', {name: name, value: val});
                }
            }
        }

        this.save();
    }

    /**
     * Get the html for the popup as defined by the attributes of this map object
     * @returns {jQuery}
     * @private
     */
    _getPopupHtml() {
        console.assert(this instanceof MapObject, 'this is not a MapObject', this);
        let result = '';

        let mapObjectName = this.options.name;
        let attributes = this._getAttributes();

        for (let index in attributes) {
            if (attributes.hasOwnProperty(index)) {
                let attribute = attributes[index];
                let name = attribute.name;

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
                        property: name,
                        map_object_name: mapObjectName,
                        label: lang.get(`messages.${mapObjectName}_${name}_label`),
                        value: this._getValue(name),
                        values: attribute.hasOwnProperty('values') ? attribute.values : [],
                        select_default_label: attribute.type === 'select' ? lang.get(`messages.${mapObjectName}_${name}_select_default_label`) : '',
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
            this._assignPopup(this.decorator);
        }
    }

    /**
     * May be overridden by implementing classes to assign a decorator to the layer.
     * @returns {L.Layer}|null
     * @protected
     */
    _getDecorator() {
        return null;
    }

    /**
     * Populates this map object with a remote map object.
     * @param remoteMapObject {object} As received from the server
     */
    loadRemoteMapObject(remoteMapObject) {
        let attributes = this._getAttributes();

        for (let index in attributes) {
            if (attributes.hasOwnProperty(index)) {
                let attribute = attributes[index];
                let name = attribute.name;

                if (remoteMapObject.hasOwnProperty(name)) {
                    let value = remoteMapObject[name];
                    // Do some preprocessing if necessary
                    switch (attribute.type) {
                        case 'bool':
                            value = value === 1;
                            break;
                    }
                    // Assign the attributes from the object
                    if (typeof attribute.setter === 'function') {
                        attribute.setter(value);
                    } else {
                        this[name] = value;
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

    /**
     * Gets if this map object is editable by a popup or through other means.
     * @returns {boolean}
     */
    isEditableByPopup() {
        return true;
    }

    /**
     * Gets if this map object is editable, default is true. May be overridden.
     * @returns {boolean}
     */
    isEditable() {
        return true;
    }

    /**
     * Gets if this map object is deletable, default is true. May be overridden.
     * @returns {boolean}
     */
    isDeletable() {
        return true;
    }

    /**
     * Local objects will never be synced to the server.
     * @returns {boolean}
     */
    isLocal() {
        return this.local;
    }

    /**
     * Sets this map object to be local or not. Local objects will never be synchronized to the server.
     * @param local bool True to sync to the server, false to never sync it.
     */
    setIsLocal(local) {
        this.local = local;
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
     * Sets this enemy to be visible by default or not. Note: only read/used at initial load in!
     * @param value boolean
     */
    setDefaultVisible(value) {
        console.assert(this instanceof MapObject, 'this is not a MapObject', this);
        this._defaultVisible = value;
    }

    /**
     * Checks if this map object should be visible on map or not.
     * @return {boolean}
     */
    shouldBeVisible() {
        let result = true;

        if (this.hasOwnProperty('seasonal_index')) {
            if (this.seasonal_index !== null && getState().getSeasonalIndex() !== this.seasonal_index) {
                result = false;
            }
        }

        if (this.hasOwnProperty('faction')) {
            let faction = getState().getDungeonRoute().faction;
            // Only when not in try mode! (no idea why, it was like this)
            if (!this.map.isTryModeEnabled() && (this.faction !== 'any' && faction !== 'any' && this.faction !== faction)) {
                // console.warn('Skipping map object that does not belong to the requested faction ', remoteMapObject, faction);
                result = false;
            }
        }

        if (this.hasOwnProperty('teeming')) {
            // If the map isn't teeming, but the enemy is teeming..
            if (!getState().getTeeming() && this.teeming === 'visible') {
                // console.warn('Skipping teeming map object', remoteMapObject);
                result = false;
            }
            // If the map is teeming, but the enemy shouldn't be there for teeming maps..
            else if (getState().getTeeming() && this.teeming === 'invisible') {
                // console.warn('Skipping teeming-filtered map object', remoteMapObject.id);
                result = false;
            }
        }

        // Floor states
        if (getState().getCurrentFloor().id !== this.floor_id) {
            result = false;
        }

        return result;
    }

    /**
     * Checks if the current MapObject is visible on the map or not.
     * @returns {*}
     */
    isVisible() {
        return this._visible;
    }

    /**
     * Sets the map object's visibility state.
     * @param visible
     */
    setVisible(visible) {
        if( !this._visible && visible ){
            console.warn(`Toggling visibility of ${this.id}; now visible!`);
        }
        this._visible = visible;
        if (visible) {
            this.signal('shown', {visible: visible});
        } else {
            this.signal('hidden', {visible: visible});
        }
    }

    /**
     * Checks if this map object is visible and if it's layer's bounds are actually on the map.
     * @returns {*}
     */
    isVisibleOnMap() {
        let result = false;
        if (this.isVisible() && this.layer !== null) {
            result = this.map.leafletMap.getBounds().contains(this.layer.getLatLng())
        }
        return result;
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

        self.layer.on('draw:edited', function () {
            // Changed = gone out of sync
            self.setSynced(false);
        });
    }

    /**
     * Called when this map object was saved successfully.
     * @param json {object} The JSON response (if any).
     */
    onSaveSuccess(json) {
        console.assert(this instanceof MapObject, 'this is not a MapObject', this);

    }

    /**
     * Called when this map object was deleted successfully.
     * @param json {object} The JSON response (if any).
     */
    onDeleteSuccess(json) {
        console.assert(this instanceof MapObject, 'this is not a MapObject', this);

    }

    /**
     * Get the data that should be sent to the server.
     * @returns {{}}
     */
    getSaveData() {
        // Construct the data to send to the server
        let data = {};
        let attributes = this._getAttributes();

        for (let index in attributes) {
            if (attributes.hasOwnProperty(index)) {
                let attribute = attributes[index];
                let name = attribute.name;

                if (attribute.isSaveable() && attribute.isEditableAdmin()) {
                    data[name] = this._getValue(name);
                }
            }
        }

        return data;
    }

    /**
     * Saves this map object to the server.
     */
    save() {
        if (this.isLocal()) {
            return;
        }
        let mapObjectName = this.options.name;

        let self = this;
        console.assert(this instanceof MapObject, 'this was not a MapObject', this);


        $.ajax({
            type: 'POST',
            url: `/ajax/${getState().getDungeonRoute().publicKey}/${this.options.route_suffix}`,
            dataType: 'json',
            data: this.getSaveData(),
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

                self.onSaveSuccess(json);
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

    /**
     * Deletes this object from the server.
     */
    delete() {
        if (this.isLocal()) {
            return;
        }

        let self = this;
        console.assert(this instanceof MapObject, 'this was not a MapObject', this);

        $.ajax({
            type: 'POST',
            url: `/ajax/${getState().getDungeonRoute().publicKey}/${this.options.route_suffix}/${this.id}`,
            dataType: 'json',
            data: {
                _method: 'DELETE'
            },
            success: function (json) {
                self.localDelete();

                self.onDeleteSuccess(json);
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