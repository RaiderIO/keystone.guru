#!/usr/bin/env python3
"""
Register per-floor dungeon map images onto a combined facade image and emit
FloorUnion / FloorUnionArea values in keystone.guru map coordinates.

The facade image is composed from the exact same per-floor images (scaled,
rotated, translated), so SIFT feature matching + RANSAC affine estimation
recovers each placement precisely. A floor image may appear multiple times on
the facade (zoomed sub-regions, or floors that share art such as "void"
variants); matching is therefore iterative per floor image: accept the best
placement, remove its inlier matches, and search again.

Coordinate space (see app/Service/Coordinates/CoordinatesService.php):
  - The map plane spans lat [0..-256], lng [0..384], aspect ratio 1.5.
  - A full-plane image of H px height maps px->map-units as 256/H.
  - FloorUnion.lat/lng = where the floor's CENTER lands on the facade plane.
  - FloorUnion.size    = lat-extent in map units the full floor occupies on
                         the facade plane (256 = exactly the whole facade).
  - FloorUnion.rotation is applied by LatLng::rotate(); the sign mapping from
    image-space rotation was calibrated against Magister's Terrace (Midnight)
    ground truth: rotation_db == image-space CW angle of the affine matrix.

Subcommands:
  match   - find placements, write placements.json + overlay_placements.png
  areas   - partition the facade into full-coverage per-placement polygons,
            write areas.json + overlay_areas.png
  stitch  - (fallback) download+stitch CDN tiles into a single floor image
"""

import argparse
import json
import math
import os
import sys
import urllib.error
import urllib.request

import cv2
import numpy as np

MAP_MAX_LAT = -256.0
MAP_MAX_LNG = 384.0
MAP_SIZE = 256.0

# Distinct BGR colors for overlays, one per placement.
COLORS = [
    (60, 76, 231), (113, 204, 46), (219, 152, 52), (34, 126, 230),
    (182, 89, 155), (15, 196, 241), (133, 160, 22), (80, 62, 44),
    (156, 188, 26), (0, 84, 211), (128, 0, 128), (0, 128, 128),
]


def px_to_latlng(x, y, width, height):
    """Facade pixel -> map plane (lat, lng)."""
    return (-(y / height) * MAP_SIZE, (x / width) * MAP_MAX_LNG)


def latlng_to_px(lat, lng, width, height):
    return (lng / MAP_MAX_LNG * width, -lat / MAP_SIZE * height)


def load_images(path):
    """Return (bgr, gray) for an image path."""
    bgr = cv2.imread(path, cv2.IMREAD_COLOR)
    if bgr is None:
        sys.exit(f'ERROR: could not read image: {path}')
    return bgr, cv2.cvtColor(bgr, cv2.COLOR_BGR2GRAY)


def solidify_blob(binary, close_px):
    """
    Edge/diff pixels -> one solid art blob: blank a border margin (decorative
    frames otherwise form a huge ring component and break hole-filling, which
    seeds from the corner), morphological close, keep the largest connected
    component, fill its enclosed holes.
    """
    h, w = binary.shape
    margin_y, margin_x = max(1, h // 25), max(1, w // 25)
    binary[:margin_y, :] = 0
    binary[-margin_y:, :] = 0
    binary[:, :margin_x] = 0
    binary[:, -margin_x:] = 0
    kernel = np.ones((close_px, close_px), np.uint8)
    blob = cv2.morphologyEx(binary, cv2.MORPH_CLOSE, kernel)

    component_count, labels, stats, _ = cv2.connectedComponentsWithStats(blob)
    if component_count <= 1:
        return np.full(binary.shape, 255, np.uint8)
    largest = 1 + int(np.argmax(stats[1:, cv2.CC_STAT_AREA]))
    mask = np.where(labels == largest, np.uint8(255), np.uint8(0))

    flooded = mask.copy()
    cv2.floodFill(flooded, np.zeros((h + 2, w + 2), np.uint8), (0, 0), 255)
    return cv2.bitwise_or(mask, cv2.bitwise_not(flooded))


def content_masks(paths, close_px, diff_threshold):
    """
    Per floor image, the visible art footprint as a solid blob (no parchment
    background, no shared fluff like the title banner), or None when it cannot
    be computed reliably.

    With >= 3 distinct images the per-pixel MEDIAN across them recovers the
    shared backdrop (all cuts of a dungeon use the same parchment, banner and
    frame), and each floor's content is simply where it differs from that
    median - this keeps even art too faint for edge detection and drops even
    strong parchment grain. With fewer images no reliable backdrop estimate
    exists (the median degenerates and edge detection cannot separate faint
    art from parchment grain - validated on Skyreach), so return None and let
    the caller fall back to rectangle-interior scoring.
    """
    unique_paths = list(dict.fromkeys(paths))
    if len(unique_paths) < 2:
        print('NOTE: single floor image - no backdrop estimate possible, '
              'using rectangle-interior area boundaries')
        return None

    images = {path: load_images(path)[0] for path in unique_paths}

    masks = {}
    if len(unique_paths) >= 3:
        backdrop = np.median(
            np.stack([images[path].astype(np.float32)
                      for path in unique_paths]), axis=0)
        for path in unique_paths:
            diff = np.abs(
                images[path].astype(np.float32) - backdrop).max(axis=2)
            binary = np.where(diff > diff_threshold,
                              np.uint8(255), np.uint8(0))
            masks[path] = solidify_blob(binary, close_px)
    else:
        # Two images: their pairwise diff marks BOTH floors' art (each art
        # position differs from the other image's parchment there), so gate it
        # with per-image "artness" - deviation from the image's own large-scale
        # median, which estimates its parchment. Art belongs to the image that
        # actually deviates from parchment at that pixel.
        first, second = (images[path].astype(np.float32)
                         for path in unique_paths)
        pair_diff = np.abs(first - second).max(axis=2) > diff_threshold
        for path in unique_paths:
            image = images[path]
            parchment = cv2.medianBlur(image, 101).astype(np.float32)
            artness = np.abs(
                image.astype(np.float32) - parchment).max(axis=2)
            binary = np.where(pair_diff & (artness > diff_threshold),
                              np.uint8(255), np.uint8(0))
            masks[path] = solidify_blob(binary, close_px)

    return masks


def draw_label(canvas, text, x, y, foreground, background):
    """Outlined text label (thick background pass + thin foreground pass)."""
    position = (int(x) + 12, int(y) - 8)
    cv2.putText(canvas, text, position, cv2.FONT_HERSHEY_SIMPLEX, 0.7,
                background, 3)
    cv2.putText(canvas, text, position, cv2.FONT_HERSHEY_SIMPLEX, 0.7,
                foreground, 1)


def decompose_affine(m):
    """Return (scale, rotation_deg_ccw_in_image_coords) of a partial affine."""
    scale = math.hypot(m[0, 0], m[1, 0])
    rotation = math.degrees(math.atan2(m[1, 0], m[0, 0]))
    return scale, rotation


def normalize_deg(deg):
    while deg <= -180.0:
        deg += 360.0
    while deg > 180.0:
        deg -= 360.0
    return deg


def sobel_magnitude(img):
    gx = cv2.Sobel(img, cv2.CV_32F, 1, 0, ksize=3)
    gy = cv2.Sobel(img, cv2.CV_32F, 0, 1, ksize=3)
    return cv2.magnitude(gx, gy)


def edge_correlation(floor_gray, facade_edges, m_affine):
    """
    Zero-normalized cross-correlation of Sobel edge magnitudes between the
    warped floor and the facade, within the warped footprint. Parchment
    background texture correlates everywhere; actual map art only correlates
    when the placement is genuinely aligned, so this separates real placements
    from texture-level false positives far better than inlier counts.

    facade_edges is the precomputed sobel_magnitude() of the facade (identical
    for every candidate, so it is computed once by the caller).
    """
    fh, fw = facade_edges.shape[:2]
    warped = cv2.warpAffine(floor_gray, m_affine, (fw, fh))
    mask = cv2.warpAffine(np.full(floor_gray.shape[:2], 255, np.uint8),
                          m_affine, (fw, fh))
    mask = cv2.erode(mask, np.ones((5, 5), np.uint8))
    if int(mask.sum()) == 0:
        return 0.0

    a = sobel_magnitude(warped)[mask > 0]
    b = facade_edges[mask > 0]
    a = a - a.mean()
    b = b - b.mean()
    denominator = float(np.linalg.norm(a) * np.linalg.norm(b))
    if denominator == 0.0:
        return 0.0
    return float(np.dot(a, b) / denominator)


def find_placements(name, floor_gray, facade_edges, sift, facade_kp, facade_desc,
                    min_inliers, max_instances, ransac_px, min_edge_corr):
    """
    Iterative multi-instance affine registration of one floor image.

    Phantom fits are common: floor images share art (legend boxes, borders,
    parchment texture) that also appears on the facade, and such fits can carry
    MORE RANSAC inliers than small genuine placements. Every candidate is
    therefore verified with edge_correlation(); rejected candidates have their
    inlier matches removed and the search continues, so a phantom cannot mask a
    genuine placement hiding in the same facade region.
    """
    kp, desc = sift.detectAndCompute(floor_gray, None)
    if desc is None or len(kp) < 8:
        return [], 0

    matcher = cv2.BFMatcher(cv2.NORM_L2)
    knn = matcher.knnMatch(desc, facade_desc, k=2)
    good = [m for m, n in (p for p in knn if len(p) == 2)
            if m.distance < 0.75 * n.distance]

    placements = []
    remaining = list(good)
    attempts = 0
    # The attempt budget is shared with rejected phantom fits, so leave slack
    # beyond max_instances for facades with a lot of shared banner/border art.
    while (len(remaining) >= min_inliers and len(placements) < max_instances
           and attempts < max_instances * 4 + 8):
        attempts += 1
        src = np.float32([kp[m.queryIdx].pt for m in remaining]).reshape(-1, 1, 2)
        dst = np.float32([facade_kp[m.trainIdx].pt for m in remaining]).reshape(-1, 1, 2)
        m_affine, mask = cv2.estimateAffinePartial2D(
            src, dst, method=cv2.RANSAC, ransacReprojThreshold=ransac_px,
            maxIters=10000, confidence=0.999)
        if m_affine is None:
            break
        inlier_count = int(mask.sum())
        if inlier_count < min_inliers:
            break

        remaining = [m for m, keep in zip(remaining, mask.ravel()) if not keep]

        scale, rotation = decompose_affine(m_affine)
        if not 0.02 < scale < 50.0:
            print(f'{name}: rejected degenerate fit (scale={scale:.3f}, '
                  f'inliers={inlier_count}), continuing search')
            continue
        duplicate = any(
            np.hypot(*(np.array(p['matrix'])[:, 2] - m_affine[:, 2])) < 10.0
            and abs(p['scale'] - scale) / p['scale'] < 0.05
            for p in placements)
        if duplicate:
            print(f'{name}: rejected duplicate of an accepted placement '
                  f'(inliers={inlier_count}), continuing search')
            continue

        corr = edge_correlation(floor_gray, facade_edges, m_affine)
        if corr < min_edge_corr:
            print(f'{name}: rejected phantom fit (inliers={inlier_count}, '
                  f'edge_corr={corr:.3f} < {min_edge_corr}), continuing search')
            continue

        placements.append({
            'matrix': m_affine.tolist(),
            'scale': scale,
            'rotation_img_deg': rotation,
            'inliers': inlier_count,
            'matches': len(good),
            'edge_corr': corr,
        })

    return placements, len(good)


def assert_map_plane_aspect(name, width, height):
    """
    Image resolutions may vary freely (all math normalizes by width/height),
    but every map-plane image MUST have the plane's 1.5 aspect ratio - any
    other framing silently corrupts every emitted lat/lng/size value.
    """
    aspect = width / height
    if abs(aspect - 1.5) > 0.01:
        sys.exit(f'ERROR: {name} is {width}x{height} (aspect {aspect:.3f}); '
                 f'map plane images must have aspect ratio 1.5 - re-frame it '
                 f'to span the full 256x384 map plane')


def cmd_match(args):
    _, facade_gray = load_images(args.facade)
    fh, fw = facade_gray.shape[:2]
    assert_map_plane_aspect(args.facade, fw, fh)
    facade_edges = sobel_magnitude(facade_gray)

    sift = cv2.SIFT_create()
    facade_kp, facade_desc = sift.detectAndCompute(facade_gray, None)
    print(f'facade: {fw}x{fh}, {len(facade_kp)} keypoints')

    results = []
    for floor_path in args.floors:
        name = os.path.splitext(os.path.basename(floor_path))[0]
        _, floor_gray = load_images(floor_path)
        h, w = floor_gray.shape[:2]
        assert_map_plane_aspect(floor_path, w, h)

        placements, good_matches = find_placements(
            name, floor_gray, facade_edges, sift, facade_kp, facade_desc,
            args.min_inliers, args.max_instances, args.ransac_px,
            args.min_edge_corr)
        if not placements:
            print(f'{name}: NO PLACEMENT FOUND ({good_matches} ratio-test matches)')

        for i, p in enumerate(placements, start=1):
            m = np.array(p['matrix'])
            center = m @ np.array([w / 2.0, h / 2.0, 1.0])
            lat, lng = px_to_latlng(center[0], center[1], fw, fh)
            size = MAP_SIZE * (p['scale'] * h / fh)
            rotation_db = normalize_deg(p['rotation_img_deg'])
            corr = p['edge_corr']
            results.append({
                'floor_image': name,
                'path': os.path.abspath(floor_path),
                'instance': i,
                'floor_px': [w, h],
                'center_px': [round(float(center[0]), 1), round(float(center[1]), 1)],
                'matrix': p['matrix'],
                'scale': round(p['scale'], 4),
                'inliers': p['inliers'],
                'edge_corr': round(corr, 3),
                'lat': round(lat, 2),
                'lng': round(lng, 2),
                'size': round(size, 1),
                'rotation': round(rotation_db, 1),
            })
            print(f"{name}#{i}: lat={lat:.2f} lng={lng:.2f} size={size:.1f} "
                  f"rotation={rotation_db:.1f} inliers={p['inliers']}/{p['matches']} "
                  f"edge_corr={corr:.3f}")

    out = {
        'facade': {'path': os.path.abspath(args.facade), 'width': fw, 'height': fh},
        'placements': results,
    }
    os.makedirs(args.out_dir, exist_ok=True)
    out_path = os.path.join(args.out_dir, 'placements.json')
    with open(out_path, 'w') as f:
        json.dump(out, f, indent=2)
    print(f'wrote {out_path}')

    render_placements_overlay(out, os.path.join(args.out_dir, 'overlay_placements.png'))


def render_placements_overlay(data, out_path):
    """
    Stripe-blend verification render: inside each placement's footprint,
    alternating horizontal bands show the facade and the (color-tinted) warped
    floor at full opacity. A correct placement shows unbroken map art across
    band boundaries; misalignment shows as broken/offset lines - which is
    exactly what vision review can judge reliably.
    """
    facade_bgr, _ = load_images(data['facade']['path'])
    canvas = facade_bgr.copy()
    band = np.zeros(canvas.shape[:2], np.uint8)
    band[(np.arange(canvas.shape[0]) // 40) % 2 == 1, :] = 255

    for idx, p in enumerate(data['placements']):
        color = COLORS[idx % len(COLORS)]
        floor_bgr, _ = load_images(p['path'])
        m = np.float32(p['matrix'])
        warped = cv2.warpAffine(floor_bgr, m, (canvas.shape[1], canvas.shape[0]))
        mask = cv2.warpAffine(
            np.full(floor_bgr.shape[:2], 255, np.uint8), m,
            (canvas.shape[1], canvas.shape[0]))
        tint = cv2.addWeighted(
            warped, 0.85, np.full_like(warped, color, dtype=np.uint8), 0.15, 0)
        paint = (mask > 0) & (band > 0)
        canvas[paint] = tint[paint]

    for idx, p in enumerate(data['placements']):
        color = COLORS[idx % len(COLORS)]
        w, h = p['floor_px']
        m = np.float32(p['matrix'])
        corners = np.float32([[0, 0], [w, 0], [w, h], [0, h]]).reshape(-1, 1, 2)
        warped_corners = cv2.transform(corners, m).astype(np.int32)
        cv2.polylines(canvas, [warped_corners], True, color, 2)
        cx, cy = p['center_px']
        cv2.drawMarker(canvas, (int(cx), int(cy)), color,
                       cv2.MARKER_CROSS, 24, 2)
        draw_label(canvas, f"{p['floor_image']}#{p['instance']}", cx, cy,
                   color, (255, 255, 255))

    cv2.imwrite(out_path, canvas)
    print(f'wrote {out_path}')


def cmd_areas(args):
    with open(args.placements) as f:
        data = json.load(f)
    fw, fh = data['facade']['width'], data['facade']['height']
    _, facade_gray = load_images(data['facade']['path'])
    placements = data['placements']
    if not placements:
        sys.exit('ERROR: no placements in input')

    # Score per placement from its ART CONTENT, not its image rectangle.
    # FloorUnionAreas route ANY facade point to a floor - users park icons on
    # empty parchment far outside the drawn dungeon - so regions must (1) never
    # cut into their own floor's visible art and (2) split empty space by
    # nearest art, like hand-drawn areas do. Inside the warped content blob the
    # score is the (positive) interior depth, so own art always outranks every
    # neighbour and overlapping-art conflicts go to the deeper blob; outside it
    # is -distance_to_blob, so empty space goes to the nearest art. argmax =>
    # every pixel owned => full coverage, no gaps.
    #
    # (A raw-edge nearest-art variant fragments badly - the filled largest-
    # component blob is what keeps the distance field, and thus the
    # boundaries, smooth.)
    scores = np.empty((len(placements), fh, fw), np.float32)
    warped_contents = [None] * len(placements)
    floor_content = content_masks(
        [p['path'] for p in placements], args.content_close_px,
        args.content_diff_threshold)
    for idx, p in enumerate(placements):
        m = np.float32(p['matrix'])
        if floor_content is not None:
            warped = cv2.warpAffine(floor_content[p['path']], m, (fw, fh),
                                    flags=cv2.INTER_NEAREST)
            if int(warped.max()) == 0:
                # Degenerate (content fully outside the facade): rect center.
                cx, cy = p['center_px']
                cv2.circle(warped, (int(cx), int(cy)), 5, 255, -1)
            warped_contents[idx] = warped
        else:
            # Fallback: the placement's full image rectangle as the "content".
            w, h = p['floor_px']
            corners = np.float32(
                [[0, 0], [w, 0], [w, h], [0, h]]).reshape(-1, 1, 2)
            warped = np.zeros((fh, fw), np.uint8)
            cv2.fillPoly(warped, [cv2.transform(corners, m).astype(np.int32)],
                         255)
        inside = cv2.distanceTransform(warped, cv2.DIST_L2, 3)
        outside = cv2.distanceTransform(cv2.bitwise_not(warped), cv2.DIST_L2, 3)
        if floor_content is None:
            # Rects nest/overlap heavily (unlike art blobs): normalize interior
            # depth per placement so big rects don't swallow small insets.
            peak = inside.max() or 1.0
            inside = inside / peak
        scores[idx] = np.where(warped > 0, inside, -outside)

    # Light smoothing rounds off boundary wiggles that would otherwise trace
    # every dent of the art outline - but ONLY over parchment: art pixels keep
    # their raw scores so a neighbour's smoothed field can never push a
    # boundary into somebody's art. (Hand-drawn areas are simple polygons; the
    # exact path through EMPTY space is the only thing smoothing may move.)
    if any(mask is not None for mask in warped_contents):
        any_content = np.zeros((fh, fw), bool)
        for mask in warped_contents:
            if mask is not None:
                any_content |= mask > 0
        for idx in range(len(placements)):
            blurred = cv2.GaussianBlur(scores[idx], (0, 0), sigmaX=6)
            scores[idx] = np.where(any_content, scores[idx], blurred)
    else:
        for idx in range(len(placements)):
            scores[idx] = cv2.GaussianBlur(scores[idx], (0, 0), sigmaX=6)

    label_map = np.argmax(scores, axis=0).astype(np.int32)

    # Reassign small enclaves (stray fragments inside another placement's
    # territory) to the surrounding region - but never a fragment that holds
    # its own floor's art: art must stay with the floor it depicts.
    for _ in range(3):
        changed = False
        for idx in range(len(placements)):
            region = (label_map == idx).astype(np.uint8)
            component_count, components = cv2.connectedComponents(region)
            for component in range(1, component_count):
                component_mask = components == component
                if int(component_mask.sum()) >= args.min_component_px:
                    continue
                if (warped_contents[idx] is not None
                        and bool((warped_contents[idx] > 0)[component_mask].any())):
                    continue
                scores[idx][component_mask] = -np.inf
                changed = True
        if not changed:
            break
        label_map = np.argmax(scores, axis=0).astype(np.int32)

    # Dilation must exceed the polygon-simplification epsilon: approxPolyDP may
    # deviate up to ~epsilon inward, and the overlap margin guarantees that
    # can never cut a region's own art out of its polygon.
    dilate_px = max(args.dilate_px, int(args.epsilon_px) + 3)
    for idx, p in enumerate(placements):
        region = (label_map == idx).astype(np.uint8) * 255
        # Dilation so neighboring polygons overlap: overlaps are handled by
        # first-match-wins in containsPoint, gaps break facade->floor lookup.
        region = cv2.dilate(region, np.ones((dilate_px, dilate_px), np.uint8))
        contours, _ = cv2.findContours(region, cv2.RETR_EXTERNAL,
                                       cv2.CHAIN_APPROX_SIMPLE)
        polygons = []
        for contour in contours:
            if cv2.contourArea(contour) < args.min_area_px:
                continue
            approx = cv2.approxPolyDP(contour, args.epsilon_px, True)
            vertices = []
            for point in approx.reshape(-1, 2):
                lat, lng = px_to_latlng(float(point[0]), float(point[1]), fw, fh)
                vertices.append({'lat': round(lat, 2), 'lng': round(lng, 2)})
            if len(vertices) >= 3:
                polygons.append(vertices)
        p['areas'] = polygons
        if not polygons:
            print(f"WARNING: {p['floor_image']}#{p['instance']} has NO area "
                  f"polygons (its region fell below --min-area-px/"
                  f"--min-component-px) - a FloorUnion without areas can never "
                  f"match a facade point. Lower those thresholds.")
        else:
            print(f"{p['floor_image']}#{p['instance']}: {len(polygons)} "
                  f"polygon(s), {sum(len(v) for v in polygons)} vertices")

    # Objective "art stays with its floor" check: fraction of each placement's
    # own warped art covered by its own final polygons. Shortfalls mean art was
    # assigned to another union - legitimate only where two arts genuinely
    # overlap on the facade.
    for idx, p in enumerate(placements):
        own = warped_contents[idx]
        if own is None or not p['areas']:
            continue
        poly_mask = np.zeros((fh, fw), np.uint8)
        for vertices in p['areas']:
            points = np.int32([
                latlng_to_px(v['lat'], v['lng'], fw, fh) for v in vertices])
            cv2.fillPoly(poly_mask, [points], 255)
        # Where two placements' blobs overlap on the facade, a boundary through
        # the overlap is unavoidable - only art claimed by NO other placement
        # must be covered by its own polygons.
        exclusive = own > 0
        for other_idx, other in enumerate(warped_contents):
            if other_idx != idx and other is not None:
                exclusive &= ~(other > 0)
        exclusive_px = int(exclusive.sum())
        covered = int((exclusive & (poly_mask > 0)).sum())
        coverage = covered / exclusive_px if exclusive_px else 1.0
        warning = ('' if coverage >= 0.99 else
                   ' <-- WARNING: art assigned to another union, inspect the '
                   'overlay')
        print(f"{p['floor_image']}#{p['instance']}: exclusive own-art "
              f"coverage {coverage:.1%}{warning}")

    os.makedirs(args.out_dir, exist_ok=True)
    out_path = os.path.join(args.out_dir, 'areas.json')
    with open(out_path, 'w') as f:
        json.dump(data, f, indent=2)
    print(f'wrote {out_path}')

    render_areas_overlay(data, label_map, os.path.join(args.out_dir, 'overlay_areas.png'))


def render_areas_overlay(data, label_map, out_path):
    facade_bgr, _ = load_images(data['facade']['path'])
    fh, fw = facade_bgr.shape[:2]
    color_layer = np.zeros_like(facade_bgr)
    for idx in range(len(data['placements'])):
        color_layer[label_map == idx] = COLORS[idx % len(COLORS)]
    canvas = cv2.addWeighted(facade_bgr, 0.55, color_layer, 0.45, 0)

    for idx, p in enumerate(data['placements']):
        color = COLORS[idx % len(COLORS)]
        for vertices in p.get('areas', []):
            points = np.int32([
                latlng_to_px(v['lat'], v['lng'], fw, fh) for v in vertices])
            cv2.polylines(canvas, [points], True, (255, 255, 255), 2)
            cv2.polylines(canvas, [points], True, color, 1)
        cx, cy = p['center_px']
        draw_label(canvas, f"{p['floor_image']}#{p['instance']}", cx, cy,
                   (255, 255, 255), (0, 0, 0))

    cv2.imwrite(out_path, canvas)
    print(f'wrote {out_path}')


def cmd_stitch(args):
    """
    Fallback source for floor images: stitch a floor's CDN tile pyramid.

    Only an HTTP 403/404 counts as "no more tiles" - any other failure
    (timeout, 5xx, undecodable tile) raises, because silently truncating the
    grid would feed a corrupt floor image into matching.
    """
    tiles = {}
    max_x = max_y = -1
    y = 0
    while True:
        row_found = False
        x = 0
        while True:
            url = f'{args.base_url}/{args.zoom}/{x}_{y}.png'
            try:
                with urllib.request.urlopen(url, timeout=15) as response:
                    raw = np.frombuffer(response.read(), np.uint8)
            except urllib.error.HTTPError as error:
                if error.code in (403, 404):
                    break
                raise
            tile = cv2.imdecode(raw, cv2.IMREAD_COLOR)
            if tile is None:
                sys.exit(f'ERROR: tile {url} could not be decoded')
            tiles[(x, y)] = tile
            row_found = True
            max_x, max_y = max(max_x, x), max(max_y, y)
            x += 1
        if not row_found:
            break
        y += 1

    if not tiles:
        sys.exit(f'ERROR: no tiles found under {args.base_url}/{args.zoom}/')

    tile_size = next(iter(tiles.values())).shape[0]
    canvas = np.zeros(((max_y + 1) * tile_size, (max_x + 1) * tile_size, 3), np.uint8)
    for (x, y), tile in tiles.items():
        canvas[y * tile_size:(y + 1) * tile_size,
               x * tile_size:(x + 1) * tile_size] = tile
    cv2.imwrite(args.output, canvas)
    print(f'wrote {args.output} ({canvas.shape[1]}x{canvas.shape[0]}, '
          f'{len(tiles)} tiles)')


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    sub = parser.add_subparsers(dest='command', required=True)

    p_match = sub.add_parser('match', help='register floors onto the facade')
    p_match.add_argument('--facade', required=True)
    p_match.add_argument('--out-dir', required=True)
    p_match.add_argument('--min-inliers', type=int, default=40)
    p_match.add_argument('--min-edge-corr', type=float, default=0.2,
                         help='reject placements whose edge correlation with '
                              'the facade is below this (phantom-fit filter)')
    p_match.add_argument('--max-instances', type=int, default=4)
    p_match.add_argument('--ransac-px', type=float, default=4.0)
    p_match.add_argument('floors', nargs='+')
    p_match.set_defaults(func=cmd_match)

    p_areas = sub.add_parser('areas', help='full-coverage facade partition')
    p_areas.add_argument('--placements', required=True,
                         help='placements.json from the match step')
    p_areas.add_argument('--out-dir', required=True)
    p_areas.add_argument('--epsilon-px', type=float, default=8.0)
    p_areas.add_argument('--min-area-px', type=float, default=2500.0)
    p_areas.add_argument('--min-component-px', type=int, default=8000,
                         help='enclaves smaller than this are merged into the '
                              'surrounding region')
    p_areas.add_argument('--dilate-px', type=int, default=5,
                         help='overlap margin between neighboring polygons; '
                              'overlaps are safe, gaps break facade lookups')
    p_areas.add_argument('--content-close-px', type=int, default=35,
                         help='morphological closing kernel used to turn a '
                              'floor image\'s edges into its solid art blob')
    p_areas.add_argument('--content-diff-threshold', type=float, default=20.0,
                         help='minimum per-pixel difference from the shared '
                              'backdrop (median of all floor images) to count '
                              'as art content')
    p_areas.set_defaults(func=cmd_areas)

    p_stitch = sub.add_parser('stitch', help='stitch CDN tiles into one image')
    p_stitch.add_argument('--base-url', required=True,
                          help='e.g. https://.../tiles/wod/skyreach/1')
    p_stitch.add_argument('--zoom', type=int, default=2)
    p_stitch.add_argument('--output', required=True)
    p_stitch.set_defaults(func=cmd_stitch)

    args = parser.parse_args()
    args.func(args)


if __name__ == '__main__':
    main()
