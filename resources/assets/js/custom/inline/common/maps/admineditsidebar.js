class CommonMapsAdmineditsidebar extends InlineCode {
    constructor(options) {
        super(options);

        this.sidebar = new Sidebar(options);
    }

    /**
     *
     */
    activate() {
        super.activate();

        this.sidebar.activate();
    }

    cleanup() {
        super.cleanup();

        this.sidebar.cleanup();
    }
}