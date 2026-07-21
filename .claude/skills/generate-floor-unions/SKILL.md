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
hand-made ground truth within ±0.72 map units, size ±1%, rotation ±0.3°. Hardened for **blind
first runs** on a further 7 Midnight/BfA dungeons (#3616): Ruby Life Pools, Kings' Rest, Temple of
Sethraliss, Voidscar Arena, Murder Row, The Blinding Vale, Altar of Fangs - see the calibration
facts below and the golden regression tests (`scripts/test.sh`, run after any change to
`register_floors.py`).

## Prerequisites

1. The Dungeon, its Floors, and the facade floor (`facade = 1`) already exist (the user creates
   those; this skill does not).
2. One image **per real floor** plus the combined facade image, all framed exactly like the
   floor's map plane (the same framing the tiles are generated from). Resolutions may vary
   freely between images (all math normalizes by width/height), but the aspect ratio is ALWAYS
   1.5 - `match` refuses any image where it isn't, since another framing silently corrupts
   every emitted lat/lng/size.
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
- **The title banner is the enemy.** Every cut carries the dungeon-name banner (and frame), and
  the facade shows it too, so a floor image can match the facade banner with an edge_corr *higher*
  than a small genuine placement (measured up to 0.61 on Midnight/BfA art) - the edge_corr filter
  alone cannot reject it, and left unchecked it either adds a phantom or, when a floor's own art is
  faint, outscores and masks the real placement. Three guards handle it, applied automatically:
  1. **Own-art keypoint masking** (on with ≥2 floor images): each floor's SIFT keypoints are
     restricted to its own art (the `content_masks` median-backdrop) so the shared banner/frame/
     parchment is never matched. This both removes banner phantoms and frees the real placements
     they were masking. Single-floor dungeons skip it (no competing banner) and match unmasked.
  2. **Off-image guard**: a fit whose center lands >15% outside the facade is rejected (the banner
     sits just off the top/bottom edge).
  3. **Review band** (`--review-edge-corr`, default 0.08): a floor that gets *no* fit above
     `--min-edge-corr` but has a geometrically sound, on-image, own-art fit above the review floor
     is surfaced as `[NEEDS REVIEW]` (thick box + `?REVIEW` label in the overlay) instead of being
     silently dropped. Only fires when the floor has zero accepted placements, so a placed floor's
     own faint phantom stays suppressed.
- **Threshold guidance**: keep `--min-edge-corr 0.2`. The old "genuine ≥0.25 / phantom ≤0.17"
  margin does NOT hold on Midnight/BfA art - a genuinely faint floor (smooth low-contrast cave)
  can score as low as ~0.11-0.19, so **do not lower the global threshold to force one in** (0.15
  would readmit real phantoms). Instead confirm the `[NEEDS REVIEW]` candidate on the overlay and,
  if genuine, keep it; if a floor is simply not drawn on the facade (an unused/absent floor, e.g.
  Den of Nalorakk's first floor) it correctly gets no placement. Duplicated-art vs. banner is a
  human call - the user flags genuine duplicated art (rare, ~twice in 150 dungeons).

### 2. Vision-review the stripe overlay

Read `out/overlay_placements.png`. Inside each placement's outline, horizontal bands alternate
between facade and warped floor image: a correct placement shows map art flowing **unbroken
across band boundaries**; misalignment shows as broken/offset lines. Also sanity-check the
console `rotation`/`size` values. Floor-image fluff (its own banner) appearing over empty
parchment inside a placement is normal.

- **Check the count**: one accepted placement per real floor (minus any floor genuinely not drawn
  on the facade). Fewer means a floor failed to match; more (beyond known duplicated-art) means a
  phantom slipped through.
- **`[NEEDS REVIEW]` placements** (thick box, `?REVIEW` label) are faint low-contrast floors below
  the auto-accept bar. Confirm on the overlay that the floor's art actually lands inside the box
  (the `areas` overlay is often clearer - a faint floor whose art sits cleanly inside its own
  colored region is genuine). Keep it only if it's real; otherwise drop that floor from the import
  map. The console prints its edge_corr and inlier ratio to help judge.

### 3. Partition into FloorUnionAreas

```bash
run.sh <workdir> areas --placements /work/out/placements.json --out-dir /work/out
```

Writes `out/areas.json` (placements + `areas` polygons in DB latlng) and `out/overlay_areas.png`
(colored partition). Every facade pixel is owned by exactly one placement (no gaps - gaps break
facade→floor lookups) and polygons are slightly dilated so neighbours overlap (overlap is safe:
first-match-wins).

Areas route ANY facade point to a floor - users park icons on empty parchment far outside the
drawn dungeon - so the partition follows one hard rule: **art always stays with the floor it
depicts; only empty parchment is negotiable** (it goes to the nearest art, like hand-drawn
areas). Each floor's art blob comes from comparing its image against the per-pixel **median of
all floor images** (>= 3 images; the median is the shared parchment/banner/frame backdrop) or,
for exactly two images, the pairwise diff gated by each image's deviation from its own
large-scale median. Boundary smoothing applies only over parchment, small-region cleanup never
moves a fragment containing its own art, and the polygon overlap margin always exceeds the
simplification epsilon - so simplification cannot cut art either. The console prints an
**exclusive own-art coverage** figure per placement (own art claimed by no other placement that
is inside its own polygons): anything under 100% deserves a look at the overlay before
inserting; tune `--content-diff-threshold`/`--content-close-px` when a content mask misses art.

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

### 6. Verify (close the loop after insert)

1. **Round-trip self-consistency** - run this whenever the dungeon has *any* enemy-carrying
   mapping version (it needs no MDT data; enemies are only sample points):

   ```bash
   docker compose exec -T -e KSG_FLOOR_UNION_VERIFY_MV=<new mapping version id> \
       app php artisan tinker .claude/skills/generate-floor-unions/scripts/verify_floor_unions.php
   ```

   For every enemy on the reference version it projects the map location onto the facade through
   the new unions and back, and prints a per-floor summary: enemies sampled, how many `projected`
   (proves the union actually transformed the point, not a no-op), how many round-trip to the
   `same-floor`, and the worst deviation. **Treat inserted unions as provisional until this has
   run.** A whole floor failing same-floor = a swapped or absent union (fix the import map); a
   couple of edge-of-floor enemies crossing an area boundary is normal (2/164 on Ruby Life Pools,
   2/277 on Magister's Terrace) - nudge those boundaries in the editor, don't chase them in code.
   Calibration runs: Kings' Rest 108/108, Temple of Sethraliss 123/123, Ruby Life Pools 162/164.
2. **Brand-new dungeon with no enemies anywhere** (the common Midnight case): the round-trip has
   nothing to sample and prints so - fall back to the overlay review (step 2) and the `areas`
   overlay + 100% exclusive own-art coverage as the acceptance signal. The enemy round-trip becomes
   available only *after* the MDT import populates enemies, which itself consumes these unions.
3. **Map editor**: the user reviews/tweaks unions and areas on the facade floor in the admin
   editor - that is the intended final-tuning surface, not a failure of the generator.

## Regression tests

`register_floors.py` is bit-for-bit deterministic on the pinned Docker image, so its `match`
output is guarded by golden files. **Run the suite after any change to `register_floors.py`:**

```bash
.claude/skills/generate-floor-unions/scripts/test.sh                     # default image root ~/maps
KSG_FLOOR_UNION_FIXTURES=/path/to/maps .../test.sh                       # explicit root
.../test.sh --regenerate                                                 # after an INTENTIONAL change
```

It re-runs `match` on both calibration dungeons (Skyreach, Magister's Terrace) and the 8 hardening
dungeons and compares every placement (lat/lng/size/rotation/edge_corr/needs_review) against
`scripts/test_expected/*.json` within tolerance, plus the coordinate math. The fixture **images are
committed in-repo** under `scripts/test_fixtures/<name>/` as **256-colour indexed PNGs** (quantized
like MDT's own tiles - ~40% smaller than source RGB, with no measurable effect on the match:
verified placements move <0.02 map units / edge_corr and no placement is gained or lost).
`KSG_FLOOR_UNION_FIXTURES` can point the suite at another image root; a fixture whose images are
missing is skipped, not failed. Comparison is order-independent within a floor image, so a
duplicated-art floor's instance relabel is not a spurious failure. If a change is a deliberate
improvement, re-review the overlays, then `--regenerate` and commit the new goldens.

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
- The area partition's content masks MUST come from the median-backdrop diff. Two rejected
  alternatives, don't retry them: raw Canny edges cannot separate faint art (Magister's Terrace
  upper-tower disc) from strong parchment grain at any threshold, and edge-"agreement" with the
  facade fragments badly on duplicated-art floors. Keep boundaries simple; the editor is for
  fine-tuning.
- **Banner phantoms are a keypoint problem, not a threshold problem** (#3616). The same
  median-backdrop the `areas` step uses is reused at *match* time to restrict each floor's
  keypoints to its own art; that is what stops banner matches. Don't try to fix banner phantoms by
  raising `--min-edge-corr` (it can't separate a 0.61 banner fit) or by post-filtering placements
  (that loses the real placements the banner was masking). The off-image guard and review band are
  the safety nets, not the primary fix.
- **Faint low-contrast floors** (smooth cave interiors: Altar of Fangs' Mutation Chambers 0.110,
  Murder Row's Illicit Rain 0.194) land below `--min-edge-corr` even when perfectly placed - the
  fit is the stable RANSAC solution (identical across keypoint densities), the edge magnitude is
  just weak. These are exactly what the `[NEEDS REVIEW]` band surfaces; a bigger SIFT keypoint
  budget does not raise their edge_corr (tried, no change). Confirm on the overlay and keep.
- **Per-dungeon calibration (Midnight/BfA set, #3616), all at defaults** (`--min-edge-corr 0.2`,
  masking on): Ruby Life Pools 2/2 (0.35-0.54), Kings' Rest 1/1 (0.87), The Blinding Vale 1/1
  (0.80), Temple of Sethraliss 2/2 (banner phantoms killed by the off-image guard), Voidscar Arena
  3/3 (a shared central-backdrop phantom on all 3 floors killed by masking), Murder Row 3/3
  (Illicit Rain via review band), Altar of Fangs 3/3 (Mutation Chambers via review band). No
  `--content-diff-threshold`/`--content-close-px` retuning was needed on this art.
