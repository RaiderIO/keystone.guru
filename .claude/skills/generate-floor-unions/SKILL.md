---
name: generate-floor-unions
description: Generate FloorUnions and FloorUnionAreas for a dungeon facade (MDT combined view) from the per-floor images and the combined facade image, using OpenCV image registration instead of the manual GIMP overlay workflow. Use when adding facade support to a dungeon or when asked to (re)compute floor unions from map images. Not for creating the Dungeon/Floor rows themselves, and not for the JSON seeder mechanics (seeder-load/seeder-save).
---

# Generate FloorUnions from facade images

Automates the manual process in the wiki page *Adding support for dungeon facades (MDT combined
view)*: instead of overlaying floor images in GIMP at 60% opacity and eyeballing center/scale/
rotation, SIFT feature matching + RANSAC recovers each floor's placement on the facade image with
sub-map-unit precision, and a full-coverage FloorUnionArea partition is derived from the
placements. Results are inserted into a **fresh bare mapping version** in the dev DB for review in
the admin map editor.

Validated against Skyreach (2 floors) and Magister's Terrace Midnight (7 images, 8 unions,
rotations up to 149.5°, duplicated-art void floors): every recoverable union matched the
hand-made ground truth within ±0.72 map units, size ±1%, rotation ±0.3°.

## Prerequisites

1. The Dungeon, its Floors, and the facade floor (`facade = 1`) already exist (the user creates
   those; this skill does not).
2. One image **per real floor** plus the combined facade image, all with aspect ratio 1.5 and
   framed exactly like the floor's map plane (the same framing the tiles are generated from).
   - **Every real floor needs its own image, including "void"/variant floors that share art.**
     Two floors can map onto the *same* facade art with different rotations/sizes because their
     own floor images differ - that union is only recoverable from that floor's own image
     (learned on Magister's Terrace: floor `grand_magister_asylum` was underivable from the 7
     provided images; its union had to be skipped).
   - No loose images? `stitch` can rebuild a floor image from the live tile CDN:
     `run.sh <dir> stitch --base-url https://keystone.guru-assets…/tiles/<exp>/<dungeon>/<floorIndex> --zoom 2 --output /work/cut_1.png`
3. Docker available on the host. Everything Python/OpenCV runs in a throwaway image
   (`scripts/Dockerfile`, built automatically by `run.sh`) - never install Python deps on the
   host or in the app image.

## Workflow

Work from a scratch dir (scratchpad) containing the images: `combined.png` + `cut_<N>.png` where
`N` is the floor index the image belongs to. All commands go through
`.claude/skills/generate-floor-unions/scripts/run.sh <workdir> <args…>`; inside the container the
workdir is `/work`.

### 1. Match placements

```bash
run.sh <workdir> match --facade /work/combined.png --out-dir /work/out \
    /work/cut_1.png /work/cut_2.png ...
```

Writes `out/placements.json` (one entry per placement: `lat`, `lng`, `size`, `rotation` in DB
units, plus `inliers`, `edge_corr`, the affine matrix) and `out/overlay_placements.png`. The JSON
embeds container-absolute `/work/...` image paths, so later `areas`/overlay runs must use the
same `<workdir>` mount.

- A floor image may legitimately produce **multiple placements** (zoomed sub-regions with
  `size > 256`, or duplicated-art floors); iterative matching finds them all.
- **Phantom fits are expected and auto-rejected**: floor images share art with the facade beyond
  their own floor (the dungeon-name banner is the classic case) and such fits can carry *more*
  RANSAC inliers than small genuine placements. The `edge_corr` filter (default `--min-edge-corr
  0.2`) separates them: measured real placements score ≥ 0.25, phantoms ≤ 0.17. If a floor is
  missing a placement or has a bogus one, tune that threshold before anything else.

### 2. Vision-review the stripe overlay

Read `out/overlay_placements.png`. Inside each placement's outline, horizontal bands alternate
between facade and warped floor image: a correct placement shows map art flowing **unbroken
across band boundaries**; misalignment shows as broken/offset lines. Also sanity-check the
console `rotation`/`size` values. Floor-image fluff (its own banner) appearing over empty
parchment inside a placement is normal.

### 3. Partition into FloorUnionAreas

```bash
run.sh <workdir> areas --placements /work/out/placements.json --out-dir /work/out
```

Writes `out/areas.json` (placements + `areas` polygons in DB latlng) and `out/overlay_areas.png`
(colored partition). Every facade pixel is owned by exactly one placement (no gaps - gaps break
facade→floor lookups), polygons are slightly dilated so neighbours overlap (overlap is safe:
first-match-wins), and each union normally gets one simple 8-16 vertex polygon. Review the
overlay: each region should cover its own floor's art.

### 4. Build the import JSON (adds target floors)

Map each `floor_image#instance` to its `target_floor_id` (query floors via tinker; the user must
disambiguate which instance belongs to which floor when duplicated-art floors are involved -
image content alone cannot decide it). Then:

```bash
jq --argjson map '{"cut_1#1":417,"cut_2#1":418}' \
  '{dungeon_key:"skyreach", facade_floor_id:424, placements:[.placements[]
    | {target_floor_id: $map[(.floor_image+"#"+(.instance|tostring))], lat, lng, size, rotation, areas}]}' \
  <workdir>/out/areas.json > <repo>/storage/app/floor_union_import_<dungeon>.json
```

`facade_floor_id` must be the dungeon's `facade = 1` floor; every placement needs a non-null
`target_floor_id`.

### 5. Insert into a fresh bare mapping version

```bash
docker compose exec -T -e KSG_FLOOR_UNION_IMPORT=storage/app/floor_union_import_<dungeon>.json \
    app php artisan tinker .claude/skills/generate-floor-unions/scripts/insert_floor_unions.php
```

Creates a **bare** mapping version (a `mapping_versions` row with no relationships) and the
unions/areas, printing all created ids. Critical implementation detail the script already
handles: `MappingVersion::create()` fires a `created` boot hook that clones the ENTIRE previous
mapping - bare versions must be created with the quiet `MappingVersion::insertGetId()` (same
trick as `MappingService::copyMappingVersionToDungeon()`).

Side effects to tell the user about:
- The new version is now the dungeon's **current** mapping version in dev.
- The `mapping:sync` cron exports it into `database/seeders/dungeondata/` in the main checkout -
  do not commit that drift; deleting the mapping version reverts it.
- To retry, delete the created version first (`MappingVersion::find(id)->delete()` cascades to
  its unions/areas) - each script run creates a NEW version.

### 6. Verify

1. **Round-trip** (when a reference mapping version with enemies exists, e.g. regenerating an
   existing facade): the bare version itself has NO enemies - iterate the *reference* version's
   enemies, but run the conversions with the *new* version:
   `convertMapLocationToFacadeMapLocation($newMv, ...)` then
   `convertFacadeMapLocationToMapLocation($newMv, ...)` must return the same floor and coords
   (tolerance 1 map unit). A couple of edge-of-floor enemies landing across an
   area boundary is normal (2/277 on Magister's Terrace) - those boundaries get nudged in the map
   editor, don't chase them in code.
2. **Map editor**: the user reviews/tweaks unions and areas on the facade floor in the admin
   editor - that is the intended final-tuning surface, not a failure of the generator.

## Gotchas / calibration facts (don't re-derive)

- **Coordinate conversion**: facade image pixel `(x, y)` in a `W×H` image →
  `lng = x/W*384`, `lat = -(y/H)*256`. `FloorUnion.lat/lng` = where the floor image's CENTER
  lands; `size = 256 * (scale * floor_px_height / facade_px_height)`; `size` may exceed 256
  (zoomed sub-region, e.g. Skyreach 300/204, Nokhud 600/840).
- **Rotation sign**: the DB `rotation` equals the image-space affine angle exactly as
  `register_floors.py` emits it - no negation. Calibrated against Magister's Terrace ground
  truth (60 / -120 / -62 / 149.5 all matched within 0.3°). Don't "fix" the sign.
- **SIFT, not ORB**: scale differences up to ~2.3× between floor image and facade placement are
  routine; SIFT handled all of them with 96-715 inliers.
- The area partition is a rect-interior Voronoi. An art-proximity variant was tried and
  **rejected**: with duplicated-art floors the same art matches several placements and the
  boundaries fragment badly. Keep boundaries simple; the editor is for fine-tuning.
