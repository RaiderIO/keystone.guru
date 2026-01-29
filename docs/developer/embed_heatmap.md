# Heatmap embedding options

If you're embedding a heatmap on your website, you have a few options available to customize the appearance/behaviour of
Keystone.guru.

## Inherited options

All options from the [route embedding options](./embed.md) are also available for heatmap embeds.

## Usage

https://keystone.guru/heatmap/retail/tazavesh-streets-of-wonder/embed/1?showPulls=1&pullsDefaultState=0 etc.

To embed just the dungeon with the mapping, without any route displayed:

https://keystone.guru/explore/retail/tazavesh-streets-of-wonder/embed/1?showPulls=1&pullsDefaultState=0 etc.

## Reference

| Name                  | Type              | Default              | Description                                                                                                                                                                                                                                               |
|-----------------------|-------------------|----------------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| locale                | **string**           | `en_US`              | One of `en_US`, `de_DE`, `es_ES`, `es_MX`, `fr_FR`, `it_IT`, `ko_KR`, `pt_BR`, `ru_RU`, `uk_UA`.                                                                                                                                                                                                                                                                                        |
| style                 | **string**        | `compact`            | `compact` aims to keep the header one row thick.                                                                                                                                                                                                          |
| headerBackgroundColor | **string\|null**  | `null`               | Header background color (such as `#FF0000`). If not passed, a background image for the dungeon itself will be loaded instead.                                                                                                                             |
| mapFacadeStyle        | **string\|null**  | `null`               | Map facade style. Can be `split_floors` (Blizzard floor style) or `facade` (for MDT-style combined floors, if the dungeon supports it).                                                                                                                   |
| mapBackgroundColor    | **string\|null**  | Color based on theme | Map background color (such as `#FF0000`).                                                                                                                                                                                                                 |
| showEnemyInfo         | **boolean**       | `0`                  | `0` or `1`. When `1`, when the user performs a mouseover on an enemy, the enemy info pane will be shown. When `0`, the enemy info pane will not be shown.                                                                                                 |
| showTitle             | **boolean**       | `1`                  | `0` or `1`. When `1`, the dungeon name will be shown in the header. When `0` it will not be shown.                                                                                                                                                        |
| showSidebar           | **boolean**       | `1`                  | `0` or `1`. When `1`, the sidebar with filters will be shown to the user. When `0` the sidebar will be hidden from view. You can only change the heatmap by passing parameters in the URL (found on this page).                                           |
| lat                   | **float**         | `null`               | Any floating point number. When passed, the map will pan to this latitude on page load (find the latitude/longitude from the Share dialog when viewing a route).                                                                                          |
| lng                   | **float**         | `null`               | Any floating point number. When passed, the map will pan to this longitude on page load (find the latitude/longitude from the Share dialog when viewing a route).                                                                                         |
| z                     | **float**         | `null`               | Any floating point number between `1` and `5`. When passed, the map will zoom to this magnification on page load (find the zoom from the Share dialog when viewing a route).                                                                              |
| type                  | **enum\|null**    | `npc_death`          | One of `npc_death`, `player_death` or `player_spell`.                                                                                                                                                                                                     |
| dataType              | **enum\|null**    | `player_position`    | One of `player_position` or `enemy_position`.                                                                                                    `                                                                                                        |
| region                | **enum\|null**    | `world`              | One of `us`, `eu`, `cn`, `tw`, `kr` or `world`.                                                                                                                                                                                                           |
| minMythicLevel        | **integer\|null** | `null`               | The minimum keystone level for runs to be included in the heatmap.                                                                                                                                                                                        |
| maxMythicLevel        | **integer\|null** | `null`               | The maximum keystone level for runs to be included in the heatmap.                                                                                                                                                                                        |
| minItemLevel          | **integer\|null** | `null`               | The minimum average item level of the party for runs to be included in the heatmap.                                                                                                                                                                       |
| maxItemLevel          | **integer\|null** | `null`               | The maximum average item level of the party for runs to be included in the heatmap.                                                                                                                                                                       |
| minPlayerDeaths       | **integer\|null** | `null`               | The minimum player deaths for runs to be included in the heatmap.                                                                                                                                                                                         |
| maxPlayerDeaths       | **integer\|null** | `null`               | The maximum player deaths for runs to be included in the heatmap.                                                                                                                                                                                         |
| includeAffixIds       | **string\|null**  | `null`               | Affix IDs to include (CSV of integers). See https://wago.tools/db2/KeystoneAffix for IDs.                                                                                                                                                                 |
| excludeAffixIds       | **string\|null**  | `null`               | Affix IDs to exclude (CSV of integers). See https://wago.tools/db2/KeystoneAffix for IDs.                                                                                                                                                                 |
| includeClassIds       | **string\|null**  | `null`               | Class IDs to include (CSV of integers). See https://wago.tools/db2/ChrClasses for IDs.                                                                                                                                                                    |
| excludeClassIds       | **string\|null**  | `null`               | Class IDs to exclude (CSV of integers). See https://wago.tools/db2/ChrClasses for IDs.                                                                                                                                                                    |
| includeSpecIds        | **string\|null**  | `null`               | Specialization IDs to include (CSV of integers). See https://wago.tools/db2/ChrSpecialization for IDs.                                                                                                                                                    |
| excludeSpecIds        | **string\|null**  | `null`               | Specialization IDs to exclude (CSV of integers). See https://wago.tools/db2/ChrSpecialization for IDs.                                                                                                                                                    |
| minPeriod             | **integer\|null** | `null`               | Runs before this period will not be considered. See https://github.com/RaiderIO/keystone.guru/blob/master/app/Models/GameServerRegion.php#L101 and https://github.com/RaiderIO/keystone.guru/blob/master/database/seeders/GameServerRegionsSeeder.php#L20 |
| maxPeriod             | **integer\|null** | `null`               | Runs after this period will not be considered.                                                                                                                                                                                                            |
| minTimerFraction      | **number\|null**  | `null`               | Runs faster than this minimum fraction of the dungeon timer will not be included.                                                                                                                                                                         |
| maxTimerFraction      | **number\|null**  | `null`               | Runs slower than this minimum fraction of the dungeon timer will not be included.                                                                                                                                                                         |

## Type = player_death

| Name                       | Type             | Default | Description                                                                                                  |
|----------------------------|------------------|---------|--------------------------------------------------------------------------------------------------------------|
| includePlayerDeathSpecIds  | **string\|null** | `null`  | These spec IDs must have died in the run before the run is included in the heatmap (CSV of integers).        |
| excludePlayerDeathSpecIds  | **string\|null** | `null`  | These spec IDs must _not_ have died in the run before the run is included in the heatmap (CSV of integers).  |
| includePlayerDeathClassIds | **string\|null** | `null`  | These class IDs must have died in the run before the run is included in the heatmap (CSV of integers).       |
| excludePlayerDeathClassIds | **string\|null** | `null`  | These class IDs must _not_ have died in the run before the run is included in the heatmap (CSV of integers). |

## Type = player_spell

| Name                  | Type             | Default | Description                                                    |
|-----------------------|------------------|---------|----------------------------------------------------------------|
| includePlayerSpellIds | **string\|null** | `null`  | Comma-separated player spell IDs to include (CSV of integers). |
