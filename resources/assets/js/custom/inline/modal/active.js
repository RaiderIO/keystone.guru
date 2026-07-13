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

        bootstrap.Modal.getOrCreateInstance(document.querySelector(this.options.id)).show();
    }
}
