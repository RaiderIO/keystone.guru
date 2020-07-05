class EnemyVisualModifier extends EnemyVisualIcon {
    constructor(enemyvisual, index) {
        super(enemyvisual);
        this.index = index;
        this.enemyvisual.register('enemyvisual:builtvisual', this, this._visualBuilt.bind(this));
    }

    /**
     * Checks if this modifier is visible. May be overridden by implementing classes.
     * @returns {boolean}
     * @private
     */
    _isVisible() {
        return this.enemyvisual.layer._icon instanceof Element && this.iconName !== '' && this.iconName !== null;
    }

    /**
     * Called whenever the visual has built its structure and we should manipulate it.
     * @private
     */
    _visualBuilt() {
        console.assert(this instanceof EnemyVisualModifier, 'this is not an EnemyVisualModifier!', this);
        // Only build our visual when we ourselves are visible
        if (this._isVisible()) {
            let element = this.enemyvisual.layer._icon;
            console.assert(element instanceof Element, 'element is not an Element! (Leaflet changed their internal structure?)', this);
            this.onVisualBuilt(element);
        }
    }

    /**
     * Gets the template data for this modifier
     * @param width {int}
     * @param height {int}
     * @param margin {int}
     * @returns {{id: number}}
     * @private
     */
    _getTemplateData(width, height, margin) {
        console.assert(this instanceof EnemyVisualModifier, 'this is not an EnemyVisualModifier!', this);

        return {
            id: this.enemyvisual.enemy.id,
            name: this._getName(),
        }
    }

    /**
     * Gets the name of this modifier for identification purposes.
     * @returns {string}
     */
    _getName() {
        console.assert(false, 'Must implement _getName() function!');
    }

    /**
     * Gets the zoom level at which this modifier is visible.
     * @returns {number}
     * @private
     */
    _getVisibleAtZoomLevel() {
        // By default, always visible
        return 1;
    }

    /**
     *
     * @param width {float}
     * @param height {float}
     * @param margin {float}
     * @returns {}
     * @private
     */
    _getLocation(width, height, margin) {
        console.assert(false, 'Must implement _getLocation() function!');
    }

    /**
     * @param currentZoomLevel {int}
     * @param width {float}
     * @param height {float}
     * @param margin {float}
     */
    updateVisibility(currentZoomLevel, width, height, margin) {
        console.assert(this instanceof EnemyVisualModifier, 'this is not an EnemyVisualModifier!', this);

        // Hide or show based on the current zoom level
        // console.log(this._getName(), this.enemyvisual.enemy.id, `#map_enemy_visual_attribute_${this._getName()}_${this.enemyvisual.enemy.id}`);
        let $attribute = $(`#map_enemy_visual_attribute_${this._getName()}_${this.enemyvisual.enemy.id}`);

        $attribute.toggle(currentZoomLevel >= this._getVisibleAtZoomLevel());
        let size = this._getLocation(width, height, margin);
        $attribute[0].style.left = `${size.left}px`;
        $attribute[0].style.top = `${size.top}px`;
    }

    /**
     * Called whenever the visual has been built, with the actual element as a parameter. Override this function in
     * child classes if needed.
     * @param element
     */
    onVisualBuilt(element) {
        let $element = $(element);
        let us = $element.find('.modifier_' + this.index);
        if (this._isVisible()) {
            us.show();
        } else {
            us.hide();
        }
    }

    cleanup() {
        super.cleanup();

        this.enemyvisual.unregister('enemyvisual:builtvisual', this);
    }
}