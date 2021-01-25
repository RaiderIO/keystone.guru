class CommonTagAddtag extends InlineCode {

    constructor(options) {
        super(options);

        // If not overridden, set default
        if (typeof options.dependencies === 'undefined') {
            options.dependencies = ['common/maps/editsidebar'];
        }
    }
}