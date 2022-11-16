/**
 * @property {Number} id
 * @property {String} faction
 * @property {String} teeming
 * @property {Boolean} local
 */
class MapObject extends Signalable {

    /**
     *
     * @param map
     * @param layer {L.layer}
     * @param options {Object}
     */
    constructor(map, layer = null, options = {}) {
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

        // Set to true when its map object has received its first mapobject:changed event
        this._initialized = false;
        // Null by default; TBD - false
        this._visible = null;
        this._defaultVisible = true;
        /** @type {Array} */
        this._cachedAttributes = null;
        this.synced = false;
        /** @type {DungeonMap} */
        this.map = map;
        /** @type {L.layer|null} */
        this.layer = layer;

        this.options = options;

        this.id = 0;
        this.faction = 'any'; // sensible default
        this.teeming = null; // visible, hidden, null
        this.local = false;
        this.label = 'default label';
        this.decorator = null;

        this.register('object:deleted', this, function () {
            self._cleanDecorator();
        });
        this.map.register('map:beforerefresh', this, function () {
            self._cleanDecorator();
        });

        this.register(['shown', 'hidden', 'object:changed'], this, function (event) {
            if (self.isVisible()) {
                self._rebuildDecorator();
            } else {
                self._cleanDecorator();
            }
        });

        this._setDefaults();
    }

    /**
     * Get the attributes that belong to this map object and how to represent them.
     * @param force {boolean} True to force a rebuild of the attributes.
     * @returns {Array}
     * @protected
     */
    _getAttributes(force = false) {
        console.assert(this instanceof MapObject, 'this is not a MapObject', this);

        let self = this;
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
                show_default: false,
                setter: function (value) {
                    self.faction = value === '' || value === null ? 'any' : value;
                }
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


    _setDefaults(parentAttribute = null) {
        // Set the defaults for our attributes so we don't get any undefined errors
        /** @type {Array} */
        let attributes = parentAttribute !== null && parentAttribute.hasOwnProperty('attributes') ? parentAttribute.attributes : this._getAttributes();

        for (let index in attributes) {
            if (attributes.hasOwnProperty(index)) {
                let attribute = attributes[index];

                // Recurse this function for objects with nested attributes
                if (attribute.type === 'object' && attribute.hasOwnProperty('attributes')) {
                    this[attribute.name] = {};
                    this._setDefaults(attribute);
                }
                // Grab default from the attribute function, otherwise directly from the property
                else if (typeof attribute.default === 'function') {
                    // Do not use _setValue - this sets some INITIAL values, just to have something there
                    if (parentAttribute !== null) {
                        this[parentAttribute.name][attribute.name] = attribute.default();
                    } else {
                        this[attribute.name] = attribute.default();
                    }
                } else if (typeof attribute.default !== 'undefined') {
                    // Do not use _setValue - this sets some INITIAL values, just to have something there
                    if (parentAttribute !== null) {
                        this[parentAttribute.name][attribute.name] = attribute.default;
                    } else {
                        this[attribute.name] = attribute.default;
                    }
                }
            }
        }
    }

    /**
     * Finds an attribute by the name of the attribute.
     * @param name {string}
     * @param attributes {object|null}
     * @returns {Attribute}|{null}
     * @private
     */
    _findAttributeByName(name, attributes = null) {
        console.assert(typeof name === 'string', `Name must be a string ${name}`, name);

        if (attributes === null) {
            attributes = this._getAttributes();
        }
        let attribute = null;
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
     * @param parentAttribute {Attribute|null}
     * @returns {*}
     * @private
     */
    _getValue(name, parentAttribute = null) {
        let attributes = parentAttribute !== null && parentAttribute.hasOwnProperty('attributes') ? parentAttribute.attributes : this._getAttributes();
        let attribute = this._findAttributeByName(name, attributes);
        console.assert(attribute !== null, `Unable to find attribute with name ${name}`, attributes);
        if (attribute === null) {
            return false;
        } else {
            return attribute.hasOwnProperty('getter') ? attribute.getter() :
                // If parent is set, grab it from the nested object
                parentAttribute !== null ? this[parentAttribute.name][name] : this[name];
        }
    }

    /**
     * Sets the value of a property using either the setter or directly.
     * @param name {string}
     * @param value {*}
     * @param parentAttribute {Attribute}
     * @private
     */
    _setValue(name, value, parentAttribute = null) {
        let attributes = parentAttribute !== null && parentAttribute.hasOwnProperty('attributes') ? parentAttribute.attributes : this._getAttributes();
        let attribute = this._findAttributeByName(name, attributes);
        console.assert(attribute !== null, `Unable to find attribute with name ${name}`, attributes);

        // Use setter if supplied
        if (attribute.hasOwnProperty('setter')) {
            attribute.setter(value);
        } else if (parentAttribute !== null) {
            this[parentAttribute.name][name] = value;
        } else {
            // Assign variable back to us
            this[name] = value;
        }
    }

    /**
     * Assigns the popup to a layer
     * @protected
     */
    _assignPopup(layer = null) {
        console.assert(this instanceof MapObject, 'this is not a MapObject', this);

        if (layer === null) {
            layer = this.layer;
        }

        let self = this;

        if (layer !== null) {
            // Always remove the popupopen event
            layer.off('popupopen');
            layer.unbindPopup();

            if (this.map.options.edit && this.isEditable() && this.isEditableByPopup()) {
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
                    $submitBtn.unbind('click').bind('click', function () {
                        self._popupSubmitClicked();
                    });

                    self._initPopup();
                };

                layer.on('popupopen', popupOpenFn);
            }
        }
    }

    /**
     * Initializes the popup AFTER it's been displayed to the user
     * @param parentAttribute {Attribute|null}
     * @returns {*}
     * @private
     */
    _initPopup(parentAttribute = null) {
        console.assert(this instanceof MapObject, 'this is not a MapObject', this);

        let self = this;

        refreshSelectPickers();

        let mapObjectName = this.options.name;
        let attributes = parentAttribute !== null && parentAttribute.hasOwnProperty('attributes')
            ? parentAttribute.attributes : this._getAttributes();

        for (let index in attributes) {
            if (attributes.hasOwnProperty(index)) {
                let attribute = attributes[index];
                let name = attribute.name;

                // Prevent infinite loops when having parentAttributes with no attributes set (enemy npc)
                if (attribute.type === 'object' && attribute !== parentAttribute) {
                    // Recursively init the popup
                    this._initPopup(attribute);
                } else if (attribute.isEditable() && attribute.type === 'color') {
                    // Clean up the previous instance if any
                    if (typeof attribute._pickr !== 'undefined') {
                        // Unset it after to be sure to clear it for the next time
                        attributes[index]._tempPickrColor = null;
                        attributes[index]._pickr.destroyAndRemove();
                    }
                    //
                    attribute._pickr = Pickr.create($.extend(c.map.colorPickerDefaultOptions, {
                        el: `#map_${mapObjectName}_edit_popup_${name}_btn_${this.id}`,
                        default: this._getValue(name, parentAttribute)
                    })).on('save', (color, instance) => {
                        // Apply the new color
                        let newColor = '#' + color.toHEXA().join('');
                        // Only save when the color is valid
                        if (self._getValue(name, parentAttribute) !== newColor && newColor.length === 7) {
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
     * @param parentAttribute {Attribute|null}
     * @private
     */
    _popupSubmitClicked(parentAttribute = null) {
        console.assert(this instanceof MapObject, 'this was not a MapObject', this);

        let mapObjectName = this.options.name;
        let attributes = parentAttribute !== null && parentAttribute.hasOwnProperty('attributes')
            ? parentAttribute.attributes : this._getAttributes();

        for (let index in attributes) {
            if (attributes.hasOwnProperty(index)) {
                let attribute = attributes[index];
                let name = attribute.name;

                // Color is already set by Pickr
                if (attribute.type === 'object' && attribute.hasOwnProperty('attributes')) {
                    this._popupSubmitClicked(attribute);
                } else if (attribute.isEditable()) {
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
                        case 'double':
                            val = parseFloat(val);
                            break;
                    }

                    if (typeof val === 'number' && isNaN(val)) {
                        val = null;
                    }

                    this._setValue(name, val, parentAttribute);
                    // Let anyone else know it's changed so they may act upon it
                    this.signal('property:changed', {name: name, value: val});
                }
            }
        }

        // Only trigger if we're the root
        if (parentAttribute === null) {
            this.save();
        }
    }

    /**
     * Get the html for the popup as defined by the attributes of this map object
     * @param parentAttribute {Attribute|null}
     * @returns {string}
     * @private
     */
    _getPopupHtml(parentAttribute = null) {
        console.assert(this instanceof MapObject, 'this is not a MapObject', this);
        let result = '';

        let mapObjectName = this.options.name;
        let attributes = parentAttribute !== null && parentAttribute.hasOwnProperty('attributes')
            ? parentAttribute.attributes : this._getAttributes();

        for (let index in attributes) {
            if (attributes.hasOwnProperty(index)) {
                let attribute = attributes[index];
                let name = attribute.name;

                if (attribute.isEditable()) {
                    let handlebarsString = '';

                    switch (attribute.type) {
                        // Nested objects should recursively handled
                        case 'object':
                            result += this._getPopupHtml(attribute);
                            continue;
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
                        value: this._getValue(name, parentAttribute),
                        values: attribute.hasOwnProperty('values') ?
                            (typeof attribute.values === 'function' ? attribute.values() : attribute.values)
                            : [],
                        select_default_label: attribute.type === 'select' ? lang.get(`messages.${mapObjectName}_${name}_select_default_label`) : '',
                        show_default: attribute.hasOwnProperty('show_default') ? attribute.show_default : true,
                        live_search: attribute.hasOwnProperty('live_search') ? attribute.live_search : false,
                        multiple: attribute.hasOwnProperty('multiple') ? attribute.multiple : false
                    }));
                }
            }
        }

        let popupTemplate = Handlebars.templates['map_popup_template'];

        if (parentAttribute === null) {
            return popupTemplate($.extend({}, getHandlebarsDefaultVariables(), {
                id: this.id,
                html: result,
                map_object_name: mapObjectName,
                map_object_name_pretty: lang.get(`messages.${mapObjectName}`),
                readonly: this.map.options.readonly
            }));
        } else {
            return result;
        }
    }

    /**
     * Sets this map object to be initialized
     * @private
     */
    _setInitialized() {
        let wasInitialized = this._initialized;
        this._initialized = true;

        if (wasInitialized !== this._initialized) {
            this.signal('object:initialized');
        }
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
     * @param parentAttribute {Attribute|null} If we're parsing a nested attribute, this will be set
     */
    loadRemoteMapObject(remoteMapObject, parentAttribute = null) {
        console.assert(this instanceof MapObject, 'this is not a MapObject', this);

        if (remoteMapObject === null) {
            console.warn('Unable to parse empty remoteMapObject');
            return;
        }

        // If we're parsing recursively, get the attributes of the parent instead of the root
        let attributes = parentAttribute !== null && parentAttribute.hasOwnProperty('attributes')
            ? parentAttribute.attributes : this._getAttributes();

        for (let index in attributes) {
            if (attributes.hasOwnProperty(index)) {
                let attribute = attributes[index];
                let name = attribute.name;

                if (remoteMapObject.hasOwnProperty(name)) {
                    let value = remoteMapObject[name];

                    // If we're going to parse this object in a nested way (otherwise calling normal setter is good enough)
                    if (attribute.type === 'object' && attribute.hasOwnProperty('attributes')) {
                        this[name] = {};
                        this.loadRemoteMapObject(value, attribute);
                    } else {
                        // Do some preprocessing if necessary
                        switch (attribute.type) {
                            case 'bool':
                                value = value >= 1;
                                break;
                        }
                        // Assign the attributes from the object
                        if (typeof attribute.setter === 'function') {
                            attribute.setter(value);
                        }
                        // If parent was set, account for it by setting the value inside the object
                        else if (parentAttribute !== null) {
                            this[parentAttribute.name][name] = value;
                        }
                        // Otherwise just set the attribute on the main object (most of the time)
                        else {
                            this[name] = value;
                        }
                    }
                } else {
                    // @TODO MapIcons don't have Teeming and Faction properties so this gets thrown a lot
                    // console.error(`Wanted to load attribute '${property}' from remoteMapObject but it didn't have this property!`);
                }
            }
        }

        // If the root has finished, we're now initialized
        if (parentAttribute === null) {
            this._setInitialized();
        }
    }

    /**
     * Deletes this object locally; removing it from the screen and everywhere else.
     *
     * @param massDelete boolean
     */
    localDelete(massDelete = false) {
        console.assert(this instanceof MapObject, 'this is not a MapObject', this);

        this.signal('object:deleted', {mass_delete: massDelete});
    }

    /**
     * Checks if this map object is initialized yet.
     * @returns {boolean}
     */
    isInitialized() {
        return this._initialized;
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
        console.assert(this instanceof MapObject, 'this is not a MapObject', this);

        let state = getState();

        // Floor states; most common reason for not being visible
        if (state.getCurrentFloor().id !== this.floor_id) {
            // console.log(`Hiding map object ${this.id} due to floor ${this.floor_id} !== ${state.getCurrentFloor().id}`);
            return false;
        }

        // All other states
        let mapContext = state.getMapContext();

        if (!state.isMapAdmin()) {
            if (this.hasOwnProperty('teeming')) {
                // If the map isn't teeming, but the enemy is teeming..
                if (!mapContext.getTeeming() && this.teeming === 'visible') {
                    // console.log(`Hiding enemy due to teeming A ${this.id}`);
                    return false;
                }
                // If the map is teeming, but the enemy shouldn't be there for teeming maps..
                else if (mapContext.getTeeming() && this.teeming === 'invisible') {
                    // console.log(`Hiding enemy due to teeming B ${this.id}`);
                    return false;
                }
            }

            if (this.hasOwnProperty('seasonal_index') && this.seasonal_index !== null) {
                // Ignore seasonal type if not set, but if it's set it must be awakened to hide the enemies based on seasonal_index
                if ((!this.hasOwnProperty('seasonal_type') ||
                        this.seasonal_type === ENEMY_SEASONAL_TYPE_AWAKENED ||
                        this.seasonal_type === ENEMY_SEASONAL_TYPE_TORMENTED
                    ) &&
                    mapContext.getSeasonalIndex() !== this.seasonal_index) {
                    // console.warn(`Hiding enemy due to seasonal_index ${this.id}`);
                    return false;
                }
            }
        }

        if (this.hasOwnProperty('faction')) {
            let faction = mapContext.getFaction();
            // Only when not in sandbox mode! (no idea why, it was like this)
            if (!this.map.isSandboxModeEnabled() && (this.faction !== 'any' && faction !== 'any' && this.faction !== faction)) {
                // console.log(`Hiding enemy due to faction ${this.id}`);
                return false;
            }
        }

        return true;
    }

    /**
     * Checks if the current MapObject is visible on the map or not.
     * @returns {*}
     */
    isVisible() {
        return this._visible === null ? false : this._visible;
    }

    /**
     * Sets the map object's visibility state.
     * @param visible
     */
    setVisible(visible) {
        let wasVisible = this._visible;
        this._visible = visible;
        // If there was a change
        if (wasVisible !== visible) {
            if (visible) {
                this.signal('shown', {visible: visible});
            } else {
                this.signal('hidden', {visible: visible});
            }
        }
    }

    /**
     * Checks if this map object is visible and if it's layer's bounds are actually on the map.
     * @returns {*}
     */
    isVisibleOnScreen() {
        let result = false;
        if (this.layer !== null && this.isVisible()) {
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
     * Unbinds and binds the tooltip again.
     */
    rebindTooltip() {
        if (this.layer !== null) {
            this.unbindTooltip();
        }
        this.bindTooltip();
    }

    /**
     * Sets the synced state of the map object. Will adjust the colors of the layer if colors are set.
     * @param value {Boolean} True to set the status to synced, false to unsynced.
     * @param massSave {Boolean} True if the source of this change was a mass save action.
     * @todo Somehow this does not work when trying to set edited colors. Very strange, couldn't get it to work
     */
    setSynced(value, massSave = false) {
        console.assert(this instanceof MapObject, 'this is not a MapObject', this);

        // If we're synced, trigger the synced event
        if (value) {
            // Refresh the tooltip
            this.bindTooltip();
            this._assignPopup();

            this.signal('object:changed', {mass_save: massSave});
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
     * @param massSave {Boolean} True if the source of saving came from a mass-save action
     */
    onSaveSuccess(json, massSave = false) {
        console.assert(this instanceof MapObject, 'this is not a MapObject', this);

    }

    /**
     * Called when this map object was deleted successfully.
     * @param json {object} The JSON response (if any).
     * @param massDelete {Boolean} True if the source of deletion came from a mass-delete action
     */
    onDeleteSuccess(json, massDelete = false) {
        console.assert(this instanceof MapObject, 'this is not a MapObject', this);

        this.cleanup();
    }

    /**
     * Get the data that should be sent to the server.
     * @param fields {string|array}
     * @returns {{}}
     */
    getSaveData(fields = '*') {
        console.assert(this instanceof MapObject, 'this is not a MapObject', this);

        if (typeof fields === 'object' && !fields.includes('id')) {
            fields.push('id');
        }

        // Construct the data to send to the server
        let data = {};
        let attributes = this._getAttributes();

        for (let index in attributes) {
            if (attributes.hasOwnProperty(index)) {
                let attribute = attributes[index];
                let name = attribute.name;

                // Either do all when fields is *, or when it's whitelisted explicitly
                if ((fields === '*' || fields.includes(name)) && attribute.isSaveable() && attribute.isEditableAdmin()) {
                    if (attribute.type === 'object' && attribute.hasOwnProperty('attributes')) {
                        // Loop over all attributes in the object and assign
                        let obj = {};
                        for (let childIndex in attribute.attributes) {
                            if (attribute.attributes.hasOwnProperty(childIndex)) {
                                let childAttribute = attribute.attributes[childIndex];
                                if (childAttribute.isSaveable() && childAttribute.isEditableAdmin()) {
                                    // Multiple select means send as an array
                                    if (attribute.hasOwnProperty('multiple') && attribute.multiple) {
                                        obj[childAttribute.name + '[]'] = this._getValue(childAttribute.name, attribute);
                                    } else {
                                        obj[childAttribute.name] = this._getValue(childAttribute.name, attribute);
                                    }
                                }
                            }
                        }

                        data[name] = obj;
                    } else if (attribute.type === 'bool') {
                        data[name] = this._getValue(name) ? 1 : 0;
                    } else {
                        // Multiple select means send as an array
                        if (attribute.hasOwnProperty('multiple') && attribute.multiple) {
                            data[name + '[]'] = this._getValue(name);
                        } else {
                            data[name] = this._getValue(name);
                        }
                    }
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

        self.signal('save:beforesend');

        let hasRouteModelBinding = this.options.hasOwnProperty('hasRouteModelBinding') ? this.options.hasRouteModelBinding : false;

        $.ajax({
            type: self.id > 0 && hasRouteModelBinding ? 'PUT' : 'POST',
            url: (this.options.hasOwnProperty('save_url') ? this.options.save_url :
                    `/ajax/${getState().getMapContext().getPublicKey()}/${this.options.route_suffix}`) +
                (self.id > 0 && hasRouteModelBinding ? `/${self.id}` : ''),
            dataType: 'json',
            data: this.getSaveData(),
            beforeSend: function () {
                $(`#map_${mapObjectName}_edit_popup_submit_${self.id}`).attr('disabled', 'disabled');
            },
            success: function (json) {
                // Apply all saved properties back our object
                self.loadRemoteMapObject(json);

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
            url: this.options.hasOwnProperty('delete_url') ? this.options.delete_url :
                `/ajax/${getState().getMapContext().getPublicKey()}/${this.options.route_suffix}/${this.id}`,
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
