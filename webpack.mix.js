const {mix} = require('laravel-mix');
const argv = require('yargs').argv;

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel application. By default, we are compiling the Sass
 | file for the application as well as bundling up all the JS files.
 |
 */

// npm run dev -- --env.full true
// false if not defined, true if defined
let full = false;
if (typeof argv.env !== 'undefined' && typeof argv.env.full !== 'undefined') {
    full = argv.env.full;
}
// npm run dev -- --env.images false
let images = false;
if (typeof argv.env !== 'undefined' && typeof argv.env.images !== 'undefined') {
    images = argv.env.images;
}

mix.copy('node_modules/@fortawesome/fontawesome-free/webfonts', 'public/webfonts');

if (mix.inProduction()) {
    // Custom processing only
    mix.styles(['resources/assets/css/**/*.css'], 'public/css/custom.css');
    mix.babel([
        // Home page only
        'resources/assets/js/custom/home.js',
        // Doesn't depend on anything
        'resources/assets/js/custom/constants.js',
        // Include in proper order
        'resources/assets/js/custom/util.js',
        'resources/assets/js/custom/signalable.js',
        'resources/assets/js/custom/dungeonmap.js',
        'resources/assets/js/custom/mapobject.js',
        'resources/assets/js/custom/enemy.js',
        'resources/assets/js/custom/enemypatrol.js',
        'resources/assets/js/custom/enemypack.js',
        'resources/assets/js/custom/route.js',
        'resources/assets/js/custom/killzone.js',
        'resources/assets/js/custom/mapcomment.js',
        'resources/assets/js/custom/dungeonstartmarker.js',
        'resources/assets/js/custom/dungeonfloorswitchmarker.js',
        'resources/assets/js/custom/hotkeys.js',

        'resources/assets/js/custom/enemyselection/enemyselection.js',
        'resources/assets/js/custom/enemyselection/killzoneenemyselection.js',
        'resources/assets/js/custom/enemyselection/mdtenemyselection.js',

        'resources/assets/js/custom/enemyvisuals/enemyvisual.js',
        'resources/assets/js/custom/enemyvisuals/enemyvisualicon.js',
        'resources/assets/js/custom/enemyvisuals/enemyvisualmain.js',
        'resources/assets/js/custom/enemyvisuals/enemyvisualmainaggressiveness.js',
        'resources/assets/js/custom/enemyvisuals/enemyvisualmainenemyforces.js',

        'resources/assets/js/custom/enemyvisuals/modifiers/modifier.js',
        'resources/assets/js/custom/enemyvisuals/modifiers/modifierinfested.js',
        'resources/assets/js/custom/enemyvisuals/modifiers/modifierinfestedvote.js',
        'resources/assets/js/custom/enemyvisuals/modifiers/modifierraidmarker.js',

        'resources/assets/js/custom/mapcontrol.js',
        'resources/assets/js/custom/mapcontrols/addisplaycontrols.js',
        'resources/assets/js/custom/mapcontrols/mapobjectgroupcontrols.js',
        'resources/assets/js/custom/mapcontrols/drawcontrols.js',
        'resources/assets/js/custom/mapcontrols/enemyforcescontrols.js',
        'resources/assets/js/custom/mapcontrols/enemyvisualcontrols.js',
        'resources/assets/js/custom/mapcontrols/factiondisplaycontrols.js',

        'resources/assets/js/custom/admin/enemyattaching.js',
        'resources/assets/js/custom/admin/admindungeonmap.js',
        'resources/assets/js/custom/admin/adminenemy.js',
        'resources/assets/js/custom/admin/adminenemypatrol.js',
        'resources/assets/js/custom/admin/adminenemypack.js',
        'resources/assets/js/custom/admin/admindrawcontrols.js',
        'resources/assets/js/custom/admin/admindungeonstartmarker.js',
        'resources/assets/js/custom/admin/admindungeonfloorswitchmarker.js',
        'resources/assets/js/custom/admin/adminmapcomment.js',

        // Include the rest
        'resources/assets/js/custom/groupcomposition.js',
        'resources/assets/js/custom/mapobjectgroup.js',
        'resources/assets/js/custom/mapobjectgroups/dungeonfloorswitchmarkermapobjectgroup.js',
        'resources/assets/js/custom/mapobjectgroups/dungeonstartmarkermapobjectgroup.js',
        'resources/assets/js/custom/mapobjectgroups/enemymapobjectgroup.js',
        'resources/assets/js/custom/mapobjectgroups/enemypackmapobjectgroup.js',
        'resources/assets/js/custom/mapobjectgroups/enemypatrolmapobjectgroup.js',
        'resources/assets/js/custom/mapobjectgroups/killzonemapobjectgroup.js',
        'resources/assets/js/custom/mapobjectgroups/mapcommentmapobjectgroup.js',
        'resources/assets/js/custom/mapobjectgroups/routemapobjectgroup.js',
        // 'resources/assets/js/custom/**/*.js'
    ], 'public/js/custom.js');
}
// .combine(, 'public/js/custom.js');

if (full || mix.inProduction()) {
    // Handlebars has a bug which requires this: https://github.com/wycats/handlebars.js/issues/1174
    mix.webpackConfig({
        resolve: {
            alias: {
                handlebars: 'handlebars/dist/handlebars.min.js'
            }
        }
    });
    mix.js('resources/assets/js/app.js', 'public/js')
        .sass('resources/assets/sass/app.scss', 'public/css')
        // Lib processing
        .styles(['resources/assets/lib/**/*.css'], 'public/css/lib.css')
        .babel('resources/assets/lib/**/*.js', 'public/js/lib.js');
}

mix.sourceMaps();

if (images) {
    if (mix.inProduction()) {
        // Copies all tiles as well which takes a while
        mix.copy('resources/assets/images', 'public/images', false);
    } else {
        // Allow import of pure JS
        // mix.copy('resources/assets/js/custom', 'public/js/custom', false);

        mix.copy('resources/assets/images/affixes', 'public/images/affixes', false);
        mix.copy('resources/assets/images/classes', 'public/images/classes', false);
        mix.copy('resources/assets/images/expansions', 'public/images/expansions', false);
        mix.copy('resources/assets/images/factions', 'public/images/factions', false);
        mix.copy('resources/assets/images/home', 'public/images/home', false);
        mix.copy('resources/assets/images/icon', 'public/images/icon', false);
        mix.copy('resources/assets/images/lib', 'public/images/lib', false);
        mix.copy('resources/assets/images/mapicon', 'public/images/mapicon', false);
        mix.copy('resources/assets/images/raidmarkers', 'public/images/raidmarkers', false);
        mix.copy('resources/assets/images/routeattributes', 'public/images/routeattributes', false);
        mix.copy('resources/assets/images/specializations', 'public/images/specializations', false);
    }
}
