class ReleaseView extends InlineCode {

    /**
     *
     */
    activate() {
        let key = 'changelog_release';
        console.log();

        let lastReadRelease = Cookies.get(key);
        if (typeof lastReadRelease === 'undefined' || lastReadRelease < this.options.max_release) {
            Cookies.set(key, this.options.max_release);
        }
    }
}