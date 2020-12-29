class CommonMapsEmbedtopbar extends InlineCode {


    constructor(options) {
        super(options);

        this.sidebar = new SidebarNavigation(options);
    }

    /**
     *
     */
    activate() {
        super.activate();

        this.sidebar.activate();

        refreshTooltips();
    }

    cleanup() {
        super.cleanup();

        this.sidebar.cleanup();
    }
}