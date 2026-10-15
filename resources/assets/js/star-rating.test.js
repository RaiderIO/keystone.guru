// ---------------------------------------------------------------------------
// Covers $.fn.starRating (#3593), the self-owned replacement for the removed
// `jquery-bar-rating` plugin. The widget is built from the 1-10
// <select id="rating_select"> in the "conclude live session" modal: it hides
// the select, renders one star per option, reflects the selected option, and on
// click writes the value back and fires onSelect(value).
// ---------------------------------------------------------------------------

const $ = require('jquery');
require('./star-rating');

/**
 * @param {?number} selectedValue
 * @returns {HTMLSelectElement}
 */
function createRatingSelect(selectedValue = null) {
    let optionsHtml = '';
    for (let i = 1; i <= 10; i++) {
        const selected = selectedValue !== null && i === selectedValue ? ' selected' : '';
        optionsHtml += `<option value="${i}"${selected}>${i}</option>`;
    }
    document.body.innerHTML = `<select id="rating_select" name="rating_select">${optionsHtml}</select>`;

    return document.querySelector('#rating_select');
}

describe('$.fn.starRating (#3593)', () => {
    afterEach(() => {
        document.body.innerHTML = '';
    });

    test('starRating_givenSelect_rendersOneStarPerOptionAndHidesSelect', () => {
        const select = createRatingSelect();

        $(select).starRating();

        expect(document.querySelectorAll('.star-rating__widget a.star-rating__star').length).toBe(10);
        expect(select.style.display).toBe('none');
    });

    test('starRating_givenSelectedOption_reflectsInitialRating', () => {
        const select = createRatingSelect(5);

        $(select).starRating();

        const stars = document.querySelectorAll('.star-rating__star');
        expect(document.querySelectorAll('.star-rating__star--selected').length).toBe(5);
        expect(stars[4].classList.contains('star-rating__star--selected')).toBe(true);
        expect(stars[5].classList.contains('star-rating__star--selected')).toBe(false);
    });

    test('starRating_givenNoSelectedOption_lightsTheFirstStar', () => {
        // The blade renders no `selected` option until the user has rated; the browser then reports index 0
        const select = createRatingSelect();

        $(select).starRating();

        const stars = document.querySelectorAll('.star-rating__star');
        expect(document.querySelectorAll('.star-rating__star--selected')).toHaveLength(1);
        expect(stars[0].classList.contains('star-rating__star--selected')).toBe(true);
        expect(stars[0].getAttribute('tabindex')).toBe('0');
    });

    test('starRating_givenStarClicked_setsSelectValueAndFiresOnSelect', () => {
        const select = createRatingSelect(2);
        const onSelect = vi.fn();

        $(select).starRating({onSelect});
        $(document.querySelectorAll('.star-rating__star')[6]).trigger('click'); // 7th star

        expect(select.value).toBe('7');
        expect(onSelect).toHaveBeenCalledTimes(1);
        expect(onSelect).toHaveBeenCalledWith('7');
        expect(document.querySelectorAll('.star-rating__star--selected').length).toBe(7);
    });

    test('starRating_givenStarHovered_highlightsUpToHoveredStarThenRestores', () => {
        const select = createRatingSelect(2);

        $(select).starRating();

        $(document.querySelectorAll('.star-rating__star')[3]).trigger('mouseenter'); // 4th star
        expect(document.querySelectorAll('.star-rating__star--active').length).toBe(4);
        expect(document.querySelectorAll('.star-rating__star--selected').length).toBe(0);

        $('.star-rating__widget').trigger('mouseleave');
        expect(document.querySelectorAll('.star-rating__star--active').length).toBe(0);
        expect(document.querySelectorAll('.star-rating__star--selected').length).toBe(2);
    });

    test('starRating_givenNonSelectElement_doesNotThrowAndRendersNothing', () => {
        document.body.innerHTML = '<input id="rating_select" type="text">';

        expect(() => $('#rating_select').starRating()).not.toThrow();
        expect(document.querySelector('.star-rating__widget')).toBeNull();
        expect(document.querySelector('#rating_select').style.display).toBe('');
    });

    test('starRating_givenStarClicked_preventsTheLinkNavigation', () => {
        const select = createRatingSelect(2);
        $(select).starRating();
        const click = $.Event('click');

        $(document.querySelectorAll('.star-rating__star')[6]).trigger(click);

        expect(click.isDefaultPrevented()).toBe(true);
    });

    test.each(['Enter', ' '])('starRating_givenKey%jOnAStar_selectsItAndFiresOnSelect', key => {
        const select = createRatingSelect(2);
        const onSelect = vi.fn();
        $(select).starRating({onSelect});
        const keydown = $.Event('keydown', {key});

        $(document.querySelectorAll('.star-rating__star')[3]).trigger(keydown); // 4th star

        expect(select.value).toBe('4');
        expect(onSelect).toHaveBeenCalledWith('4');
        expect(keydown.isDefaultPrevented()).toBe(true);
    });

    test('starRating_givenOtherKeyOnAStar_leavesTheRatingAlone', () => {
        const select = createRatingSelect(2);
        const onSelect = vi.fn();
        $(select).starRating({onSelect});

        $(document.querySelectorAll('.star-rating__star')[3]).trigger($.Event('keydown', {key: 'a'}));

        expect(select.value).toBe('2');
        expect(onSelect).not.toHaveBeenCalled();
    });

    test('starRating_givenSelectedOption_marksOnlyThatStarCheckedAndFocusable', () => {
        const select = createRatingSelect(5);

        $(select).starRating();

        const stars = Array.from(document.querySelectorAll('.star-rating__star'));
        expect(stars.map(star => star.getAttribute('aria-checked'))).toEqual(stars.map((star, index) => String(index === 4)));
        expect(stars.map(star => star.getAttribute('tabindex'))).toEqual(stars.map((star, index) => (index === 4 ? '0' : '-1')));
        expect(stars[4].getAttribute('aria-label')).toBe('5');
        expect(document.querySelector('.star-rating__widget').getAttribute('role')).toBe('radiogroup');
    });

    test('starRating_givenStarClicked_movesTheCheckedStateAndTabStop', () => {
        const select = createRatingSelect(2);
        $(select).starRating();

        $(document.querySelectorAll('.star-rating__star')[6]).trigger('click');

        const stars = document.querySelectorAll('.star-rating__star');
        expect(stars[6].getAttribute('aria-checked')).toBe('true');
        expect(stars[6].getAttribute('tabindex')).toBe('0');
        expect(stars[1].getAttribute('aria-checked')).toBe('false');
        expect(stars[1].getAttribute('tabindex')).toBe('-1');
    });

    test('starRating_givenRepeatInit_doesNotDuplicateWidget', () => {
        const select = createRatingSelect(2);

        $(select).starRating();
        $(select).starRating();

        expect(document.querySelectorAll('.star-rating__widget').length).toBe(1);
    });
});
