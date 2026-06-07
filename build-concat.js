require('dotenv').config();

const fs = require('node:fs');
const path = require('path');
const { execSync } = require('node:child_process');

const isProd = process.argv.includes('--production');
const isWatch = process.argv.includes('--watch');

// ---------------------------------------------------------------------------
// Version
// ---------------------------------------------------------------------------
let version;
if (process.env.npm_config_output_version) {
    version = process.env.npm_config_output_version;
} else {
    version = execSync('git rev-list HEAD -1').toString().trim();
    fs.writeFile('version', version, (err) => {
        if (err) { console.error(err); }
    });
}

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/**
 * Recursively collect .js or .css files from a directory in alphabetical order.
 * @param {string} dir
 * @param {string} ext
 * @returns {string[]}
 */
function collectFiles(dir, ext) {
    const results = [];
    if (!fs.existsSync(dir)) {
        return results;
    }
    for (const entry of fs.readdirSync(dir, { withFileTypes: true }).sort((a, b) => a.name.localeCompare(b.name))) {
        const fullPath = path.join(dir, entry.name);
        if (entry.isDirectory()) {
            results.push(...collectFiles(fullPath, ext));
        } else if (entry.name.endsWith(ext)) {
            results.push(fullPath);
        }
    }
    return results;
}

/**
 * Recursively copy src directory into dest directory.
 * @param {string} src
 * @param {string} dest
 */
function copyDir(src, dest) {
    if (!fs.existsSync(src)) {
        console.warn(`  Skipping copy (not found): ${src}`);
        return;
    }
    fs.mkdirSync(dest, { recursive: true });
    for (const entry of fs.readdirSync(src, { withFileTypes: true })) {
        const srcPath = path.join(src, entry.name);
        const destPath = path.join(dest, entry.name);
        if (entry.isDirectory()) {
            copyDir(srcPath, destPath);
        } else {
            fs.copyFileSync(srcPath, destPath);
        }
    }
}

/**
 * Concatenate a list of files. Paths may contain glob-style wildcards for
 * the trailing segment; those are expanded using collectFiles().
 * @param {string[]} filePaths
 * @returns {string}
 */
function concatenate(filePaths) {
    const parts = [];
    for (const filePath of filePaths) {
        if (!fs.existsSync(filePath)) {
            console.warn(`  Warning: file not found, skipping: ${filePath}`);
            continue;
        }
        parts.push(fs.readFileSync(filePath, 'utf8'));
    }
    return parts.join('\n');
}

// ---------------------------------------------------------------------------
// File lists
// ---------------------------------------------------------------------------

/**
 * Inline JS files: all .js files inside subdirectories of resources/assets/js/custom/inline/.
 * inlinemanager.js and inlinecode.js are already listed explicitly in SCRIPTS below.
 */
function getInlineScripts() {
    const inlineRoot = 'resources/assets/js/custom/inline';
    const files = [];
    for (const entry of fs.readdirSync(inlineRoot, { withFileTypes: true }).sort((a, b) => a.name.localeCompare(b.name))) {
        if (entry.isDirectory()) {
            files.push(...collectFiles(path.join(inlineRoot, entry.name), '.js'));
        }
    }
    return files;
}

const SCRIPTS = [
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
    'resources/assets/js/custom/mapcontext/mapcontextdungeonroutesearch.js',

    // Depends on map object groups + Map Context
    'resources/assets/js/custom/statemanager.js',

    // Echo objects
    'resources/assets/js/custom/echo/echouser.js',
    'resources/assets/js/custom/echo/echohandler.js',

    // Echo messages
    'resources/assets/js/custom/echo/message/message.js',
    'resources/assets/js/custom/echo/message/modelmessage.js',
    'resources/assets/js/custom/echo/message/listen/livesession/invite.js',
    'resources/assets/js/custom/echo/message/listen/livesession/stop.js',
    'resources/assets/js/custom/echo/message/listen/models/brushline/changed.js',
    'resources/assets/js/custom/echo/message/listen/models/brushline/deleted.js',
    'resources/assets/js/custom/echo/message/listen/models/killzone/changed.js',
    'resources/assets/js/custom/echo/message/listen/models/killzone/deleted.js',
    'resources/assets/js/custom/echo/message/listen/models/mapicon/changed.js',
    'resources/assets/js/custom/echo/message/listen/models/mapicon/deleted.js',
    'resources/assets/js/custom/echo/message/listen/models/npc/changed.js',
    'resources/assets/js/custom/echo/message/listen/models/npc/deleted.js',
    'resources/assets/js/custom/echo/message/listen/models/overpulledenemy/changed.js',
    'resources/assets/js/custom/echo/message/listen/models/overpulledenemy/deleted.js',
    'resources/assets/js/custom/echo/message/listen/models/path/changed.js',
    'resources/assets/js/custom/echo/message/listen/models/path/deleted.js',
    'resources/assets/js/custom/echo/message/listen/usercolorchanged.js',
    'resources/assets/js/custom/echo/message/whisper/viewport.js',
    'resources/assets/js/custom/echo/message/whisper/mouseposition.js',
    'resources/assets/js/custom/echo/message/messagefactory.js',

    // Echo message handlers
    'resources/assets/js/custom/echo/messagehandler/messagehandler.js',
    'resources/assets/js/custom/echo/messagehandler/listen/livesession/invite.js',
    'resources/assets/js/custom/echo/messagehandler/listen/livesession/stop.js',
    'resources/assets/js/custom/echo/messagehandler/listen/models/basemodelhandler.js',
    'resources/assets/js/custom/echo/messagehandler/listen/models/modelchangedhandler.js',
    'resources/assets/js/custom/echo/messagehandler/listen/models/modeldeletedhandler.js',
    'resources/assets/js/custom/echo/messagehandler/listen/models/brushline/changed.js',
    'resources/assets/js/custom/echo/messagehandler/listen/models/brushline/deleted.js',
    'resources/assets/js/custom/echo/messagehandler/listen/models/killzone/changed.js',
    'resources/assets/js/custom/echo/messagehandler/listen/models/killzone/deleted.js',
    'resources/assets/js/custom/echo/messagehandler/listen/models/mapicon/changed.js',
    'resources/assets/js/custom/echo/messagehandler/listen/models/mapicon/deleted.js',
    'resources/assets/js/custom/echo/messagehandler/listen/models/npc/changed.js',
    'resources/assets/js/custom/echo/messagehandler/listen/models/npc/deleted.js',
    'resources/assets/js/custom/echo/messagehandler/listen/models/overpulledenemy/changed.js',
    'resources/assets/js/custom/echo/messagehandler/listen/models/overpulledenemy/deleted.js',
    'resources/assets/js/custom/echo/messagehandler/listen/models/path/changed.js',
    'resources/assets/js/custom/echo/messagehandler/listen/models/path/deleted.js',
    'resources/assets/js/custom/echo/messagehandler/listen/usercolorchanged.js',
    'resources/assets/js/custom/echo/messagehandler/whisper/whispermessagehandler.js',
    'resources/assets/js/custom/echo/messagehandler/whisper/viewport.js',
    'resources/assets/js/custom/echo/messagehandler/whisper/mouseposition.js',

    // Depends on EchoHandler
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
    'resources/assets/js/custom/mapstate/enemyselection/dungeonroutesearchenemyselection.js',
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
    'resources/assets/js/custom/enemyvisuals/enemyvisualmainenemygroup.js',
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

    // Inline code base classes (must come before all inline implementations)
    'resources/assets/js/custom/inline/inlinemanager.js',
    'resources/assets/js/custom/inline/inlinecode.js',
];

// ---------------------------------------------------------------------------
// Build
// ---------------------------------------------------------------------------

function buildAll() {
    console.log(sprintf('Building concat bundles (v=%s, prod=%s)...', version, isProd));

    // 1. Compile Handlebars templates
    console.log('  Compiling Handlebars templates...');
    const hbsCmd = sprintf(
        'handlebars%s resources/assets/js/handlebars/ -f resources/assets/js/handlebars.js',
        isProd ? ' -m' : ''
    );
    execSync(hbsCmd, { stdio: 'inherit' });

    fs.mkdirSync('public/js', { recursive: true });
    fs.mkdirSync('public/css', { recursive: true });

    // 2. custom-{v}.js
    const allScripts = [...SCRIPTS, ...getInlineScripts()];
    let customJs = concatenate(allScripts);

    if (isProd) {
        console.log('  Transpiling custom.js with Babel...');
        const babel = require('@babel/core');
        const result = babel.transformSync(customJs, {
            presets: [['@babel/preset-env', { targets: '> 0.5%, last 2 versions, not dead' }]],
            compact: false,
        });
        customJs = result.code;
    }

    const customJsPath = sprintf('public/js/custom-%s.js', version);
    fs.writeFileSync(customJsPath, customJs);
    console.log(sprintf('  Written: %s', customJsPath));

    // 3. lib-{v}.js
    const libFiles = collectFiles('resources/assets/lib', '.js');
    let libJs = concatenate(libFiles);

    if (isProd) {
        const babel = require('@babel/core');
        const result = babel.transformSync(libJs, {
            presets: [['@babel/preset-env', { targets: '> 0.5%, last 2 versions, not dead' }]],
            compact: false,
        });
        libJs = result.code;
    }

    const libJsPath = sprintf('public/js/lib-%s.js', version);
    fs.writeFileSync(libJsPath, libJs);
    console.log(sprintf('  Written: %s', libJsPath));

    // 4. custom-{v}.css (plain CSS concatenation)
    const cssFiles = collectFiles('resources/assets/css', '.css');
    const customCss = concatenate(cssFiles);
    const customCssPath = sprintf('public/css/custom-%s.css', version);
    fs.writeFileSync(customCssPath, customCss);
    console.log(sprintf('  Written: %s', customCssPath));

    // 5. Copy static assets
    console.log('  Copying static assets...');
    copyDir('node_modules/@fortawesome/fontawesome-free/webfonts', 'public/webfonts');
    copyDir('resources/assets/webfonts', 'public/webfonts');
    copyDir('resources/assets/vendor', 'public/vendor');

    console.log('Done.');
}

// ---------------------------------------------------------------------------
// sprintf helper (avoids dependency on sprintf-js)
// ---------------------------------------------------------------------------
function sprintf(fmt, ...args) {
    let i = 0;
    return fmt.replace(/%s/g, () => args[i++]);
}

// ---------------------------------------------------------------------------
// Entry point
// ---------------------------------------------------------------------------
if (isWatch) {
    const chokidar = require('chokidar');

    let building = false;

    function rebuild(filePath) {
        if (building) { return; }
        building = true;
        console.log(sprintf('Changed: %s — rebuilding...', filePath));
        try {
            buildAll();
        } catch (err) {
            console.error('Build failed:', err.message);
        } finally {
            building = false;
        }
    }

    buildAll();

    chokidar.watch([
        'resources/assets/js/handlebars',
        'resources/assets/js/custom',
        'resources/assets/lib',
        'resources/assets/css',
    ], {
        ignored: /node_modules/,
        ignoreInitial: true,
        usePolling: true,
        interval: 2000,
    }).on('all', (event, filePath) => rebuild(filePath));

    console.log('Watching resources/assets for changes...');
} else {
    buildAll();
}
