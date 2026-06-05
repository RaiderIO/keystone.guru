/**
 * @typedef {Object} CommonMapsAdmineditsidebarOptions
 * @property {string} switchDungeonFloorSelect
 * @property {number} defaultSelectedFloorId
 */

/**
 * @property {CommonMapsAdmineditsidebarOptions} options
 */
class CommonMapsAdmineditsidebar extends InlineCode {
    constructor(id, bladePath, options) {
        super(id, bladePath, options);

        this.sidebar = new SidebarNavigation(options);
    }

    activate() {
        super.activate();

        this.sidebar.activate();
    }


    cleanup() {
        super.cleanup();

        this.sidebar.cleanup();
    }
}
