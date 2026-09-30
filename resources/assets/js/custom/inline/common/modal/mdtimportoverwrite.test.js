// `mdtimportoverwrite.js` is concatenated into a bundle in the browser and extends the bare `InlineCode` global,
// so it must be on `globalThis` before the class body is evaluated. A real jQuery over a jsdom body stands in for
// the modal markup.

global.$ = global.jQuery = require('jquery');

const {InlineCode}    = require('../../inlinecode');
globalThis.InlineCode = InlineCode;

const {CommonModalMdtimportoverwrite} = require('./mdtimportoverwrite');

/**
 * @param {number|null} pendingDraftId
 * @returns {CommonModalMdtimportoverwrite}
 */
function makeModal(pendingDraftId) {
    document.body.innerHTML = `
        <input type="checkbox" id="discard_existing_draft">
        <button id="submit" disabled></button>
    `;

    return new CommonModalMdtimportoverwrite('test-id', 'common/modal/mdtimportoverwrite', {
        discardExistingDraftSelector: '#discard_existing_draft',
        submitSelector:               '#submit',
        pendingDraftId:               pendingDraftId,
    });
}

test('canSubmit_givenNoPreviewedString_returnsFalse', () => {
    // Arrange
    const modal = makeModal(null);

    // Act
    const canSubmit = modal.canSubmit();

    // Assert
    expect(canSubmit).toBe(false);
});

test('canSubmit_givenPreviewedStringAndNoPendingDraft_returnsTrue', () => {
    // Arrange
    const modal                  = makeModal(null);
    modal._previewedImportString = '!~MDT2~abc';

    // Act
    const canSubmit = modal.canSubmit();

    // Assert
    expect(canSubmit).toBe(true);
});

test('canSubmit_givenPendingDraftWithoutConfirmation_returnsFalse', () => {
    // Arrange
    const modal                  = makeModal(42);
    modal._previewedImportString = '!~MDT2~abc';

    // Act
    const canSubmit = modal.canSubmit();

    // Assert
    expect(canSubmit).toBe(false);
});

test('canSubmit_givenPendingDraftWithConfirmation_returnsTrue', () => {
    // Arrange
    const modal                  = makeModal(42);
    modal._previewedImportString = '!~MDT2~abc';
    $('#discard_existing_draft').prop('checked', true);

    // Act
    const canSubmit = modal.canSubmit();

    // Assert
    expect(canSubmit).toBe(true);
});

test('buildImportPayload_givenConfirmedPendingDraft_sendsDisplayedDraftId', () => {
    // Arrange
    const modal                  = makeModal(42);
    modal._previewedImportString = '!~MDT2~abc';
    $('#discard_existing_draft').prop('checked', true);

    // Act
    const payload = modal.buildImportPayload();

    // Assert
    expect(payload).toEqual({import_string: '!~MDT2~abc', discard_existing_draft_id: 42});
});

test('buildImportPayload_givenNoPendingDraft_neverSendsDraftId', () => {
    // Arrange
    const modal                  = makeModal(null);
    modal._previewedImportString = '!~MDT2~abc';
    $('#discard_existing_draft').prop('checked', true);

    // Act
    const payload = modal.buildImportPayload();

    // Assert
    expect(payload).toEqual({import_string: '!~MDT2~abc'});
});

test('buildImportPayload_givenUnconfirmedPendingDraft_neverSendsDraftId', () => {
    // Arrange
    const modal                  = makeModal(42);
    modal._previewedImportString = '!~MDT2~abc';

    // Act
    const payload = modal.buildImportPayload();

    // Assert
    expect(payload).toEqual({import_string: '!~MDT2~abc'});
});
