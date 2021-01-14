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

        $('#embed_copy_mdt_string').bind('click', this._fetchMdtExportStringAndCopy.bind(this));

        refreshTooltips();
    }

    cleanup() {
        super.cleanup();

        this.sidebar.cleanup();
    }

    /**
     *
     * @private
     */
    _fetchMdtExportStringAndCopy() {
        $.ajax({
            type: 'GET',
            url: `/ajax/${getState().getMapContext().getPublicKey()}/mdtExport`,
            dataType: 'json',
            beforeSend: function () {
                $('#embed_copy_mdt_string_loader').show();
                $('#embed_copy_mdt_string').hide();
            },
            success: function (json) {
                copyToClipboard(json.mdt_string, null, 2000);
            },
            complete: function () {
                $('#embed_copy_mdt_string_loader').hide();
                $('#embed_copy_mdt_string').show();
            }
        });
    }
}