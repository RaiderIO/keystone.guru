/**
 * Whether every term of the filter occurs somewhere in a row's filter text, in any order and ignoring case and
 * accents - "zul g" finds Zul'Gurub, "brd" finds Blackrock Depths by its abbreviation.
 *
 * @param {string} filterText The row's lowercase name and abbreviation
 * @param {string} query What the visitor typed
 * @returns {boolean}
 */
function dungeonSheetRowMatches(filterText, query) {
    const haystack = foldForDungeonSheetFilter(filterText);

    return foldForDungeonSheetFilter(query).split(/\s+/)
        .filter(term => term !== '')
        .every(term => haystack.includes(term));
}

/**
 * @param {string} text
 * @returns {string}
 */
function foldForDungeonSheetFilter(text) {
    return text.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();
}

/**
 * The mobile dungeon sheet in the header: the filter narrows the rows and hides the sections it empties, Enter
 * follows the first row left, and opening the sheet brings the selected dungeon into view. Switching game version
 * reloads the page, so the sheet reopens itself afterwards on the new version's dungeons.
 */
class DungeonSheet {
    /**
     * Matches Bootstrap's `lg` breakpoint, from which the sheet is display: none (`d-lg-none`).
     */
    static DESKTOP_MEDIA_QUERY = '(min-width: 992px)';

    static REOPEN_STORAGE_KEY = 'dungeon_sheet_reopen_at';

    /**
     * A switch that takes longer than this never reopens the sheet - it was abandoned, and the visitor would not
     * expect a sheet on whatever page they reach next.
     */
    static REOPEN_MAX_AGE_MS = 30000;

    /**
     * @param {HTMLElement} element The #dungeon_sheet
     */
    constructor(element) {
        this.element = element;
        this.body = element.querySelector('.dungeon_sheet_body');
        this.input = element.querySelector('.dungeon_sheet_filter_input');
        this.rows = [...element.querySelectorAll('.dungeon_sheet_row')];
        this.groups = [...element.querySelectorAll('.dungeon_sheet_group')];
        this.empty = element.querySelector('.dungeon_sheet_empty');
        this.desktopMediaQuery = window.matchMedia(DungeonSheet.DESKTOP_MEDIA_QUERY);

        // The sticky header is a stacking context below Bootstrap's body-level backdrop
        document.body.appendChild(element);

        this.input.addEventListener('input', () => this.filter(this.input.value));
        this.input.addEventListener('keydown', this._onInputKeyDown.bind(this));
        element.addEventListener('show.bs.offcanvas', this._onShow.bind(this));
        element.addEventListener('hidden.bs.offcanvas', this._onHidden.bind(this));
        element.querySelectorAll('.game_version_segment').forEach(
            segment => segment.addEventListener('click', this._onGameVersionClick.bind(this))
        );

        // Bootstrap only dismisses an offcanvas on resize once it stops being position: fixed, so an open sheet
        // hidden by d-lg-none would leave its backdrop, scroll lock and focus trap behind
        this.desktopMediaQuery.addEventListener('change', event => {
            if (event.matches) {
                this.hide();
            }
        });

        // Back from the version's page restores this one from the bfcache as it was left: mid-switch
        window.addEventListener('pageshow', event => {
            if (event.persisted) {
                this._endGameVersionSwitch();
            }
        });

        this._reopenAfterGameVersionSwitch();
    }

    show() {
        bootstrap.Offcanvas.getOrCreateInstance(this.element).show();
    }

    hide() {
        bootstrap.Offcanvas.getInstance(this.element)?.hide();
    }

    /**
     * The tapped version takes the selection at once and the list dims, so the tap reads as taken while the
     * page reloads.
     *
     * @param {MouseEvent} event
     */
    _onGameVersionClick(event) {
        // A modified click opens the version elsewhere - this page is not reloading
        if (event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) {
            return;
        }

        const segment = event.currentTarget;
        if (segment.hasAttribute('aria-current')) {
            return;
        }

        sessionStorage.setItem(DungeonSheet.REOPEN_STORAGE_KEY, `${Date.now()}`);

        this.element.querySelectorAll('.game_version_segment').forEach(other => {
            other.classList.remove('border-accent');
            other.removeAttribute('aria-current');
        });
        segment.classList.add('border-accent');
        segment.setAttribute('aria-current', 'true');
        this.element.classList.add('is-switching');
        this.body.setAttribute('aria-busy', 'true');
    }

    _endGameVersionSwitch() {
        this.element.classList.remove('is-switching');
        this.body.removeAttribute('aria-busy');
        this.element.querySelectorAll('.game_version_segment').forEach(segment => {
            const isCurrent = segment.dataset.current === 'true';
            segment.classList.toggle('border-accent', isCurrent);
            if (isCurrent) {
                segment.setAttribute('aria-current', 'true');
            } else {
                segment.removeAttribute('aria-current');
            }
        });
    }

    _reopenAfterGameVersionSwitch() {
        const reopenAt = parseInt(sessionStorage.getItem(DungeonSheet.REOPEN_STORAGE_KEY) ?? '', 10);
        sessionStorage.removeItem(DungeonSheet.REOPEN_STORAGE_KEY);

        if (Number.isNaN(reopenAt) || Date.now() - reopenAt > DungeonSheet.REOPEN_MAX_AGE_MS || this.desktopMediaQuery.matches) {
            return;
        }

        this.show();
    }

    /**
     * @param {string} query
     */
    filter(query) {
        for (const row of this.rows) {
            row.parentElement.hidden = !dungeonSheetRowMatches(row.dataset.filterText ?? '', query);
        }

        for (const group of this.groups) {
            group.hidden = group.querySelector('li:not([hidden])') === null;
        }

        this.empty.hidden = this.visibleRows().length > 0;
    }

    /**
     * @returns {HTMLAnchorElement[]}
     */
    visibleRows() {
        return this.rows.filter(row => !row.parentElement.hidden);
    }

    /**
     * @param {KeyboardEvent} event
     */
    _onInputKeyDown(event) {
        // Enter also confirms an IME composition, which must not navigate away
        if (event.key !== 'Enter' || event.isComposing) {
            return;
        }

        event.preventDefault();
        this.visibleRows()[0]?.click();
    }

    /**
     * Centres the selected dungeon in the list. The sheet is still off-screen but laid out here, so the scroll
     * lands before it slides in rather than visibly after.
     */
    _onShow() {
        const selected = this.element.querySelector('.dungeon_sheet_row[aria-current]');
        if (selected === null) {
            return;
        }

        const bodyRect = this.body.getBoundingClientRect();
        const rowRect = selected.getBoundingClientRect();
        this.body.scrollTop += rowRect.top - bodyRect.top - (bodyRect.height - rowRect.height) / 2;
    }

    _onHidden() {
        if (this.input.value === '') {
            return;
        }

        this.input.value = '';
        this.filter('');
    }
}

// Guarded export for the test runner (Vitest). This is a no-op in the browser,
// where `module` is undefined, so it does not affect the concatenated bundle.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {DungeonSheet, dungeonSheetRowMatches};
}
