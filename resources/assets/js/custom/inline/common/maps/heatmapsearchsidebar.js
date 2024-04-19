class CommonMapsHeatmapsearchsidebar extends InlineCode {


    constructor(options) {
        super(options);

        this.sidebar = new Sidebar(options);

        this._draggable = null;
    }


    /**
     *
     */
    activate() {
        super.activate();

        console.assert(this instanceof CommonMapsHeatmapsearchsidebar, 'this is not a CommonMapsHeatmapsearchsidebar', this);

        this.map = getState().getDungeonMap();

        // let self = this;

        this.sidebar.activate();

        if (this.options.defaultState > 1 && $('#map').width() > this.options.defaultState) {
            this.sidebar.showSidebar();
        }
    }

    /**
     *
     */
    cleanup() {
        console.assert(this instanceof CommonMapsHeatmapsearchsidebar, 'this is not a CommonMapsHeatmapsearchsidebar', this);

    }
}
