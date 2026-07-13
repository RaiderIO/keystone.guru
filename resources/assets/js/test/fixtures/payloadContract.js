// ---------------------------------------------------------------------------
// Shared FE/BE payload contract helpers for the Create Route flow (#3514/#3535).
//
// tests/Fixtures/CreateRoute/payloads/*.json describes, per scenario, the exact set of POST fields
// the browser is expected to submit. Both this vitest suite (custom/inline/common/forms/createroute.
// serialization.test.js) and the PHP contract test (tests/Feature/Controller/
// DungeonRouteCreateContractTest.php) load the same JSON and substitute their own {{placeholder}}
// values (JS: sentinel ids from test/fixtures/createRouteForm.js; PHP: real seeded DB ids), then
// compare against what their side of the stack actually produces.
// ---------------------------------------------------------------------------

const fs   = require('fs');
const path = require('path');

const PLACEHOLDER_PATTERN = /\{\{\s*([a-zA-Z0-9_]+)\s*}}/g;

/**
 * Serializes a `<form>` element the same way a browser would on submit: via `FormData`. Duplicate
 * names (e.g. `class[]` submitted once per party member) and DOM order are preserved.
 *
 * @param {HTMLFormElement} form
 * @returns {{name: string, value: string}[]}
 */
function serializeForm(form) {
    return Array.from(new FormData(form).entries())
        .map(([name, value]) => ({name, value: String(value)}));
}

/**
 * Resolves every `{{placeholder}}` token in a fixture's field values against a substitutions map.
 * Fields whose value contains no placeholder are returned unchanged.
 *
 * @param {{description: string, endpoint: string, gameVersion: string, auth: boolean, fields: {name: string, value: string}[]}} fixture
 * @param {Object<string, string|number>} substitutions
 * @returns {{name: string, value: string}[]}
 */
function resolveFixture(fixture, substitutions) {
    return fixture.fields.map(({name, value}) => ({
        name,
        value: String(value).replace(PLACEHOLDER_PATTERN, (match, token) => {
            if (!Object.prototype.hasOwnProperty.call(substitutions, token)) {
                throw new Error(`resolveFixture: no substitution provided for placeholder "${token}" (field "${name}")`);
            }
            return String(substitutions[token]);
        }),
    }));
}

/**
 * Reads every `*.json` payload fixture from tests/Fixtures/CreateRoute/payloads (relative to the
 * repo root), keyed by filename without its extension.
 *
 * @returns {Object<string, {description: string, endpoint: string, gameVersion: string, auth: boolean, fields: {name: string, value: string}[]}>}
 */
function loadPayloadFixtures() {
    const fixturesDir = path.resolve(__dirname, '../../../../../tests/Fixtures/CreateRoute/payloads');
    const fixtures     = {};

    for (const filename of fs.readdirSync(fixturesDir)) {
        if (!filename.endsWith('.json')) {
            continue;
        }
        fixtures[filename.replace(/\.json$/, '')] = JSON.parse(fs.readFileSync(path.join(fixturesDir, filename), 'utf8'));
    }

    return fixtures;
}

/**
 * Groups an array of {name, value} pairs into name -> sorted values, so two field lists can be
 * compared as a per-key multiset. Tom Select reorders the underlying `<select>`'s options on every
 * selection, so within-key order is not part of the FE/BE contract - only the set of names and, per
 * name, the multiset of submitted values.
 *
 * @param {{name: string, value: string}[]} pairs
 * @returns {Object<string, string[]>}
 */
function groupByNameAsMultiset(pairs) {
    const grouped = {};

    for (const {name, value} of pairs) {
        (grouped[name] ??= []).push(value);
    }
    for (const name of Object.keys(grouped)) {
        grouped[name].sort();
    }

    return grouped;
}

/**
 * Asserts that two {name, value} pair arrays are equivalent under the FE/BE payload contract: the
 * same set of field names, and per name the same multiset of values. Delegates to Vitest's `expect`
 * so a mismatch renders a readable diff.
 *
 * @param {{name: string, value: string}[]} actual
 * @param {{name: string, value: string}[]} expected
 */
function assertPayloadsMatch(actual, expected) {
    expect(groupByNameAsMultiset(actual)).toEqual(groupByNameAsMultiset(expected));
}

module.exports = {serializeForm, resolveFixture, loadPayloadFixtures, groupByNameAsMultiset, assertPayloadsMatch};
