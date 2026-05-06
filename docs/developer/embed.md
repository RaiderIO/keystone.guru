# Route embedding options

If you're embedding a route on your website, you have a few options available to customize the appearance/behavior of
Keystone.guru.

## Usage

https://keystone.guru/abcd1234/embed?showPulls=1&pullsDefaultState=0 etc.

You can also test out the embed options by visiting https://keystone.guru/embed/abcd1234, where `abcd1234` is the
route's public key.

## Reference

| Name                  | Type                 | Default              | Description                                                                                                                                                                                                                                                                                                                                                                             |
|-----------------------|----------------------|----------------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| locale                | **string**           | `en_US`              | One of `en_US`, `de_DE`, `es_ES`, `es_MX`, `fr_FR`, `it_IT`, `ko_KR`, `pt_BR`, `ru_RU`, `uk_UA`.                                                                                                                                                                                                                                                                                        |
| style                 | **string**           | `regular`            | `compact` or `regular`. `compact` aims to keep the header one row thick, `regular` keeps it two rows thick.                                                                                                                                                                                                                                                                             |
| pullsDefaultState     | **boolean\|integer** | `0`                  | `0` or `1`. When `1`, the pulls sidebar will be shown upon page load. When `0`, the pulls sidebar will be hidden upon page load. If not passed, the pulls sidebar will be hidden from view. If a value greater than 1 is passed, the pulls sidebar will be hidden by default, unless the width of the map is greater than this value, after which the sidebar will be shown by default. |
| pullsHideOnMove       | **boolean**          | `0`                  | `0` or `1`. When `1`, the pulls sidebar will automatically hide if the user moves the map around. When `0`, the pulls sidebar will always remain open. By default the pulls sidebar will remain open on desktop, and automatically hide when a mobile device is detected.                                                                                                               |
| headerBackgroundColor | **string\|null**     | `null`               | Header background color (such as `#FF0000`). If not passed, a background image for the dungeon itself will be loaded instead.                                                                                                                                                                                                                                                           |
| mapFacadeStyle        | **string\|null**     | `null`               | Map facade style. Can be `split_floors` (Blizzard floor style) or `facade` (for MDT-style combined floors, if the dungeon supports it).                                                                                                                                                                                                                                                 |
| mapBackgroundColor    | **string\|null**     | Color based on theme | Map background color (such as `#FF0000`).                                                                                                                                                                                                                                                                                                                                               |
| showEnemyInfo         | **boolean**          | `0`                  | `0` or `1`. When `1`, when the user performs a mouseover on an enemy, the enemy info pane will be shown. When `0`, the enemy info pane will not be shown.                                                                                                                                                                                                                               |
| showPulls             | **boolean**          | `1`                  | `0` or `1`. When `1`, will allow the display of the pulls sidebar. If not passed, the pulls sidebar will be available.                                                                                                                                                                                                                                                                  |
| showEnemyForces       | **boolean**          | `1`                  | `0` or `1`. When `1`, shows enemy forces of the current route.                                                                                                                                                                                                                                                                                                                          |
| showAffixes           | **boolean**          | `0`                  | `0` or `1`. When `1`, the most relevant affixes for the dungeon will be shown (based on the current affix week for the route's dungeon).                                                                                                                                                                                                                                                |
| lat                   | **float**            | `null`               | Any floating point number. When passed, the map will pan to this latitude on page load (find the latitude/longitude from the Share dialog when viewing a route).                                                                                                                                                                                                                        |
| lng                   | **float**            | `null`               | Any floating point number. When passed, the map will pan to this longitude on page load (find the latitude/longitude from the Share dialog when viewing a route).                                                                                                                                                                                                                       |
| z                     | **float**            | `null`               | Any floating point number between `1` and `5`. When passed, the map will zoom to this magnification on page load (find the zoom from the Share dialog when viewing a route).                                                                                                                                                                                                            |

### Regular embed reference

| Name      | Type        | Default | Description                                        |
|-----------|-------------|---------|----------------------------------------------------|
| showTitle | **boolean** | `1`     | `1` to show the title of the route, `0` to hide it |

### Compact embed reference

No additional properties for compact embeds.
