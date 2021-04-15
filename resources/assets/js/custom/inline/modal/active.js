class ModalActive extends InlineCode {

    /**
     *
     */
    activate() {
        super.activate();

        $(this.options.id).modal({
            show: true
        });
    }
}