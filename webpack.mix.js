const mix = require('laravel-mix');
const argv = require('yargs').argv;
const GitRevisionPlugin = require('git-revision-webpack-plugin');
const WebpackShellPlugin = require('webpack-shell-plugin');
let gitRevisionPlugin = null; // Init in the config below

mix.webpackConfig({
    watchOptions: {
        ignored: ['node_modules', 'vendor'],
        poll: 1000 // Check for changes every second
    },
    // Handlebars has a bug which requires this: https://github.com/wycats/handlebars.js/issues/1174
    resolve: {
        alias: {
            handlebars: 'handlebars/dist/handlebars.min.js'
        }
    },
    // Translations
    module: {
        rules: [{
            // Matches all PHP or JSON files in `resources/lang` directory.
            test: /resources[\\\/]lang.+\.(php|json)$/,
            loader: 'laravel-localization-loader',
        }]
    },
    plugins: [
        // Use git version to output our files
        gitRevisionPlugin = new GitRevisionPlugin({
            versionCommand: 'tag | (tail -n 1)'
        }),

        // // Compile handlebars
        new WebpackShellPlugin({
            onBuildStart: [
                // Update version file. Required by PHP version library to get the proper version
                // See https://stackoverflow.com/a/39611938/771270
                // This is now handled by ./compile.sh, it was either missing -l or saying it doesn't exist as an option
                // 'git tag | (tail -n 1) > version',
                // Compile handlebars
                'handlebars ' + (mix.inProduction() ? '-m ' : '') +
                'resources/assets/js/handlebars/ -f resources/assets/js/handlebars.js'
            ],
            onBuildEnd: []
        })
    ]
});

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
let images = true;
if (typeof argv.env !== 'undefined' && typeof argv.env.images !== 'undefined') {
    images = argv.env.images;
}

mix.copy('node_modules/@fortawesome/fontawesome-free/webfonts', 'public/webfonts');

let precompile = [
    // Translations
    'resources/assets/js/messages.js'
];

mix.js(precompile, 'resources/assets/js/precompile.js');

let scripts = [
    // Include the precompiled scripts
    'resources/assets/js/precompile.js',

    // Home page only
    'resources/assets/js/custom/home.js',
    // Doesn't depend on anything
    'resources/assets/js/custom/constants.js',

    // Pre-compiled handlebars
    'resources/assets/js/handlebars.js',

    // Include in proper order
    'resources/assets/js/custom/util.js',
    'resources/assets/js/custom/signalable.js',

    // Map object groups
    'resources/assets/js/custom/mapobjectgroup.js',
    'resources/assets/js/custom/mapobjectgroups/brushlinemapobjectgroup.js',
    'resources/assets/js/custom/mapobjectgroups/dungeonfloorswitchmarkermapobjectgroup.js',
    'resources/assets/js/custom/mapobjectgroups/dungeonstartmarkermapobjectgroup.js',
    'resources/assets/js/custom/mapobjectgroups/enemymapobjectgroup.js',
    'resources/assets/js/custom/mapobjectgroups/enemypackmapobjectgroup.js',
    'resources/assets/js/custom/mapobjectgroups/enemypatrolmapobjectgroup.js',
    'resources/assets/js/custom/mapobjectgroups/killzonemapobjectgroup.js',
    'resources/assets/js/custom/mapobjectgroups/pathmapobjectgroup.js',
    'resources/assets/js/custom/mapobjectgroups/mapcommentmapobjectgroup.js',

    // Depends on the above
    'resources/assets/js/custom/mapobjectgroups/mapobjectgroupmanager.js',

    // Depends on map object groups
    'resources/assets/js/custom/statemanager.js',
    'resources/assets/js/custom/dungeonmap.js',
    'resources/assets/js/custom/mapobject.js',
    'resources/assets/js/custom/polyline.js',

    'resources/assets/js/custom/enemy.js',
    'resources/assets/js/custom/enemypatrol.js',
    'resources/assets/js/custom/enemypack.js',
    'resources/assets/js/custom/path.js',
    'resources/assets/js/custom/killzone.js',
    'resources/assets/js/custom/mapcomment.js',
    'resources/assets/js/custom/dungeonstartmarker.js',
    'resources/assets/js/custom/dungeonfloorswitchmarker.js',
    'resources/assets/js/custom/hotkeys.js',
    'resources/assets/js/custom/brushline.js',

    'resources/assets/js/custom/enemyselection/enemyselection.js',
    'resources/assets/js/custom/enemyselection/killzoneenemyselection.js',
    'resources/assets/js/custom/enemyselection/mdtenemyselection.js',

    'resources/assets/js/custom/enemyvisuals/enemyvisual.js',
    'resources/assets/js/custom/enemyvisuals/enemyvisualicon.js',
    'resources/assets/js/custom/enemyvisuals/enemyvisualmain.js',
    'resources/assets/js/custom/enemyvisuals/enemyvisualmainenemyclass.js',
    'resources/assets/js/custom/enemyvisuals/enemyvisualmainenemyforces.js',
    'resources/assets/js/custom/enemyvisuals/enemyvisualmainnpctype.js',

    'resources/assets/js/custom/enemyvisuals/modifiers/modifier.js',
    'resources/assets/js/custom/enemyvisuals/modifiers/modifierclassification.js',
    'resources/assets/js/custom/enemyvisuals/modifiers/modifierraidmarker.js',
    'resources/assets/js/custom/enemyvisuals/modifiers/modifiertruesight.js',

    'resources/assets/js/custom/mapcontrol.js',
    'resources/assets/js/custom/mapcontrols/addisplaycontrols.js',
    'resources/assets/js/custom/mapcontrols/echocontrols.js',
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
    'resources/assets/js/custom/admin/adminpanelcontrols.js',
    'resources/assets/js/custom/admin/admindungeonstartmarker.js',
    'resources/assets/js/custom/admin/admindungeonfloorswitchmarker.js',
    'resources/assets/js/custom/admin/adminmapcomment.js',

    // Inline code
    'resources/assets/js/custom/inline/inlinemanager.js',
    'resources/assets/js/custom/inline/inlinecode.js',

    // All inline code last
    'resources/assets/js/custom/inline/*/**/*.js',
];

// Output of files

// Custom processing only
mix.styles(['resources/assets/css/**/*.css'], 'public/css/custom-' + gitRevisionPlugin.version() + '.css');

// Dashboard JS
let dashboardScripts = [
    'resources/assets/js/dashboard/argon.js',
    'resources/assets/js/dashboard/charts.js',
    'resources/assets/js/dashboard/customcharts.js',
    // 'resources/assets/js/dashboard/routes/*.js',
    // 'resources/assets/js/dashboard/teams/*.js',
    // 'resources/assets/js/dashboard/users/*.js',
];

// Do not translate in development
if (mix.inProduction()) {
    mix.babel(scripts, 'public/js/custom-' + gitRevisionPlugin.version() + '.js');
} else {
    mix.scripts(scripts, 'public/js/custom-' + gitRevisionPlugin.version() + '.js');
}

mix.js('resources/assets/js/app.js', 'public/js/app-' + gitRevisionPlugin.version() + '.js')
    // Build a dashboard version for the admin
    .scripts(dashboardScripts, 'public/js/dashboard-' + gitRevisionPlugin.version() + '.js')
    .sass('resources/assets/sass/app.scss', 'public/css/app-' + gitRevisionPlugin.version() + '.css')
    // Lib processing
    .styles(['resources/assets/lib/**/*.css'], 'public/css/lib-' + gitRevisionPlugin.version() + '.css')
    .babel('resources/assets/lib/**/*.js', 'public/js/lib-' + gitRevisionPlugin.version() + '.js');

mix.sourceMaps();

if (images) {
    // if (mix.inProduction()) {
    // Copies all tiles as well which takes a while
    // mix.copy('resources/assets/images', 'public/images', false);
    // } else {
    // Allow import of pure JS
    // mix.copy('resources/assets/js/custom', 'public/js/custom', false);

    mix.copy('resources/assets/images/affixes', 'public/images/affixes', false);
    mix.copy('resources/assets/images/classes', 'public/images/classes', false);
    mix.copy('resources/assets/images/echo', 'public/images/echo', false);
    mix.copy('resources/assets/images/enemytypes', 'public/images/enemytypes', false);
    mix.copy('resources/assets/images/expansions', 'public/images/expansions', false);
    mix.copy('resources/assets/images/factions', 'public/images/factions', false);
    mix.copy('resources/assets/images/oauth', 'public/images/oauth', false);
    mix.copy('resources/assets/images/home', 'public/images/home', false);
    mix.copy('resources/assets/images/icon', 'public/images/icon', false);
    mix.copy('resources/assets/images/lib', 'public/images/lib', false);
    mix.copy('resources/assets/images/mapicon', 'public/images/mapicon', false);
    mix.copy('resources/assets/images/raidmarkers', 'public/images/raidmarkers', false);
    mix.copy('resources/assets/images/routeattributes', 'public/images/routeattributes', false);
    mix.copy('resources/assets/images/specializations', 'public/images/specializations', false);
    // }
}
