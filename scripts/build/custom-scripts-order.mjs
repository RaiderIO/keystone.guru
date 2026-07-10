/**
 * Ordered list of scripts that make up public/js/custom-{version}.js.
 *
 * Moved verbatim from webpack.mix.js — these files are global-scope scripts (no modules), so
 * concatenation order is load-bearing: base classes must precede subclasses, and everything the
 * InlineManager instantiates by name (eval(className)) must be included.
 *
 * Plain paths are files; entries ending in a glob are expanded (sorted alphabetically) at build
 * time by concat.mjs.
 */
export const customScripts = [
    // Pre-compiled handlebars
    'resources/assets/js/handlebars.js',

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
    'resources/assets/js/custom/mapobjectgroups/arrowmapobjectgroup.js',
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
    'resources/assets/js/custom/echo/message/listen/models/arrow/changed.js',
    'resources/assets/js/custom/echo/message/listen/models/arrow/deleted.js',
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
    'resources/assets/js/custom/echo/messagehandler/listen/models/arrow/changed.js',
    'resources/assets/js/custom/echo/messagehandler/listen/models/arrow/deleted.js',
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
    'resources/assets/js/custom/models/arrow.js',
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

    // Inline code
    'resources/assets/js/custom/inline/inlinemanager.js',
    'resources/assets/js/custom/inline/inlinecode.js',
    'resources/assets/js/custom/inline/inlinecodeajaxbatchprocessor.js',

    // All inline code last
    'resources/assets/js/custom/inline/*/**/*.js',
];

/** Scripts that make up public/js/lib-{version}.js (was mix.babel('resources/assets/lib/**')). */
export const libScriptsGlob = 'resources/assets/lib/**/*.js';
