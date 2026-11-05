const ENEMY_MULTI_SELECT_DRAWN_EVENT = 'enemymultiselect:drawn';
const ENEMY_MULTI_EDIT_POPUP_ID = 'multi';

/**
 * Draws a box like a rectangle, but creates nothing: it hands its bounds to AdminEnemyMultiEdit instead.
 */
L.Draw.EnemyMultiSelect = L.Draw.Rectangle.extend({
    statics: {
        TYPE: 'enemymultiselect'
    },
    initialize: function (map, options) {
        L.Draw.Rectangle.prototype.initialize.call(this, map, options);

        // After the parent's initialize, which assigns its own type
        this.type = L.Draw.EnemyMultiSelect.TYPE;
    },
    _fireCreatedEvent: function () {
        this._map.fire(ENEMY_MULTI_SELECT_DRAWN_EVENT, {bounds: this._shape.getBounds()});
    }
});

/**
 * Edits every enemy inside a box drawn with L.Draw.EnemyMultiSelect at once, through the same popup a single
 * enemy shows. Only the fields the mapper changed are written to the selected enemies.
 */
class AdminEnemyMultiEdit {
    /**
     * @param map {DungeonMap}
     */
    constructor(map) {
        console.assert(map instanceof DungeonMap, 'map is not a DungeonMap', map);

        this.map = map;
        /** @type {Enemy[]} */
        this.enemies = [];
        /** @type {Attribute[]} */
        this._attributes = [];
        /** @type {Object.<string, *>} The value each attribute's field showed when the popup opened */
        this._initialValues = {};
        /** @type {Set<string>} Attributes whose selected enemies did not all share one value */
        this._mixedAttributeNames = new Set();
        /** @type {Set<string>} Attributes whose field the mapper interacted with */
        this._touchedAttributeNames = new Set();
        /** @type {L.Popup|null} */
        this._popup = null;
        /** @type {EnemyMultiEditEnemySelection|null} The map state the open popup belongs to */
        this._mapState = null;

        this._onSelectionDrawn = this._onSelectionDrawn.bind(this);
        this.map.leafletMap.on(ENEMY_MULTI_SELECT_DRAWN_EVENT, this._onSelectionDrawn);

        let self = this;
        this.map.register('map:mapstatechanged', this, function (mapStateChangedEvent) {
            if (self._mapState !== null && mapStateChangedEvent.data.previousMapState === self._mapState) {
                self._closePopup();
            }
        });
    }

    /**
     * @param drawnEvent {{bounds: L.LatLngBounds}}
     * @private
     */
    _onSelectionDrawn(drawnEvent) {
        this.select(this.findEnemiesInBounds(drawnEvent.bounds), drawnEvent.bounds.getCenter());
    }

    /**
     * @param bounds {L.LatLngBounds}
     * @returns {Enemy[]} The enemies the mapper can see and edit inside the bounds
     */
    findEnemiesInBounds(bounds) {
        let enemyMapObjectGroup = this.map.mapObjectGroupManager.getEnemyMapObjectGroup();

        return Object.values(enemyMapObjectGroup.objects).filter(function (enemy) {
            return enemy.layer !== null && enemy.isEditable() &&
                enemyMapObjectGroup.isMapObjectVisible(enemy) &&
                bounds.contains(enemy.layer.getLatLng());
        });
    }

    /**
     * Selects the enemies and opens the edit popup for them.
     * @param enemies {Enemy[]}
     * @param latLng {L.LatLng} Where to open the popup
     */
    select(enemies, latLng) {
        if (enemies.length === 0) {
            showInfoNotification(lang.get('js.enemy_multi_edit_no_enemies_selected'));
            return;
        }

        this._closePopup();

        this.enemies = enemies;
        this._mapState = new EnemyMultiEditEnemySelection(this.map, enemies);
        this.map.setMapState(this._mapState);
        this._openPopup(latLng);
    }

    /**
     * @param latLng {L.LatLng}
     * @private
     */
    _openPopup(latLng) {
        let self = this;
        /** @type {Enemy} */
        let templateEnemy = this.enemies[0];

        this._attributes = templateEnemy._getAttributes().filter(function (attribute) {
            return attribute.isBulkEditable() && !['object', 'button', 'color'].includes(attribute.type);
        });
        this._mixedAttributeNames = new Set();
        this._touchedAttributeNames = new Set();

        let html = templateEnemy._getPopupHtml(null, {
            id: ENEMY_MULTI_EDIT_POPUP_ID,
            title: lang.get('js.enemy_multi_edit_title', {count: this.enemies.length}),
            isAttributeIncluded: function (attribute) {
                return self._attributes.includes(attribute);
            },
            getTemplateData: function (attribute, data) {
                let sharedValue = self.getSharedValue(attribute.name);
                if (sharedValue.mixed) {
                    self._mixedAttributeNames.add(attribute.name);

                    return $.extend(data, {
                        value: null,
                        description: lang.get('js.enemy_multi_edit_mixed_values_description'),
                        // So that picking any real value is a change, also in a select that has no empty option
                        show_default: true,
                        select_default_label: lang.get('js.enemy_multi_edit_mixed_values_label'),
                    });
                }

                return $.extend(data, {value: sharedValue.value});
            },
        });

        this._popup = L.popup({
            maxWidth: 400,
            minWidth: 300,
            className: 'popupCustom',
        }).setLatLng(latLng).setContent(html);

        let mapState = this._mapState;
        this._popup.on('remove', function () {
            self._popup = null;
            self._mapState = null;
            if (self.map.getMapState() === mapState) {
                self.map.setMapState(null);
            }
        });

        this._popup.openOn(this.map.leafletMap);
        this._initPopup();
    }

    /**
     * @private
     */
    _initPopup() {
        let self = this;
        /** @type {Enemy} */
        let templateEnemy = this.enemies[0];

        templateEnemy._initPopup(null, ENEMY_MULTI_EDIT_POPUP_ID);

        // Replaces the template enemy's own submit handler that _initPopup() bound
        $(`#map_${templateEnemy.options.name}_edit_popup_submit_${ENEMY_MULTI_EDIT_POPUP_ID}`)
            .unbind('click').bind('click', function () {
            self.submit();
        });

        this._initialValues = {};
        for (let attribute of this._attributes) {
            let $element = this._getElement(attribute);
            if (attribute.type === 'bool' && this._mixedAttributeNames.has(attribute.name)) {
                $element.prop('indeterminate', true);
            }

            this._initialValues[attribute.name] = templateEnemy._readPopupValue(attribute, ENEMY_MULTI_EDIT_POPUP_ID);
            $element.on('change input', function () {
                self._touchedAttributeNames.add(attribute.name);
            });
        }
    }

    /**
     * @param attribute {Attribute}
     * @returns {jQuery}
     * @private
     */
    _getElement(attribute) {
        return $(`#map_${this.enemies[0].options.name}_edit_popup_${attribute.name}_${ENEMY_MULTI_EDIT_POPUP_ID}`);
    }

    /**
     * @param name {String}
     * @returns {{mixed: Boolean, value: *}} The value every selected enemy has for this attribute, if they all agree
     */
    getSharedValue(name) {
        let values = this.enemies.map((enemy) => enemy._getValue(name));
        let mixed = values.some((value) => !_.isEqual(value, values[0]));

        return {mixed: mixed, value: mixed ? null : values[0]};
    }

    /**
     * Reads the popup and applies what the mapper changed to every selected enemy.
     */
    submit() {
        let templateEnemy = this.enemies[0];
        let changedValues = {};

        for (let attribute of this._attributes) {
            let value = templateEnemy._readPopupValue(attribute, ENEMY_MULTI_EDIT_POPUP_ID);
            let isChanged = !_.isEqual(value, this._initialValues[attribute.name]) ||
                // A mixed field shows no one value, so choosing the one it happens to display still counts
                (this._mixedAttributeNames.has(attribute.name) && this._touchedAttributeNames.has(attribute.name));

            if (isChanged) {
                changedValues[attribute.name] = value;
            }
        }

        let enemies = this.enemies;
        this._closePopup();
        this.applyValues(enemies, changedValues);
    }

    /**
     * @param enemies {Enemy[]}
     * @param values {Object.<string, *>} Attribute name => the value to give it
     */
    applyValues(enemies, values) {
        if (Object.keys(values).length === 0) {
            return;
        }

        for (let enemy of enemies) {
            for (let name in values) {
                enemy._setValue(name, values[name]);
                enemy.signal('property:changed', {name: name, value: values[name]});
            }

            enemy.save();
        }
    }

    /**
     * @private
     */
    _closePopup() {
        if (this._popup !== null) {
            this.map.leafletMap.closePopup(this._popup);
        }
    }

    cleanup() {
        this._closePopup();
        this.map.leafletMap.off(ENEMY_MULTI_SELECT_DRAWN_EVENT, this._onSelectionDrawn);
        this.map.unregister('map:mapstatechanged', this);
    }
}

// Guarded export for the test runner (Vitest). This is a no-op in the browser,
// where `module` is undefined, so it does not affect the concatenated bundle.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        AdminEnemyMultiEdit,
        ENEMY_MULTI_SELECT_DRAWN_EVENT,
    };
}
