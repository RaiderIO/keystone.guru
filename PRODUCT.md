# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Users

Primary: **route consumers** — World of Warcraft Mythic+ players looking for a proven route
for a specific dungeon and key level, to copy into Mythic Dungeon Tools (MDT) or follow on
the site. They arrive with a concrete dungeon/key in mind and want to find, evaluate, and
take a route with minimal friction.

Secondary: **route creators** — guide writers, high-key pushers, and team leaders who build
and publish the routes consumers follow.

Also served: organized M+ **teams** collaborating on routes (live multi-user editing, team
tagging), a real but smaller scenario.

## Product Purpose

A website where users can build and find routes for completing Mythic+ dungeons in World of
Warcraft. Success for the primary user is walking away with a route they trust for their key.

## Positioning

- **Combat-log-backed data is the sell and cannot be replicated by competitors**: real
  combat-log evidence under the routes — heatmaps of where runs die/wipe, NPC and spell data
  observed from actual runs (Compendium), routes regenerated from real runs.
- **Live collaboration** (multi-user route editing) is a second genuine USP, also not
  replicated by competitors, though less central to the pitch.
- **Web-native MDT-compatible planning** (shareable URLs, embeds, thumbnails, no addon
  install) is true but copyable — copies exist (e.g. threechest.io). It is table stakes,
  not the differentiator.

## Operating Context

- Consumers typically alt-tab from the game or plan before a session; the destination for a
  chosen route is usually MDT in the game client (via export string) or following the map on
  the site.
- Routes and heatmaps are embedded on third-party sites (Raider.IO and others); the embed is
  a first-class surface, not an afterthought.
- Content tracks the live game: seasons, dungeon pools, and affixes rotate; data and
  mappings must keep up with WoW patches.

## Capabilities and Constraints

- Route builder on interactive Leaflet dungeon maps; MDT import/export string parity is a
  hard constraint features must not break.
- Combat-log ingestion pipeline feeding heatmaps, the NPC/spell Compendium, and
  auto-generated routes from real runs.
- Live collaborative editing (presence channels/websockets), teams, profiles, route
  discovery/search.
- Laravel 12 + Docker web application; no native apps — mobile is mobile web.

## Brand Commitments

- **Free with ads + Patreon**: core features stay free; anonymous users see ads, Patreon
  removes them. Layouts must keep ad slots viable.
- **WoW-adjacent dark look**: the dark, game-adjacent aesthetic and WoW terminology are
  binding brand identity, not incidental styling.
- **MDT compatibility** and **embeddability** are durable product commitments (see above).
- Existing name, logo, and domain (keystone.guru); affiliated with Raider.IO.

## Evidence on Hand

- Real combat-log-derived data in production (heatmaps, Compendium observations, parsed
  runs) — the differentiator is backed by live data, not claims.
- Published routes, thumbnails, and third-party embeds in the wild.

## Product Principles

1. **Consumer-first**: the person finding a route outranks the person building one when
   they conflict.
2. **Show the evidence**: surface the combat-log data behind routes — it is the claim
   competitors cannot copy.
3. **Never strand MDT**: every planning feature keeps a working round-trip with MDT.
4. **The route travels**: URLs, embeds, exports, and thumbnails make a route usable
   wherever the player is.
5. **Keep pace with the game**: seasons and patches are routine, not events.
