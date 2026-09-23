/**
 * @typedef {Object} CollectionViewOptions
 * @property {string} copyLinkButtonSelector
 */

/**
 * @property {CollectionViewOptions} options
 */
class CollectionView extends InlineCode {

    activate() {
        super.activate();

        let $copyLinkButton = $(this.options.copyLinkButtonSelector);
        $copyLinkButton.unbind('click').bind('click', function () {
            copyToClipboard($copyLinkButton.data('url'));
        });
    }
}
