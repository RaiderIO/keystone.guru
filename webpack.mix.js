const mix = require('laravel-mix');
const {GitRevisionPlugin} = require('git-revision-webpack-plugin');
const WebpackShellPluginNext = require('webpack-shell-plugin-next');

// npm run dev --env.version <version>
let version;
let gitRevisionPluginList = [];
if (typeof process.env.npm_config_output_version !== 'undefined' &&
    process.env.hasOwnProperty('npm_config_output_version') &&
    typeof process.env.npm_config_output_version !== 'undefined') {
    version = process.env.npm_config_output_version;
} else {
    let gitRevisionPlugin = new GitRevisionPlugin({
        versionCommand: 'rev-list HEAD -1'
    });
    gitRevisionPluginList.push(gitRevisionPlugin);
    version = gitRevisionPlugin.version();

    // Write the version that's going to be used to file
    const fs = require('node:fs');
    fs.writeFile('version', version, err => {
        if (err) {
            console.error(err);
        } else {
            // file written successfully
        }
    });
}

mix.options({
    // This dramatically speeds up the build process -  adding new .scss for the redesign greatly increased build times without this
    processCssUrls: false
}).webpackConfig({
    output: {
        publicPath: '/',
    },
    watchOptions: {
        ignored: ['node_modules', 'vendor', 'storage'],
        poll: 2000 // Check for changes every two seconds
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
            test: /lang.+\.(php|json)$/,
            loader: 'laravel-localization-loader',
        }]
    },
    plugins: [
        // Compile handlebars
        new WebpackShellPluginNext({
            onBuildStart: {
                scripts: [
                    // Compile handlebars
                    'handlebars ' + (mix.inProduction() ? '-m ' : '') +
                    'resources/assets/js/handlebars/ -f resources/assets/js/handlebars.js'
                ],
                blocking: true,
                parallel: false
            },
            onBuildEnd: []
        })
        // Use git version to output our files
    ].concat(gitRevisionPluginList)
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

// npm run dev --env.full true
// false if not defined, true if defined
let full = false;
if (typeof process.env.npm_config_full !== 'undefined' &&
    process.env.hasOwnProperty('npm_config_full') &&
    typeof process.env.npm_config_full !== 'undefined') {
    full = process.env.npm_config_full;
}
// npm run dev --env.images false
let images = false;
if (typeof process.env.npm_config_images !== 'undefined' &&
    process.env.hasOwnProperty('npm_config_images') &&
    typeof process.env.npm_config_images !== 'undefined') {
    images = process.env.npm_config_images;
}

mix.copy('node_modules/@fortawesome/fontawesome-free/webfonts', 'public/webfonts');
mix.copy('resources/assets/webfonts', 'public/webfonts');
mix.copy('resources/assets/vendor/', 'public/vendor');

let precompile = [
    // Translations
    'resources/assets/js/messages.js'
];

mix.js(precompile, 'resources/assets/js/precompile.js');

let scripts = [
    // Include the precompiled scripts
    'public/resources/assets/js/precompile.js',

    // Pre-compiled handlebars
    'resources/assets/js/handlebars.js',

    // Home page only
    'resources/assets/js/custom/home.js',
    // Doesn't depend on anything
    'resources/assets/js/custom/colorutil.js',
    'resources/assets/js/custom/util.js',
    'resources/assets/js/custom/constants.js',

    // Include in proper order
    'resources/assets/js/custom/signalable.js',

    // Map plugins
    'resources/assets/js/custom/mapplugins/mapplugin.js',
    'resources/assets/js/custom/mapplugins/patherplugin.js',
    'resources/assets/js/custom/mapplugins/heatplugin.js',

    // Map object groups
    'resources/assets/js/custom/mapobjectgroups/mapobjectgroup.js',
    'resources/assets/js/custom/mapobjectgroups/polygonmapobjectgroup.js',
    'resources/assets/js/custom/mapobjectgroups/polylinemapobjectgroup.js',
    'resources/assets/js/custom/mapobjectgroups/brushlinemapobjectgroup.js',
    'resources/assets/js/custom/mapobjectgroups/dungeonfloorswitchmarkermapobjectgroup.js',
    'resources/assets/js/custom/mapobjectgroups/enemymapobjectgroup.js',
    'resources/assets/js/custom/mapobjectgroups/enemypackmapobjectgroup.js',
    'resources/assets/js/custom/mapobjectgroups/enemypatrolmapobjectgroup.js',
    'resources/assets/js/custom/mapobjectgroups/killzonemapobjectgroup.js',
    'resources/assets/js/custom/mapobjectgroups/killzonepathmapobjectgroup.js',
    'resources/assets/js/custom/mapobjectgroups/pathmapobjectgroup.js',
    'resources/assets/js/custom/mapobjectgroups/mapiconmapobjectgroup.js',
    'resources/assets/js/custom/mapobjectgroups/usermousepositionmapobjectgroup.js',
    'resources/assets/js/custom/mapobjectgroups/mountableareamapobjectgroup.js',
    'resources/assets/js/custom/mapobjectgroups/floorunionmapobjectgroup.js',
    'resources/assets/js/custom/mapobjectgroups/floorunionareamapobjectgroup.js',

    // Depends on the above
    'resources/assets/js/custom/mapobjectgroups/mapobjectgroupmanager.js',
    'resources/assets/js/custom/enemyvisuals/enemyvisualmanager.js',
    'resources/assets/js/custom/enemyforces/enemyforcesmanager.js',

    // Map Context
    'resources/assets/js/custom/mapcontext/mapcontext.js',
    'resources/assets/js/custom/mapcontext/mapcontextdungeonroute.js',
    'resources/assets/js/custom/mapcontext/mapcontextlivesession.js',
    'resources/assets/js/custom/mapcontext/mapcontextmappingversion.js',
    'resources/assets/js/custom/mapcontext/mapcontextmappingversionedit.js',
    'resources/assets/js/custom/mapcontext/mapcontextdungeonexplore.js',

    // Depends on map object groups + Map Context
    'resources/assets/js/custom/statemanager.js',

    // Echo objects
    'resources/assets/js/custom/echo/echouser.js',
    'resources/assets/js/custom/echo/echo.js',

    // Echo messages
    'resources/assets/js/custom/echo/message/message.js',
    'resources/assets/js/custom/echo/message/listen/killzone/changed.js',
    'resources/assets/js/custom/echo/message/listen/killzone/deleted.js',
    'resources/assets/js/custom/echo/message/listen/livesession/invite.js',
    'resources/assets/js/custom/echo/message/listen/livesession/stop.js',
    'resources/assets/js/custom/echo/message/listen/npc/changed.js',
    'resources/assets/js/custom/echo/message/listen/npc/deleted.js',
    'resources/assets/js/custom/echo/message/listen/overpulledenemy/changed.js',
    'resources/assets/js/custom/echo/message/listen/overpulledenemy/deleted.js',
    'resources/assets/js/custom/echo/message/whisper/viewport.js',
    'resources/assets/js/custom/echo/message/whisper/mouseposition.js',
    'resources/assets/js/custom/echo/message/messagefactory.js',

    // Echo message handlers
    'resources/assets/js/custom/echo/messagehandler/messagehandler.js',
    'resources/assets/js/custom/echo/messagehandler/listen/colorchanged.js',
    'resources/assets/js/custom/echo/messagehandler/listen/killzone/changed.js',
    'resources/assets/js/custom/echo/messagehandler/listen/killzone/deleted.js',
    'resources/assets/js/custom/echo/messagehandler/listen/livesession/invite.js',
    'resources/assets/js/custom/echo/messagehandler/listen/livesession/stop.js',
    'resources/assets/js/custom/echo/messagehandler/listen/overpulledenemy/changed.js',
    'resources/assets/js/custom/echo/messagehandler/listen/overpulledenemy/deleted.js',
    'resources/assets/js/custom/echo/messagehandler/listen/npc/changed.js',
    'resources/assets/js/custom/echo/messagehandler/listen/npc/deleted.js',
    'resources/assets/js/custom/echo/messagehandler/whisper/whispermessagehandler.js',
    'resources/assets/js/custom/echo/messagehandler/whisper/viewport.js',
    'resources/assets/js/custom/echo/messagehandler/whisper/mouseposition.js',

    // Depends on Echo
    'resources/assets/js/custom/dungeonmap.js',
    'resources/assets/js/custom/hotkeys.js',

    // Models
    'resources/assets/js/custom/models/static/mapicontype.js',
    'resources/assets/js/custom/models/static/spell.js',
    'resources/assets/js/custom/models/attribute.js',
    'resources/assets/js/custom/models/mapobject.js',
    'resources/assets/js/custom/models/versionablemapobject.js',
    'resources/assets/js/custom/models/polyline.js',
    'resources/assets/js/custom/models/enemy.js',
    'resources/assets/js/custom/models/pridefulenemy.js',
    'resources/assets/js/custom/models/enemypatrol.js',
    'resources/assets/js/custom/models/enemypack.js',
    'resources/assets/js/custom/models/path.js',
    'resources/assets/js/custom/models/killzone.js',
    'resources/assets/js/custom/models/killzonepath.js',
    'resources/assets/js/custom/models/icon.js',
    'resources/assets/js/custom/models/mapicon.js',
    'resources/assets/js/custom/models/mapiconawakenedobelisk.js',
    'resources/assets/js/custom/models/dungeonfloorswitchmarker.js',
    'resources/assets/js/custom/models/brushline.js',
    'resources/assets/js/custom/models/usermouseposition.js',
    'resources/assets/js/custom/models/mountablearea.js',

    'resources/assets/js/custom/input/usermousepositionplayer.js',

    'resources/assets/js/custom/mapstate/mapstate.js',
    'resources/assets/js/custom/mapstate/editmapstate.js',
    'resources/assets/js/custom/mapstate/deletemapstate.js',
    'resources/assets/js/custom/mapstate/pathermapstate.js',
    'resources/assets/js/custom/mapstate/mapobjectmapstate.js',
    'resources/assets/js/custom/mapstate/addkillzonemapstate.js',
    'resources/assets/js/custom/mapstate/addawakenedobeliskgatewaymapstate.js',
    'resources/assets/js/custom/mapstate/raidmarkerselectmapstate.js',
    'resources/assets/js/custom/mapstate/enemyselection/enemyselection.js',
    'resources/assets/js/custom/mapstate/enemyselection/editkillzoneenemyselection.js',
    'resources/assets/js/custom/mapstate/enemyselection/enemypatrolenemyselection.js',
    'resources/assets/js/custom/mapstate/enemyselection/mdtenemyselection.js',
    'resources/assets/js/custom/mapstate/enemyselection/selectkillzoneenemyselectionoverpull.js',
    'resources/assets/js/custom/mapstate/enemyselection/viewkillzoneenemyselection.js',

    'resources/assets/js/custom/enemyvisuals/enemyvisual.js',
    'resources/assets/js/custom/enemyvisuals/enemyvisualicon.js',
    'resources/assets/js/custom/enemyvisuals/enemyvisualmain.js',
    'resources/assets/js/custom/enemyvisuals/enemyvisualmainenemyclass.js',
    'resources/assets/js/custom/enemyvisuals/enemyvisualmainenemyforces.js',
    'resources/assets/js/custom/enemyvisuals/enemyvisualmainenemyportrait.js',
    'resources/assets/js/custom/enemyvisuals/enemyvisualmainenemyskippable.js',
    'resources/assets/js/custom/enemyvisuals/enemyvisualmainmdt.js',
    'resources/assets/js/custom/enemyvisuals/enemyvisualmainnpctype.js',

    'resources/assets/js/custom/enemyvisuals/modifiers/modifier.js',
    'resources/assets/js/custom/enemyvisuals/modifiers/modifieractiveaura.js',
    'resources/assets/js/custom/enemyvisuals/modifiers/modifierclassification.js',
    'resources/assets/js/custom/enemyvisuals/modifiers/modifierraidmarker.js',
    'resources/assets/js/custom/enemyvisuals/modifiers/modifierteeming.js',
    'resources/assets/js/custom/enemyvisuals/modifiers/modifiertruesight.js',

    'resources/assets/js/custom/mapcontrols/mapcontrol.js',
    'resources/assets/js/custom/mapcontrols/addisplaycontrols.js',
    'resources/assets/js/custom/mapcontrols/drawcontrols.js',
    'resources/assets/js/custom/mapcontrols/dungeonspeedrunrequirednpcscontrols.js',
    'resources/assets/js/custom/mapcontrols/echocontrols.js',
    'resources/assets/js/custom/mapcontrols/enemyforcescontrols.js',
    'resources/assets/js/custom/mapcontrols/enemyvisualcontrols.js',
    'resources/assets/js/custom/mapcontrols/factiondisplaycontrols.js',

    'resources/assets/js/custom/admin/enemyattaching.js',
    'resources/assets/js/custom/admin/admindungeonmap.js',
    'resources/assets/js/custom/admin/adminenemy.js',
    'resources/assets/js/custom/admin/adminenemypack.js',
    'resources/assets/js/custom/admin/adminenemypatrol.js',
    'resources/assets/js/custom/admin/adminfloorunion.js',
    'resources/assets/js/custom/admin/adminfloorunionarea.js',
    'resources/assets/js/custom/admin/admindrawcontrols.js',
    'resources/assets/js/custom/admin/adminpanelcontrols.js',
    'resources/assets/js/custom/admin/admindungeonfloorswitchmarker.js',
    'resources/assets/js/custom/admin/adminmapicon.js',
    'resources/assets/js/custom/admin/adminmountablearea.js',

    // Inline code
    'resources/assets/js/custom/inline/inlinemanager.js',
    'resources/assets/js/custom/inline/inlinecode.js',

    // All inline code last
    'resources/assets/js/custom/inline/*/**/*.js',
];

// Output of files

// Custom processing only
mix.styles(['resources/assets/css/**/*.css'], `public/css/custom-${version}.css`);

// Do not translate in development
if (mix.inProduction()) {
    mix.babel(scripts, `public/js/custom-${version}.js`);
} else {
    mix.scripts(scripts, `public/js/custom-${version}.js`);
}

mix.js('resources/assets/js/app.js', `public/js/app-${version}.js`)
    .sass('resources/assets/sass/app.scss', `public/css/app-${version}.css`)
    .sass('resources/assets/sass/theme/theme.scss', `public/css/theme-${version}.css`)
    .sass('resources/assets/sass/home.scss', `public/css/home-${version}.css`)
    .sass('resources/assets/sass/custom/custom.scss', `public/css/custom-compiled-${version}.css`)
    // Lib processing
    // .styles(['resources/assets/lib/**/*.css'], `public/css/lib-${version}.css`)
    .babel('resources/assets/lib/**/*.js', `public/js/lib-${version}.js`);

mix.sourceMaps();

if (images) {
    // if (mix.inProduction()) {
    // Copies all tiles as well which takes a while
    // mix.copy('resources/assets/images', 'public/images');
    // } else {
    // Allow import of pure JS
    // mix.copy('resources/assets/js/custom', 'public/js/custom');

    mix.copy('resources/assets/images/affixes', 'public/images/affixes');
    mix.copy('resources/assets/images/classes', 'public/images/classes');
    mix.copy('resources/assets/images/dungeons', 'public/images/dungeons');
    mix.copy('resources/assets/images/echo', 'public/images/echo');
    mix.copy('resources/assets/images/enemyclasses', 'public/images/enemyclasses');
    mix.copy('resources/assets/images/enemyclassifications', 'public/images/enemyclassifications');
    mix.copy('resources/assets/images/enemymodifiers', 'public/images/enemymodifiers');
    mix.copy('resources/assets/images/enemyportraits', 'public/images/enemyportraits');
    mix.copy('resources/assets/images/enemytypes', 'public/images/enemytypes');
    mix.copy('resources/assets/images/expansions', 'public/images/expansions');
    mix.copy('resources/assets/images/external', 'public/images/external');
    mix.copy('resources/assets/images/factions', 'public/images/factions');
    mix.copy('resources/assets/images/flags', 'public/images/flags');
    mix.copy('resources/assets/images/gameversions', 'public/images/gameversions');
    mix.copy('resources/assets/images/home', 'public/images/home');
    mix.copy('resources/assets/images/icon', 'public/images/icon');
    mix.copy('resources/assets/images/lib', 'public/images/lib');
    mix.copy('resources/assets/images/logo', 'public/images/logo');
    mix.copy('resources/assets/images/mapicon', 'public/images/mapicon');
    mix.copy('resources/assets/images/oauth', 'public/images/oauth');
    mix.copy('resources/assets/images/raidmarkers', 'public/images/raidmarkers');
    mix.copy('resources/assets/images/routeattributes', 'public/images/routeattributes');
    mix.copy('resources/assets/images/specializations', 'public/images/specializations');
    mix.copy('resources/assets/images/spells', 'public/images/spells');
    // }
}
