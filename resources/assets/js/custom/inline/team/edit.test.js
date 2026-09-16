// TeamEdit is a global-script style class extending the bare global `InlineCode`; `_renderName` is
// called via the prototype without constructing an instance (which would need jQuery/DataTables).

const {InlineCode} = require('../inlinecode');
globalThis.InlineCode = InlineCode;
globalThis.Handlebars = require('handlebars');

const {TeamEdit} = require('./edit');

describe('TeamEdit._renderName', () => {
    it('_renderName_givenNameContainingMarkup_returnsNameEscaped', () => {
        // Arrange
        const name = '<img src=x onerror=alert(1)>';

        // Act
        const result = TeamEdit.prototype._renderName(name, 'display', {name}, null);

        // Assert: the cell must render the name as text, so parsing the output creates no element.
        const cell = document.createElement('td');
        cell.innerHTML = result;
        expect(cell.children.length).toBe(0);
        expect(cell.textContent).toBe(name);
    });

    it('_renderName_givenPlainName_returnsNameUnchanged', () => {
        // Arrange
        const name = 'Wotuu';

        // Act
        const result = TeamEdit.prototype._renderName(name, 'display', {name}, null);

        // Assert
        expect(result).toBe('Wotuu');
    });
});
