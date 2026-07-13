# Create Route payload fixtures

These JSON files are the **FE/BE contract** for the dungeon route creation flow (`POST /routes/new`
and `POST /routes/new/temporary`). Each file describes the exact set of POST fields the browser is
expected to submit for one scenario.

Both sides of the stack load the same file and substitute their own values for any `{{placeholder}}`
token:

- The Vitest integration suite (`resources/assets/js/custom/inline/common/forms/createroute.
  serialization.test.js`) builds the real form DOM, activates the real inline JS classes + Tom
  Select, drives the scenario's user actions, serializes the form with `FormData`, and compares it
  against the fixture resolved with its own sentinel ids (`resources/assets/js/test/fixtures/
  createRouteForm.js`).
- The PHP contract test (`tests/Feature/Controller/DungeonRouteCreateContractTest.php`) resolves the
  same fixture against real seeded database ids and POSTs it to the real endpoint.

Schema:

```json
{
  "description": "...",
  "endpoint": "dungeonroute.savenew",
  "gameVersion": "retail",
  "auth": true,
  "fields": [{"name": "dungeon_id", "value": "{{dungeon_id}}"}, {"name": "dungeon_difficulty", "value": ""}]
}
```

- `fields` is a `{name, value}` pair array (not an object) so duplicate keys are representable
  (e.g. `class[]` appears once per party member, `route_select_affixes[]` once per selected affix).
- Values that are code constants or static defaults (`""`, `"-1"`, `"0"`, difficulty `"1"`/`"2"` -
  `DungeonConstants::DIFFICULTY_ALL` is identical in every environment) are written literally.
- Values that come from the seeded database (dungeon/team/affix-group/attribute/faction/class/
  specialization/race/key-level ids) are `{{placeholder}}` tokens, substituted by each side with its
  own ids.
- These files are hand-written, not snapshot-generated. Review changes to them like an API change:
  a fixture drifting from the real form (or the real endpoint) silently breaks the guarantee this
  contract exists to provide.
