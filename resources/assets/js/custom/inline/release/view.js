class ReleaseView extends InlineCode {

    /**
     *
     */
    activate() {
        let key = 'changelog_release';
        console.log(this.options);

        let lastReadRelease = Cookies.get(key);
        if (typeof lastReadRelease === 'undefined' || lastReadRelease < this.options.max_release) {
            Cookies.set(key, this.options.max_release);
        }

        if (typeof this.options.releases === 'object') {
            let $copyReleaseReddit = $('.copy_release_format_reddit');
            $copyReleaseReddit.bind('click', function () {
                let template = Handlebars.templates['release_copy_to_reddit'];

                let data = $.extend({}, getHandlebarsDefaultVariables(), {
                    releases: self._getReleaseById(parseInt($(this).data('id')))
                });

                // Build the status bar from the template
                self.statusbar = $(template(data));
            });

            let $copyReleaseDiscord = $('.copy_release_format_discord');
            $copyReleaseDiscord.bind('click', function () {

            });
        }
    }

    /**
     *
     * @param id
     * @returns {null}|object
     * @private
     */
    _getReleaseById(id) {
        let result = null;

        for (let i = 0; i < this.options.releases.length; i++) {
            let release = this.options.releases[i];
            if (release.id === id) {
                result = release;
                break;
            }
        }

        return result;
    }
}