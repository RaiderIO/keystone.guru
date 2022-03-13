class ReleaseView extends InlineCode {

    /**
     *
     */
    activate() {
        super.activate();

        let key = 'changelog_release';

        let self = this;

        let lastReadRelease = Cookies.get(key);
        if (typeof lastReadRelease === 'undefined' || lastReadRelease < this.options.max_release) {
            Cookies.set(key, this.options.max_release);
        }

        if (typeof this.options.releases === 'object') {
            let $copyReleaseReddit = $('.copy_release_format_reddit');
            $copyReleaseReddit.unbind('click').bind('click', function () {
                self._copyToClipboard(parseInt($(this).data('id')), 'release_copy_to_reddit');
            });

            let $copyReleaseDiscord = $('.copy_release_format_discord');
            $copyReleaseDiscord.unbind('click').bind('click', function () {
                self._copyToClipboard(parseInt($(this).data('id')), 'release_copy_to_discord');
            });

            let $copyReleaseGithub = $('.copy_release_format_github');
            $copyReleaseGithub.unbind('click').bind('click', function () {
                self._copyToClipboard(parseInt($(this).data('id')), 'release_copy_to_github');
            });
        }
    }

    /**
     * Copies a release to the clipboard
     * @param releaseId
     * @param handlebarsTemplate
     * @private
     */
    _copyToClipboard(releaseId, handlebarsTemplate) {
        let template = Handlebars.templates[handlebarsTemplate];

        let release = this._getReleaseById(releaseId);

        let createdAtDate = (new Date(release.created_at));

        let data = $.extend({}, getHandlebarsDefaultVariables(), {
            version: release.version,
            date: createdAtDate.getFullYear() + '/' + _.padStart(createdAtDate.getMonth() + 1, 2, '0') + '/' + _.padStart(createdAtDate.getDate(), 2, '0'),
            description: release.changelog.description,
            categories: []
        });

        let currentCategory = null;
        let currentCategoryChanges;
        for (let i = 0; i < release.changelog.changes.length; i++) {
            let currentChange = release.changelog.changes[i];
            if (currentCategory !== currentChange.category.category) {
                currentCategory = currentChange.category.category;
                currentCategoryChanges = []
                data.categories.push({
                    category: currentCategory,
                    changes: currentCategoryChanges
                });
            }

            currentCategoryChanges.push(currentChange);
        }
        // https://codepen.io/shaikmaqsood/pen/XmydxJ
        let $temp = $('<textarea>');
        $('body').append($temp);
        $temp.val(decodeHtmlEntity(template(data))).select();
        document.execCommand('copy');
        $temp.remove();

        showInfoNotification(lang.get('messages.copied_to_clipboard'));
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
