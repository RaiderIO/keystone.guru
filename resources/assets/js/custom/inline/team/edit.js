class TeamEdit extends InlineCode {

    /**
     *
     * @param path
     */
    activate() {
        // Copy to clipboard functionality
        $('#team_invite_link_copy_to_clipboard').bind('click', function () {
            // https://codepen.io/shaikmaqsood/pen/XmydxJ
            let $temp = $("<input>");
            $("body").append($temp);
            $temp.val($('#team_members_invite_link').val()).select();
            document.execCommand("copy");
            $temp.remove();

            showInfoNotification(lang.get('messages.copied_to_clipboard'));
        });
    }
}