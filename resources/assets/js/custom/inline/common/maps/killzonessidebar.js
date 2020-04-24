class CommonMapsKillzonessidebar extends InlineCode {


    constructor(options) {
        super(options);

        this.sidebar = new Sidebar(options);
        this.sidebar.activate();
    }

    /**
     *
     */
    activate() {
        console.log('Killzones sidebar!');
    }
}