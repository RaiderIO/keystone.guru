class CommonMapsViewsidebar extends InlineCode {


    constructor(options) {
        super(options);

        this.sidebar = new SidebarNavigation(options);
    }

    /**
     *
     */
    activate() {
        super.activate();

        let self = this;
        this.sidebar.activate();

        refreshTooltips();
    }


    cleanup() {
        super.cleanup();

        this.sidebar.cleanup();
    }
}