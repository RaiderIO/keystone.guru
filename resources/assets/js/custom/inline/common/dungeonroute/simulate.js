class CommonDungeonrouteSimulate extends InlineCode {
    /**
     *
     */
    activate() {
        super.activate();

        // Copy to clipboard functionality
        $('.copy_simulationcraft_string_to_clipboard').unbind('click').bind('click', function () {
            let $exportResult = $('#mdt_export_result');
            copyToClipboard($exportResult.val(), $exportResult);
        });
    }
}
