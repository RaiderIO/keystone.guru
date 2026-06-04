/**
 * @typedef {Object} ReleaseViewOptions
 * @property {number} max_release
 */

/**
 * @property {ReleaseViewOptions} options
 */
class ReleaseView extends InlineCode {

    activate() {
        super.activate();

        let key = 'changelog_release';

        let lastReadRelease = Cookies.get(key);
        if (typeof lastReadRelease === 'undefined' || lastReadRelease < this.options.max_release) {
            Cookies.set(key, this.options.max_release, cookieDefaultAttributes);
        }
    }
}
