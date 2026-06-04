/**
 * @typedef {Object} ModalActiveOptions
 * @property {string} id
 */

/**
 * @property {ModalActiveOptions} options
 */
class ModalActive extends InlineCode {

    activate() {
        super.activate();

        $(this.options.id).modal({
            show: true
        });
    }
}
