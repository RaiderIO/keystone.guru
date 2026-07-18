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

    test('starRating_givenNoMatchingSelect_doesNotThrowAndRendersNothing', () => {
        expect(() => $('#rating_select').starRating()).not.toThrow();
        expect(document.querySelector('.star-rating__widget')).toBeNull();
    });

    test('starRating_givenRepeatInit_doesNotDuplicateWidget', () => {
        const select = createRatingSelect(2);

        $(select).starRating();
        $(select).starRating();

        expect(document.querySelectorAll('.star-rating__widget').length).toBe(1);
    });
});
