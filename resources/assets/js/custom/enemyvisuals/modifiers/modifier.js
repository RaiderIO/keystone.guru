class EnemyVisualModifier extends EnemyVisualIcon {
    constructor(enemyvisual, index) {
        super(enemyvisual);
        this.index = index;
        this.enemyvisual.register('enemyvisual:builtvisual', this, this._visualBuilt.bind(this));

        /** @type {boolean|null} */
        this._wasVisible = null;
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
     * @returns {{}}
     * @protected
     */
    _getLocation(width, height, margin) {
        console.assert(false, 'Must implement _getLocation() function!');
    }

    /**
     * @param currentZoomLevel {float}
     * @param width {float}
     * @param height {float}
     * @param margin {float}
     */
    updateVisibility(currentZoomLevel, width, height, margin) {
        console.assert(this instanceof EnemyVisualModifier, 'this is not an EnemyVisualModifier!', this);

        let visible = currentZoomLevel >= this._getVisibleAtZoomLevel();
        let visibleChanged = this._wasVisible !== visible;

        if (visible || visibleChanged) {
            // Use plain JS for performance reasons
            let attribute = document.getElementById(`map_enemy_visual_attribute_${this._getName()}_${this.enemyvisual.enemy.id}`);

            // Only if there was a change in visibility
            if (visibleChanged) {
                // Hide or show based on the current zoom level
                // Null = visible, none = invisible
                attribute.style.display = visible ? null : 'none';
            }

            // Only update left/right
            if (visible) {
                let size = this._getLocation(width, height, margin);
                attribute.style.left = `${size.left}px`;
                attribute.style.top = `${size.top}px`;
            }
        }

        this._wasVisible = visible;
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