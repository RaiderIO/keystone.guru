class ProfileEdit extends InlineCode {


    activate() {
        super.activate();

        let $classColors = $('.profile_class_color');
        $classColors.bind('click', function () {
            $('#echo_color').val($(this).data('color'));
        });

        $('#profile_user_reports_table').DataTable({});
    }
}