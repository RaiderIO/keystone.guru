class AdminUserList extends InlineCode {

    /**
     *
     */
    activate() {

        // Add a new row when the button is pressed
        $(this.options.patreon_select_selector).bind('change', function () {
            let $this = $(this);

            $.ajax({
                type: 'PUT',
                url: `/ajax/user/${$this.data('userid')}/patreon/paidtier`,
                data: {
                    paidtiers: $this.val()
                },
                dataType: 'json',
                success: function () {
                    showSuccessNotification('Paid tiers updated successfully');
                }
            });
        });
    }
}