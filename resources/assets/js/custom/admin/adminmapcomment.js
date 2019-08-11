class AdminMapComment extends MapComment {

    // Actually this class is quite empty. But I'll have it anyways for any possible later additions.
    constructor(map, layer) {
        super(map, layer);
    }

    _popupSubmitClicked(){
        console.assert(this instanceof AdminMapComment, 'this was not a MapComment', this);
        // Set an additional parameter
        this.always_visible = $('#map_map_comment_edit_popup_always_visible_' + this.id).val();

        // Now the rest and submit
        super._popupSubmitClicked();
    }
}