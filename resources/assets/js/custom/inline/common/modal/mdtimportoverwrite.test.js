// `mdtimportoverwrite.js` is concatenated into a bundle in the browser and extends the bare `InlineCode` global,
// so it must be on `globalThis` before the class body is evaluated. A real jQuery over a jsdom body stands in for
// the modal markup.

global.$ = global.jQuery = require('jquery');

const {InlineCode}    = require('../../inlinecode');
globalThis.InlineCode = InlineCode;

const {CommonModalMdtimportoverwrite} = require('./mdtimportoverwrite');

/**
 * @param {boolean} hasPendingDraft
 * @returns {CommonModalMdtimportoverwrite}
 */
function makeModal(hasPendingDraft) {
    document.body.innerHTML = `
        <input type="checkbox" id="discard_existing_draft">
        <button id="submit" disabled></button>
    `;

    return new CommonModalMdtimportoverwrite('test-id', 'common/modal/mdtimportoverwrite', {
        discardExistingDraftSelector: '#discard_existing_draft',
        submitSelector:               '#submit',
        hasPendingDraft:              hasPendingDraft,
    });
}

test('canSubmit_givenNoPreviewedString_returnsFalse', () => {
    // Arrange
    const modal = makeModal(false);

    // Act
    const canSubmit = modal.canSubmit();

    // Assert
    expect(canSubmit).toBe(false);
});

test('canSubmit_givenPreviewedStringAndNoPendingDraft_returnsTrue', () => {
    // Arrange
    const modal                  = makeModal(false);
    modal._previewedImportString = '!~MDT2~abc';

    // Act
    const canSubmit = modal.canSubmit();

    // Assert
    expect(canSubmit).toBe(true);
});

test('canSubmit_givenPendingDraftWithoutConfirmation_returnsFalse', () => {
    // Arrange
    const modal                  = makeModal(true);
    modal._previewedImportString = '!~MDT2~abc';

    // Act
    const canSubmit = modal.canSubmit();

    // Assert
    expect(canSubmit).toBe(false);
});

test('canSubmit_givenPendingDraftWithConfirmation_returnsTrue', () => {
    // Arrange
    const modal                  = makeModal(true);
    modal._previewedImportString = '!~MDT2~abc';
    $('#discard_existing_draft').prop('checked', true);

    // Act
    const canSubmit = modal.canSubmit();

    // Assert
    expect(canSubmit).toBe(true);
});

test('buildImportPayload_givenConfirmedPendingDraft_sendsDiscardFlag', () => {
    // Arrange
    const modal                  = makeModal(true);
    modal._previewedImportString = '!~MDT2~abc';
    $('#discard_existing_draft').prop('checked', true);

    // Act
    const payload = modal.buildImportPayload();

    // Assert
    expect(payload).toEqual({import_string: '!~MDT2~abc', discard_existing_draft: 1});
});

test('buildImportPayload_givenNoPendingDraft_neverSendsDiscardFlag', () => {
    // Arrange
    const modal                  = makeModal(false);
    modal._previewedImportString = '!~MDT2~abc';
    $('#discard_existing_draft').prop('checked', true);

    // Act
    const payload = modal.buildImportPayload();

    // Assert
    expect(payload.discard_existing_draft).toBe(0);
});
