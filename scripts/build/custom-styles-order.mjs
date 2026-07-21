/**
 * Ordered list of CSS globs that make up public/css/custom-{version}.css.
 *
 * base -> lib -> sections, so a page-section override always lands after (and therefore wins a
 * cascade tie against) the base/library rules it customizes. Before #3506 the whole tree was one
 * flat, alphabetically-sorted glob; splitting it into base/lib/sections subfolders would otherwise
 * have silently reshuffled that alphabetical order, so the order is made explicit here instead.
 *
 * Each entry is expanded (sorted alphabetically) at build time by expandScriptList from concat.mjs.
 */
export const customStyles = [
    'resources/assets/css/base/**/*.css',
    'resources/assets/css/lib/**/*.css',
    'resources/assets/css/sections/**/*.css',
];
