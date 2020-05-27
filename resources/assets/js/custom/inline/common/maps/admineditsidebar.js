class CommonMapsAdmineditsidebar extends InlineCode {
    constructor(options) {
        super(options);

        this.sidebar = new Sidebar(options);
    }

    /**
     *
     */
    activate() {
        this.sidebar.activate();

        let self = this;

        $(this.options.switchDungeonFloorSelect).change(function () {
            // Pass the new floor ID to the map
            getState().setFloorId($(self.options.switchDungeonFloorSelect).val());
            getState().getDungeonMap().refreshLeafletMap();
        });
    }

    cleanup() {
        super.cleanup();

        this.sidebar.cleanup();
    }
}