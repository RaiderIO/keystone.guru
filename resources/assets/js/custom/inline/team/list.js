class TeamList extends InlineCode {

    constructor(options){
        super(options);

        this._dt = null;
    }

    /**
     *
     */
    activate() {
        super.activate();

        this._dt = $('#team_table').DataTable({
            'searching': false,
            'bLengthChange': false,
            'language': {
                'emptyTable': lang.get('messages.datatable_no_teams_in_table')
            },
        });

        this._dt.on('click', 'tbody td.clickable', function (clickEvent) {
            window.location.href = '/team/' + $(clickEvent.currentTarget).parent().data('teamid');
        });

        this._dt.on('mouseenter', 'tbody tr', function () {
            $(this).addClass('row_selected');
        });

        this._dt.on('mouseleave', 'tbody tr', function () {
            $(this).removeClass('row_selected');
        });
    }
}