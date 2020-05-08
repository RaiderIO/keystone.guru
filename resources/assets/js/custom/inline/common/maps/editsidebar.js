class CommonMapsEditsidebar extends InlineCode {


    constructor(options) {
        super(options);

        this.sidebar = new Sidebar(options);
        this.sidebar.activate();
    }

    /**
     *
     */
    activate() {
        let self = this;

        // Copy to clipboard functionality
        $('#map_copy_to_clipboard').bind('click', function () {
            // https://codepen.io/shaikmaqsood/pen/XmydxJ
            let $temp = $("<input>");
            $("body").append($temp);
            $temp.val($('#map_shareable_link').val()).select();
            document.execCommand("copy");
            $temp.remove();

            showInfoNotification(lang.get('messages.copied_to_clipboard'));
        });

        $(this.options.switchDungeonFloorSelect).change(function () {
            // Pass the new floor ID to the map
            getState().setFloorId($(self.options.switchDungeonFloorSelect).val());
            getState().getDungeonMap().refreshLeafletMap();
        });
    }
}