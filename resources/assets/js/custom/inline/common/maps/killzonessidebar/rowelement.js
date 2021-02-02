class RowElement extends Signalable {

    constructor(killZonesSidebar, handlebarsTemplate) {
        super();

        this.killZonesSidebar = killZonesSidebar;
        this.handlebarsTemplate = handlebarsTemplate;
        // Shortcut
        this.map = this.killZonesSidebar.map;
    }

    /**
     *
     * @return Object
     * @protected
     */
    _getTemplateData() {
        return [];
    }

    /**
     *
     */
    updateText() {

    }

    /**
     *
     */
    refresh() {

    }

    /**
     *
     * @param $targetContainer
     */
    render($targetContainer) {
        // Build the handlebars template
        let template = Handlebars.templates[this.handlebarsTemplate];

        let data = $.extend({}, getHandlebarsDefaultVariables(), this._getTemplateData());

        // Render the element into the sidebar
        $targetContainer.append(template(data));
    }

    remove() {

    }
}