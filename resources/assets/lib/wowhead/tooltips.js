// https://www.wowhead.com/tooltips
const whTooltips = {colorLinks: true, iconizeLinks: true, renameLinks: true};
window.WH = new function () {
    this.REMOTE = !("." + location.hostname).endsWith(".wowhead.com") && location.hostname !== "wh-site" || location.pathname === "/widgets/power/demo.html";
    this.STATIC_URL = "https://wow.zamimg.com";
    this.staticUrl = this.STATIC_URL;
    const e = {Exocet: "https://use.typekit.net/qwt0uqi.css"};
    this.PageMeta = {};
    const t = {requestedFonts: {}};
    this.defineEnum = function (e, t) {
        let a = {};
        let i = [];
        Object.keys(e).forEach((n => {
            a[n] = t ? new t(a) : {};
            a[n].name = n;
            a[n].value = e[n];
            Object.freeze(a[n]);
            i.push(a[n])
        }));
        a.cases = () => i.slice();
        a.tryFrom = e => i.find((t => t.value === e));
        a.from = e => {
            let t = a.tryFrom(e);
            if (!t) {
                throw new Error(`Value ${e} is not a valid backing value for this enum.`)
            }
            return t
        };
        a.values = () => i.map((e => e.value));
        return Object.freeze(a)
    };
    this.extendStatic = function (e, t) {
        return new (WH.setPrototype(e, t))
    };
    this.findKey = function (e, t, a) {
        let i = Object.keys(e).find((a => e[a] === t));
        if (a && i != null) {
            i = parseInt(i)
        }
        return i
    };
    this.loadFont = function (a) {
        if (t.requestedFonts[a]) {
            return
        }
        let i = e[a];
        if (!i) {
            WH.error("Could not find a URL for the specified font.", a);
            return
        }
        if (document.head.querySelector('link[rel="stylesheet"][href="' + i + '"]')) {
            t.requestedFonts[a] = true;
            return
        }
        t.requestedFonts[a] = true;
        WH.ae(document.head, WH.ce("link", {rel: "stylesheet", type: "text/css", href: i}))
    };
    this.onLoad = function (e) {
        if (document.readyState === "loading") {
            document.addEventListener("DOMContentLoaded", e)
        } else {
            requestAnimationFrame(e)
        }
    };
    this.setPrototype = function (e, t) {
        t.prototype = e;
        return t
    }
};
WH.dataEnv = {MAIN: 1, PTR: 2, BETA: 3, CLASSIC: 4, TBC: 5, D2: 6, DI: 7, WRATH: 8};
WH.dataEnvKey = {1: "live", 2: "ptr", 3: "beta", 4: "classic", 5: "tbc", 6: "d2", 7: "di", 8: "wrath"};
WH.dataEnvTerm = {
    1: "live",
    2: "ptr",
    3: "beta",
    4: "classic",
    5: "burningCrusade",
    6: "diablo2",
    7: "diabloImmortal",
    8: "wrathofthelichking"
};
WH.dataTree = {RETAIL: 1, CLASSIC: 4, TBC: 5, D2: 6, DI: 7, WRATH: 8};
WH.dataTreeShortTerm = {
    [WH.dataTree.RETAIL]: "retail",
    [WH.dataTree.CLASSIC]: "classic",
    [WH.dataTree.TBC]: "theburningcrusade_short",
    [WH.dataTree.D2]: "diablo2",
    [WH.dataTree.DI]: "diabloImmortal_short",
    [WH.dataTree.WRATH]: "wrathofthelichking_short"
};
WH.dataTreeTerm = {
    1: "retail",
    4: "classic",
    5: "burningCrusade",
    6: "diablo2",
    7: "diabloImmortal",
    8: "wrathofthelichking"
};
WH.dataEnvToTree = {};
WH.dataEnvToTree[WH.dataEnv.MAIN] = WH.dataTree.RETAIL;
WH.dataEnvToTree[WH.dataEnv.PTR] = WH.dataTree.RETAIL;
WH.dataEnvToTree[WH.dataEnv.BETA] = WH.dataTree.RETAIL;
WH.dataEnvToTree[WH.dataEnv.CLASSIC] = WH.dataTree.CLASSIC;
WH.dataEnvToTree[WH.dataEnv.TBC] = WH.dataTree.TBC;
WH.dataEnvToTree[WH.dataEnv.D2] = WH.dataTree.D2;
WH.dataEnvToTree[WH.dataEnv.DI] = WH.dataTree.DI;
WH.dataEnvToTree[WH.dataEnv.WRATH] = WH.dataTree.WRATH;
WH.dataTreeToRoot = {};
WH.dataTreeToRoot[WH.dataTree.RETAIL] = WH.dataEnv.MAIN;
WH.dataTreeToRoot[WH.dataTree.CLASSIC] = WH.dataEnv.CLASSIC;
WH.dataTreeToRoot[WH.dataTree.TBC] = WH.dataEnv.TBC;
WH.dataTreeToRoot[WH.dataTree.D2] = WH.dataEnv.D2;
WH.dataTreeToRoot[WH.dataTree.DI] = WH.dataEnv.DI;
WH.dataTreeToRoot[WH.dataTree.WRATH] = WH.dataEnv.WRATH;
WH.EFFECT_SCALING_CLASS_1 = -1;
WH.EFFECT_SCALING_CLASS_2 = -2;
WH.EFFECT_SCALING_CLASS_3 = -3;
WH.EFFECT_SCALING_CLASS_4 = -4;
WH.EFFECT_SCALING_CLASS_5 = -5;
WH.EFFECT_SCALING_CLASS_6 = -6;
WH.EFFECT_SCALING_CLASS_7 = -7;
WH.EFFECT_SCALING_CLASS_8 = -8;
WH.EFFECT_SCALING_CLASS_9 = -9;
WH.Timewalking = new function () {
    const e = this;
    this.MODE_TBC = 1;
    this.MODE_WOTLK = 2;
    this.MODE_CATA = 3;
    this.MODE_MISTS = 4;
    this.MODE_WOD = 5;
    const t = [{
        id: e.MODE_TBC,
        charLevel: 30,
        gearIlvl: 35,
        stringId: "twtbc",
        termAbbrev: "theburningcrusade_short"
    }, {
        id: e.MODE_WOTLK,
        charLevel: 30,
        gearIlvl: 35,
        stringId: "twwotlk",
        termAbbrev: "wrathofthelichking_abbrev"
    }, {
        id: e.MODE_CATA,
        charLevel: 35,
        gearIlvl: 40,
        stringId: "twcata",
        termAbbrev: "cataclysm_abbrev"
    }, {
        id: e.MODE_MISTS,
        charLevel: 35,
        gearIlvl: 40,
        stringId: "twmists",
        termAbbrev: "mistsofpandaria_abbrev"
    }, {id: e.MODE_WOD, charLevel: 40, gearIlvl: 45, stringId: "twwod", termAbbrev: "warlordsofdraenor_abbrev"}];
    this.getConfigs = function () {
        return t
    };
    this.getCharLevelFromIlvl = function (t) {
        for (let a of e.getConfigs()) {
            if (a.gearIlvl === t) {
                return a.charLevel
            }
        }
        return null
    };
    this.getGearIlvlByStringId = function (t) {
        for (let a of e.getConfigs()) {
            if (a.stringId === t) {
                return a.gearIlvl
            }
        }
        return null
    }
};
WH.Types = new function () {
    const e = this;
    this.NPC = 1;
    this.OBJECT = 2;
    this.ITEM = 3;
    this.ITEM_SET = 4;
    this.QUEST = 5;
    this.SPELL = 6;
    this.ZONE = 7;
    this.FACTION = 8;
    this.HUNTER_PET = 9;
    this.ACHIEVEMENT = 10;
    this.TITLE = 11;
    this.EVENT = 12;
    this.PLAYER_CLASS = 13;
    this.RACE = 14;
    this.SKILL = 15;
    this.CURRENCY = 17;
    this.PROJECT = 18;
    this.SOUND = 19;
    this.BUILDING = 20;
    this.FOLLOWER = 21;
    this.MISSION_ABILITY = 22;
    this.MISSION = 23;
    this.SHIP = 25;
    this.THREAT = 26;
    this.RESOURCE = 27;
    this.CHAMPION = 28;
    this.ICON = 29;
    this.ORDER_ADVANCEMENT = 30;
    this.FOLLOWER_ALLIANCE = 31;
    this.FOLLOWER_HORDE = 32;
    this.SHIP_ALLIANCE = 33;
    this.SHIP_HORDE = 34;
    this.CHAMPION_ALLIANCE = 35;
    this.CHAMPION_HORDE = 36;
    this.TRANSMOG_ITEM = 37;
    this.BFA_CHAMPION = 38;
    this.BFA_CHAMPION_ALLIANCE = 39;
    this.AFFIX = 40;
    this.BFA_CHAMPION_HORDE = 41;
    this.AZERITE_ESSENCE_POWER = 42;
    this.AZERITE_ESSENCE = 43;
    this.STORYLINE = 44;
    this.ADVENTURE_COMBATANT_ABILITY = 46;
    this.ENCOUNTER = 47;
    this.COVENANT = 48;
    this.SOULBIND = 49;
    this.DI_EQUIP_ITEM = 50;
    this.DI_SKILL = 54;
    this.DI_PARAGON_SKILL = 55;
    this.DI_SET = 56;
    this.DI_NPC = 57;
    this.DI_MISC_ITEM = 58;
    this.DI_ZONE = 59;
    this.DI_QUEST = 60;
    this.DI_OBJECT = 61;
    this.GATHERER_SCREENSHOT = 91;
    this.GATHERER_GUIDE_IMAGE = 98;
    this.GUIDE = 100;
    this.TRANSMOG_SET = 101;
    this.OUTFIT = 110;
    this.GEAR_SET = 111;
    this.GATHERER_LISTVIEW = 158;
    this.GATHERER_SURVEY_COVENANTS = 161;
    this.NEWS_POST = 162;
    this.BATTLE_PET_ABILITY = 200;
    const t = {
        [e.BFA_CHAMPION_ALLIANCE]: "bfa-champion",
        [e.BFA_CHAMPION_HORDE]: "bfa-champion",
        [e.CHAMPION_ALLIANCE]: "champion",
        [e.CHAMPION_HORDE]: "champion",
        [e.DI_EQUIP_ITEM]: "equip-item",
        [e.DI_MISC_ITEM]: "misc-item",
        [e.DI_NPC]: "npc",
        [e.DI_OBJECT]: "object",
        [e.DI_PARAGON_SKILL]: "paragon-skill",
        [e.DI_QUEST]: "quest",
        [e.DI_SET]: "set",
        [e.DI_SKILL]: "skill",
        [e.DI_ZONE]: "zone",
        [e.FOLLOWER_ALLIANCE]: "follower",
        [e.FOLLOWER_HORDE]: "follower",
        [e.SHIP_ALLIANCE]: "ship",
        [e.SHIP_HORDE]: "ship"
    };
    const a = [this.NPC, this.OBJECT, this.ITEM, this.ITEM_SET, this.QUEST, this.SPELL, this.ZONE, this.FACTION, this.HUNTER_PET, this.ACHIEVEMENT, this.TITLE, this.EVENT, this.PLAYER_CLASS, this.RACE, this.SKILL, this.CURRENCY, this.SOUND, this.BUILDING, this.FOLLOWER, this.MISSION_ABILITY, this.MISSION, this.SHIP, this.THREAT, this.RESOURCE, this.CHAMPION, this.ICON, this.ORDER_ADVANCEMENT, this.BFA_CHAMPION, this.AFFIX, this.AZERITE_ESSENCE_POWER, this.AZERITE_ESSENCE, this.STORYLINE, this.ADVENTURE_COMBATANT_ABILITY, this.BATTLE_PET_ABILITY];
    const i = {
        [e.ACHIEVEMENT]: "achievement",
        [e.ADVENTURE_COMBATANT_ABILITY]: "adventure-combatant-ability",
        [e.AFFIX]: "affix",
        [e.AZERITE_ESSENCE]: "azerite-essence",
        [e.AZERITE_ESSENCE_POWER]: "azerite-essence-power",
        [e.BATTLE_PET_ABILITY]: "pet-ability",
        [e.BFA_CHAMPION]: "bfa-champion",
        [e.BFA_CHAMPION_ALLIANCE]: "bfa-champion_a",
        [e.BFA_CHAMPION_HORDE]: "bfa-champion_h",
        [e.BUILDING]: "building",
        [e.CHAMPION]: "champion",
        [e.CHAMPION_ALLIANCE]: "champion_a",
        [e.CHAMPION_HORDE]: "champion_h",
        [e.COVENANT]: "covenant",
        [e.CURRENCY]: "currency",
        [e.DI_EQUIP_ITEM]: "di-equip-item",
        [e.DI_MISC_ITEM]: "di-misc-item",
        [e.DI_NPC]: "di-npc",
        [e.DI_OBJECT]: "di-object",
        [e.DI_PARAGON_SKILL]: "di-paragon-skill",
        [e.DI_QUEST]: "di-quest",
        [e.DI_SET]: "di-set",
        [e.DI_SKILL]: "di-skill",
        [e.DI_ZONE]: "di-zone",
        [e.ENCOUNTER]: "encounter",
        [e.EVENT]: "event",
        [e.FACTION]: "faction",
        [e.FOLLOWER]: "follower",
        [e.FOLLOWER_ALLIANCE]: "follower_a",
        [e.FOLLOWER_HORDE]: "follower_h",
        [e.GEAR_SET]: "gear-set",
        [e.GUIDE]: "guide",
        [e.HUNTER_PET]: "pet",
        [e.ICON]: "icon",
        [e.ITEM]: "item",
        [e.ITEM_SET]: "item-set",
        [e.MISSION]: "mission",
        [e.MISSION_ABILITY]: "mission-ability",
        [e.NEWS_POST]: "news",
        [e.NPC]: "npc",
        [e.OBJECT]: "object",
        [e.ORDER_ADVANCEMENT]: "order-advancement",
        [e.OUTFIT]: "outfit",
        [e.PLAYER_CLASS]: "class",
        [e.QUEST]: "quest",
        [e.RACE]: "race",
        [e.RESOURCE]: "resource",
        [e.SHIP]: "ship",
        [e.SHIP_ALLIANCE]: "ship_a",
        [e.SHIP_HORDE]: "ship_h",
        [e.SKILL]: "skill",
        [e.SOULBIND]: "soulbind",
        [e.SOUND]: "sound",
        [e.SPELL]: "spell",
        [e.STORYLINE]: "storyline",
        [e.THREAT]: "threat",
        [e.TITLE]: "title",
        [e.TRANSMOG_SET]: "transmog-set",
        [e.ZONE]: "zone"
    };
    const n = function () {
        let t = {};
        t[WH.dataTree.RETAIL] = [e.ACHIEVEMENT, e.ADVENTURE_COMBATANT_ABILITY, e.AFFIX, e.AZERITE_ESSENCE, e.AZERITE_ESSENCE_POWER, e.BATTLE_PET_ABILITY, e.BFA_CHAMPION, e.BUILDING, e.CHAMPION, e.CURRENCY, e.EVENT, e.FACTION, e.FOLLOWER, e.GATHERER_GUIDE_IMAGE, e.GATHERER_LISTVIEW, e.GATHERER_SCREENSHOT, e.GUIDE, e.HUNTER_PET, e.ICON, e.ITEM, e.ITEM_SET, e.MISSION, e.MISSION_ABILITY, e.NPC, e.OBJECT, e.ORDER_ADVANCEMENT, e.OUTFIT, e.PLAYER_CLASS, e.QUEST, e.RACE, e.RESOURCE, e.SHIP, e.SKILL, e.SOUND, e.SPELL, e.STORYLINE, e.THREAT, e.TITLE, e.TRANSMOG_SET, e.ZONE];
        t[WH.dataTree.CLASSIC] = [e.FACTION, e.GATHERER_GUIDE_IMAGE, e.GATHERER_LISTVIEW, e.GATHERER_SCREENSHOT, e.GEAR_SET, e.GUIDE, e.HUNTER_PET, e.ICON, e.ITEM, e.ITEM_SET, e.NPC, e.OBJECT, e.OUTFIT, e.PLAYER_CLASS, e.QUEST, e.RACE, e.RESOURCE, e.SKILL, e.SOUND, e.SPELL, e.ZONE];
        t[WH.dataTree.TBC] = [e.FACTION, e.GATHERER_GUIDE_IMAGE, e.GATHERER_LISTVIEW, e.GATHERER_SCREENSHOT, e.GUIDE, e.HUNTER_PET, e.ICON, e.ITEM, e.ITEM_SET, e.NPC, e.OBJECT, e.OUTFIT, e.PLAYER_CLASS, e.QUEST, e.RACE, e.RESOURCE, e.SKILL, e.SOUND, e.SPELL, e.ZONE];
        t[WH.dataTree.D2] = [];
        t[WH.dataTree.DI] = [e.DI_EQUIP_ITEM, e.DI_MISC_ITEM, e.DI_NPC, e.DI_OBJECT, e.DI_PARAGON_SKILL, e.DI_QUEST, e.DI_SET, e.DI_SKILL, e.DI_ZONE];
        t[WH.dataTree.WRATH] = [e.ACHIEVEMENT, e.FACTION, e.GATHERER_GUIDE_IMAGE, e.GATHERER_LISTVIEW, e.GATHERER_SCREENSHOT, e.GUIDE, e.HUNTER_PET, e.ICON, e.ITEM, e.ITEM_SET, e.NPC, e.OBJECT, e.OUTFIT, e.PLAYER_CLASS, e.QUEST, e.RACE, e.RESOURCE, e.SKILL, e.SOUND, e.SPELL, e.ZONE];
        return t
    }();
    const r = 0;
    const o = 1;
    const s = 2;
    const l = 3;
    const c = {typeNames: undefined};
    this.existsInDataEnv = function (e, t) {
        return n[WH.getDataTree(t)].includes(e)
    };
    this.getIdByString = function (e) {
        return WH.findKey(i, e, true)
    };
    this.getDetailPageName = function (e) {
        return t[e] || i[e]
    };
    this.getGame = function (t) {
        let a = (e.getRequiredTrees(t) || [])[0];
        return a ? WH.Game.getByTree(a) : undefined
    };
    this.getGameWowTypes = function () {
        return a.slice()
    };
    this.getPreferredDataEnv = function (e) {
        let t = WH.Types.getRequiredTrees(e);
        if (t) {
            return t.includes(WH.getDataTree()) ? WH.getDataEnv() : WH.getRootByTree(t[0])
        }
    };
    this.getReferenceName = function (e) {
        return i[e]
    };
    this.getRequiredTrees = function (e) {
        let t = [];
        let a = false;
        for (let i in n) {
            if (!n.hasOwnProperty(i)) {
                continue
            }
            if (n[i].includes(e)) {
                t.push(parseInt(i))
            } else {
                a = true
            }
        }
        return a ? t : null
    };
    this.getStringId = function (e) {
        return i[e]
    };
    this.hasName = function (e) {
        return c.typeNames.hasOwnProperty(e)
    };
    this.getLowerPlural = function (e) {
        return u(e)[l]
    };
    this.getLowerSingular = function (e) {
        return u(e)[o]
    };
    this.getUpperPlural = function (e) {
        return u(e)[s]
    };
    this.getUpperSingular = function (e) {
        return u(e)[r]
    };

    function u(e) {
        if (c.typeNames === undefined) {
            c.typeNames = WH.getPageData("types.names") || {}
        }
        return c.typeNames[e] || Array(4).fill(WH.term("unknownType_format", e), 0, 4)
    }
};
WH.error = function (e) {
    console.error.apply(console.error, Array.prototype.slice.call(arguments));
    if (!e) {
        console.error("The error message was empty, and thus will not be logged.");
        return
    }
    if (WH.Track) {
        WH.Track.nonInteractiveEvent.apply(WH.Track, ["Error"].concat(Array.prototype.slice.call(arguments)))
    }
};
WH.info = function (e) {
    console.info.apply(console.info, Array.prototype.slice.call(arguments))
};
WH.log = function (e) {
    console.log.apply(console.log, Array.prototype.slice.call(arguments))
};
WH.warn = function (e) {
    console.warn.apply(console.warn, Array.prototype.slice.call(arguments))
};
(function () {
    const e = {};
    WH.getPageData = function (t) {
        if (WH.REMOTE) {
            return undefined
        }
        if (e.hasOwnProperty(t)) {
            return e[t]
        }
        let a = document.querySelector(("script#data." + t).replace(/\./g, "\\."));
        if (a) {
            return JSON.parse(a.innerHTML)
        }
        return undefined
    };
    WH.setPageData = function (t, a) {
        if (e.hasOwnProperty(t)) {
            WH.warn("Duplicate data key", t)
        }
        e[t] = a
    }
})();
Object.assign(WH.PageMeta, WH.getPageData("pageMeta") || {});
WH.PageMeta.serverTime = WH.PageMeta.serverTime ? new Date(WH.PageMeta.serverTime) : new Date;
if (WH.PageMeta.staticUrl !== undefined) {
    WH.STATIC_URL = WH.PageMeta.staticUrl;
    WH.staticUrl = WH.PageMeta.staticUrl
}
WH.stringCompare = function (e, t) {
    if (e == t) return 0;
    if (e == null) return -1;
    if (t == null) return 1;
    var a = parseFloat(e);
    var i = parseFloat(t);
    if (!isNaN(a) && !isNaN(i) && a != i) {
        return a < i ? -1 : 1
    }
    if (typeof e == "string" && typeof t == "string") {
        return e.localeCompare(t, undefined, {numeric: true})
    }
    return e < t ? -1 : 1
};
WH.trim = function (e) {
    return e.replace(/(^\s*|\s*$)/g, "")
};
WH.rtrim = function (e, t) {
    let a = e.length;
    while (--a > 0 && e.charAt(a) === t) {
    }
    e = e.substring(0, a + 1);
    if (e === t) {
        e = ""
    }
    return e
};
WH.sprintf = function (e) {
    if (typeof e !== "string") {
        WH.error("No format passed to WH.sprintf.", e);
        return ""
    }
    for (var t = 1, a = arguments.length; t < a; ++t) {
        e = e.replace("$" + t, arguments[t])
    }
    return e
};
WH.sprintfGlobal = function (e) {
    for (var t = 1, a = arguments.length; t < a; ++t) {
        e = e.replace(new RegExp("\\$" + t, "g"), arguments[t])
    }
    return e
};
WH.stringReplace = function (e, t, a) {
    while (e.indexOf(t) != -1) {
        e = e.replace(t, a)
    }
    return e
};
WH.term = function (e) {
    if (!WH.TERMS) {
        return e
    }
    let t = [WH.TERMS[e]].concat(Array.prototype.slice.call(arguments, 1));
    return WH.Strings.sprintf.apply(null, t)
};
WH.wowTerm = function (e) {
    if (!WH.GlobalStrings) {
        return e
    }
    let t = [WH.GlobalStrings[e]].concat(Array.prototype.slice.call(arguments, 1));
    return WH.Strings.sprintf.apply(null, t)
};
WH.htmlEntities = function (e) {
    return e.replace(/[\u00A0-\u9999<>\&]/gim, (function (e) {
        return "&#" + e.charCodeAt(0) + ";"
    }))
};
WH.stub = function (e) {
    let t = e.split(".");
    let a = WH;
    for (let e = 0, i; i = t[e]; e++) {
        if (!a[i]) {
            a[i] = {}
        }
        a = a[i]
    }
};
WH.urlEncode = function (e) {
    e = encodeURIComponent(e);
    e = WH.stringReplace(e, "+", "%2B");
    return e
};
WH.urlEncodeHref = function (e) {
    e = encodeURIComponent(e);
    e = WH.stringReplace(e, "%20", "+");
    e = WH.stringReplace(e, "%3D", "=");
    return e
};
WH.numberFormat = function (e) {
    var t = ("" + parseFloat(e)).split(".");
    e = t[0];
    var a = t.length > 1 ? "." + t[1] : "";
    if (e.length <= 3) {
        return e + a
    }
    return WH.numberFormat(e.substr(0, e.length - 3)) + "," + e.substr(e.length - 3) + a
};
WH.numberLocaleFormat = function (e, t) {
    var a = "";
    if (typeof t == "number") {
        a = Locale.locales[t].name
    } else {
        if (typeof t == "string") {
            a = t
        } else {
            a = Locale.getName()
        }
    }
    if (a.length == 4) {
        a = a.substr(0, 2).toLowerCase() + "-" + a.substr(2).toUpperCase()
    }
    var i = "" + e;
    try {
        i = e.toLocaleString(a)
    } catch (t) {
        i = e.toLocaleString()
    }
    return i
};
WH.inArray = function (e, t, a, i) {
    if (e == null) {
        return -1
    }
    if (!Array.isArray(e)) {
        WH.error("Tried looking for a value in a haystack which is not an array.", arguments);
        return -1
    }
    var n;
    if (a) {
        n = e.length;
        for (var r = i || 0; r < n; ++r) {
            if (a(e[r]) == t) {
                return r
            }
        }
        return -1
    }
    n = e.indexOf(t, i);
    if (n >= 0) {
        return n
    }
    n = e.length;
    for (var o = i || 0; o < n; ++o) {
        if (e[o] == t) {
            return o
        }
    }
    return -1
};
WH.isSet = function (e) {
    return typeof window[e] !== "undefined"
};
if (!WH.isSet("console")) {
    var console = {
        log: function () {
        }
    }
}
WH.arrayWalk = function (e, t, a) {
    for (var i = 0, n = e.length; i < n; ++i) {
        var r = t(e[i], a, e, i);
        if (r != null) {
            e[i] = r
        }
    }
};
WH.arrayApply = function (e, t, a) {
    for (var i = 0, n = e.length; i < n; ++i) {
        t(e[i], a, e, i)
    }
};
WH.arrayFilter = function (e, t) {
    var a = [];
    for (var i = 0, n = e.length; i < n; ++i) {
        if (t(e[i])) {
            a.push(e[i])
        }
    }
    return a
};
WH.arrayUnique = function (e) {
    var t = {};
    for (var a = e.length - 1; a >= 0; --a) {
        t[e[a]] = 1
    }
    var i = [];
    for (var n in t) {
        i.push(n)
    }
    return i
};
WH.closest = function (e, t) {
    while (e && e.nodeType === Node.ELEMENT_NODE) {
        if (e.matches(t)) {
            return e
        }
        e = e.parentNode
    }
    return undefined
};
WH.ge = function (e) {
    if (typeof e != "string") {
        return e
    }
    return document.getElementById(e)
};
WH.gE = function (e, t) {
    return e.getElementsByTagName(t)
};
WH.qs = function (e, t) {
    return (t || document).querySelector(e)
};
WH.qsa = function (e, t) {
    return (t || document).querySelectorAll(e)
};
WH.ce = function (e, t, a) {
};
WH.ce = function (e) {
    return function (t, a, i) {
        var n = e(t);
        if (a) {
            WH.cOr(n, a)
        }
        if (i) {
            WH.ae(n, i)
        }
        if (n.tagName === "INPUT" && n.type === "range" && !WH.isRemote() && WH.DOM) {
            WH.DOM.styleRangeElement(n)
        }
        return n
    }
}(typeof document.createElementOriginal === "function" ? document.createElementOriginal.bind(document) : document.createElement.bind(document));
WH.de = function (e, t) {
    if (typeof e === "string") {
        e = (t || document).querySelector(e)
    }
    if (e && e.parentNode) {
        e.parentNode.removeChild(e)
    }
};
WH.ae = function (e, t) {
    if (Array.isArray(t)) {
        WH.arrayApply(t, e.appendChild.bind(e));
        return t
    } else {
        return e.appendChild(t)
    }
};
WH.aeb = function (e, t) {
    return e.parentNode.insertBefore(t, e)
};
WH.aef = function (e, t) {
    return e.insertBefore(t, e.firstChild)
};
WH.ee = function (e, t) {
    if (!t) {
        t = 0
    }
    while (e.childNodes[t]) {
        e.removeChild(e.childNodes[t])
    }
};
WH.ct = function (e) {
    return document.createTextNode(e)
};
WH.st = function (e, t) {
    if (e.firstChild && e.firstChild.nodeType == 3) {
        e.firstChild.nodeValue = t
    } else {
        WH.aef(e, WH.ct(t))
    }
};
WH.noWrap = function (e) {
    e.style.whiteSpace = "nowrap"
};
WH.rf = function () {
    return false
};
WH.rfWithoutControlKey = function (e) {
    if (e.ctrlKey || e.shiftKey || e.altKey || e.metaKey) {
        return
    }
    return false
};
WH.aE = function (e, t, a, i) {
    if (!e) {
        return
    }
    if (typeof e === "string") {
        e = document.querySelectorAll(e)
    } else if (e instanceof EventTarget) {
        e = [e]
    } else if (Array.isArray(e) || e instanceof NodeList) {
    } else {
        e = [e]
    }
    t = typeof t === "string" ? [t] : t;
    for (let n = 0; n < e.length; n++) {
        for (let r of t) {
            e[n].addEventListener(r, a, i || false)
        }
    }
};
WH.dE = function (e, t, a, i) {
    if (!e) {
        return
    }
    if (typeof e === "string") {
        e = document.querySelectorAll(e)
    } else if (e instanceof EventTarget) {
        e = [e]
    } else if (Array.isArray(e) || e instanceof NodeList) {
    } else {
        e = [e]
    }
    for (let n = 0; n < e.length; n++) {
        e[n].removeEventListener(t, a, i || false)
    }
};
WH.preventSelectStart = function (e) {
    e.dataset.preventSelectStart = "true"
};
WH.sp = function (e) {
    if (!e) {
        e = window.event
    }
    e.stopPropagation()
};
WH.setCookie = function (e, t, a, i, n, r) {
    var o = new Date;
    var s = e + "=" + encodeURI(a) + "; ";
    o.setDate(o.getDate() + t);
    s += "expires=" + o.toUTCString() + "; ";
    if (i) {
        s += "path=" + i + "; "
    }
    if (n) {
        s += "domain=" + n + "; "
    }
    if (r === true) {
        s += "secure;"
    }
    document.cookie = s;
    WH.getCookies(e);
    WH.getCookies.C[e] = a
};
WH.deleteCookie = function (e, t, a, i) {
    WH.setCookie(e, -1, "", t, a, i);
    WH.getCookies.C[e] = null
};
WH.getCookies = function (e) {
    if (WH.getCookies.I == null) {
        var t = decodeURI(document.cookie).split("; ");
        WH.getCookies.C = {};
        for (var a = 0, i = t.length; a < i; ++a) {
            var n = t[a].indexOf("="), r, o;
            if (n != -1) {
                r = t[a].substr(0, n);
                o = t[a].substr(n + 1)
            } else {
                r = t[a];
                o = ""
            }
            WH.getCookies.C[r] = o
        }
        WH.getCookies.I = 1
    }
    if (!e) {
        return WH.getCookies.C
    } else {
        return WH.getCookies.C[e]
    }
};
WH.dO = function (e) {
    return WH.cO({}, e)
};
WH.cO = function (e, t) {
    for (var a in t) {
        if (t[a] !== null && typeof t[a] == "object" && t[a].length) {
            e[a] = t[a].slice(0)
        } else {
            e[a] = t[a]
        }
    }
    return e
};
WH.cOr = function (e, t, a) {
    for (var i in t) {
        if (a && i.substr(0, a.length) == a) {
            continue
        }
        if (t[i] !== null && typeof t[i] == "object") {
            if (Array.isArray(t[i])) {
                e[i] = t[i].slice(0)
            } else {
                if (!e[i]) {
                    e[i] = {}
                }
                WH.cOr(e[i], t[i], a)
            }
        } else {
            e[i] = t[i]
        }
    }
    return e
};
WH.fround = function (e) {
    if (Math.fround) {
        return Math.fround(e)
    } else if (typeof Float32Array != "undefined" && Float32Array) {
        var t = new Float32Array(1);
        t[0] = +e;
        return t[0]
    } else {
        return e
    }
};
WH.displayBlock = function (e, t) {
    if (typeof e === "string") {
        e = (t || document).querySelector(e);
        if (!e) {
            return
        }
    }
    e.style.display = "block"
};
WH.displayDefault = function (e, t) {
    if (typeof e === "string") {
        e = (t || document).querySelector(e);
        if (!e) {
            return
        }
    }
    e.style.removeProperty("display")
};
WH.displayInline = function (e, t) {
    if (typeof e === "string") {
        e = (t || document).querySelector(e);
        if (!e) {
            return
        }
    }
    e.style.display = "inline"
};
WH.displayNone = function (e, t) {
    if (typeof e === "string") {
        e = (t || document).querySelector(e);
        if (!e) {
            return
        }
    }
    e.style.display = "none"
};
WH.setData = function (e, t, a, i) {
    let n;
    if (typeof e === "string") {
        n = (i || document).querySelectorAll(e)
    } else if (e) {
        if (e.dataset) {
            n = [e]
        } else if (e.length) {
            n = e
        } else {
            WH.error("Element not supported by WH.setData().", t, a, e, i);
            return
        }
    } else {
        WH.error("No element passed to WH.setData().", t, a, e, i);
        return
    }
    if (a == null) {
        for (let e = 0, a; a = n[e]; e++) {
            delete a.dataset[t]
        }
    } else {
        for (let e = 0, i; i = n[e]; e++) {
            i.dataset[t] = a
        }
    }
};
WH.getWindowSize = function () {
    return {w: window.innerWidth, h: window.innerHeight}
};
WH.getScroll = function () {
    return {x: window.scrollX, y: window.scrollY}
};
WH.getCursorPos = function (e) {
    return {x: e.pageX, y: e.pageY}
};
WH.ac = function (e, t) {
    let a = 0;
    let i = 0;
    while (e) {
        let t;
        if (e.style.transform && (t = e.style.transform.match(/scale\(([\d.]+)\)/i))) {
            a *= parseFloat(t[1]);
            i *= parseFloat(t[1])
        }
        a += e.offsetLeft;
        i += e.offsetTop;
        let n = e.parentNode;
        while (n && n !== e.offsetParent && n.offsetParent) {
            if (n.scrollLeft || n.scrollTop) {
                a -= n.scrollLeft | 0;
                i -= n.scrollTop | 0;
                break
            }
            n = n.parentNode
        }
        e = e.offsetParent
    }
    if (window.Lightbox && Lightbox.isVisible()) {
        t = true
    }
    if (t) {
        let e = WH.getScroll();
        a += e.x;
        i += e.y
    }
    let n = [a, i];
    n.x = a;
    n.y = i;
    return n
};
WH.getOffset = function (e, t) {
    let a = e.getBoundingClientRect();
    let i = {left: a.left, top: a.top};
    if (t !== true) {
        let e = WH.getScroll();
        i.left += e.x;
        i.top += e.y
    }
    return i
};
WH.scrollTo = function (e, t) {
    t = t || {};
    if (typeof e === "string") {
        let t = document.querySelector(e);
        if (!t) {
            WH.error("Could not select element to scroll to.", e);
            return
        }
        e = t
    }
    if (!e || e.nodeType !== Node.ELEMENT_NODE) {
        WH.error("Invalid target to scroll to.", e);
        return
    }
    if (t.asNeeded) {
        let a = e.getBoundingClientRect();
        let i = t.position === "center" ? 10 : 0;
        if (a.top >= i && a.top + a.height + i < window.innerHeight && a.left >= i && a.left + a.width + i < window.innerWidth) {
            return
        }
    }
    e.scrollIntoView({behavior: t.animated === false ? "auto" : "smooth", block: t.position || "start"})
};
WH.isElementFixedPosition = function (e) {
    while (e && e.nodeType === Node.ELEMENT_NODE) {
        if (getComputedStyle(e).getPropertyValue("position") === "fixed") {
            return true
        }
        e = e.parentNode
    }
    return false
};
WH.createReverseLookupJson = function (e) {
    var t = {};
    for (var a in e) {
        t[e[a]] = a
    }
    return t
};
WH.getLocaleFromDomain = function (e) {
    var t = WH.getLocaleFromDomain.L;
    if (e && typeof e == "string") {
        var a = e.split(".");
        return t[a[0]] || 0
    }
    return 0
};
WH.getLocaleFromDomain.L = {ko: 1, fr: 2, de: 3, cn: 4, es: 6, ru: 7, pt: 8, it: 9};
WH.getDomainFromLocale = function (e) {
    var t;
    if (WH.getDomainFromLocale.L) {
        t = WH.getDomainFromLocale.L
    } else {
        t = WH.getDomainFromLocale.L = WH.createReverseLookupJson(WH.getLocaleFromDomain.L)
    }
    return t[e] ? t[e] : ""
};
WH.getTypeIdFromTypeString = function (e) {
    if (!WH.getTypeIdFromTypeString.lookup[e]) {
        WH.error("No type ID found for type string [" + e + "].");
        return -1
    }
    return WH.getTypeIdFromTypeString.lookup[e]
};
WH.getTypeIdFromTypeString.lookup = {
    mount: -1e3,
    recipe: -1001,
    "battle-pet": -1002,
    npc: 1,
    object: 2,
    item: 3,
    itemset: 4,
    "item-set": 4,
    quest: 5,
    spell: 6,
    zone: 7,
    faction: 8,
    pet: 9,
    achievement: 10,
    title: 11,
    event: 12,
    statistic: 16,
    currency: 17,
    building: 20,
    follower: 21,
    garrisonability: 22,
    missionability: 22,
    "mission-ability": 22,
    mission: 23,
    ship: 25,
    threat: 26,
    resource: 27,
    champion: 28,
    icon: 29,
    "order-advancement": 30,
    "bfa-champion": 38,
    affix: 40,
    "azerite-essence": 43,
    "azerite-essence-power": 42,
    storyline: WH.Types.STORYLINE,
    "adventure-combatant-ability": WH.Types.ADVENTURE_COMBATANT_ABILITY,
    "di-equip-item": 50,
    "di-skill": 54,
    "di-paragon-skill": 55,
    "di-set": 56,
    "di-npc": 57,
    "di-misc-item": 58,
    "di-zone": 59,
    "di-quest": 60,
    "di-object": 61,
    guide: 100,
    "transmog-set": 101,
    outfit: 110,
    petability: 200,
    "pet-ability": 200
};
WH.fetch = function () {
    let e = function (e) {
        let t = [];
        for (let a in e) {
            if (e.hasOwnProperty(a)) {
                t.push(encodeURIComponent(a) + "=" + encodeURIComponent(e[a]))
            }
        }
        return t.join("&")
    };
    let t = function (e) {
        if (e.contentType) {
            return e.contentType
        }
        if (typeof e.form === "object") {
            return "application/x-www-form-urlencoded"
        }
        if (e.hasOwnProperty("json")) {
            return "application/json"
        }
        if (typeof e.body === "string") {
            return "application/octet-stream"
        }
        return undefined
    };
    if (typeof window.fetch !== "function") {
        let a = function (e, t, a) {
            let i = this;
            let n = i.responseText;
            let r = (i.getResponseHeader("content-type") || "").indexOf("application/json") === 0;
            let o = null;
            if (i.status < 200 || i.status > 399) {
                o = "Legacy WH.fetch call got a bad response code."
            } else if (r) {
                try {
                    n = JSON.parse(n)
                } catch (e) {
                    n = undefined;
                    o = "Could not process Legacy WH.fetch JSON response. " + e.message
                }
            }
            if (o) {
                WH.error(o, e, i.status, i.responseText, i);
                if (t.error) {
                    t.error(n, i.status)
                }
            } else {
                if (t.success) {
                    t.success(n, i.status)
                }
            }
            if (t.complete) {
                t.complete(n, i.status)
            }
        };
        let i = function (e, t, a) {
            let i = this;
            let n = "Legacy WH.fetch call could not complete.";
            let r = i.responseText || undefined;
            WH.error(n, e, i.status, i.responseText, i);
            if (t.error) {
                t.error(r, i.status)
            }
            if (t.complete) {
                t.complete(r, i.status)
            }
        };
        return function (n, r) {
            r = r || {};
            if (r.query) {
                n += (n.indexOf("?") > -1 ? "&" : "?") + WH.Url.buildQuery(r.query)
            }
            let o = r.method || "GET";
            if (r.hasOwnProperty("data") || typeof r.body === "string") {
                o = r.method || "POST"
            }
            let s = new XMLHttpRequest;
            WH.aE(s, "load", a.bind(s, n, r));
            WH.aE(s, "error", i.bind(s, n, r));
            s.overrideMimeType("text/plain");
            s.open(o, n, true);
            let l = t(r);
            if (l) {
                s.setRequestHeader("Content-Type", l)
            }
            if (typeof r.form === "object") {
                s.send(e(r.form))
            } else if (r.hasOwnProperty("json")) {
                s.send(JSON.stringify(r.json))
            } else if (typeof r.body === "string") {
                s.send(r.body)
            } else {
                s.send()
            }
        }
    }
    let a = function (e, t, a, i) {
        if (!a.ok) {
            WH.error("WH.fetch call got a bad response code.", e, a.status, i, a);
            if (t.error) {
                t.error(i, a.status)
            }
        } else {
            if (t.success) {
                t.success(i, a.status)
            }
        }
        if (t.complete) {
            t.complete(i, a.status)
        }
    };
    let i = function (e, t, a, i) {
        let n = "Could not process WH.fetch response, callback errored. " + i.message;
        WH.error(n, e, a.status, "", a);
        if (t.error) {
            t.error(undefined, a.status)
        }
        if (t.complete) {
            t.complete(undefined, a.status)
        }
    };
    let n = function (e, t, n) {
        let r = (n.headers.get("content-type") || "").indexOf("application/json") === 0;
        (r ? n.json() : n.text()).then(a.bind(null, e, t, n))["catch"](i.bind(null, e, t, n))
    };
    let r = function (e, t, a) {
        let i = "WH.fetch call could not complete. " + a.message;
        WH.error(i, e, 0, "", a);
        if (t.error) {
            t.error(undefined, 0, a.message)
        }
        if (t.complete) {
            t.complete(undefined, 0, a.message)
        }
    };
    return function (a, i) {
        i = i || {};
        if (i.query) {
            a += (a.indexOf("?") > -1 ? "&" : "?") + WH.Url.buildQuery(i.query)
        }
        let o = typeof i.cookies === "boolean" ? i.cookies : true;
        let s = {
            credentials: o ? "same-origin" : "omit",
            headers: new Headers,
            method: i.method || "GET",
            mode: i.mode || "same-origin"
        };
        let l = t(i);
        if (l) {
            s.headers.set("Content-Type", l)
        }
        if (typeof i.form === "object") {
            s.method = i.method || "POST";
            s.body = e(i.form)
        } else if (i.hasOwnProperty("json")) {
            s.method = i.method || "POST";
            s.body = JSON.stringify(i.json)
        } else if (typeof i.body === "string") {
            s.method = i.method || "POST";
            s.body = i.body
        }
        fetch(a, s).then(n.bind(null, a, i))["catch"](r.bind(null, a, i))
    }
}();
WH.ajaxIshRequest = function (e, t) {
    var a = document.getElementsByTagName("head")[0];
    if (e.substr(0, 5) === "http:" && location.protocol === "https:") {
        WH.error("Refused to AJAX-ish load URL.", e);
        return undefined
    }
    if (t) {
        var i = WH.ce("script", {type: "text/javascript", src: e});
        WH.ae(a, i);
        return i
    }
    var n = WH.getGets();
    if (n.refresh != null) {
        if (n.refresh.length) {
            e += "&refresh=" + n.refresh
        } else {
            e += "&refresh"
        }
    }
    if (n.locale != null) {
        e += "&locale=" + n.locale
    }
    if (n.ptr != null) {
        e += "&ptr"
    }
    var i = WH.ce("script", {type: "text/javascript", src: e, charset: "utf8"});
    WH.ae(a, i);
    return i
};
WH.xhrJsonRequest = function (e, t) {
    var a = new XMLHttpRequest;
    a.onload = function (e) {
        var a = e.target.response;
        switch (e.target.responseType) {
            case"json":
                break;
            case"":
            case"text":
                try {
                    a = JSON.parse(a)
                } catch (a) {
                    WH.error("Could not parse expected JSON response", e.target);
                    return t()
                }
                break;
            default:
                WH.error("Unexpected response type from JSON request", e.target);
                return t()
        }
        return t(a)
    };
    a.onerror = function () {
        return t()
    };
    a.open("GET", e, true);
    a.responseType = "json";
    a.send()
};
WH.getGets = function () {
    if (WH.getGets.C != null) {
        return WH.getGets.C
    }
    var e = WH.getQueryString();
    var t = WH.parseQueryString(e);
    WH.getGets.C = t;
    return t
};
WH.visitUrlWithPostData = function (e, t) {
    var a = WH.ce("form");
    a.action = e;
    a.method = "post";
    for (var i in t) {
        if (t.hasOwnProperty(i)) {
            var n = WH.ce("input");
            n.type = "hidden";
            n.name = i;
            n.value = t[i];
            WH.ae(a, n)
        }
    }
    WH.ae(document.body, a);
    a.submit()
};
WH.getQueryString = function () {
    var e = "";
    if (location.pathname) {
        e += location.pathname.substr(1)
    }
    if (location.search) {
        if (location.pathname) {
            e += "&"
        }
        e += location.search.substr(1)
    }
    return e
};
WH.parseQueryString = function (e) {
    e = decodeURIComponent(e).replace(/^\?+/, "");
    var t = e.split("&");
    var a = {};
    for (var i = 0, n = t.length; i < n; ++i) {
        WH.splitQueryParam(t[i], a)
    }
    return a
};
WH.splitQueryParam = function (e, t) {
    if (e === "") {
        return
    }
    var a = e.indexOf("=");
    var i;
    var n;
    if (a != -1) {
        i = e.substr(0, a);
        n = e.substr(a + 1)
    } else {
        i = e;
        n = ""
    }
    t[i] = n
};
WH.createRect = function (e, t, a, i) {
    return {l: e, t: t, r: e + a, b: t + i}
};
WH.intersectRect = function (e, t) {
    return !(e.l >= t.r || t.l >= e.r || e.t >= t.b || t.t >= e.b)
};
WH.getViewport = function () {
    var e = $(window);
    return new Rectangle(e.scrollLeft(), e.scrollTop(), e.width(), e.height())
};
WH.keyPressIsAlphaNumeric = function (e) {
    var t = document.all ? e.keycode : e.which;
    return t > 47 && t < 58 || t > 64 && t < 91 || t > 95 && t < 112 || t == 222 || t == 0
};
WH.isRemote = function () {
    return !WH.PageMeta.wowhead
};
WH.isDev = function () {
    return !!WH.PageMeta.dev
};
WH.getDataEnv = function () {
    return WH.PageMeta.hasOwnProperty("dataEnv") ? WH.PageMeta.dataEnv.env : WH.dataEnv.MAIN
};
WH.getDataEnvFromKey = function (e) {
    for (let t in WH.dataEnvKey) {
        if (WH.dataEnvKey[t] === e) {
            return parseInt(t)
        }
    }
    return undefined
};
WH.getDataEnvKey = function (e) {
    return WH.dataEnvKey[e || WH.getDataEnv()]
};
WH.getDataEnvSeoName = function (e) {
    e = e || WH.getDataEnv();
    return WH.TERMS[e === WH.dataEnv.TBC ? "tbcClassic" : WH.getDataEnvTerm(e)]
};
WH.getDataEnvTerm = function (e) {
    return WH.dataEnvTerm[e || WH.getDataEnv()]
};
WH.getDataTree = function (e) {
    if (e !== undefined) {
        return WH.dataEnvToTree[e]
    }
    return WH.PageMeta.hasOwnProperty("dataEnv") ? WH.PageMeta.dataEnv.tree : WH.dataTree.RETAIL
};
WH.getDataTreeKey = function (e) {
    return WH.getDataEnvKey(WH.dataTreeToRoot[e || WH.getDataTree()])
};
WH.getDataTreeShortTerm = function (e) {
    return WH.dataTreeShortTerm[e || WH.getDataTree()]
};
WH.getDataTreeTerm = function (e) {
    return WH.dataTreeTerm[e || WH.getDataTree()]
};
WH.getRootByTree = function (e) {
    return WH.dataTreeToRoot[e]
};
WH.getRootEnv = function (e) {
    return WH.dataTreeToRoot[WH.getDataTree(e || WH.getDataEnv())]
};
WH.getServerTime = function () {
    return WH.PageMeta.serverTime
};
WH.getDataCacheVersion = function (e) {
    return (WH.PageMeta.activeDbChanged && WH.PageMeta.activeDbChanged[e || WH.getDataEnv()] || "0").toString()
};
WH.isBeta = function () {
    return WH.getDataEnv() === WH.dataEnv.BETA
};
WH.isBetaActive = function () {
    if (WH.PageMeta.hasOwnProperty("dataEnv")) {
        return WH.PageMeta.dataEnv.active.beta
    }
    return !!WH.REMOTE
};
WH.isClassicTree = function (e) {
    return WH.getDataTree(e) === WH.dataTree.CLASSIC
};
WH.isDataEnvActive = function (e) {
    switch (e) {
        case WH.dataEnv.BETA:
            return WH.isBetaActive();
        case WH.dataEnv.PTR:
            return WH.isPtrActive();
        default:
            return true
    }
};
WH.isPtr = function () {
    return WH.getDataEnv() === WH.dataEnv.PTR
};
WH.isPtrActive = function () {
    if (WH.PageMeta.hasOwnProperty("dataEnv")) {
        return WH.PageMeta.dataEnv.active.ptr
    }
    return !!WH.REMOTE
};
WH.isRetailTree = function (e) {
    return WH.getDataTree(e) === WH.dataTree.RETAIL
};
WH.isTbcTree = function (e) {
    return WH.getDataTree(e) === WH.dataTree.TBC
};
WH.isWrathTree = function (e) {
    return WH.getDataTree(e) === WH.dataTree.WRATH
};
WH.suppressExternalDebug = function () {
    return !!WH.PageMeta.suppressExternalDebug
};
WH.setupFooterMenus = function () {
    var e = {
        "footer-help-menu": mn_footer_help,
        "footer-tools-menu": mn_footer_tools,
        "footer-about-menu": mn_footer_about
    };
    for (var t in e) {
        if (!e.hasOwnProperty(t)) continue;
        var a = $("#" + t);
        if (a.length) {
            a.addClass("hassubmenu");
            Menu.add(a.get(0), e[t])
        }
    }
};
WH.getScreenshotUrl = function (e, t, a) {
    if (!t) {
        t = "normal"
    }
    a = a || {};
    var i = t == "normal" && typeof a.description == "string" && a.description ? "-" + WH.Strings.slug(a.description, true) : "";
    var n = {2: ".jpg", 3: ".png", 18: ".webp"};
    var r = n[a.imageType || 2] || n[2];
    return a.staffOnly ? "/admin/screenshots/view/" + e + "?ext=" + r.replace(/\./, "") : WH.staticUrl + "/uploads/screenshots/" + t + "/" + e + i + r
};
WH.maxLevel = WH.maxLevel || 60;
WH.maxSkill = WH.maxSkill || 900;
WH.convertRatingToPercent = function (e, t, a, i) {
    let n = (WH.convertRatingToPercent.LT || {})[t] || {};
    let r = WH.findSparseKey(n, e);
    let o = n[r] || 0;
    if (i != null && WH.isWrathTree() && !WH.isRemote()) {
        const e = WH.Wow.Item.Stat;
        const a = WH.Wow.PlayerClass;
        if ([e.ID_HASTE_RATING, e.ID_HASTE_MELEE_RATING].includes(t)) {
            if ([a.PALADIN, a.DEATH_KNIGHT, a.SHAMAN, a.DRUID].includes(i)) {
                o /= 1.3
            }
        }
    }
    return o ? a / o : 0
};
WH.statToRating = {
    11: 0,
    12: 1,
    13: 2,
    14: 3,
    15: 4,
    16: 5,
    17: 6,
    18: 7,
    19: 8,
    20: 9,
    21: 10,
    25: 15,
    26: 15,
    27: 15,
    28: 17,
    29: 18,
    30: 19,
    31: 5,
    32: 8,
    34: 15,
    35: 15,
    36: 17,
    37: 23,
    44: 24,
    49: 25,
    57: 26,
    59: 11,
    60: 12,
    61: 13,
    62: 16,
    63: 20,
    64: 21,
    40: 29
};
WH.statToJson = {
    0: "mana",
    1: "health",
    3: "agi",
    4: "str",
    5: "int",
    6: "spi",
    7: "sta",
    8: "energy",
    9: "rage",
    10: "focus",
    13: "dodgertng",
    14: "parryrtng",
    15: "blockrtng",
    16: "mlehitrtng",
    17: "rgdhitrtng",
    18: "splhitrtng",
    19: "mlecritstrkrtng",
    20: "rgdcritstrkrtng",
    21: "splcritstrkrtng",
    22: "corruption",
    23: "corruptionres",
    24: "_splhitrtng",
    25: "_mlecritstrkrtng",
    26: "_rgdcritstrkrtng",
    27: "_splcritstrkrtng",
    28: "mlehastertng",
    29: "rgdhastertng",
    30: "splhastertng",
    31: "hitrtng",
    32: "critstrkrtng",
    33: "_hitrtng",
    34: "_critstrkrtng",
    35: "resirtng",
    36: "hastertng",
    37: "exprtng",
    38: "atkpwr",
    39: "rgdatkpwr",
    40: "versatility",
    41: "splheal",
    42: "spldmg",
    43: "manargn",
    44: "armorpenrtng",
    45: "splpwr",
    46: "healthrgn",
    47: "splpen",
    49: "mastrtng",
    50: "armorbonus",
    51: "firres",
    52: "frores",
    53: "holres",
    54: "shares",
    55: "natres",
    56: "arcres",
    57: "pvppower",
    58: "amplify",
    59: "multistrike",
    60: "readiness",
    61: "speedbonus",
    62: "lifesteal",
    63: "avoidance",
    64: "sturdiness",
    66: "cleave",
    71: "agistrint",
    72: "agistr",
    73: "agiint",
    74: "strint"
};
WH.jsonToStat = {};
for (var i in WH.statToJson) {
    WH.jsonToStat[WH.statToJson[i]] = i
}
WH.individualToGlobalStat = {
    16: 31,
    17: 31,
    18: 31,
    19: 32,
    20: 32,
    21: 32,
    24: 33,
    25: 34,
    26: 34,
    27: 34,
    28: 36,
    29: 36,
    30: 36
};
WH.convertScalingFactor = function (e, t, a, i, n) {
    var r = WH.convertScalingFactor.SV;
    var o = WH.convertScalingFactor.SD.stats;
    if (!r || !r[e]) {
        if (g_user.roles & U_GROUP_ADMIN) {
            alert("There are no item scaling values for level " + e)
        }
        return n ? {} : 0
    }
    const s = 10;
    var l = {}, c = r[e], u = o[a];
    if (!u || !(i >= 0 && i < s)) {
        l.v = c[t]
    } else {
        let e = WH.findSparseKey(u, i);
        let a = WH.findSparseKey(u, i + s);
        l.n = WH.statToJson[u[e]];
        l.s = u[e];
        l.v = Math.floor(c[t] * u[a] / 1e4)
    }
    return n ? l : l.v
};
WH.getScalingDistributionCurve = function (e) {
    let t = ((WH.convertScalingFactor.SD || {}).curves || {})[e];
    return t ? {minLevel: t[0], maxLevel: t[1], curve: t[2]} : undefined
};
g_itemScalingCallbacks = [];
WH.getSpellScalingIndexFromScalingClass = function (e, t) {
    switch (e) {
        case WH.EFFECT_SCALING_CLASS_2:
            if (t == 463) {
                return 13
            }
            break;
        case WH.EFFECT_SCALING_CLASS_7:
            return 13;
        case WH.EFFECT_SCALING_CLASS_8:
        case WH.EFFECT_SCALING_CLASS_9:
            return 19
    }
    if (e < 0) {
        return Math.abs(e) + 12
    }
    return e
};
WH.effectAverage = function (e, t, a, i) {
    var n = WH.convertScalingSpell.RandPropPoints;
    var r = e["scalingClass"];
    if (e["effectScalingClass"] && e["effectScalingClass"][i] != 0) {
        r = e["effectScalingClass"][i]
    }
    var o = e["coefficient"][i];
    var s = 1;
    var l = 0;
    if (o != 0 && r != 0) {
        if (e["scalesWithItemLevel"]) {
            if (r == WH.EFFECT_SCALING_CLASS_8) {
                l = n[a][0]
            } else if (r == WH.EFFECT_SCALING_CLASS_9) {
                l = n[a][2]
            } else {
                l = n[a][1]
            }
        } else {
            let e = WH.getSpellScalingIndexFromScalingClass(r);
            l = WH.convertScalingSpell.SV[t][e - 1]
        }
        if (r == WH.EFFECT_SCALING_CLASS_7 && e["aura"] && e["aura"][i] == 4) {
            s = WH.getCombatRatingMult(a, 12)
        }
        return o * l * s
    }
    return e["effectBasePoints"][i]
};
WH.convertScalingSpell = function (e, t, a, i, n, r) {
    var o = WH.convertScalingSpell.SpellInformation;
    if (!o || !o[t]) {
        return e
    }
    a = a - 1;
    if (e.effects == undefined) e.effects = {};
    if (!e.effects.hasOwnProperty(a + 1)) {
        e.effects[a + 1] = {}
    }
    var s = o[t];
    var l = 0;
    var c = WH.effectAverage(s, n, r, a);
    if (s["deltaCoefficient"][a] != 0) {
        var u = s["deltaCoefficient"][a];
        var d = Math.ceil(c - c * u / 2);
        var p = Math.floor(c + c * u / 2);
        if (i == 0) {
            l = (d + p) / 2
        } else if (i == 1) {
            l = d
        } else if (i == 2) {
            l = p
        }
    } else if (s["coefficient"][a] != 0) {
        l = c
    } else {
        l = s["effectBasePoints"][a]
    }
    l = Math.abs(l);
    var f = "avg";
    switch (parseInt(i)) {
        case 0:
        case 3:
            f = "avg";
            break;
        case 1:
            f = "min";
            break;
        case 2:
            f = "max";
            break;
        case 4:
            f = "pts";
            break;
        default:
            f = "avg"
    }
    var g = 5;
    var m = g;
    if (window.g_pageInfo && window.g_pageInfo.type == WH.Types.AZERITE_ESSENCE_POWER) {
        m = WH.Wow.Item.INVENTORY_TYPE_NECK
    }
    if (s.scalesWithItemLevel && s.appliesRatingAura && s.appliesRatingAura[a]) {
        l *= WH.getCombatRatingMult(r, m)
    }
    e.effects[a + 1][f] = l;
    return e
};
WH.getDataSource = function () {
    if (WH.isSet("g_pageInfo")) {
        switch (g_pageInfo.type) {
            case WH.Types.ITEM:
                if (WH.isSet("g_items")) {
                    return g_items
                }
                break;
            case WH.Types.SPELL:
                if (WH.isSet("g_spells")) {
                    return g_spells
                }
                break;
            case WH.Types.AZERITE_ESSENCE_POWER:
                if (WH.isSet("g_azeriteEssencePowers")) {
                    return g_azeriteEssencePowers
                }
                break;
            case WH.Types.BATTLE_PET_ABILITY:
                if (WH.isSet("g_petabilities")) {
                    return g_petabilities
                }
                break
        }
    }
    return []
};
WH.setJsonItemLevel = function (e, t, a) {
    if (a && a.scalingcategory - 11 > 0) {
        var i = a.maxlvlscaling ? Math.min(t, a.maxlvlscaling) : t;
        var n = WH.getSpellScalingValue(a.scalingcategory, i);
        for (var r = 1; r < 3; ++r) {
            var o = a["itemenchspell" + r];
            var s = a["itemenchtype" + r];
            var l = WH.statToJson[o];
            if (s == 5 && e[l]) {
                var c = a["damage" + r];
                if (c) {
                    e[l] = Math.round(n * c)
                }
            }
        }
        if (a.allstats) {
            for (var u in e) {
                e[u] = Math.round(n * a["damage1"])
            }
        }
    }
    if (!e.scadist || !e.scaflags) {
        return
    }
    e.bonuses = e.bonuses || {};
    var d = e.scaflags & 255, p = e.scaflags >> 8 & 255, f = (e.scaflags & 1 << 16) != 0,
        g = (e.scaflags & 1 << 17) != 0, m = (e.scaflags & 1 << 18) != 0, h;
    switch (d) {
        case 5:
        case 1:
        case 7:
        case 17:
            h = 7;
            break;
        case 3:
        case 12:
            h = 8;
            break;
        case 16:
        case 11:
        case 14:
            h = 9;
            break;
        case 15:
            h = 10;
            break;
        case 23:
        case 21:
        case 22:
        case 13:
            h = 11;
            break;
        default:
            h = -1
    }
    if (h >= 0) {
        for (var r = 0; r < 10; ++r) {
            var H = WH.convertScalingFactor(t, h, e.scadist, r, 1);
            if (H.n) {
                e[H.n] = H.v
            }
            e.bonuses[H.s] = H.v
        }
    }
    if (m) {
        e.splpwr = e.bonuses[45] = WH.convertScalingFactor(t, 6)
    }
    if (f) {
        switch (d) {
            case 3:
                e.armor = WH.convertScalingFactor(t, 11 + p);
                break;
            case 5:
                e.armor = WH.convertScalingFactor(t, 15 + p);
                break;
            case 1:
                e.armor = WH.convertScalingFactor(t, 19 + p);
                break;
            case 7:
                e.armor = WH.convertScalingFactor(t, 23 + p);
                break;
            case 16:
                e.armor = WH.convertScalingFactor(t, 28);
                break;
            case 14:
                e.armor = WH.convertScalingFactor(t, 29);
                break;
            default:
                e.armor = 0
        }
    }
    if (g) {
        var W = e.mledps ? "mle" : "rgd", v;
        switch (d) {
            case 23:
            case 21:
            case 22:
            case 13:
                e.dps = e[W + "dps"] = WH.convertScalingFactor(t, m ? 2 : 0);
                v = .3;
                break;
            case 17:
                e.dps = e[W + "dps"] = WH.convertScalingFactor(t, m ? 3 : 1);
                v = .2;
                break;
            case 15:
                e.dps = e[W + "dps"] = WH.convertScalingFactor(t, p == 19 ? 5 : 4);
                v = .3;
                break;
            default:
                e.dps = e[W + "dps"] = 0;
                v = 0
        }
        e.dmgmin = e[W + "dmgmin"] = Math.floor(e.dps * e.speed * (1 - v));
        e.dmgmax = e[W + "dmgmax"] = Math.floor(e.dps * e.speed * (1 + v))
    }
};
WH.getContentTuningLevels = function (e) {
    let t = (WH.contentTuningLevels || {}).keys || {};
    let a = Object.keys(t).find((function (a) {
        return t[a].includes(e)
    }));
    if (a !== undefined) {
        return {minLevel: WH.contentTuningLevels.values[a][0], maxLevel: WH.contentTuningLevels.values[a][1]}
    }
};
WH.scaleItemEnchantment = function (e, t) {
    var a = e.enchantment;
    if (e.scalinginfo && e.scalinginfo.scalingcategory - 11 > 0) {
        var i = a.match(/\d+/g);
        if (i) {
            var n = parseInt(e.scalinginfo.maxlvlscaling) ? Math.min(t, parseInt(e.scalinginfo.maxlvlscaling)) : t;
            var r = WH.getSpellScalingValue(e.scalinginfo.scalingcategory, n);
            for (var o = 0; o < i.length; ++o) {
                var s = e.scalinginfo["damage" + (o + 1)];
                if (s) {
                    a = a.replace(i[o], Math.round(r * s))
                }
            }
        }
    }
    return a
};
WH.getItemRandPropPointsType = function (e) {
    var t = e.slotbak ? e.slotbak : e.slot;
    switch (t) {
        case 1:
        case 4:
        case 5:
        case 7:
        case 15:
        case 17:
        case 20:
        case 25:
            return 0;
        case 2:
        case 9:
        case 11:
        case 16:
            return 2;
        case 3:
        case 6:
        case 8:
        case 10:
        case 12:
            return 1;
        case 13:
        case 14:
        case 21:
        case 22:
        case 23:
            return 3;
        case 26:
            if (e.subclass == 19) {
                return 3
            }
            return 0;
        case 28:
            return 4;
            break;
        default:
            return -1
    }
};
WH.scaleItemLevel = function (e, t) {
    let a = e.level;
    let i = WH.curvePoints;
    if (!i) {
        return a
    }
    let n = null;
    let r = null;
    let o = null;
    if (e.scadist) {
        let t = WH.getScalingDistributionCurve(e.scadist);
        if (t && t.curve) {
            r = t.minLevel;
            o = t.maxLevel;
            n = t.curve
        }
    } else {
        if (e.contenttuning) {
            let t = WH.getContentTuningLevels(e.contenttuning);
            if (t) {
                r = t.minLevel;
                o = t.maxLevel
            }
        }
        n = e.playercurve
    }
    if (n) {
        let e = t ? t : WH.maxLevel;
        if (r && e < r) {
            e = r
        }
        if (o && e > o) {
            e = o
        }
        let s = i[n];
        if (s && s.length > 0) {
            let t = -1;
            for (let a in s) {
                let i = s[a];
                if (i[1] >= e) {
                    t = a;
                    break
                }
            }
            let i = s[t != -1 ? t : s.length - 1];
            let n = null;
            let r = 0;
            if (t > 0) {
                n = s[t - 1];
                let a = i[1] - n[1];
                if (a > 0) {
                    let t = e - n[1];
                    let o = t / a;
                    let s = i[2] - n[2];
                    let l = o * s;
                    r = n[2] + l
                }
            } else {
                r = i[2]
            }
            if (r > 0) {
                a = Math.round(r)
            }
        }
    }
    return a
};
WH.findSparseKey = function (e, t) {
    if (e.hasOwnProperty(t)) {
        return t.toString()
    }
    return Object.keys(e).reduce((function (e, a) {
        let i = parseInt(a);
        return i > t || parseInt(e) > i ? e : a
    }), "0")
};
WH.applyStatModifications = function (e, t, a, i, n, r, o, s) {
    const l = WH.Wow.Item;
    var c = {};
    if (e.hasOwnProperty("level")) {
        c = WH.dO(e)
    } else {
        WH.cOr(c, e, "__")
    }
    if (n && n.length) {
        var u = false;
        for (var d = 0; d < n.length; ++d) {
            var p = n[d];
            if (p > 0 && WH.isSet("g_itembonuses") && g_itembonuses[p]) {
                var f = g_itembonuses[p];
                for (var g = 0; g < f.length; ++g) {
                    var m = f[g];
                    switch (m[0]) {
                        case 11:
                        case 13:
                            if (u === false || m[2] < u) {
                                c.scadist = m[1];
                                c.scadistbonus = p;
                                c.scadistbonustype = m[0];
                                c.contenttuning = m[3];
                                c.playercurve = m[4];
                                u = m[2]
                            }
                            break;
                        default:
                            break
                    }
                }
            }
        }
    }
    c.level = WH.scaleItemLevel(c, r);
    if (a == "pvp" && e.pvpUpgrade) {
        c.level += e.pvpUpgrade
    }
    if (c.subitems && c.subitems[t]) {
        for (var h in c.subitems[t].jsonequip) {
            if (!c.hasOwnProperty(h)) {
                c[h] = 0
            }
            c[h] += c.subitems[t].jsonequip[h]
        }
    }
    c.extraStats = [];
    if (n && n.length) {
        if (e.statsInfo) {
            c.statsInfo = {};
            for (var d in e.statsInfo) {
                c.statsInfo[d] = {
                    alloc: parseInt(e.statsInfo[d].alloc),
                    qty: e.statsInfo[d].qty,
                    socketMult: e.statsInfo[d].socketMult
                }
            }
        }
        var H = [0, 0, 0, 0, 2147483647, 2147483647, 2147483647, 2147483647];
        var W = c.scadistbonus ? false : 0;
        let t = [24, 25];
        let a = 0;
        for (var d = 0; d < n.length; ++d) {
            var p = n[d];
            if (p > 0 && WH.isSet("g_itembonuses") && g_itembonuses[p]) {
                var f = g_itembonuses[p];
                for (var g = 0; g < f.length; ++g) {
                    var m = f[g];
                    if (m[0] == 25) {
                        let e = c.statsInfo[t[a]];
                        if (e && e.alloc) {
                            m[0] = 2;
                            m[2] = e.alloc;
                            delete c.statsInfo[t[a]];
                            a = Math.min(a + 1, t.length - 1)
                        } else {
                            continue
                        }
                    }
                    switch (m[0]) {
                        case 1:
                            if (!c.scadistbonus) {
                                c.level += m[1];
                                W = false
                            }
                            break;
                        case 2:
                            if (c.statsInfo) {
                                if (c.statsInfo.hasOwnProperty(m[1])) {
                                    c.statsInfo[m[1]].alloc += m[2]
                                } else {
                                    c.extraStats.push(m[1]);
                                    c.statsInfo[m[1]] = {alloc: parseInt(m[2]), qty: 0, socketMult: 0}
                                }
                            }
                            break;
                        case 3:
                            c.quality = parseInt(m[1]);
                            break;
                        case 4:
                            var v = m[1];
                            var T = m[2];
                            var E = 4;
                            var b = 4;
                            do {
                                if (T <= H[E]) {
                                    var y = v;
                                    v = H[E - 4];
                                    H[E - 4] = y;
                                    var I = T;
                                    T = H[E];
                                    H[E] = I
                                }
                                ++E;
                                --b
                            } while (b);
                            break;
                        case 5:
                            c.nameSuffix = WH.Wow.Item.getNameDescription(m[1]) || c.nameSuffix;
                            break;
                        case 6:
                            var S = c.nsockets ? c.nsockets : 0;
                            c.nsockets = S + m[1];
                            for (var w = S; w < S + m[1]; ++w) {
                                c["socket" + (w + 1)] = m[2]
                            }
                            break;
                        case 7:
                            break;
                        case 8:
                            c.reqlevel += m[1];
                            break;
                        case 14:
                            if (W !== false) {
                                W = c.level
                            }
                            break;
                        case 16:
                            c.bond = parseInt(m[1]);
                            break;
                        case 35:
                            c.limitcategory = parseInt(m[1]);
                        default:
                            break
                    }
                }
            }
        }
        if (W) {
            c.level = W;
            c.previewLevel = W
        }
        c.namedesc = c.namedesc ? c.namedesc : "";
        for (var g = 0; g < 4; ++g) {
            let e = WH.Wow.Item.getNameDescription(H[g]);
            if (e) {
                c.namedesc += (!c.namedesc ? "" : " ") + e;
                if (!g) {
                    c.namedesccolor = WH.Wow.Item.getNameDescriptionColor(H[g])
                }
            }
        }
    }
    (function () {
        if (!s || !s.length || !c.statsInfo) {
            return
        }
        for (let t, a = 0; t = WH.Wow.Item.Stat.CRAFTING_STAT_FROM[a]; a++) {
            let i = s[a];
            if (!i) {
                continue
            }
            if (!c.statsInfo[t]) {
                continue
            }
            if (c.statsInfo[i]) {
                c.statsInfo[i].alloc += e.statsInfo[t].alloc
            } else {
                c.statsInfo[i] = c.statsInfo[t];
                c.extraStats.push(i)
            }
            delete c.statsInfo[t]
        }
    })();
    if (e.statsInfo && e.level && WH.applyStatModifications.ScalingData && WH.applyStatModifications.ScalingData.AL.length > 1) {
        let t = WH.applyStatModifications.ScalingData.armor.total;
        let n = WH.applyStatModifications.ScalingData.armor.shield;
        let r = WH.applyStatModifications.ScalingData.armor.quality;
        let s = WH.applyStatModifications.ScalingData.SV;
        let u = WH.applyStatModifications.ScalingData.AL;
        let p = WH.applyStatModifications.ScalingData.socketCost;
        c.level = i ? i : a && e.upgrades && e.upgrades[a - 1] ? c.level + e.upgrades[a - 1] : c.level;
        var _ = c.level - e.level;
        var A = Math.pow(1.15, _ / 15);
        var M = WH.getItemRandPropPointsType(c);
        let f;
        var L = [];
        for (f = c.level; f >= 0; f--) {
            if (s.hasOwnProperty(f)) {
                L = s[f];
                break
            }
        }
        let g = 0;
        if (M != -1) {
            let e = 0;
            switch (c.quality) {
                case 5:
                case 4:
                    e = 0;
                    break;
                case 7:
                case 3:
                    e = 1;
                    break;
                case 2:
                    e = 2;
                    break;
                default:
                    break
            }
            let t = WH.findSparseKey(L, e);
            let a = WH.findSparseKey(L[t] || {}, M);
            g = (L[t] || {})[a] || 0
        }
        let m = WH.findSparseKey(p, f);
        let h = p[m] || 0;
        for (var d in WH.statToJson) {
            var R = WH.statToJson[d];
            if (c[R] || c.statsInfo && c.statsInfo[d]) {
                var k = 0;
                var C = 0;
                if (c.statsInfo.hasOwnProperty(d)) {
                    k = parseFloat(c.statsInfo[d].socketMult);
                    C = parseInt(c.statsInfo[d].alloc)
                }
                var x = Math.round(k * h);
                if (C && (g > 0 || c.contenttuning > 0)) {
                    c[R] = C * 1e-4 * g - x
                } else {
                    c[R] = (c[R] + x) * A - x
                }
                if (R == "sta") {
                    c[R] = c[R] * WH.getStaminaRatingMult(c.level, c.slot || g_items[c.id].slot)
                } else if (o && WH.inArray(WH.applyStatModifications.BASE_STATS, d) < 0) {
                    c[R] = c[R] * WH.getCombatRatingMult(c.level, c.slot || g_items[c.id].slot)
                } else if (R === "corruption" || R === "corruptionres") {
                    c[R] = C
                }
                switch (R) {
                    case"agistrint":
                        c["agi"] = c["str"] = c["int"] = c[R];
                        break;
                    case"agistr":
                        c["agi"] = c["str"] = c[R];
                        break;
                    case"agiint":
                        c["agi"] = c["int"] = c[R];
                        break;
                    case"strint":
                        c["str"] = c["int"] = c[R];
                        break;
                    default:
                        break
                }
            }
        }
        if (c["armor"]) {
            let e = c.quality === l.QUALITY_HEIRLOOM ? l.QUALITY_RARE : c.quality;
            let a = c.subclass === l.ARMOR_SUBCLASS_CLOAKS ? l.ARMOR_SUBCLASS_CLOTH : c.subclass;
            if (l.isBodyArmor(l.CLASS_ARMOR, a)) {
                let i = WH.findSparseKey(r, c.level);
                let n = WH.findSparseKey(r[i] || {}, e);
                let o = (r[i] || {})[n] || 0;
                let s = WH.findSparseKey(t, c.level);
                let l = WH.findSparseKey(t[s] || {}, a - 1);
                let d = (t[s] || {})[l] || 0;
                let p = u[c.slot][a - 1];
                c["armor"] = Math.floor(d * o * p + .5)
            }
            if (c.subclass === l.ARMOR_SUBCLASS_SHIELDS) {
                let t = WH.findSparseKey(n, c.level);
                let a = WH.findSparseKey(n[t] || {}, e);
                c["armor"] = Math.round((n[t] || {})[a] || 0)
            }
        }
        if (c["dps"]) {
            var N = ["dps", "mledps", "rgddps"];
            var O = ["dmgmin1", "mledmgmin", "rgddmgmin", "dmgmax1", "mledmgmax", "rgddmgmax"];
            var P = WH.getEffectiveWeaponDamage(c, false);
            var D = WH.getEffectiveWeaponDamage(c, true);
            P = Math.floor(Math.max(1, P));
            D = Math.max(1, D);
            if (!WH.isRetailTree()) {
                P = c.damagemin || c.dmgmin1 || P;
                D = c.damagemax || c.dmgmax1 || D
            }
            var B = (P + D) / 2 / c.speed;
            var U = B >= 1e3 ? 0 : WH.isRetailTree() ? 1 : 2;
            B = parseFloat(B.toFixed(U));
            for (var d in N) {
                if (c[N[d]]) {
                    c[N[d]] = B
                }
            }
            for (var d in O) {
                if (c[O[d]]) {
                    if (O[d].indexOf("max") != -1) {
                        c[O[d]] = D
                    } else {
                        c[O[d]] = P
                    }
                }
            }
        }
    }
    return c
};
WH.applyStatModifications.BASE_STATS = [4, 3, 5, 71, 72, 73, 74, 7, 1, 0, 8, 9, 2, 10];
WH.getItemDamageValue = function (e, t, a) {
    let i = WH.applyStatModifications.ScalingData.DV;
    if (i && i[e]) {
        let n = 7 * a + t;
        return i[e][WH.findSparseKey(i[e], n)]
    }
    return 0
};
WH.getEffectiveWeaponDamage = function (e, t) {
    var a = e.level;
    var i = e.subclass;
    var n = e.quality;
    var r = e.slotbak ? e.slotbak : e.slot;
    var o = 0;
    var s = false;
    var l = e.flags2 & 512;
    if (e.classs != 2) {
        return 0
    }
    if (n > 7) {
        return 0
    }
    if (n == 7) {
        n = 3
    }
    if (r > 22) {
        if (r == 24) {
            o = 0;
            s = true
        }
        if (!s && (r <= 24 || r > 26)) {
            s = true
        }
    } else {
        if (r == 21 || r == 22 || r == 13) {
            if (!l) {
                o = WH.getItemDamageValue(a, n, 0)
            } else {
                o = WH.getItemDamageValue(a, n, 1)
            }
            s = true
        }
        if (!s && r != 15) {
            if (r != 17) {
                s = true
            } else {
                if (!l) {
                    o = WH.getItemDamageValue(a, n, 2)
                } else {
                    o = WH.getItemDamageValue(a, n, 3)
                }
                s = true
            }
        }
    }
    if (!s && i >= 2) {
        if (i == 2 || i == 3 || i == 18) {
            if (!l) {
                o = WH.getItemDamageValue(a, n, 2)
            } else {
                o = WH.getItemDamageValue(a, n, 3)
            }
            s = true
        }
        if (!s && i == 19) {
            o = WH.getItemDamageValue(a, n, 1)
        }
    }
    if (o > 0) {
        var c = e.dmgrange || 0;
        if (!t) {
            return o * e.speed * (1 - c / 2)
        } else {
            return Math.floor(o * e.speed * (1 + c / 2) + .5)
        }
    }
    return 0
};
WH.getJsonReforge = function (e, t) {
    if (!t) {
        if (!WH.reforgeStats) {
            return []
        }
        e.__reforge = {};
        e.__reforge.all = [];
        for (var t in WH.reforgeStats) {
            var a = WH.getJsonReforge(e, t);
            if (a.amount) {
                e.__reforge.all.push(a)
            }
        }
        return e.__reforge.all
    }
    if (!WH.reforgeStats || !WH.reforgeStats[t]) {
        return {}
    }
    e.__statidx = {};
    for (var i in e) {
        if (WH.individualToGlobalStat[WH.jsonToStat[i]]) {
            e.__statidx[WH.individualToGlobalStat[WH.jsonToStat[i]]] = e[i]
        } else {
            e.__statidx[WH.jsonToStat[i]] = e[i]
        }
    }
    if (!e.__reforge) {
        e.__reforge = {}
    }
    var a = e.__reforge[t] = WH.dO(WH.reforgeStats[t]);
    e.__reforge[t].amount = Math.floor(a.v * (e.__statidx[a.i1] && !e.__statidx[a.i2] ? e.__statidx[a.i1] : 0));
    return e.__reforge[t]
};
WH.getJsonItemEnchantMask = function (e) {
    if (e.classs == 2 && e.subclass == 19) {
        return 1 << 21 - 1
    }
    return 1 << e.slot - 1
};
WH.getArtifactKnowledgeMultiplier = function (e) {
    let t = WH.Tooltip.ARTIFACT_KNOWLEDGE_MULTIPLIERS || {};
    let a = WH.findSparseKey(t, e);
    return t[a] || 1
};
WH.getCurveValue = function (e, t) {
    var a;
    if (!WH.curvePoints || !(a = WH.curvePoints[e])) {
        return undefined
    }
    var i = a[0][1];
    var n = a[0][2];
    if (i > t) {
        return n
    }
    for (var r = 0, o; o = a[r]; r++) {
        if (t == o[1]) {
            return o[2]
        }
        if (t < o[1]) {
            return (o[2] - n) / (o[1] - i) * (t - i) + n
        }
        i = o[1];
        n = o[2]
    }
    return n
};
WH.setItemModifications = function (e, t, a, i, n, r, o) {
    if (!WH.isSet("g_items") || !g_items[t] || !g_items[t].jsonequip) {
        return e
    }
    if (!n) {
        n = WH.maxLevel
    }
    a = a ? a.split(":") : null;
    var s = g_items[t].bonusesData;
    var l = 0;
    var c = a ? a.indexOf("u") : -1;
    if (s && c != -1) {
        l = a[c + 1];
        a.splice(c, 1)
    }
    if (!r) {
        r = WH.Timewalking.getGearIlvlByStringId(i) || 0
    }
    i = !r ? i : null;
    var u = WH.applyStatModifications(g_items[t].jsonequip, 0, i, r, a, n, undefined, o);
    if (!u.name && g_items[t].hasOwnProperty("name_" + Locale.getName())) {
        u.name = g_items[t]["name_" + Locale.getName()];
        u.quality = g_items[t].quality
    }
    if (l) {
        var d = WH.bonusesBtnGetContextBonusId(a);
        var p = s[d].sub[l].sub;
        for (var f in p) {
            var g = WH.applyStatModifications(g_items[t].jsonequip, 0, i, r, [d, f]);
            for (var m in g.statsInfo) {
                var h = g[WH.statToJson[m]];
                if (u.statsInfo[m]) {
                    if (typeof u[WH.statToJson[m]] == "number" || !u[WH.statToJson[m]]) {
                        var H = u[WH.statToJson[m]] ? u[WH.statToJson[m]] : h;
                        u[WH.statToJson[m]] = {};
                        u[WH.statToJson[m]]["min"] = H;
                        u[WH.statToJson[m]]["max"] = H
                    }
                    var W = u[WH.statToJson[m]]["min"];
                    var v = u[WH.statToJson[m]]["max"];
                    if (h < W) {
                        u[WH.statToJson[m]]["min"] = h
                    } else if (h > v) {
                        u[WH.statToJson[m]]["max"] = h
                    }
                }
            }
        }
    }
    e = e.replace(/(<!--ilvl-->)\d+\+?/, (function (e, t) {
        return t + u.level + (u.previewLevel ? "+" : "")
    }));
    let T = false;
    let E = 1;
    let b = WH.maxLevel;
    if (u.scadist) {
        let e = WH.getScalingDistributionCurve(u.scadist);
        if (e && e.maxLevel) {
            T = true;
            E = e.minLevel || 1;
            b = e.maxLevel
        }
    } else if (u.contenttuning) {
        let e = WH.getContentTuningLevels(u.contenttuning);
        if (e) {
            T = true;
            E = e.minLevel;
            b = e.maxLevel
        }
    } else if (u.scadistbonus && u.scadistbonustype === 13 && u.playercurve) {
        let e = WH.curvePoints[u.playercurve];
        E = e[0][1];
        b = Math.min(e[e.length - 1][1], WH.maxLevel);
        T = true
    }
    if (T) {
        n = n && n <= b ? n : b;
        e = e.replace(/(<!--lvl-->)\d+/g, (function (e, t) {
            return t + (n && n <= b ? n : b)
        }));
        e = e.replace(/(<!--minlvl-->)\d+/, (function (e, t) {
            return t + E
        }));
        e = e.replace(/(<!--maxlvl-->)\d+/, (function (e, t) {
            return t + b
        }));
        let a = false;
        e = e.replace(/<!--i\?(\d+):(\d+):(\d+):(\d+)(?::(\d+):(\d+))?/, (function (e, t, i, r, o, s, l) {
            a = true;
            return "\x3c!--i?" + t + ":" + E + ":" + b + ":" + n + ":" + (u.scadist || u.contenttuning) + ":" + (l || 0)
        }));
        if (!a) {
            e += "\x3c!--i?" + t + ":" + E + ":" + b + ":" + n + ":" + (u.scadist || u.contenttuning) + ":0--\x3e"
        }
        e = e.replace(/(<!--huindex-->)\d+/, (function (e, t) {
            let a = 0;
            if (u.scadistbonus && u.heirloombonuses) {
                for (let e = 0, t; t = u.heirloombonuses[e]; e++) {
                    if (parseInt(u.scadistbonus) === t) {
                        a = e + 1;
                        break
                    }
                }
            }
            return t + a
        }))
    } else {
        e = e.replace(/<!--i\?(\d+):(\d+):(\d+):(\d+)(?::(\d+):(\d+))?/, (function (e, t, a, i, r, o, s) {
            return "\x3c!--i?" + t + ":" + a + ":" + i + ":" + (n ? n : i)
        }))
    }
    var y;
    if (y = WH.ge("sl" + t)) {
        y.style.display = T ? "block" : "none"
    }
    e = e.replace(/(<!--pvpilvl-->)\d+/, (function (e, t) {
        return t + (u.level + (i != "pvp" ? u.pvpUpgrade : 0))
    }));
    e = e.replace(/(<!--ilvldelta-->)\d+/, (function (e, t) {
        var a = 1718;
        var i = Math.floor(WH.getCurveValue(a, u.level) || 2);
        return t + i
    }));
    e = e.replace(/(<!--rlvl-->)\d+/, (function (e, t) {
        return t + u.reqlevel
    }));
    e = e.replace(/(<!--uindex-->)\d+/, (function (e, t) {
        return i && i != "pvp" ? t + i : e
    }));
    var I = typeof u.dmgrange != "undefined" && u.dmgrange;
    var S = new RegExp("(\x3c!--dmg--\x3e)[\\d,]+" + (I ? "(\\D*?)[\\d,]+" : "") + "");
    e = e.replace(S, (function (e, t, a) {
        return t + WH.numberFormat(u.dmgmin1) + (I ? a + WH.numberFormat(u.dmgmax1) : "")
    }));
    e = e.replace(/(<!--dps-->\D*?)([\d,]+(?:\.\d+)?)/, (function (e, t) {
        var a = u.dps >= 1e3 ? 0 : WH.isRetailTree() ? 1 : 2;
        return t + (u.dps ? WH.numberFormat(u.dps.toFixed(a)) : "0")
    }));
    e = e.replace(/(<!--amr-->)\d+/, (function (e, t) {
        return t + u.armor
    }));
    var w = WH.getCombatRatingMult(u.level, g_items[t].slot);
    e = function (e) {
        let t = WH.ce("div", {innerHTML: e});
        WH.qsa("span", t).forEach((function (e) {
            let t;
            let a;
            let i;
            let n;
            e.childNodes.forEach((function (e) {
                if (e.nodeType === Node.COMMENT_NODE) {
                    let r;
                    if (r = (e.nodeValue || "").match(/^stat(\d+)$/)) {
                        t = parseInt(r[1]);
                        i = e
                    }
                    if (r = (e.nodeValue || "").match(/^rtg(\d+)$/)) {
                        a = parseInt(r[1]);
                        n = e
                    }
                }
            }));
            if (t === undefined && a === undefined) {
                return
            }
            let r = false;
            if (a) {
                let e = u[WH.statToJson[a]] ? u[WH.statToJson[a]] : 0;
                let t = e < 0 ? "-" : "+";
                if (e) {
                    let t = Math.round((l && e.min ? e.min : e) * w);
                    let a = Math.round((l && e.max ? e.max : e) * w);
                    e = WH.numberLocaleFormat(t != a ? t + "-" + a : t)
                } else {
                    r = true;
                    e = 0
                }
                let i = n.previousSibling;
                if (i && i.nodeType === Node.TEXT_NODE) {
                    i.nodeValue = i.nodeValue.replace(/[-+]$/, t)
                }
                let o = n.nextSibling;
                if (o && o.nodeType === Node.TEXT_NODE) {
                    o.nodeValue = o.nodeValue.replace(/[-\d\.,]+/, e)
                }
            } else {
                let e = u[WH.statToJson[t]] ? u[WH.statToJson[t]] : 0;
                if (e) {
                    let t = Math.round(l && e.min ? e.min : e);
                    let a = Math.round(l && e.max ? e.max : e);
                    e = (t > 0 ? "+" : "-") + WH.numberLocaleFormat(t != a ? t + "-" + a : t)
                } else {
                    r = true;
                    e = "+0"
                }
                let a = i.nextSibling;
                if (a && a.nodeType === Node.TEXT_NODE) {
                    a.nodeValue = a.nodeValue.replace(/[-+][-\d\.,]+/, e)
                }
            }
            if (r) {
                WH.displayNone(e);
                let t = e.nextSibling;
                while (t) {
                    if (t.nodeType === Node.ELEMENT_NODE) {
                        if (t.nodeName.toLowerCase() === "br") {
                            t.parentNode.replaceChild(document.createComment("br"), t)
                        }
                        break
                    }
                    t = t.nextSibling
                }
            } else {
                WH.displayDefault(e)
            }
        }));
        return t.innerHTML
    }(e);
    if (u.extraStats && u.extraStats.length) {
        e = e.replace(/<!--re--><span[^<]*?<\/span>(<br \/>)?/, "");
        var A = WH.applyStatModifications.BASE_STATS;
        e = e.replace(/<!--ebstats-->/, (function (e) {
            var t = "";
            for (var a = 0; a < u.extraStats.length; ++a) {
                var i = u.extraStats[a];
                if (A.indexOf(i) == -1) {
                    continue
                }
                var n = "$1$2 " + (WH.statToJson && WH.statToJson[i] && WH.Wow.Item.Stat.jsonToName(WH.statToJson[i]) || "Unknown");
                var r = WH.statToJson && WH.statToJson[i] ? u[WH.statToJson[i]] : 0;
                var o = Math.round((l && r.min ? r.min : r) * w);
                var s = Math.round((l && r.max ? r.max : r) * w);
                var c = WH.numberLocaleFormat(o != s ? o + "-" + s : o);
                t += "<br /><span>\x3c!--stat" + i + "--\x3e" + WH.sprintf(n, o < 0 ? "-" : "+", c) + "</span>"
            }
            return t + e
        }));
        e = e.replace(/<!--egstats-->/, (function (e) {
            var t = "";
            for (var a = 0; a < u.extraStats.length; ++a) {
                var i = u.extraStats[a];
                if (A.indexOf(i) != -1) {
                    continue
                }
                var n = w;
                var r = "q2";
                switch (WH.statToJson[i]) {
                    case"corruption":
                        r = "stat-corruption";
                        n = 1;
                        break;
                    case"corruptionres":
                        r = "q6";
                        n = 1;
                        break
                }
                var o = "$1$2 " + (WH.statToJson && WH.statToJson[i] && WH.Wow.Item.Stat.jsonToName(WH.statToJson[i]) || "Unknown");
                var s = WH.statToJson && WH.statToJson[i] ? u[WH.statToJson[i]] : 0;
                var c = Math.round((l && s.min ? s.min : s) * n);
                var d = Math.round((l && s.max ? s.max : s) * n);
                var p = WH.numberLocaleFormat(c != d ? c + "-" + d : c);
                var f = WH.sprintf("\x3c!--rtg$1--\x3e$2", i, p);
                var g = "";
                if (WH.statToRating && WH.statToRating[i]) {
                    g = WH.sprintf("&nbsp;<small>(\x3c!--rtg%$1--\x3e0&nbsp;@&nbsp;L$2" + WH.maxLevel + ")</small>", i, l ? "" : "\x3c!--lvl--\x3e")
                }
                var m = "";
                if (i == 50) {
                    m = "\x3c!--stat%d--\x3e"
                }
                if (i == 64) {
                    o = o.substr(5);
                    g = ""
                }
                t += '<br /><span class="' + r + '">' + m + WH.sprintf(o, c >= 0 ? "+" : "-", f) + g + "</span>"
            }
            return t + e
        }))
    }
    e = e.replace(/(<!--nstart-->)(.*)(<!--nend-->)/, (function (e, t, a, i) {
        var n = u.quality;
        var r = u.name;
        var o = u.nameSuffix ? " " + u.nameSuffix : "";
        return t + WH.sprintf('<b class="q$1">$2</b>', n, r + o) + i
    }));
    e = e.replace(/(<!--ndstart-->)(.*)(<!--ndend-->)/, (function (e, t, a, i) {
        if (!u.namedesc) {
            return t + i
        }
        if (!u.namedesccolor) {
            return e
        }
        var n = parseInt(u.namedesccolor).toString(16);
        while (n.length < 6) {
            n = "0" + n
        }
        return t + WH.sprintf('<br /><span style="color: $1">$2</span>', "#" + n, u.namedesc) + i
    }));
    var M = g_items[t].jsonequip.nsockets | 0;
    if (!M && u.nsockets || M && u.nsockets > M) {
        e = e.replace(/<!--ps-->(<br(?: \/)?>)?/, (function (e, t) {
            var a = "";
            for (var i = M; i < u.nsockets; ++i) {
                if (!u["socket" + (i + 1)]) {
                    continue
                }
                var n = u["socket" + (i + 1)];
                var r = "socket-unknown";
                var o = 81;
                var s = n;
                switch (n) {
                    case 1:
                        r = "socket-meta";
                        o = 81;
                        s = 1;
                        break;
                    case 2:
                        r = "socket-red";
                        o = 81;
                        s = 2;
                        break;
                    case 3:
                        r = "socket-yellow";
                        o = 81;
                        s = 3;
                        break;
                    case 4:
                        r = "socket-blue";
                        o = 81;
                        s = 4;
                        break;
                    case 5:
                        r = "socket-hydraulic";
                        o = 81;
                        s = 5;
                        break;
                    case 6:
                        r = "socket-cogwheel";
                        o = 81;
                        s = 6;
                        break;
                    case 7:
                        r = "socket-prismatic";
                        o = 81;
                        s = 9;
                        break;
                    case 8:
                        r = "socket-relic-iron";
                        o = 225;
                        s = 64;
                        break;
                    case 9:
                        r = "socket-relic-blood";
                        o = 225;
                        s = 128;
                        break;
                    case 10:
                        r = "socket-relic-shadow";
                        o = 225;
                        s = 256;
                        break;
                    case 11:
                        r = "socket-relic-fel";
                        o = 225;
                        s = 512;
                        break;
                    case 12:
                        r = "socket-relic-arcane";
                        o = 225;
                        s = 1024;
                        break;
                    case 13:
                        r = "socket-relic-frost";
                        o = 225;
                        s = 2048;
                        break;
                    case 14:
                        r = "socket-relic-fire";
                        o = 225;
                        s = 4096;
                        break;
                    case 15:
                        r = "socket-relic-water";
                        o = 225;
                        s = 8192;
                        break;
                    case 16:
                        r = "socket-relic-life";
                        o = 225;
                        s = 16384;
                        break;
                    case 17:
                        r = "socket-relic-storm";
                        o = 225;
                        s = 32768;
                        break;
                    case 18:
                        r = "socket-relic-holy";
                        o = 225;
                        s = 65536;
                        break;
                    case 19:
                        r = "socket-red";
                        o = 81;
                        s = 10;
                        break;
                    case 20:
                        r = "socket-yellow";
                        o = 81;
                        s = 11;
                        break;
                    case 21:
                        r = "socket-blue";
                        o = 81;
                        s = 12;
                        break;
                    case 22:
                        r = "socket-domination";
                        o = 81;
                        s = 13;
                        break;
                    default:
                        break
                }
                let e = WH.Url.generatePath(WH.sprintf("/items/gems?filter=$1;$2;0", o, s));
                var l = WH.sprintf('<a href="' + WH.Strings.escapeHtml(e) + '" class="$1 q0">', r);
                l += g_socket_names[n] ? g_socket_names[n] : g_gem_types[n] ? WH.sprintf(WH.TERMS.emptyrelicslot_format.replace("%s", "$1"), g_gem_types[n]) : "Unknown Socket";
                l += "</a>";
                a += "<br>" + l
            }
            return (M == 0 ? "<br>" : "") + a + "<br><br>"
        }))
    }
    if (a && WH.Tooltip.BONUS_ITEM_EFFECTS) {
        e = e.replace(/<!--itemEffects:(\d)-->/, (function (e, t) {
            let i = u.extraStats && u.extraStats.indexOf(parseInt(WH.jsonToStat.corruption)) >= 0;
            let n = "";
            for (let e, t = 0; e = a[t]; t++) {
                let t = WH.Tooltip.BONUS_ITEM_EFFECTS[e] || [];
                for (let e, a = 0; e = t[a]; a++) {
                    let t = WH.Tooltip.ITEM_EFFECT_TOOLTIP_HTML[e];
                    if (t) {
                        if (i) {
                            t = t.replace(/\b(class=")q2\b/g, "$1stat-corruption")
                        }
                        n += (n ? "<br>" : "") + t
                    }
                }
            }
            return n + (n && t ? "<br>" : "") + e
        }))
    }
    if (WH.applyStatModifications && WH.convertScalingSpell.SpellInformation) {
        var L;
        var R = {effects: {}};
        var k = /(<!--pts(\d):(\d):(\d+(?:\.\d+)?):(\d+)(:\d+(?:\.\d+)?)?(:crm)?-->(?:<!--rtg\d+-->)?)(\d+(?:\.\d+)?)(<!---->(%?))?/g;
        while ((L = k.exec(e)) !== null) {
            var C = L[2];
            var x = L[3];
            var N = L[5];
            if (N <= 0) {
                continue
            }
            R[N] = R[N] || {};
            let e = u.scadistbonus && u.scadistbonustype === 13 ? g_items[t].level : u.level;
            WH.cO(R[N], WH.convertScalingSpell(R[N], N, C, x, n, e))
        }
        e = WH.adjustSpellPoints(e, R, u.level, g_items[t].jsonequip.slot)
    }
    let O = WH.Timewalking.getCharLevelFromIlvl(r) || 0;
    if (O) {
        e = e.replace(/<!--ee(\d+):(\d+):(\d+):(\d+):(\d+):(\d+)-->([^<]*)<\/span>/gi, (function (e, t, a, i, n, r, o, s) {
            var l = {
                enchantment: s,
                scalinginfo: {
                    scalingcategory: t,
                    minlvlscaling: a,
                    maxlvlscaling: i,
                    damage1: n / 1e3,
                    damage2: r / 1e3,
                    damage3: o / 1e3
                }
            };
            var c = WH.scaleItemEnchantment(l, O);
            return "\x3c!--ee--\x3e" + c + "</span>"
        }))
    }
    e = e.replace(/(<!--rtg%(\d+)-->)([\.,0-9]+)%?/g, (function (t, a, i, r) {
        _ = e.match(new RegExp("\x3c!--rtg" + i + "--\x3e([\\d\\.,]+)(-[\\d\\.,]+)?"));
        if (!_) {
            return t
        }
        if (_[2]) {
            _[2] = _[2].replace(/\D/, "")
        }
        _[1] = _[1].replace(/\D/, "");
        var o = _[2] ? (Math.abs(parseInt(_[2])) + parseInt(_[1])) / 2 : _[1];
        return a + (_[2] ? "~" : "") + Math.round(WH.convertRatingToPercent(n ? n : WH.maxLevel, i, o) * 100) / 100 + (i != 49 ? "%" : "")
    }));
    e = e.replace(/<!--bo-->(<br(?: \/)?>)?/, (function (e, t) {
        let a = "";
        if (u.bond) {
            switch (u.bond) {
                case 1:
                    a = WH.GlobalStrings.ITEM_BIND_ON_PICKUP;
                    break;
                case 2:
                    a = WH.GlobalStrings.ITEM_BIND_ON_EQUIP;
                    break;
                case 3:
                    a = WH.GlobalStrings.ITEM_BIND_ON_USE;
                    break;
                case 4:
                case 5:
                    a = WH.GlobalStrings.ITEM_BIND_QUEST;
                    break;
                default:
                    a = WH.TERMS.unknownBindType_stc;
                    break
            }
        }
        if (a != "") {
            a = "<br />" + a
        }
        return "\x3c!--bo--\x3e" + a + t
    }));
    e = e.replace(/<!--bo-->/, (function (e) {
        if (!a) {
            return e
        }
        let t = WH.getPageData("wow.item.bonuses.upgrades") || {};
        let i = "";
        a.some((function (e) {
            if (t[e]) {
                let a = WH.ce("div");
                WH.ae(a, WH.ce("br"));
                WH.ae(a, WH.ce("span", {className: "q"}, WH.ct(WH.Strings.sprintf(WH.GlobalStrings.ITEM_UPGRADE_TOOLTIP_FORMAT, t[e][0], t[e][1]))));
                i = a.innerHTML;
                return true
            }
        }));
        return i + e
    }));
    e = e.replace(/<!--ue-->/, (function () {
        if (!u.limitcategory) {
            return ""
        }
        let e = "";
        let t = (WH.getPageData("wow.item.bonusLimitCategoryNames") || {})[u.limitcategory];
        if (t) {
            let a = t.uniqueEquipped ? WH.GlobalStrings.ITEM_UNIQUE_EQUIPPABLE : WH.GlobalStrings.ITEM_UNIQUE;
            e = WH.Strings.escapeHtml(a + WH.TERMS.colon_punct + WH.Strings.sprintf(WH.TERMS.parens_format, t.name, t.maxCount));
            e = "<br />" + e
        }
        return e
    }));
    (function () {
        var a = WH.ce("div");
        a.innerHTML = e;
        a.querySelectorAll('a[href*="/spell="]').forEach((function (e) {
            var t = e.dataset.wowhead || "";
            t = t.replace(/(^|&)i?lvl=\d+/g, "");
            if (n) {
                t += (t ? "&" : "") + "lvl=" + n
            }
            if (u.level) {
                t += (t ? "&" : "") + "ilvl=" + u.level
            }
            e.dataset.wowhead = t
        }));
        let i = WH.getPageData("item.sellprice." + t);
        let r = a.querySelector(".whtt-sellprice");
        if (i && r) {
            let e = r.firstChild;
            WH.ee(r);
            WH.ae(r, e);
            let t = i.itemLevel;
            let a = t[u.level] || t[Math.max.apply(null, Object.keys(t))];
            let n = i.quality[u.quality] || 0;
            let o = Math.floor(i.base * a * n);
            WH.ae(r, WH.Wow.buildMoney({copper: o}))
        }
        e = a.innerHTML
    })();
    return e
};
WH.setTooltipLevel = function (e, t, a) {
    var i = typeof e;
    if (i == "number") {
        var n = WH.getDataSource();
        if (n[e] && n[e][(a ? "buff_" : "tooltip_") + Locale.getName()]) {
            e = n[e][(a ? "buff_" : "tooltip_") + Locale.getName()]
        } else {
            return e
        }
    } else if (i != "string") {
        return e
    }
    e = e.replace(/<!--(gem|ee)(\d+):(\d+):(\d+):(\d+):(\d+):(\d+)-->([^<]*)<\/span>/gi, (function (e, a, i, n, r, o, s, l, c) {
        var u = {
            enchantment: c,
            scalinginfo: {
                scalingcategory: i,
                minlvlscaling: n,
                maxlvlscaling: r,
                damage1: o / 1e3,
                damage2: s / 1e3,
                damage3: l / 1e3
            }
        };
        var d = WH.scaleItemEnchantment(u, t);
        return "\x3c!--" + a + "--\x3e" + d + "</span>"
    }));
    var r = e.match(/<!--i?\?([0-9-:]*)-->/);
    var o;
    var s;
    if (r) {
        o = r[1].split(":").map(Number);
        t = Math.min(o[2], Math.max(o[1], t));
        s = o[4] || 0
    }
    if (s) {
        if (!e.match(/<!--pts\d:\d:\d+(?:\.\d+)?:\d+-->/g) && !(s < 0) && !a) {
            e = WH.setItemModifications(e, o[0], null, null, t);
            WH.updateItemStringLink.call(this)
        } else {
            if (s > 0) {
                if (!o[7] && WH.isSet("g_pageInfo") && g_pageInfo.type == 3 && g_items[g_pageInfo.typeId] && g_items[g_pageInfo.typeId].quality != 7) {
                    t = Math.min(g_items[g_pageInfo.typeId].reqlevel, t)
                }
                var l = {scadist: s};
                e = e.replace(/<!--cast-->\d+\.\d+/, "\x3c!--cast--\x3e" + l.cast);
                var c = /<!--pts([0-9-:]*)-->/g;
                var u = c.exec(e);
                l.effects = true;
                while (u != null) {
                    var d = u[1].split(":").map(Number);
                    var p = d[0];
                    var f = d[1];
                    var g = d[3];
                    if (g > 0) {
                        if (l[g] == undefined) {
                            l[g] = {};
                            l[g].effects = {}
                        }
                        WH.cO(l[g], WH.convertScalingSpell(l[g], g, p, f, t, t))
                    }
                    u = c.exec(e)
                }
                if (l.effects) {
                    var m = 5;
                    var h = m;
                    if (window.g_pageInfo && window.g_pageInfo.type == WH.Types.AZERITE_ESSENCE_POWER) {
                        h = WH.Wow.Item.INVENTORY_TYPE_NECK
                    }
                    e = WH.adjustSpellPoints(e, l, t, h);
                    if (this.modified) {
                        for (var H in this.modified[1]) {
                            var W = this.modified[1][H];
                            for (var v = 0; v < W.length; ++v) {
                                W[v][0] = WH.adjustSpellPoints(W[v][0], l, t, h);
                                W[v][1] = WH.adjustSpellPoints(W[v][1], l, t, h)
                            }
                        }
                    }
                }
            } else {
                var T = -s;
                var E = WH.getSpellScalingValue(T, t);
                for (var b = 0; b < 3; ++b) {
                    var y = o[5 + b] / 1e3;
                    e = e.replace(new RegExp("\x3c!--gem" + (b + 1) + "--\x3e(.+?)<"), "\x3c!--gem" + (b + 1) + "--\x3e" + Math.round(E * y) + "<")
                }
            }
        }
    }
    e = e.replace(/<!--ppl(\d+):(\d+):(\d+):(\d+):(\d+)(?::(1))?-->\s*\d+/gi, (function (e, a, i, n, r, o, s) {
        var l = s ? Math.ceil : Math.floor;
        return "\x3c!--ppl" + a + ":" + i + ":" + n + ":" + r + ":" + o + "--\x3e" + l(parseInt(r) + (Math.min(Math.max(t, i), n) - i) * o / 100)
    }));
    e = e.replace(/(<!--rtg%(\d+)-->)([\.0-9]+)%?/g, (function (a, i, n, r) {
        _ = e.match(new RegExp("\x3c!--rtg" + n + "--\x3e(\\d+)"));
        if (!_) {
            return a
        }
        return i + Math.round(WH.convertRatingToPercent(t, n, _[1]) * 100) / 100 + (n != 49 ? "%" : "")
    }));
    e = e.replace(/(<!--i?\?\d+:\d+:\d+:)\d+/g, "$1" + t);
    e = e.replace(/<!--lvl-->\d+/g, "\x3c!--lvl--\x3e" + t);
    return e
};
WH.updateTooltipSingular = function (e) {
    return e.replace(/(\d+)(\D*)<!--singular:(.*?):(.*?)-->.*?<!--singular-->/gi, (function (e, t, a, i, n) {
        return t + a + "\x3c!--singular:" + i + ":" + n + "--\x3e" + (parseInt(t) === 1 ? i : n) + "\x3c!--singular--\x3e"
    }))
};
WH.getSpellScalingValue = function (e, t) {
    var a = WH.convertScalingSpell ? WH.convertScalingSpell.SV : null;
    if (!a) {
        return 0
    }
    return a[t][e - 1]
};
WH.adjustSpellPoints = function (e, t, a, i) {
    var n = 1;
    if (a && i) {
        n = WH.getCombatRatingMult(a, i)
    }
    for (var r = 1; r <= 20; ++r) {
        e = e.replace(new RegExp("\x3c!--pts" + r + ":0:0:(\\d+)(:\\d+(?:\\.\\d+)?)?(:crm)?--\x3e(\x3c!--rtg\\d+--\x3e)?(.+?)<", "g"), (function (e, a, i, o, s) {
            var l = t[a] && t[a].hasOwnProperty("effects") ? t[a].effects[r] : t.effects[r];
            if (!l) {
                return e
            }
            var c = Math.round(l.avg * (o ? n : 1));
            return "\x3c!--pts" + r + ":0:0:" + a + (i || "") + (o || "") + "--\x3e" + (s ? s : "") + c + "<"
        }));
        e = e.replace(new RegExp("\x3c!--pts" + r + ":1:0:(\\d+)(:\\d+(?:\\.\\d+)?)?(:crm)?--\x3e(\x3c!--rtg\\d+--\x3e)?(.+?)<", "g"), (function (e, a, i, o, s) {
            var l = t[a] && t[a].hasOwnProperty("effects") ? t[a].effects[r] : t.effects[r];
            if (!l) {
                return e
            }
            var c = Math.round(l.min * (o ? n : 1));
            return "\x3c!--pts" + r + ":1:0:" + a + (i || "") + (o || "") + "--\x3e" + (s ? s : "") + c + "<"
        }));
        e = e.replace(new RegExp("\x3c!--pts" + r + ":2:0:(\\d+)(:\\d+(?:\\.\\d+)?)?(:crm)?--\x3e(\x3c!--rtg\\d+--\x3e)?(.+?)<", "g"), (function (e, a, i, o, s) {
            var l = t[a] && t[a].hasOwnProperty("effects") ? t[a].effects[r] : t.effects[r];
            if (!l) {
                return e
            }
            var c = Math.round(l.max * (o ? n : 1));
            return "\x3c!--pts" + r + ":2:0:" + a + (i || "") + (o || "") + "--\x3e" + (s ? s : "") + c + "<"
        }));
        e = e.replace(new RegExp("\x3c!--pts" + r + ":3:(\\d+(?:\\.\\d+)?):(\\d+)(:\\d+(?:\\.\\d+)?)?(:crm)?--\x3e(\x3c!--rtg\\d+--\x3e)?(.+?)<", "g"), (function (e, a, i, o, s, l) {
            var c = t[i] && t[i].hasOwnProperty("effects") ? t[i].effects[r] : t.effects[r];
            if (!c) {
                return e
            }
            var u = Math.round(c.avg * a * (s ? n : 1));
            return "\x3c!--pts" + r + ":3:" + a + ":" + i + (o || "") + (s || "") + "--\x3e" + (l ? l : "") + u + "<"
        }));
        e = e.replace(new RegExp("\x3c!--pts" + r + ":4:0:(\\d+)(:\\d+(?:\\.\\d+)?)?(:crm)?--\x3e(\x3c!--rtg\\d+--\x3e)?(.+?)<", "g"), (function (e, a, i, o, s) {
            var l = t[a] && t[a].hasOwnProperty("effects") ? t[a].effects[r] : t.effects[r];
            if (!l) {
                return e
            }
            var c = Math.round(l.pts * (o ? n : 1));
            return "\x3c!--pts" + r + ":4:0:" + a + (i || "") + (o || "") + "--\x3e" + (s ? s : "") + c + "<"
        }))
    }
    return e
};
WH.getStaminaRatingMult = function (e, t) {
    if (!WH.staminaFactor) {
        return 1
    }
    let a = 0;
    switch (t) {
        case 2:
        case 11:
            a = 3;
            break;
        case 12:
            a = 2;
            break;
        case 13:
        case 14:
        case 15:
        case 17:
        case 21:
        case 22:
        case 23:
        case 25:
        case 26:
            a = 1;
            break
    }
    let i = WH.findSparseKey(WH.staminaFactor, e);
    let n = WH.findSparseKey(WH.staminaFactor[i] || {}, a);
    return (WH.staminaFactor[i] || {})[n] || 1
};
WH.getCombatRatingMult = function (e, t) {
    if (!WH.convertRatingToPercent.RM) {
        return 1
    }
    let a = 0;
    switch (t) {
        case 2:
        case 11:
            a = 3;
            break;
        case 12:
            a = 2;
            break;
        case 13:
        case 14:
        case 15:
        case 17:
        case 21:
        case 22:
        case 23:
        case 25:
        case 26:
            a = 1;
            break
    }
    let i = WH.findSparseKey(WH.convertRatingToPercent.RM, e);
    let n = WH.findSparseKey(WH.convertRatingToPercent.RM[i] || {}, a);
    return (WH.convertRatingToPercent.RM[i] || {})[n] || 1
};
WH.roundArtifactPower = function (e) {
    var t = 1;
    if (e > 50) {
        t = 5
    }
    if (e > 1e3) {
        t = 25
    }
    if (e > 5e3) {
        t = 50
    }
    return WH.roundHalfEven(e / t) * t
};
WH.roundHalfEven = function (e) {
    if (Math.floor(e * 1e5) % 1e5 == 5e4) {
        var t = Math.floor(e);
        return t + t % 2
    }
    return Math.round(e)
};
WH.setTooltipSpells = function (e, t, a, i) {
    t = t || [];
    a = a || {};
    if (!t.length) {
        t = [0]
    } else {
        let e = window.g_pageInfo ? g_pageInfo["typeId"] : null;
        if (e) {
            let a = t.indexOf(parseInt(e));
            if (a !== -1) {
                t[a] = 0
            }
        }
    }
    if (i === undefined) {
        var n = function (e) {
            var t = [];
            if (e.hasOwnProperty("data")) {
                t.push(e.data)
            }
            for (var a = 0; a < e.children.length; a++) {
                t = t.concat(n(e.children[a]))
            }
            return t
        };
        for (let e in a) {
            if (!a.hasOwnProperty(e)) {
                continue
            }
            if (a[e].length < 2) {
                continue
            }
            for (var r = 0; r < a[e].length; r++) {
                a[e][r] = {data: a[e][r], children: []};
                var o = 0;
                for (var s = 0; s <= 1; s++) {
                    var l = -1;
                    while ((l = a[e][r].data[s].indexOf("\x3c!--sp" + e + "--\x3e", l + 1)) >= 0) {
                        o++
                    }
                }
                var c = r - o;
                if (c < 0) {
                    continue
                }
                while (o-- > 0) {
                    var u = a[e].splice(c, 1);
                    r--;
                    a[e][r].children.push(u[0])
                }
            }
            a[e] = n({children: a[e]})
        }
    }
    i = i || {};
    var d = function (e) {
        i[e] = (i[e] || 0) + 1;
        if (i[e] >= (a[e] || []).length) {
            i[e] = 0
        }
    };
    var p = [];
    var f = /<!--sp([0-9]+):[01]-->/g;
    var g;
    while (g = f.exec(e)) {
        var m = g[0];
        var h = g.index + m.length;
        var H = "\x3c!--sp" + g[1] + "--\x3e";
        var W = e.indexOf(H, h);
        if (W < 0) {
            WH.warn("Could not find closing end tag for tooltip spell.", H, e);
            return e
        }
        var v = new RegExp("\x3c!--sp" + g[1] + ":[01]--\x3e", "g");
        v.lastIndex = h;
        var T = v.exec(e);
        while (T && T.index < W) {
            W = e.indexOf(H, W + H.length);
            if (W < 0) {
                WH.warn("Could not find nested closing end tag for tooltip spell.", H, e);
                return e
            }
            T = v.exec(e)
        }
        p.push(e.substring(g.index, W + H.length));
        f.lastIndex = W + H.length
    }
    var E = 0;
    var b = /^(<!--sp([0-9]+):[01]-->).*(<!--sp\2-->)$/;
    for (var y = 0; y < p.length; ++y) {
        var I = p[y].match(b)[2];
        var S = WH.inArray(t, parseInt(I)) >= 0 ? 1 : 0;
        if (a[I] == null) {
            continue
        }
        if (i[I] == null) {
            i[I] = 0
        }
        var w = a[I][i[I]];
        if (w == null || w[S] == null) {
            continue
        }
        d(I);
        if (S && (g = w[2].match(/^(!?)(\d+)$/))) {
            if (g[1]) {
                if (WH.inArray(t, parseInt(g[2])) >= 0) {
                    S = 0
                }
            } else {
                t.push(parseInt(g[2]))
            }
        }
        var _ = w[S];
        _ = WH.setTooltipSpells(_, t, a, i);
        var A = "\x3c!--sp" + I + ":" + S + "--\x3e" + _ + "\x3c!--sp" + I + "--\x3e";
        e = e.substr(0, E) + e.substr(E).replace(p[y], A);
        E = e.indexOf(A, E) + A.length;
        if (S) {
            for (var M = y + 1; M < p.length; M++) {
                if (e.indexOf(p[M], E) !== E) {
                    break
                }
                g = p[M].match(b);
                A = g[1] + g[3];
                e = e.substr(0, E) + e.substr(E).replace(p[M], A);
                d(g[2]);
                E += A.length;
                y++
            }
        }
    }
    e = WH.Tooltip.evalFormulas(e);
    return e
};
WH.enhanceTooltip = function (e, t, a, i, n, r, o, s, l, c, u, d, p, f) {
    if ((!WH.applyStatModifications || !WH.applyStatModifications.ScalingData) && (f || s)) {
        g_itemScalingCallbacks.push(function (g) {
            return function () {
                var m = WH.enhanceTooltip.call(g, e, t, a, i, n, r, o, s, l, c, u, d, p, f);
                WH.updateTooltip.call(g, m)
            }
        }(this));
        return WH.TERMS.loading_ellipsis
    }
    var g = typeof e, m, h;
    var H = WH.getDataSource();
    var W = WH.isSet("g_pageInfo") ? g_pageInfo.type : null;
    h = WH.isSet("g_pageInfo") ? g_pageInfo.typeId : null;
    this._spellModifiers = r;
    if (g == "number") {
        h = e;
        var v = "tooltip_";
        if (n) v = "buff_";
        if (d) v = "tooltip_premium_";
        if (p) v = "text_";
        if (H[h] && H[h][v + Locale.getName()]) {
            e = H[h][v + Locale.getName()];
            m = H[h][(n ? "buff" : "") + "spells_" + Locale.getName()];
            this._rppmModList = H[h]["rppmmod"];
            if (m) {
                e = WH.setTooltipSpells(e, r, m)
            }
        } else {
            return e
        }
    } else if (g != "string") {
        return e
    }
    var T;
    if (a) {
        var E = WH.getGets();
        if (E.lvl) {
            e = WH.setTooltipLevel(e, E.lvl, n)
        }
        T = E.ilvl
    }
    let b = function () {
        let e = WH.parseQueryString(WH.getQueryString());
        if (!e["crafted-stats"]) {
            return []
        }
        return e["crafted-stats"].split(":").map((function (e) {
            return parseInt(e)
        })).filter((function (e) {
            return WH.Wow.Item.Stat.CRAFTING_STAT_TO.includes(e)
        }))
    };
    let y = b();
    if ((f || s || y.length) && h) {
        e = WH.setItemModifications(e, h, f, s, this._selectedLevel ? this._selectedLevel : null, T, y)
    }
    if (t) {
        e = e.replace(/\(([^\)]*?<!--lvl-->[^\(]*?)\)/gi, (function (e, t) {
            return '(<a href="javascript:" onmousedown="return false" class="tip" style="color: white; cursor: pointer" onclick="WH.staticTooltipLevelClick(this, null, 0)" onmouseover="WH.Tooltip.showAtCursor(event, \'<span class=\\\'q2\\\'>\' + WH.TERMS.clicktochangelevel_stc + \'</span>\')" onmousemove="WH.Tooltip.cursorUpdate(event)" onmouseout="WH.Tooltip.hide()">' + t + "</a>)"
        }));
        if (e.indexOf("\x3c!--artpow:") > 0) {
            if (!this.hasOwnProperty("_knowledgeLevel")) {
                var I = /(&|\?)artk=(\d+)/.exec(location.href);
                if (I && parseInt(I[2]) <= g_artifact_knowledge_max_level) {
                    this._knowledgeLevel = parseInt(I[2])
                }
            }
            var S = this._knowledgeLevel ? parseInt(this._knowledgeLevel) : 0;
            e = e.replace(/(<!--ndstart-->)?<!--ndend-->/i, (function (e, t) {
                return (t ? t + "<br />" : " ") + '<a href="javascript:" onmousedown="return false" class="tip" style="color: white; cursor: pointer" onclick="WH.staticTooltipKnowledgeLevelClick(this, null, ' + h + ')" onmouseover="WH.Tooltip.showAtCursor(event, \'<span class=\\\'q2\\\'>\' + WH.TERMS.clicktochangelevel_stc + \'</span>\')" onmousemove="WH.Tooltip.cursorUpdate(event)" onmouseout="WH.Tooltip.hide()">' + WH.sprintf(WH.TERMS.knowledge_format.replace("%d", "$1"), S) + "</a>"
            }));
            e = e.replace(/(<!--artpow:(\d+)-->)[\d\.\,]+/, (function (e, t, a) {
                return t + WH.numberLocaleFormat(WH.roundArtifactPower(parseInt(a) * WH.getArtifactKnowledgeMultiplier(S)))
            }))
        }
    }
    if (i && Slider) {
        var w = WH.groupSizeScalingShouldShow(h);
        if (n) {
            n.bufftip = this;
            if (w && WH.isSet("g_difficulties") && g_difficulties[w]) {
                e = WH.groupSizeScalingOnChange.call(n, this, g_difficulties[w].maxplayers, 1, true)
            }
        } else {
            var _ = new RegExp("\x3c!--" + (W && W == 3 ? "i" : "") + "\\?(\\d+):(\\d+):(\\d+):(\\d+)");
            var A = e.match(_);
            if (typeof A == "undefined" && W == 3) {
                _ = new RegExp("\x3c!--\\?(\\d+):(\\d+):(\\d+):(\\d+)");
                A = e.match(_)
            }
            if (!A && !WH.isRetailTree()) {
                _ = new RegExp("\x3c!--ppl(\\d+):(\\d+):(\\d+):(\\d+):(\\d+)");
                var M = e.match(_);
                if (M) {
                    A = [null, null, M[2], WH.maxLevel, WH.maxLevel]
                }
            }
            if (w && WH.isSet("g_difficulties") && g_difficulties[w]) {
                var L = WH.ce("label");
                L.innerHTML = WH.TERMS.difficulty + ": ";
                this._difficultyBtn = WH.ce("a");
                this._difficultyBtn.ttId = h;
                WH.difficultyBtnBuildMenu.call(this, h);
                Menu.add(this._difficultyBtn, this._difficultyBtn.menu);
                let t = WH.ge("dd" + h);
                WH.ae(t, L);
                WH.ae(t, this._difficultyBtn);
                t.style.display = "block";
                WH.difficultyBtnOnChange.call(this, H[h].initial_dd || w, H[h].initial_ddSize);
                e = WH.groupSizeScalingOnChange.call(this, this, g_difficulties[w].maxplayers, 0, true)
            } else if (A) {
                if (A[2] != A[3]) {
                    this.slider = Slider.init(i, {
                        maxValue: parseInt(A[3]),
                        minValue: Math.max(parseInt(A[2]), 1),
                        onMove: WH.tooltipSliderMove.bind(this),
                        title: WH.GlobalStrings.LEVEL
                    });
                    Slider.setValue(this.slider, parseInt(A[4]));
                    i.style.display = "block";
                    this.slider.onmouseover = function (e) {
                        WH.Tooltip.showAtCursor(e, WH.TERMS.dragtochangelevel_stc, "q2")
                    };
                    this.slider.onmousemove = WH.Tooltip.cursorUpdate;
                    this.slider.onmouseout = WH.Tooltip.hide;
                    WH.Tooltip.simple(Slider.getInput(this.slider), WH.TERMS.clicktochangelevel_stc, "q2")
                }
            }
        }
    }
    if (o && !o.dataset.initialized) {
        if (n && n.modified) {
            n.bufftip = this
        } else {
            let e = WH.getPageData("WH.Wow.Covenant.data");
            for (let t in m) {
                let a = Object.keys(e).find((a => e[a].spellId === parseInt(t)));
                if ((!WH.Gatherer.get(WH.Types.SPELL, t) || r.includes(t)) && !a) {
                    continue
                }
                let i = WH.Gatherer.get(WH.Types.SPELL, t);
                let n = i["name_" + Locale.getName()];
                let s = i["rank_" + Locale.getName()] || "";
                let l = s ? WH.term("parens_format", n, s) : n;
                let c = WH.ce("label");
                let u = WH.ce("input", {type: "checkbox", dataset: {spellId: t}});
                WH.ae(c, u);
                WH.aE(u, "click", WH.tooltipSpellsChange.bind(this));
                let d = WH.ce("a", undefined, WH.ct(l));
                if (a) {
                    d.classList.add("covenant-" + WH.Wow.Covenant.getSlug(a))
                } else {
                    d.href = WH.Entity.getUrl(WH.Types.SPELL, t, n);
                    WH.aE(d, "click", (function (e) {
                        e.preventDefault();
                        u.click()
                    }))
                }
                WH.ae(c, d);
                c.setAttribute("unselectable", "");
                WH.ae(o, c);
                WH.ae(o, WH.ce("br"))
            }
        }
        WH.onLoad((() => {
            let e = e => {
                let t = WH.qs(`.tooltip-options #ks${h} input[type="checkbox"][data-spell-id="${e}"]`);
                if (t) {
                    t.checked = true
                }
            };
            let t = WH.Url.parseQueryString(location.search);
            if (t.covenant) {
                let a = ((WH.getPageData("WH.Wow.Covenant.data") || {})[t.covenant] || {}).spellId;
                if (a) {
                    e(a)
                }
            }
            if (t.spellModifier) {
                t.spellModifier.split(":").forEach((t => {
                    e(t)
                }))
            }
            WH.tooltipSpellsChange.call(this)
        }));
        this.modified = [o, m, r];
        o.style.display = WH.DOM.isEmpty(o) ? "none" : "inline-block";
        o.dataset.initialized = "true"
    }
    if (u) {
        var M = e.match(/<!--rppm-->(\d+(?:\.\d+)?)<!--rppm-->/);
        if (M) {
            var R = $("#rppm" + h);
            if (this._rppmModList.hasOwnProperty(4)) {
                this._rppmModBase = parseFloat(M[1]);
                if (R.is(":empty")) {
                    this._rppmSpecModList = this._rppmModList[4];
                    this._rppmSpecModList.splice(0, 0, {spec: -1, modifiervalue: 0, filename: ""});
                    R.append(WH.getMajorHeading(WH.TERMS.realppmmodifiers, 2, 3));
                    for (var k in this._rppmSpecModList) {
                        var C = WowheadIcon.create(this._rppmSpecModList[k]["filename"], 0, null);
                        C.style.display = "inline-block";
                        C.style.verticalAlign = "middle";
                        var x = $('<input name="rppmmod" type="radio" id="rppm-' + k + '" />');
                        x.get(0).checked = this._rppmSpecModList[k]["spec"] == -1;
                        R.append(x).append(this._rppmSpecModList[k]["spec"] == -1 ? "" : C).append('<label for="rppm-' + k + '"> <a>' + (this._rppmSpecModList[k]["spec"] == -1 ? WH.TERMS.none : WH.Wow.PlayerClass.Specialization.getName(this._rppmSpecModList[k]["spec"])) + "</a></label>").append("<br />");
                        var N = this;
                        $("#rppm-" + k).change((function () {
                            WH.tooltipRPPMChange.call(this, N)
                        }))
                    }
                } else {
                    var O = this._rppmModBase;
                    var P = this._rppmSpecModList;
                    e = e.replace(/<!--rppm-->(\[?)(\d+(?:\.\d+)?)([^<]*)<!--rppm-->/, (function (e, t, a, i) {
                        return "\x3c!--rppm--\x3e" + t + (O * (1 + parseFloat(P[$('input[name="rppmmod"]:checked', R).attr("id").match(/\d+$/)[0]].modifiervalue))).toFixed(2) + i + "\x3c!--rppm--\x3e"
                    }))
                }
            }
            R.toggle(!R.is(":empty"));
            var D = "";
            if (this._rppmModList.hasOwnProperty(1)) {
                D += " + " + WH.Wow.Item.Stat.jsonToAbbr("hastertng")
            } else if (this._rppmModList.hasOwnProperty(2)) {
                D += " + " + WH.Wow.Item.Stat.jsonToAbbr("critstrkrtng")
            }
            if (g_pageInfo.type == 6 && this._rppmModList.hasOwnProperty(6)) {
                D += " + " + "Budget"
            }
            if (D.length > 0) {
                e = e.replace(/<!--rppm-->\[?(\d+(?:\.\d+)?)([^<]*)<!--rppm-->/, (function (e, t, a) {
                    return "\x3c!--rppm--\x3e[" + t + D + "]" + a + "\x3c!--rppm--\x3e"
                }))
            }
        }
    }
    if (c) {
        for (k = 1; k <= l; ++k) {
            $(c).append('<input type="checkbox" id="item-upgrade-' + k + '" />').append('<label for="item-upgrade-' + k + '"><a>' + WH.term("itemUpgrade_format", k) + "</a></label>").append("<br />");
            $("#item-upgrade-" + k).change(WH.upgradeItemTooltip.bind(this, c, k))
        }
        if (H[h] && H[h].hasOwnProperty("tooltip_" + Locale.getName() + "_pvp")) {
            $(c).append('<input type="checkbox" id="item-upgrade-pvp" />').append('<label for="item-upgrade-pvp"><a>' + WH.TERMS.pvpmode + "</a></label>").append("<br />");
            $("#item-upgrade-pvp").change(WH.upgradeItemTooltip.bind(this, c, "pvp"))
        }
        for (let e of WH.Timewalking.getConfigs()) {
            if (H[h] && H[h].hasOwnProperty("tooltip_" + Locale.getName() + "_" + e.stringId)) {
                $(c).append('<input type="checkbox" id="item-upgrade-' + e.stringId + '">').append('<label for="item-upgrade-' + e.stringId + '"><a>' + WH.TERMS.timewalking + WH.TERMS.wordspace_punct + WH.TERMS[e.termAbbrev] + "</a></label>").append("<br>");
                $("#item-upgrade-" + e.stringId).change(WH.upgradeItemTooltip.bind(this, c, e.stringId))
            }
        }
        $(c).toggle(!$(c).is(":empty"))
    }
    let B;
    if (W == 3) {
        var U = $("#cs" + h);
        if (U && WH.Wow.Item.tooltipHasSpecStats(e)) {
            if (!this._classSpecBtn) {
                var F = WH.ce("label");
                F.innerHTML = WH.TERMS.showingtooltipfor_stc + " ";
                this._classSpecBtn = WH.ce("a");
                this._classSpecBtn.ttId = h;
                WH.classSpecBtnBuildMenu.call(this, H[h].hasOwnProperty("validMenuSpecs") ? H[h].validMenuSpecs : false);
                Menu.add(this._classSpecBtn, this._classSpecBtn.menu);
                U.append(F).append(this._classSpecBtn).show()
            }
            B = WH.LocalStorage.fallbackGet("tooltips_class:spec");
            B = B ? B.split(":") : null;
            var q = /(&|\?)class=(\d+)/.exec(location.href);
            if (q) {
                B = [q[2], 0]
            }
            var G = /(&|\?)spec=(\d+)/.exec(location.href);
            var z, j;
            if (G) {
                z = G[2];
                j = WH.Wow.PlayerClass.getBySpec(z);
                if (j) {
                    B = [j, z]
                }
            }
            if (B && B.length == 2) {
                e = WH.classSpecBtnOnChange.call(this, B[0], B[1], e, true)
            } else {
                $(this._classSpecBtn).text(WH.isRetailTree() ? WH.TERMS.chooseaspec_stc : WH.TERMS.chooseAClass_stc)
            }
        }
    }
    if (H[h] && WH.bonusesBtnShouldShow(H[h].bonusesData)) {
        var V = $("#bs" + h);
        if (V && !this._bonusesBtn) {
            var K = WH.ce("label");
            K.innerHTML = WH.TERMS.itembonuses + ": ";
            this._bonusesBtn = WH.ce("a");
            this._bonusesBtn.ttId = h;
            this._bonusesBtn.menu = WH.bonusesBtnBuildMenu.call(this, H[h]);
            Menu.add(this._bonusesBtn, this._bonusesBtn.menu);
            $(this._bonusesBtn).text(WH.TERMS.selectbonus_stc);
            V.append(K).append(this._bonusesBtn).show();
            if (f !== "") {
                WH.bonusesBtnOnChange.call(this, f, true)
            }
        }
    }
    (function () {
        let e = WH.ge("craftedStatsSelector" + h);
        if (!H[h] || !e || e.dataset.initialized) {
            return
        }
        const t = this;
        let a = 0;
        let i;
        let n = function (e) {
            let t = b();
            let i = t.indexOf(e);
            if (i >= 0) {
                t.splice(i, 1)
            } else {
                t.push(e);
                t = t.slice(-1 * a)
            }
            WH.Url.replacePageQuery((function (e) {
                if (t.length) {
                    e["crafted-stats"] = t.join(":")
                } else {
                    delete e["crafted-stats"]
                }
            }));
            r();
            if (H[h]["tooltip_" + Locale.getName()]) {
                let e = this._bonusesBtn && this._bonusesBtn.selectedBonus ? this._bonusesBtn.selectedBonus : null;
                let t = WH.enhanceTooltip.call(this, h, true, true, false, null, this._spellModifiers, WH.ge("ks" + h), s, null, null, true, null, null, e);
                WH.updateTooltip.call(this, t)
            }
        };
        let r = function () {
            let e = "";
            let t = b();
            if (!t.length) {
                e = WH.TERMS.none
            } else {
                t.forEach((function (t) {
                    e += (e ? " + " : "") + WH.Wow.Item.Stat.jsonToDesc(WH.statToJson[t])
                }))
            }
            WH.st(i, e)
        };
        e.dataset.initialized = 1;
        let o = H[h].jsonequip && H[h].jsonequip.statsInfo || {};
        WH.Wow.Item.Stat.CRAFTING_STAT_FROM.forEach((function (e) {
            if (o.hasOwnProperty(e)) {
                a++
            }
        }));
        if (!a) {
            return
        }
        WH.displayDefault(e);
        WH.ae(e, WH.ce("label", {}, WH.ct(WH.TERMS.optionalReagentStats + WH.TERMS.colon_punct)));
        i = WH.ce("a", {}, WH.ct(WH.TERMS.none));
        WH.ae(e, i);
        let l = [];
        WH.Wow.Item.Stat.CRAFTING_STAT_TO.forEach((function (e) {
            l.push(Menu.createItem({
                crumb: e,
                label: WH.Wow.Item.Stat.jsonToDesc(WH.statToJson[e]),
                url: n.bind(t, e),
                options: {
                    checkedFunc: function (e) {
                        return b().includes(parseInt(e[Menu.ITEM_CRUMB]))
                    }
                }
            }))
        }));
        l.sort((function (e, t) {
            return e[Menu.ITEM_LABEL].localeCompare(t[Menu.ITEM_LABEL])
        }));
        Menu.add(i, l);
        r()
    }).call(this);
    let J = this.slider ? this.slider._max : WH.maxLevel;
    let Y = this._selectedLevel || J;
    let Q = B ? B[0] : WH.Wow.PlayerClass.WARRIOR;
    e = WH.addRatingPercent(e, Y, J, Q);
    if (W === WH.Types.ITEM) {
        WH.updateItemStringLink.call(this)
    }
    e = WH.updateTooltipSingular(e);
    return e
};
WH.addRatingPercent = function (e, t, a, i) {
    let n = WH.ce("div", {innerHTML: e});
    WH.qsa("span", n).forEach((function (e) {
        let n;
        let r;
        e.childNodes.forEach((function (e) {
            if (e.nodeType === Node.COMMENT_NODE) {
                let t = (e.nodeValue || "").match(/^rtg(\d+)$/);
                if (t) {
                    n = parseInt(t[1]);
                    r = e
                }
            }
        }));
        if (n === undefined) {
            return
        }
        let o = r.nextSibling.nodeValue.match(/(\d+)(.*)$/);
        if (!o) {
            return
        }
        let s = WH.qs("small.rating-percent");
        if (s) {
            WH.de(s)
        }
        let l = parseInt(o[0]);
        let c = o[2];
        let u = WH.convertRatingToPercent(t, n, l, i);
        let d = WH.TERMS ? WH.term("valueAtLevel_format", u.toFixed(2), t) : " (" + u.toFixed(2) + "% @ L" + t + ")";
        let p = r.nextSibling;
        let f = WH.ce("small", {className: "rating-percent"}, WH.ct(d));
        if (c === ".") {
            p.parentNode.insertBefore(WH.ct(l), p);
            p.parentNode.insertBefore(f, p);
            p.parentNode.insertBefore(WH.ct("."), p)
        } else {
            p.parentNode.insertBefore(WH.ce("span", null, WH.ct(l + c)), p);
            p.parentNode.insertBefore(f, p)
        }
        p.parentNode.removeChild(p);
        f.setAttribute("onclick", "WH.tooltipLevelPrompt(" + t + ", " + a + ");")
    }));
    return n.innerHTML
};
WH.tooltipLevelPrompt = function (e, t) {
    let a = 1;
    let i = prompt(WH.sprintf(WH.TERMS.ratinglevel_format, a, t), e.toString());
    if (i === null) {
        return
    }
    i = parseInt(i);
    if (i < a || i > t) {
        alert("Invalid value; must be between " + a + " and " + t + ".");
        return
    }
    let n = WH.qs(".wowhead-tooltip");
    if (n.slider) {
        Slider.setValue(n.slider, i)
    }
    WH.staticTooltipLevelClick(n, i, 1)
};
WH.groupSizeScalingShouldShow = function (e) {
    if (WH.isSet("g_difficulties") && WH.isSet("g_spells") && g_spells[e] && g_spells[e].difficulties && g_spells[e].difficulties.length > 0) {
        return g_spells[e].difficulties[0]
    }
    return false
};
WH.groupSizeScalingSliderMove = function (e, t, a) {
    var i = WH.getDataSource();
    var n = WH.isSet("g_pageInfo") ? g_pageInfo["typeId"] : null;
    if (!i[n]) {
        return
    }
    let r = this._difficultyBtn.selectedDD;
    let o = a.value;
    WH.Url.replacePageQuery((function (e) {
        if (r != WH.groupSizeScalingShouldShow(n) || o != g_difficulties[WH.groupSizeScalingShouldShow(n)].maxplayers) {
            e.dd = r;
            e.ddsize = o
        } else {
            delete e.dd;
            delete e.ddsize
        }
    }));
    WH.groupSizeScalingOnChange.call(this, this, a.value, 0);
    if (this.bufftip) {
        WH.groupSizeScalingOnChange.call(this, this.bufftip, a.value, 1)
    }
    WH.Tooltip.hide()
};
WH.groupSizeScalingOnChange = function (e, t, a, i) {
    const n = this;
    while (e.className.indexOf("tooltip") == -1) {
        e = e.parentNode
    }
    t = parseInt(t);
    if (isNaN(t)) {
        return
    }
    var r = WH.getDataSource();
    var o = WH.isSet("g_pageInfo") ? g_pageInfo["typeId"] : null;
    if (!r[o]) {
        return
    }
    var s = this._difficultyBtn.selectedDD;
    var l = Locale.getName();
    var c = "server_" + (a ? "buff_" : "tooltip_") + l;
    var u = "dd" + s + "ddsize" + t;
    WH.groupSizeScalingOnChange.lastCall = u;
    if (!r[o][c]) {
        r[o]["server_tooltip_" + l] = {};
        r[o]["server_buff_" + l] = {};
        var d = "dd" + r[o].initial_dd + "ddsize" + r[o].initial_ddSize;
        r[o]["server_tooltip_" + l][d] = r[o]["tooltip_" + l];
        r[o]["server_buff_" + l][d] = r[o]["buff_" + l]
    }
    if (r[o][c][u]) {
        var p = r[o][c][u];
        if (i) {
            return p
        }
        WH.updateTooltip.call(e, p);
        return
    }
    if (i) {
        return r[o][c.substr(7)]
    }
    if (a) {
        return
    }
    if (r[o][c].hasOwnProperty(u)) {
        return
    }
    r[o][c][u] = "";
    var f = WH.Entity.getUrl(WH.Types.SPELL, o) + "?dd=" + s + "&ddsize=" + t;
    if (WH.isBeta() || WH.isPtr()) {
        f += "&" + WH.getDataCacheVersion()
    }
    WH.xhrJsonRequest(f, (function (a) {
        if (!a) {
            return
        }
        r[o]["server_tooltip_" + l][u] = a["tooltip"];
        r[o]["server_buff_" + l][u] = a["buff"];
        if (WH.groupSizeScalingOnChange.lastCall === u) {
            WH.groupSizeScalingOnChange.call(n, e, t);
            if (n.bufftip) {
                WH.groupSizeScalingOnChange.call(n, n.bufftip, t, true)
            }
        }
    }))
};
WH.difficultyBtnBuildMenu = function (e) {
    var t = [];
    var a = g_spells[e];
    for (var i = 0; i < a.difficulties.length; ++i) {
        var n = a.difficulties[i];
        var r = [n, WH.Wow.Difficulty.getName(n), WH.difficultyBtnOnChange.bind(this, n, false)];
        t.push(r)
    }
    this._difficultyBtn.menu = t
};
WH.difficultyBtnOnChange = function (e, t) {
    this._difficultyBtn.selectedDD = e;
    $(this._difficultyBtn).text("");
    WH.arrayWalk(this._difficultyBtn.menu, (function (e) {
        e.checked = false
    }));
    var a = Menu.findItem(this._difficultyBtn.menu, [e]);
    a.checked = true;
    $(this._difficultyBtn).text(a[Menu.ITEM_LABEL]);
    var i = this._difficultyBtn.selectedPlayers || t;
    var n = g_difficulties[e].minplayers, r = g_difficulties[e].maxplayers, o = g_difficulties[e].maxplayers;
    if (i) {
        if (i > r) {
            o = r
        } else if (i < n) {
            o = n
        } else {
            o = i
        }
    }
    n = r;
    var s = $("#sl" + this._difficultyBtn.ttId);
    s.html("").hide();
    this.slider = null;
    if (n != r) {
        s.show();
        this.slider = Slider.init(s.get(0), {
            maxValue: parseInt(r),
            minValue: parseInt(n),
            onMove: WH.groupSizeScalingSliderMove.bind(this),
            title: WH.TERMS.players
        });
        Slider.setValue(this.slider, parseInt(o));
        this.slider.onmouseover = function (e) {
            WH.Tooltip.showAtCursor(e, WH.TERMS.dragtochangeplayers_stc, "q2")
        };
        this.slider.onmousemove = WH.Tooltip.cursorUpdate;
        this.slider.onmouseout = WH.Tooltip.hide;
        WH.Tooltip.simple(Slider.getInput(this.slider), WH.TERMS.clicktochangeplayers_stc, "q2")
    }
    WH.groupSizeScalingSliderMove.call(this, null, null, {value: o})
};
WH.classSpecBtnOnChange = function (e, t, a, i) {
    e = parseInt(e);
    t = t ? parseInt(t) : null;
    WH.ee(this._classSpecBtn);
    this._classSpecBtn.selectedSpec = t;
    let n = Menu.findItem(this._classSpecBtn.menu, [e, t]);
    if (n && n[Menu.ITEM_OPTIONS] && n[Menu.ITEM_OPTIONS].tinyIcon) {
        let e = n[Menu.ITEM_OPTIONS].tinyIcon;
        let t = WH.Icon.create(e, WH.Icon.SMALL, "javascript:");
        t.style.display = "inline-block";
        t.style.verticalAlign = "middle";
        WH.ae(this._classSpecBtn, t)
    }
    let r = WH.Wow.PlayerClass.Specialization.getName(t);
    WH.ae(this._classSpecBtn, WH.ce("span", undefined, WH.ct(" " + (!WH.isRetailTree() || !r ? WH.Wow.PlayerClass.getName(e) : WH.Strings.sprintf(WH.TERMS.specclass_format, r, WH.Wow.PlayerClass.getName(e))))));
    if (!i) {
        WH.LocalStorage.set("tooltips_class:spec", e + ":" + t)
    }
    var o = a ? a : this.innerHTML;
    o = o.replace(/<!--scstart(\d+):(\d+)--><span class="q(\d+)">(<!--asc\d+-->)?(.*?)<\/span><!--scend-->/i, (function (t, a, i, n, r, o) {
        n = 1;
        var s = a == 2 && (!g_classes_allowed_weapon[e] || WH.inArray(g_classes_allowed_weapon[e], i) == -1);
        var l = a == 4 && (!g_classes_allowed_armor[e] || WH.inArray(g_classes_allowed_armor[e], i) == -1);
        if (s || l) {
            n = 10
        }
        return "\x3c!--scstart" + a + ":" + i + '--\x3e<span class="q' + n + '">' + (r ? r : "") + o + "</span>\x3c!--scend--\x3e"
    }));
    if (WH.isRetailTree()) {
        o = o.replace(/<span[^>]*?><!--stat(\d+)-->([-+][\d\.,]+(?:-[\d\.,]+)?)(\D*?)<\/span>/gi, (function (a, i, n, r) {
            let o = WH.ce("div", {innerHTML: a});
            let s = WH.qs("span", o);
            s.classList.remove("q0", "q2");
            i = parseInt(i);
            if (i === 50) {
                s.classList.add("q2")
            }
            if (g_grayedOutStats[i] && g_grayedOutStats[i].indexOf(t) != -1) {
                s.classList.remove("q2");
                s.classList.add("q0")
            }
            let l = t ? WH.getStatForSpec(i, t) : WH.getStatForClass(i, e);
            if (l !== i && WH.statToJson[l]) {
                let e = WH.Wow.Item.Stat.jsonToName(WH.statToJson[l]);
                if (e) {
                    r = " " + e
                }
            }
            s.innerHTML = "\x3c!--stat" + i + "--\x3e";
            WH.ae(s, WH.ct(n + r));
            return s.outerHTML
        }));
        o = o.replace(/(<!--traitspecstart:(\d+)(?::(\d+))?-->)[\w\W]*?(<!--traitspecend-->)/g, (function (e, a, i, n, r) {
            var o = "";
            if (WH.isSet("g_pageInfo") && g_pageInfo.hasOwnProperty("typeId") && g_pageInfo.type == 3 && g_items.hasOwnProperty(g_pageInfo.typeId) && g_items[g_pageInfo.typeId].hasOwnProperty("affectsArtifactPowerTypesData") && g_items[g_pageInfo.typeId].affectsArtifactPowerTypesData.hasOwnProperty(i) && g_items[g_pageInfo.typeId].affectsArtifactPowerTypesData[i].hasOwnProperty(t)) {
                o = g_items[g_pageInfo.typeId].affectsArtifactPowerTypesData[i][t]
            } else if (n) {
                o = '<span style="color: #00FF00">' + WH.term("relicrank" + (n != 1 ? "s" : "") + "increase_format", n) + ": </span>" + WH.TERMS.relic_minortrait
            }
            return a + o + r
        }))
    }
    WH.Url.replacePageQuery((function (a) {
        if (e) {
            a["class"] = e
        } else {
            delete a["class"]
        }
        if (WH.isRetailTree() && t) {
            a.spec = t
        } else {
            delete a.spec
        }
    }));
    if (!a) {
        this.innerHTML = WH.Tooltip.evalFormulas(o)
    }
    return o
};
WH.classSpecBtnBuildMenu = function (e) {
    var t = [];
    if (!WH.isRetailTree()) {
        t.push([, WH.TERMS.chooseAClass_stc]);
        var a = Menu.findItem(mn_spells, [7]);
        t = t.concat($.extend(true, [], Menu.getSubmenu(a)))
    } else {
        t.push([, WH.TERMS.chooseaspec_stc]);
        var i = Menu.findItem(mn_spells, [-12]);
        t = t.concat($.extend(true, [], Menu.getSubmenu(i)))
    }
    for (var n in g_chr_specs_by_class) {
        var r = g_chr_specs_by_class[n];
        for (var o in t) {
            var s = t[o];
            if (s[Menu.ITEM_CRUMB] == n) {
                if (!WH.isRetailTree()) {
                    s[Menu.ITEM_URL] = WH.classSpecBtnOnChange.bind(this, n, 0, false)
                } else {
                    if (s[Menu.ITEM_URL]) {
                        s[Menu.ITEM_URL] = null
                    }
                    for (var l = 0, c = r.length; l < c; l++) {
                        var u = Menu.getSubmenu(t[o]);
                        for (var d = 0, p = u.length; d < p; d++) {
                            var f = u[d];
                            if (f[Menu.ITEM_CRUMB] == r[l]) {
                                if (e && WH.inArray(e, r[l]) < 0) {
                                    delete f[Menu.ITEM_OPTIONS].tinyIcon;
                                    f[Menu.ITEM_OPTIONS].className = "q0";
                                    f[Menu.ITEM_URL] = "javascript:"
                                } else {
                                    f[Menu.ITEM_URL] = WH.classSpecBtnOnChange.bind(this, n, r[l], false)
                                }
                                break
                            }
                        }
                    }
                }
                break
            }
        }
    }
    this._classSpecBtn.menu = t
};
WH.getStatForClass = function (e, t) {
    let a = undefined;
    let i = WH.Wow.PlayerClass.Specialization.getByClass(t) || [];
    for (let t = 0, n; n = i[t]; t++) {
        let t = WH.getStatForSpec(e, n);
        if (a === undefined) {
            a = t
        } else if (a !== t) {
            a = null;
            break
        }
    }
    return a
};
WH.getStatForSpec = function (e, t) {
    var a = 3;
    var i = 4;
    var n = 5;
    var r = 71;
    var o = 72;
    var s = 73;
    var l = 74;
    var c;
    var u;
    var d = g_specPrimaryStatOrders[t];
    var p = g_specPrimaryStatOrders[t].length;
    if (e === r) {
        u = 0;
        if (!p) {
            return n
        }
        while (1) {
            c = d[u];
            if (c >= a && c <= n) {
                break
            }
            u++;
            if (u >= p) {
                return n
            }
        }
    } else {
        if (e !== o) {
            if (e !== s) {
                if (e !== l) {
                    return e
                }
                u = 0;
                if (p) {
                    while (1) {
                        c = d[u];
                        if (c >= i && c <= n) {
                            break
                        }
                        u++;
                        if (u >= p) {
                            return n
                        }
                    }
                    return c
                }
                return n
            }
            u = 0;
            if (p) {
                while (1) {
                    c = d[u];
                    if (d[u] === a) {
                        break
                    }
                    if (d[u] === n) {
                        break
                    }
                    u++;
                    if (u >= p) {
                        return n
                    }
                }
                return c
            }
            return n
        }
        u = 0;
        if (!p) {
            return a
        }
        while (1) {
            c = d[u];
            if (c >= a && c <= i) {
                break
            }
            u++;
            if (u >= p) {
                return a
            }
        }
    }
    return c
};
WH.bonusesBtnShouldShow = function (e) {
    for (var t in e) {
        if (e.hasOwnProperty(t)) {
            return true
        }
    }
    return false
};
WH.bonusesBtnBuildMenu = function (e) {
    let t = [];
    let a = e.bonusesData;
    if (a) {
        for (let i in a) {
            if (!a.hasOwnProperty(i)) {
                continue
            }
            let n = a[i].groupedUpgrade;
            let r = WH.getItemBonusName.call(this, i, e);
            let o = Menu.createItem({
                crumb: i,
                label: r,
                url: WH.bonusesBtnOnChange.bind(this, (n ? "u:" : "") + i, false)
            });
            if (typeof n == "undefined") {
                for (let t in a[i].sub) {
                    if (!a[i].sub.hasOwnProperty(t)) {
                        continue
                    }
                    n = a[i].sub[t].groupedUpgrade;
                    r = WH.getItemBonusName.call(this, t, e, i);
                    if (r === "???") {
                        continue
                    }
                    let s = Menu.createItem({
                        crumb: t,
                        label: r,
                        url: WH.bonusesBtnOnChange.bind(this, i + ":" + (n ? "u:" : "") + t, false, true)
                    });
                    Menu.addToSubmenu(o, s)
                }
            }
            let s = Menu.getSubmenu(o);
            if (s) {
                s.sort((function (e, t) {
                    let a = WH.getItemBonusChanceType(e[Menu.ITEM_CRUMB]);
                    let i = WH.getItemBonusChanceType(t[Menu.ITEM_CRUMB]);
                    return WH.stringCompare(a, i) || WH.stringCompare(e[Menu.ITEM_LABEL], t[Menu.ITEM_LABEL])
                }));
                let e = [];
                let t = 0;
                for (let n = 0; n < s.length; ++n) {
                    let r = s[n][Menu.ITEM_CRUMB];
                    if (r && a[i].sub[r].type !== t) {
                        t = a[i].sub[r].type;
                        let o = WH.TERMS.unknown;
                        switch (t) {
                            case 1:
                                o = WH.TERMS.upgrades;
                                break;
                            case 2:
                                o = WH.TERMS.stats;
                                break;
                            case 4:
                                o = WH.TERMS.sockets;
                                break;
                            default:
                                break
                        }
                        e.push({index: n, name: o})
                    }
                }
                for (let t = 0; t < e.length; ++t) {
                    let a = e[t].index + t;
                    let i = Menu.createHeading({label: e[t].name});
                    s.splice(a, 0, i)
                }
            }
            t.push(o)
        }
        let i = {};
        for (let e = 0, a; a = t[e]; e++) {
            if (i.hasOwnProperty(a[Menu.ITEM_LABEL])) {
                let e = ++i[a[Menu.ITEM_LABEL]];
                a[Menu.ITEM_LABEL] = WH.term("parens_format", a[Menu.ITEM_LABEL], e)
            } else {
                i[a[Menu.ITEM_LABEL]] = 1
            }
        }
        t.sort((function (e, t) {
            return WH.stringCompare(e[Menu.ITEM_LABEL].innerText || e[Menu.ITEM_LABEL], t[Menu.ITEM_LABEL].innerText || t[Menu.ITEM_LABEL])
        }))
    }
    let i = [Menu.createHeading({label: WH.TERMS.selectbonus_stc})];
    if (t.length > 0) {
        i = i.concat(t)
    }
    return i
};
WH.getItemBonusChanceType = function (e) {
    var t = 0;
    if (e > 0 && WH.isSet("g_itembonuses") && g_itembonuses && g_itembonuses[e]) {
        var a = g_itembonuses[e];
        for (var i = 0; i < a.length; ++i) {
            var n = a[i];
            var r = 0;
            switch (n[0]) {
                case 1:
                case 3:
                case 4:
                case 5:
                case 11:
                    r = 1;
                    break;
                case 2:
                    r = 2;
                    break;
                case 6:
                    r = 4;
                    break;
                default:
                    break
            }
            if (r && (!t || r < t)) {
                t = r
            }
        }
    }
    return t
};
WH.getItemBonusUpgradeType = function (e) {
    if (e > 0 && WH.isSet("g_itembonuses") && g_itembonuses && g_itembonuses[e]) {
        var t = g_itembonuses[e];
        for (var a = 0; a < t.length; ++a) {
            var i = t[a];
            switch (i[0]) {
                case 3:
                case 4:
                case 5:
                case 11:
                    return 1 << i[0];
                default:
                    break
            }
        }
    }
    return 0
};
WH.getItemBonusName = function (e, t, a) {
    var i = "";
    var n = t.level;
    let r = false;
    if (a && WH.isSet("g_itembonuses") && a > 0 && g_itembonuses[a]) {
        for (var o = 0; o < g_itembonuses[a].length; ++o) {
            var s = g_itembonuses[a][o];
            if (s[0] == 1) {
                n += s[1]
            }
        }
    }
    if (WH.isSet("g_itembonuses") && e > 0 && g_itembonuses[e]) {
        var l = g_itembonuses[e].slice();
        l.sort((function (e, t) {
            return e[0] - t[0]
        }));
        var c = "";
        var u = "";
        let a = "";
        let m = "";
        let h = "";
        for (var o = 0; o < l.length; ++o) {
            var s = l[o];
            switch (s[0]) {
                case 1:
                    c = WH.TERMS.itemlevel + " " + (n + s[1]);
                    break;
                case 2:
                    i += (i ? " / " : "") + (WH.statToJson[s[1]] && WH.Wow.Item.Stat.jsonToName(WH.statToJson[s[1]]) || "Unknown stat");
                    if (s[1] == 23) {
                        i += " " + s[2];
                        r = true
                    }
                    break;
                case 3:
                    u = s[1];
                    break;
                case 4:
                    a = WH.Wow.Item.getNameDescription(s[1]) || a;
                    break;
                case 5:
                    m = WH.Wow.Item.getNameDescription(s[1]) || m;
                    break;
                case 6:
                    var d = s[2];
                    i += (i ? " / " : "") + s[1] + " " + (g_socket_names[d] || (g_gem_types[d] ? WH.sprintf(WH.TERMS.emptyrelicslot_format.replace("%s", "$1"), g_gem_types[d]) : "Unknown Socket"));
                    break;
                case 8:
                    i += (i ? " / " : "") + WH.sprintf(WH.TERMS.requireslevel_format.replace("%s", "$1"), t.reqlevel + s[1]);
                    break;
                case 11:
                    if (t.heirloombonuses) {
                        var p = "?";
                        for (var f = 0, g; g = t.heirloombonuses[f]; f++) {
                            if (parseInt(e) === g) {
                                p = f + 1;
                                break
                            }
                        }
                        i += (i ? " / " : "") + WH.sprintf(WH.TERMS.heirloomupgradejs_format, p)
                    }
                    break;
                case 13:
                    c = WH.TERMS.scaleswithlevel_stc;
                    break;
                case 14:
                    if (t.actualBonusLevels && t.actualBonusLevels[e]) {
                        c = WH.TERMS.itemlevel + " " + t.actualBonusLevels[e] + "+"
                    } else {
                        c = WH.TERMS.itemlevel + " " + n + "+"
                    }
                    break;
                case 23:
                    if (s[1] > 0) {
                        h = WH.Tooltip.ITEM_EFFECT_NAMES[s[1]] || ""
                    }
                    break;
                case 34:
                    let o = WH.getPageData("wow.item.bonuses.upgrades") || {};
                    if (o[e]) {
                        h = WH.Strings.sprintf(WH.GlobalStrings.ITEM_UPGRADE_TOOLTIP_FORMAT, o[e][0], o[e][1])
                    }
                    break;
                default:
                    break
            }
        }
        if (r && !c && n) {
            c = WH.TERMS.itemlevel + WH.TERMS.wordspace_punct + n
        }
        if (c) {
            i = i ? c + " / " + i : c
        }
        a += m ? WH.TERMS.wordspace_punct + m : "";
        i += a ? " / " + a : "";
        if (h && !r) {
            i = h + (i ? " / " + i : "")
        }
        i += u && t.quality != u ? " / " + WH.Wow.Item.getQualityName(u) : "";
        if (i.substr(0, 3) == " / ") {
            i = i.substr(3)
        }
    } else if (e == "0") {
        i = WH.TERMS.normal
    }
    return i ? i : a ? WH.TERMS.openparenthesis_punct + e + WH.TERMS.closedparenthesis_punct : WH.TERMS.normal
};
WH.bonusesBtnGetContextBonusId = function (e) {
    let t = 0;
    let a = WH.getPageData("wow.item.bonuses.listGroup");
    if (e && e.length) {
        for (let i = 0; i < e.length; ++i) {
            let n = parseInt(e[i]);
            if (window.g_itembonuses && g_itembonuses["-1"] && g_itembonuses["-1"].includes(n) || a !== null && a.includes(n)) {
                t = e[i];
                break
            }
        }
    }
    return t
};
WH.bonusesBtnIsComboValid = function (e, t, a) {
    if (!e[t] || !e[t].sub) {
        return false
    }
    var i = e[t].sub;
    var n = 32768;
    var r = 32768;
    for (var o in a) {
        var s = a[o];
        if (s != t) {
            if (i[s]) {
                if ((n & i[s].type) == 1) {
                } else if (n & i[s].type) {
                    n = false;
                    break
                } else {
                    n |= i[s].type
                }
                if (r & i[s].upgradeType) {
                    r = false;
                    break
                } else {
                    r |= i[s].upgradeType
                }
            } else {
                n = false;
                break
            }
        }
    }
    return n && r
};
WH.bonusesGetItem = function () {
    var e = WH.getDataSource();
    var t = this._bonusesBtn.ttId;
    return e[t]
};
WH.bonusesGetDefaultAdjustmentBonus = function (e) {
    var t = WH.bonusesGetItem.call(this);
    var a = WH.bonusesBtnGetContextBonusId(e);
    if (t.defaultAdjustmentBonuses[a]) {
        return t.defaultAdjustmentBonuses[a].toString()
    }
    return null
};
WH.bonusesBtnOnChange = function (e, t, a) {
    var i = WH.getDataSource();
    var n = this._bonusesBtn.ttId;
    var r = i[n].bonusesData;
    if (a === true) {
        var o = e.split(":");
        var s = 0;
        var l = o.indexOf("u");
        if (l != -1) {
            s = o[l + 1];
            o.splice(l, 1)
        }
        var c = o[0];
        var u = !Menu.findItem(this._bonusesBtn.menu, o).checked;
        var d = 0;
        var p = [];
        WH.arrayWalk(this._bonusesBtn.menu, (function (e) {
            if (e.checked) {
                d = e[Menu.ITEM_CRUMB];
                var t = Menu.getSubmenu(e);
                if (t) {
                    WH.arrayWalk(t, (function (e) {
                        if (e[Menu.ITEM_CRUMB] && e.checked) {
                            p.push(e[Menu.ITEM_CRUMB]);
                            if (d == c && r[d].sub[e[Menu.ITEM_CRUMB]].groupedUpgrade && !s) {
                                s = e[Menu.ITEM_CRUMB]
                            }
                        }
                    }))
                }
            }
        }));
        var f;
        if (d == c) {
            if (u) {
                f = p.concat(o)
            } else {
                p.splice(p.indexOf(o[1]), 1);
                f = p.concat([c])
            }
        } else {
            f = o
        }
        f.sort((function (e, t) {
            return e - t
        }));
        if (!WH.bonusesBtnIsComboValid(r, c, f)) {
            f = o;
            var g = r[c].sub[o[1]].type;
            var m = r[c].sub[o[1]].upgradeType;
            for (var h = 0; h < p.length; ++h) {
                if (g != r[c].sub[p[h]].type) {
                    f.push(p[h])
                } else if (m != r[c].sub[p[h]].upgradeType) {
                    f.push(p[h])
                }
            }
            f.sort((function (e, t) {
                return e - t
            }))
        }
        if (s) {
            var H = f.indexOf(s);
            if (H != -1) {
                f.splice(f.indexOf(s), 0, "u")
            }
        }
        e = f.join(":").replace(/^0:/, "")
    }
    this._bonusesBtn.selectedBonus = e;
    var W = this._bonusesBtn.selectedBonus.split(":");
    var v = WH.bonusesGetDefaultAdjustmentBonus.call(this, W);
    if (v != null) {
        var T = false;
        for (var h in W) {
            var E = W[h];
            if (1372 <= E && E <= 1672) {
                T = true
            }
        }
        if (!T) {
            W.push(v);
            this._bonusesBtn.selectedBonus = W.join(":")
        }
    }
    var l = W.indexOf("u");
    if (l != -1) {
        W.splice(l, 1)
    }
    var b = [];
    for (var h = 0; h < this._bonusesBtn.menu.length; h++) {
        let e = this._bonusesBtn.menu[h][Menu.ITEM_CRUMB];
        if (e && b.indexOf(e) < 0) {
            b.push(e)
        }
    }
    W.sort((function (e, t) {
        return (b.indexOf(e) < 0 ? 1 : -1) - (b.indexOf(t) < 0 ? 1 : -1)
    }));
    $(this._bonusesBtn).html("");
    var y = WH.bonusesBtnGetContextBonusId(W);
    WH.arrayWalk(this._bonusesBtn.menu, (function (e) {
        e.checked = e[Menu.ITEM_CRUMB] == y;
        var t = Menu.getSubmenu(e);
        if (t) {
            WH.arrayWalk(t, (function (t) {
                if (t[Menu.ITEM_CRUMB]) {
                    t.checked = e.checked && W.indexOf(t[Menu.ITEM_CRUMB]) != -1;
                    if (t.$a) {
                        t[Menu.ITEM_OPTIONS] = null;
                        Menu.updateItem(t)
                    }
                }
            }))
        }
    }));
    var I = Menu.findItem(this._bonusesBtn.menu, [y]);
    if (I) {
        var S = Menu.getSubmenu(I);
        if (S) {
            WH.arrayWalk(S, (function (e) {
                if (e[Menu.ITEM_CRUMB]) {
                    var t = W;
                    if (W.indexOf(e[Menu.ITEM_CRUMB]) == -1) {
                        t = t.concat([e[Menu.ITEM_CRUMB]])
                    }
                    t.sort((function (e, t) {
                        return e - t
                    }));
                    if (!WH.bonusesBtnIsComboValid(r, y, t) && W.indexOf(v) == -1) {
                        e[Menu.ITEM_OPTIONS] = {class: "q0"}
                    } else {
                        e[Menu.ITEM_OPTIONS] = {}
                    }
                    Menu.updateItem(e)
                }
            }))
        }
    }
    let w = I && I[Menu.ITEM_LABEL] || WH.getItemBonusName.call(this, y, i[n]);
    for (var h = 0; h < W.length; ++h) {
        if (W[h] != y && W[h] != v) {
            w += " + " + WH.getItemBonusName.call(this, W[h], i[n], y)
        }
    }
    $(this._bonusesBtn).append(w);
    var _ = 0;
    if (WH.isSet("g_itembonuses") && g_items && g_items[n]) {
        for (var h in W) {
            var A = W[h];
            if (g_itembonuses[A]) {
                for (var M = 0; M < g_itembonuses[A].length; ++M) {
                    var L = g_itembonuses[A][M];
                    if (L[0] == 7 && g_items[n].appearances && g_items[n].appearances[L[1]]) {
                        _ = g_items[n].appearances[L[1]][0];
                        break
                    }
                }
            }
        }
    }
    var R = $("#e8c7e052e3e0");
    if (R.length > 0) {
        var k = R.get(0).attributes.onclick.value;
        var C = new RegExp("\\(this, " + n + ", \\[[^\\]]*?],");
        if (C.test(k)) {
            var x = [];
            for (var N in W) {
                var O = W[N];
                if (O == 0) {
                    x.push(O);
                    continue
                }
                var P = WH.isSet("g_itembonuses") && g_itembonuses[O] ? g_itembonuses[O] : [];
                for (var D in P) {
                    if (!P.hasOwnProperty(D)) {
                        continue
                    }
                    var B = P[D][0];
                    var U = P[D][1];
                    if (WH.inArray([1, 2, 6, 14], B) != -1) {
                        if (B == 2 && WH.inArray([61, 62, 63, 64, 66], U) != -1) {
                            continue
                        }
                        x.push(O)
                    }
                }
            }
            R.get(0).attributes.onclick.value = k.replace(C, "(this, " + n + ", [" + x.join(",") + "],")
        }
    }
    var F = $("#ic" + n);
    if (F.length > 0 && g_items) {
        var q = g_items.getIcon(n, W);
        if (q) {
            F[0].removeChild(F[0].firstChild);
            F[0].appendChild(WowheadIcon.create(q, 2))
        }
    }
    var G = $("#wh-mv-view-in-3d-button")[0];
    if (G) {
        if (!G.dataset.mvDisplayIdOrig && G.dataset.mvDisplayId) {
            G.dataset.mvDisplayIdOrig = G.dataset.mvDisplayId
        }
        if (!_ && G.dataset.mvDisplayIdOrig) {
            _ = G.dataset.mvDisplayIdOrig
        }
        if (_) {
            let e = WH.Gatherer.get(parseInt(G.dataset.mvType), parseInt(G.dataset.mvTypeId));
            let t = e && e.jsonequip && e.jsonequip.races;
            let a = WH.Wow.Models.getRaceIdFromMask(t);
            if (e.classs !== WH.Wow.Item.CLASS_ARMOR) {
                a = undefined
            }
            G.attributes.onclick.value = G.attributes.onclick.value.replace(/"displayId":\d+/, '"displayId":' + _);
            G.dataset.mvDisplayId = _;
            let i = WH.ge("sticky-screenshot-model-substitute");
            if (i) {
                i.src = WH.Wow.Item.getThumbUrl(parseInt(_), a)
            }
        }
    }
    let z = this._bonusesBtn.selectedBonus.replace(/u:/, "");
    WH.Url.replacePageQuery((function (e) {
        if (z) {
            e.bonus = z
        } else {
            delete e.bonus
        }
    }));
    WH.updateItemStringLink.call(this);
    if (!t && i[n]["tooltip_" + Locale.getName()]) {
        var j = WH.ge("sl" + n);
        j.innerHTML = "";
        this.slider = null;
        var V = WH.enhanceTooltip.call(this, n, true, true, j, null, this._spellModifiers, WH.ge("ks" + n), this._selectedUpgrade, null, null, true, null, null, this._bonusesBtn.selectedBonus);
        WH.updateTooltip.call(this, V)
    }
};
WH.updateItemStringLink = function () {
    var e = WH.getDataSource();
    var t = WH.isSet("g_pageInfo") ? g_pageInfo["typeId"] : null;
    if (e[t]) {
        var a = "";
        var i = [];
        if (this._bonusesBtn && this._bonusesBtn.selectedBonus) {
            a = this._bonusesBtn.selectedBonus.replace(/u:/, "");
            i = a.split(":")
        }
        var n = typeof this._selectedUpgrade == "number" ? this._selectedUpgrade : 0;
        var r = e[t].upgradeData.length > 0 ? e[t].upgradeData[n].id : "";
        var o = this._selectedLevel ? this._selectedLevel : WH.maxLevel;
        var s = this._knowledgeLevel ? this._knowledgeLevel : 0;
        var l = this._classSpecBtn && this._classSpecBtn.selectedSpec ? this._classSpecBtn.selectedSpec : "";
        var c = 0;
        var u = "";
        if (r) {
            c |= 4;
            u = (u ? ":" : "") + r
        } else if (i.length && g_itembonuses) {
            e:for (var d = 0, p; p = i[d]; d++) {
                if (!g_itembonuses[p]) {
                    continue
                }
                for (var f = 0, g; g = g_itembonuses[p][f]; f++) {
                    if (g[0] == 11 || g[0] == 13) {
                        c |= 512;
                        u = (u ? ":" : "") + o;
                        break e
                    }
                }
            }
        }
        if (s) {
            c |= 8388608;
            u = (u ? ":" : "") + (s + 1)
        }
        var m = "" + (c ? c : "") + "::" + (i.length ? i.length + ":" : "") + a + ":" + u;
        var h = WH.ge("open-links-button");
        if (h) {
            var H = {
                type: 3,
                typeId: t,
                linkColor: "ff" + WH.Wow.Item.getQualityColor(e[t].quality, true).replace(/^#/, ""),
                linkId: "item:" + t + "::::::::" + o + ":" + l + ":" + m,
                linkName: e[t]["name_" + Locale.getName()],
                bonuses: i,
                slot: e[t].slot
            };
            if (o != WH.maxLevel) {
                H.lvl = o
            }
            if (l) {
                H.spec = l
            }
            if (sliderControl = WH.ge("sl" + t)) {
                H.dropLevel = $(sliderControl).find("input").val()
            }
            h.onclick = WH.Links.show.bind(WH.Links, h, H)
        }
    }
};
WH.upgradeItemTooltip = function (e, t) {
    var a = WH.getDataSource();
    var i = g_pageInfo["typeId"];
    if (a[i]) {
        var n = $("#" + e.id + " > input");
        var r = null;
        if (typeof t != "number") {
            n.each((function (e, a) {
                if (a.id.indexOf(t) != -1) {
                    r = a;
                    return false
                }
            }))
        } else {
            r = n.get(t - 1)
        }
        var o = r.checked;
        n.each((function (e, t) {
            t.checked = false
        }));
        r.checked = o;
        if (!o) {
            t = null
        }
        this._selectedUpgrade = t;
        WH.updateItemStringLink.call(this);
        if (a[i]["tooltip_" + Locale.getName()]) {
            var s = this._bonusesBtn && this._bonusesBtn.selectedBonus ? this._bonusesBtn.selectedBonus : null;
            var l = WH.enhanceTooltip.call(this, i, true, true, false, null, this._spellModifiers, WH.ge("ks" + i), t, null, null, true, null, null, s);
            WH.updateTooltip.call(this, l)
        }
    }
};
WH.updateTooltip = function (e) {
    e = WH.updateTooltipSingular(e);
    if (this.classList.contains("partial-sub-tooltip")) {
        this.innerHTML = WH.Tooltip.evalFormulas(e);
        return
    }
    this.innerHTML = "<table><tr><td>" + WH.Tooltip.evalFormulas(e) + '</td><th style="background-position: top right"></th></tr><tr><th style="background-position: bottom left"></th><th style="background-position: bottom right"></th></tr></table>';
    WH.Tooltip.finalizeSizeAndReveal(this)
};
WH.staticTooltipLevelClick = function (e, t, a, i) {
    while (e.className.indexOf("tooltip") == -1) {
        e = e.parentNode
    }
    var n = e.innerHTML;
    var r = n.match(/<!--i\?(\d+):(\d+):(\d+):(\d+)/);
    if (!r) {
        r = n.match(/<!--\?(\d+):(\d+):(\d+):(\d+)/)
    }
    if (!r && !WH.isRetailTree()) {
        r = n.match(/<!--ppl(\d+):(\d+):(\d+):(\d+):(\d+)/);
        if (r) {
            r = [null, r[1], r[2], WH.maxLevel, 0]
        }
    }
    if (!r) {
        return
    }
    var o = parseInt(r[1]), s = parseInt(r[2]), l = parseInt(r[3]), c = parseInt(r[4]);
    if (s >= l) {
        return
    }
    if (isNaN(t)) {
        t = prompt(WH.sprintf(WH.TERMS.ratinglevel_format, s, l), c)
    }
    t = parseInt(t);
    if (isNaN(t)) {
        return
    }
    if (t == c || t < s || t > l) {
        return
    }
    e._selectedLevel = t;
    var u = WH.getDataSource();
    r = WH.setTooltipLevel.bind(e, u[o][(i ? "buff_" : "tooltip_") + Locale.getName()], t, i)();
    var d = e._bonusesBtn && e._bonusesBtn.selectedBonus ? e._bonusesBtn.selectedBonus : null;
    var p = e._selectedUpgrade ? e._selectedUpgrade : 0;
    r = WH.enhanceTooltip.call(e, r, true, null, null, null, null, null, p, null, null, null, null, null, d);
    WH.updateTooltip.call(e, r);
    if (e.slider && !a) {
        Slider.setValue(e.slider, t)
    }
    if (!i) {
        WH.tooltipSpellsChange.bind(e)()
    }
};
WH.staticTooltipKnowledgeLevelClick = function (e, t, a) {
    while (e.className.indexOf("tooltip") == -1) {
        e = e.parentNode
    }
    var i = e.innerHTML;
    if (isNaN(t)) {
        WH.Tooltip.hide();
        t = prompt(WH.sprintf(WH.TERMS.ratinglevel_format, 0, g_artifact_knowledge_max_level), e._knowledgeLevel ? e._knowledgeLevel : 0)
    }
    t = parseInt(t);
    if (isNaN(t)) {
        return
    }
    if (t < 0 || t > g_artifact_knowledge_max_level) {
        return
    }
    e._knowledgeLevel = t;
    WH.Url.replacePageQuery((function (e) {
        if (t) {
            e.artk = t
        } else {
            delete e.artk
        }
    }));
    var n = WH.getDataSource();
    i = WH.setTooltipLevel.bind(e, n[a]["tooltip_" + Locale.getName()], e._selectedLevel, null)();
    var r = e._bonusesBtn && e._bonusesBtn.selectedBonus ? e._bonusesBtn.selectedBonus : null;
    var o = e._selectedUpgrade ? e._selectedUpgrade : 0;
    i = WH.enhanceTooltip.call(e, i, true, null, null, null, null, null, o, null, null, null, null, null, r);
    WH.updateTooltip.call(e, i)
};
WH.tooltipSliderMove = function (e, t, a) {
    WH.staticTooltipLevelClick(this, a.value, 1);
    if (this.bufftip) {
        WH.staticTooltipLevelClick(this.bufftip, a.value, 1, 1)
    }
    WH.Tooltip.hide()
};
WH.tooltipSpellsChange = function () {
    if (!this.modified) {
        return
    }
    var e = this.modified[0], t = this.modified[1], a = [];
    $.each($("input:checked", e), (function (e, t) {
        a.push(parseInt(t.dataset.spellId))
    }));
    this.modified[2] = a;
    WH.qsa(".lazy-load-background", this).forEach((e => e.classList.remove("lazy-load-background")));
    this.innerHTML = WH.setTooltipSpells(this.innerHTML, a, t);
    if (this.bufftip) {
        WH.tooltipSpellsChange.bind(this.bufftip)()
    }
    WH.Url.replacePageQuery((function (e) {
        let t = WH.getPageData("WH.Wow.Covenant.data");
        delete e.spellModifier;
        delete e.covenant;
        let i = [];
        a.forEach((a => {
            let n = Object.keys(t).find((e => t[e].spellId === a));
            if (n) {
                e.covenant = n
            } else {
                i.push(a)
            }
        }));
        if (i.length) {
            e.spellModifier = i.join(":")
        }
    }))
};
WH.tooltipRPPMChange = function (e) {
    var t = $(this).attr("id").match(/\d+$/)[0];
    WH.qsa(".lazy-load-background", e).forEach((e => e.classList.remove("lazy-load-background")));
    e.innerHTML = WH.Tooltip.evalFormulas(e.innerHTML.replace(/<!--rppm-->(\[?)(\d+(?:\.\d+)?)([^<]*)<!--rppm-->/, (function (a, i, n, r) {
        return "\x3c!--rppm--\x3e" + i + (e._rppmModBase * (1 + parseFloat(e._rppmSpecModList[t].modifiervalue))).toFixed(2) + r + "\x3c!--rppm--\x3e"
    })))
};
WH.validateBpet = function (e, t) {
    var a = 1, i = 25, n = 25, r = 0, o = 4, s = 3, l = (1 << 10) - 1, c = 3, u = $.extend({}, t);
    if (e.minlevel) {
        a = e.minlevel
    }
    if (e.maxlevel) {
        i = e.maxlevel
    }
    if (e.companion) {
        i = a
    }
    if (!u.level) {
        u.level = n
    }
    u.level = Math.min(Math.max(u.level, a), i);
    if (e.minquality) {
        r = e.minquality;
        if (e.untameable) {
            o = r
        }
    }
    if (e.maxquality) {
        o = e.maxquality
    }
    if (u.quality == null) {
        u.quality = s
    }
    u.quality = Math.min(Math.max(u.quality, r), o);
    if (e.companion) {
        delete u.quality
    }
    if (e.breeds > 0) {
        l = e.breeds & l
    }
    if (!(l & 1 << c - 3)) {
        c = Math.floor(3 + Math.log(l) / Math.LN2)
    }
    if (u.breed && u.breed >= 13) {
        u.breed -= 10
    }
    if (!u.breed || !(l & 1 << u.breed - 3)) {
        u.breed = c
    }
    return u
};
WH.calcBattlePetStats = function (e, t, a, i, n) {
    if (!WH.battlePetBreedStats[t]) {
        t = 3
    }
    var r = e.health;
    if (isNaN(r)) {
        r = 0
    }
    var o = e.power;
    if (isNaN(o)) {
        o = 0
    }
    var s = e.speed;
    if (isNaN(s)) {
        s = 0
    }
    if (isNaN(a)) {
        a = 1
    }
    a = Math.min(Math.max(0, a), 5);
    if (isNaN(i)) {
        i = 1
    }
    i = Math.min(Math.max(1, i), 25);
    var l = WH.battlePetBreedStats[t];
    var c = 1 + a / 10;
    r = (r + l[0]) * 5 * i * c + 100;
    o = (o + l[1]) * i * c;
    s = (s + l[2]) * i * c;
    if (n) {
        r = r * 5 / 6;
        o = o * 4 / 5
    }
    return {health: Math.round(r), power: Math.round(o), speed: Math.round(s)}
};
WH.battlePetBreedStats = {
    3: [.5, .5, .5],
    4: [0, 2, 0],
    5: [0, 0, 2],
    6: [2, 0, 0],
    7: [.9, .9, 0],
    8: [0, .9, .9],
    9: [.9, 0, .9],
    10: [.4, .9, .4],
    11: [.4, .4, .9],
    12: [.9, .4, .4]
};
WH.battlePetAbilityLevels = [1, 2, 4, 10, 15, 20];
WH.Tooltip = {
    ARTIFACT_KNOWLEDGE_MULTIPLIERS: undefined,
    BONUS_ITEM_EFFECTS: undefined,
    ITEM_EFFECT_NAMES: undefined,
    ITEM_EFFECT_TOOLTIP_HTML: undefined,
    MAX_WIDTH: 320,
    showingTooltip: false,
    applyTooltipDataAttrs: function (e, t, a, i) {
        if (i) {
            e.dataset.status = {0: "loading", 1: "loading", 2: "error", 3: "error", 4: "ok", 5: "loading"}[i]
        } else {
            delete e.dataset.status
        }
        let n = t && WH.Game.getKey(WH.Game.getByEnv(t));
        if (n) {
            e.dataset.game = n
        } else {
            delete e.dataset.game
        }
        let r = t && WH.getDataTreeKey(WH.getDataTree(t));
        if (r) {
            e.dataset.tree = r
        } else {
            delete e.dataset.tree
        }
        let o = t && WH.getDataEnvKey(t);
        if (o) {
            e.dataset.env = o
        } else {
            delete e.dataset.env
        }
        let s = a && WH.Types.getReferenceName(a);
        if (s) {
            e.dataset.type = s
        } else {
            delete e.dataset.type
        }
    },
    clearHeightCapping: function (e, t) {
        t.style.maxHeight = null;
        delete e.whttHeightCap;
        delete e.dataset.height;
        e.style.maxHeight = null
    },
    create: function (e, t) {
        let a = WH.ce("div", {className: "wowhead-tooltip"});
        let i = WH.ce("table");
        let n = WH.ce("tbody");
        let r = WH.ce("tr");
        let o = WH.ce("tr");
        let s = WH.ce("td");
        let l = WH.ce("th", {style: {backgroundPosition: "top right"}});
        let c = WH.ce("th", {style: {backgroundPosition: "bottom left"}});
        let u = WH.ce("th", {style: {backgroundPosition: "bottom right"}});
        let d = {tooltip: a};
        if (e) {
            s.innerHTML = WH.Tooltip.evalFormulas(e)
        }
        WH.ae(r, s);
        WH.ae(r, l);
        WH.ae(n, r);
        WH.ae(o, c);
        WH.ae(o, u);
        WH.ae(n, o);
        WH.ae(i, n);
        if (!t) {
            d.icon = WH.ce("div", {className: "whtt-tooltip-icon", style: {visibility: "hidden"}});
            WH.ae(a, d.icon)
        }
        WH.ae(a, i);
        if (!t) {
            d.logo = WH.ce("div", {className: "wowhead-tooltip-powered"});
            WH.ae(a, d.logo)
        }
        return d
    },
    getMultiPartHtml: function (e, t) {
        return "<table><tr><td>" + e + "</td></tr></table><table><tr><td>" + t + "</td></tr></table>"
    },
    evalFormulas: function (e) {
        if (typeof e !== "string") {
            return e
        }
        let t = /<span class="wh-tooltip-formula" style="display:none">(\[[\w\W]*?\])<\/span>(?:\d+(?:\.\d+)?)?/g;
        e = e.replace(t, "$1");
        var a = 0;
        var i = 0;
        var n = "";
        var r = 0;
        for (var o = 0; o < e.length; o++) {
            var s = e.substr(o, 1);
            switch (s) {
                case"[":
                    a++;
                    i = 0;
                    n = "";
                    break;
                case"]":
                    a--;
                    if (a < 0) {
                        a = 0
                    }
                    i = 0;
                    n = "";
                    break;
                case"(":
                    if (a > 0) {
                        break
                    }
                    n += s;
                    i++;
                    break;
                case")":
                    if (a > 0) {
                        break
                    }
                    if (i > 0) {
                        n += s;
                        i--
                    }
                    break;
                default:
                    if (a == 0 && i > 0) {
                        n += s
                    }
            }
            if (a == 0 && i == 0 && n) {
                r = o - n.length + 1;
                if (/[^ ()0-9\+\*\/\.-]/.test(n.replace(/<!--[\w\W]*?-->/g, "").replace(/\b(floor|ceil|abs)\b/gi, ""))) {
                    n = "";
                    continue
                }
                if (/^\([0-9\.]*\)$/.test(n)) {
                    n = "";
                    continue
                }
                if (!/<!--[\w\W]*?-->/g.test(n)) {
                    n = "";
                    continue
                }
                e = e.substr(0, r) + "[" + e.substring(r, o + 1) + "]" + e.substr(o + 1);
                o += 2;
                n = ""
            }
        }
        e = e.replace(/\[([^\]]+)\]/g, (function (e, t) {
            var a;
            t = t.replace(/<!--[\w\W]*?-->/g, "");
            t = t.replace(/\b(floor|ceil|abs)\b/gi, "Math.$1");
            try {
                a = Function('"use strict";return (' + t + ")")()
            } catch (e) {
                a = undefined
            }
            if (typeof a === "undefined") {
                return e
            }
            return '<span class="wh-tooltip-formula" style="display:none">' + e + "</span>" + Math.abs(a).toFixed(3).replace(/0+$/, "").replace(/\.$/, "")
        }));
        return e
    },
    finalizeSize: function (e, t) {
        const a = 20;
        let i = WH.qs("table", e);
        let n = WH.qs("td", i);
        let r = n.childNodes;
        e.classList.remove("tooltip-slider");
        if (r.length >= 2 && r[0].nodeName === "TABLE" && r[1].nodeName === "TABLE") {
            let n = r[0];
            let o = r[1];
            n.style.whiteSpace = "nowrap";
            let s = parseInt(e.style.width);
            if (!e.slider || !s) {
                s = Math.max(n.getBoundingClientRect().width, o.getBoundingClientRect().width) + a
            }
            if (s > WH.Tooltip.MAX_WIDTH) {
                n.style.whiteSpace = null
            }
            for (let e = 2; e < r.length; e++) {
                if (r[e].nodeName === "BLOCKQUOTE") {
                    s = Math.max(s, r[e].getBoundingClientRect().width + a)
                }
            }
            s = Math.min(WH.Tooltip.MAX_WIDTH, s);
            if (s > 20) {
                if (e.slider) {
                    Slider.setSize(e.slider, s - 6);
                    e.classList.add("tooltip-slider")
                }
                e.classList.add("wowhead-tooltip-width-restriction");
                e.classList.add("wowhead-tooltip-width-" + s);
                e.style.width = s + "px";
                n.style.width = o.style.width = "100%";
                if (t && e.offsetHeight > document.documentElement.offsetHeight) {
                    i.classList.add("shrink")
                }
            }
        } else if (r.length && e.slider) {
            let n = r[0];
            let o = n.nodeName === "TABLE";
            if (o) {
                n.style.whiteSpace = "nowrap"
            }
            let s = parseInt(e.style.width);
            if (!s && o) {
                s = n.getBoundingClientRect().width + a;
                if (s > WH.Tooltip.MAX_WIDTH) {
                    n.style.whiteSpace = null
                }
            } else {
                s = i.getBoundingClientRect().width + a
            }
            s = Math.min(WH.Tooltip.MAX_WIDTH, s);
            if (s > 20) {
                e.style.width = s + "px";
                if (o) {
                    n.style.width = "100%"
                }
                if (e.slider) {
                    Slider.setSize(e.slider, s - 6);
                    e.classList.add("tooltip-slider")
                }
                if (t && e.offsetHeight > document.documentElement.offsetHeight) {
                    i.classList.add("shrink")
                }
            }
        }
    },
    finalizeSizeAndReveal: function (e) {
        WH.Tooltip.finalizeSize(e, false);
        WH.Tooltip.setTooltipVisibility(e, true)
    },
    isVisible: function () {
        return WH.Tooltip.showingTooltip || WH.Tooltip.tooltip && WH.DOM.isVisible(WH.Tooltip.tooltip)
    },
    attachImage: function (e, t) {
        WH.qsa(":scope > .image", WH.Tooltip.tooltipTable.parentNode).forEach((e => WH.de(e)));
        let a = typeof e;
        if (a === "number") {
            let t = WH.getDataSource();
            let a = e;
            if (t[a] && t[a]["image_" + Locale.getName()]) {
                e = t[a]["image_" + Locale.getName()]
            } else {
                return
            }
        } else if (a !== "string" || !e) {
            return
        }
        let i = WH.ce("div", {className: "image" + (t ? " " + t : ""), style: {backgroundImage: "url(" + e + ")"}});
        WH.Tooltip.tooltipTable.parentNode.insertBefore(i, WH.Tooltip.tooltipTable.nextSibling)
    },
    append: function (e, t, a, i, n) {
        let r = WH.Tooltip.create(a);
        let o = r.tooltip;
        WH.Tooltip.applyTooltipDataAttrs(o, i, n);
        WH.Tooltip.setIconInElement(r.icon, t, n, i);
        WH.ae(e, o);
        WH.Tooltip.finalizeSizeAndReveal(o)
    },
    prepare: function (e, t, a, i, n) {
        if (!WH.Tooltip.tooltip) {
            let e = WH.Tooltip.create();
            WH.Tooltip.icon = e.icon;
            WH.Tooltip.logo = e.logo;
            let t = e.tooltip;
            t.style.left = t.style.top = "-2323px";
            WH.Tooltip.tooltip = t;
            WH.Tooltip.tooltipTable = WH.gE(t, "table")[0];
            WH.Tooltip.tooltipTd = WH.gE(t, "td")[0];
            let a = WH.Tooltip.create(undefined, true).tooltip;
            a.style.left = a.style.top = "-2323px";
            WH.Tooltip.tooltip2 = a;
            WH.Tooltip.tooltipTable2 = WH.gE(a, "table")[0];
            WH.Tooltip.tooltipTd2 = WH.gE(a, "td")[0]
        }
        WH.Tooltip.applyTooltipDataAttrs(WH.Tooltip.tooltip, e, t, n);
        WH.Tooltip.applyTooltipDataAttrs(WH.Tooltip.tooltip2, e, t, n);
        let r = a === true ? "fixed" : "absolute";
        WH.Tooltip.tooltip.style.position = r;
        WH.Tooltip.tooltip2.style.position = r;
        let o = i || document.fullscreenElement || document.body;
        WH.ae(o, WH.Tooltip.tooltip);
        WH.ae(o, WH.Tooltip.tooltip2);
        if (e === WH.dataEnv.DI && WH.getDataEnv() !== WH.dataEnv.DI) {
            WH.loadFont("Exocet")
        }
    },
    prepareScreen: function () {
        if (WH.Tooltip.screen) {
            WH.Tooltip.screen.style.display = "block"
        } else {
            WH.Tooltip.screen = WH.ce("div", {id: "wowhead-tooltip-screen", className: "wowhead-tooltip-screen"});
            WH.Tooltip.screenCloser = WH.ce("a", {
                id: "wowhead-tooltip-screen-close",
                className: "wowhead-tooltip-screen-close",
                onclick: $WowheadPower.clearTouchTooltip
            });
            WH.Tooltip.screenInnerWrapper = WH.ce("div", {
                id: "wowhead-tooltip-screen-inner-wrapper",
                className: "wowhead-tooltip-screen-inner-wrapper"
            });
            WH.Tooltip.screenInner = WH.ce("div", {
                id: "wowhead-tooltip-screen-inner",
                className: "wowhead-tooltip-screen-inner"
            });
            WH.Tooltip.screenInnerBox = WH.ce("div", {
                id: "wowhead-tooltip-screen-inner-box",
                className: "wowhead-tooltip-screen-inner-box"
            });
            WH.Tooltip.screenCaption = WH.ce("div", {
                id: "wowhead-tooltip-screen-caption",
                className: "wowhead-tooltip-screen-caption"
            });
            WH.ae(WH.Tooltip.screen, WH.Tooltip.screenCloser);
            WH.ae(WH.Tooltip.screenInner, WH.Tooltip.screenInnerBox);
            WH.ae(WH.Tooltip.screenInnerWrapper, WH.Tooltip.screenInner);
            WH.ae(WH.Tooltip.screen, WH.Tooltip.screenInnerWrapper);
            WH.ae(WH.Tooltip.screen, WH.Tooltip.screenCaption);
            WH.ae(document.body, WH.Tooltip.screen)
        }
        WH.Tooltip.mobileTooltipShown = true;
        WH.Tooltip.setupIScroll()
    },
    destroyIScroll: function () {
        if (WH.Tooltip.iScroll) {
            WH.Tooltip.iScroll.destroy();
            WH.Tooltip.iScroll = null
        }
    },
    setupIScroll: function () {
        if (!WH.Tooltip.mobileScrollSetUp) {
            var e = function (e) {
                if (WH.Tooltip.mobileTooltipShown) {
                    if (!document.getElementById("wowhead-tooltip-screen-inner").contains(e.target)) {
                        e.preventDefault()
                    }
                }
            };
            WH.aE(document.body, "touchmove", e);
            WH.aE(document.body, "mousewheel", e);
            WH.Tooltip.mobileScrollSetUp = true
        }
        if (typeof IScroll != "function") {
            return
        }
        setTimeout((function () {
            WH.Tooltip.destroyIScroll();
            WH.Tooltip.iScroll = new IScroll(WH.Tooltip.screenInnerWrapper, {mouseWheel: true, tap: true})
        }), 1)
    },
    setTooltipVisibility: function (e, t) {
        if (t) {
            e.setAttribute("data-visible", "yes");
            e.style.visibility = "visible"
        } else {
            e.setAttribute("data-visible", "no");
            e.style.visibility = "hidden"
        }
    },
    set: function (e, t, a, i) {
        WH.Tooltip.tooltip.style.width = "550px";
        WH.Tooltip.tooltip.style.left = "-2323px";
        WH.Tooltip.tooltip.style.top = "-2323px";
        WH.Tooltip.tooltip.className = "wowhead-tooltip";
        if (e.nodeName) {
            WH.ee(WH.Tooltip.tooltipTd);
            WH.ae(WH.Tooltip.tooltipTd, e)
        } else {
            WH.Tooltip.tooltipTd.innerHTML = WH.Tooltip.evalFormulas(e)
        }
        WH.Tooltip.tooltip.style.display = "";
        WH.Tooltip.setTooltipVisibility(WH.Tooltip.tooltip, true);
        WH.Tooltip.finalizeSize(WH.Tooltip.tooltip, true);
        if (t) {
            WH.Tooltip.showSecondary = true;
            WH.Tooltip.tooltip2.style.width = "550px";
            WH.Tooltip.tooltip2.style.left = "-2323px";
            WH.Tooltip.tooltip2.style.top = "-2323px";
            if (t.nodeName) {
                WH.ee(WH.Tooltip.tooltipTd2);
                WH.ae(WH.Tooltip.tooltipTd2, t)
            } else {
                WH.Tooltip.tooltipTd2.innerHTML = WH.Tooltip.evalFormulas(t)
            }
            WH.Tooltip.tooltip2.style.display = "";
            WH.Tooltip.finalizeSize(WH.Tooltip.tooltip2, true)
        } else {
            WH.Tooltip.showSecondary = false
        }
        if (WH.Device.isTouch()) {
            let e = WH.Tooltip.showSecondary ? WH.Tooltip.tooltipTd2 : WH.Tooltip.tooltipTd;
            let t = WH.ce("a");
            t.href = "javascript:";
            t.className = "wowhead-touch-tooltip-closer";
            t.onclick = $WowheadPower.clearTouchTooltip;
            WH.ae(e, t)
        }
        WH.Tooltip.tooltipTable.style.display = e == "" ? "none" : "";
        WH.Tooltip.attachImage(a, i);
        WH.Tooltip.generateEvent("show")
    },
    moveTests: [{}, {top: false}, {right: false}, {right: false, top: false}],
    move: function (e, t, a, i, n, r) {
        if (!WH.Tooltip.tooltip) {
            return
        }
        let o = WH.Tooltip.tooltip;
        o.style.left = "-1000px";
        o.style.top = "-1000px";
        o.style.width = null;
        o.style.maxWidth = WH.Tooltip.MAX_WIDTH + "px";
        let s = o.getBoundingClientRect().width;
        let l = WH.Tooltip.tooltip2;
        l.style.left = "-1000px";
        l.style.top = "-1000px";
        l.style.width = null;
        l.style.maxWidth = WH.Tooltip.MAX_WIDTH + "px";
        let c = WH.Tooltip.showSecondary ? l.getBoundingClientRect().width : 0;
        o.style.maxWidth = null;
        l.style.maxWidth = null;
        o.style.width = s ? s + "px" : "auto";
        l.style.width = c + "px";
        if (e || t) {
            let e = o.whttHeightCap;
            let t = (e || {}).maxHeight || window.innerHeight;
            let a = (e || {}).innerScroll;
            if (o.offsetHeight >= t) {
                if (a = a || WH.qs(".whtt-scroll", o)) {
                    o.dataset.height = "restricted";
                    o.style.maxHeight = t + "px";
                    if (!e) {
                        let e = o.scrollHeight - o.offsetHeight;
                        a.style.maxHeight = a.scrollHeight - e + "px";
                        o.whttHeightCap = {innerScroll: a, maxHeight: o.offsetHeight}
                    }
                }
            } else {
                if (a) {
                    WH.Tooltip.clearHeightCapping(o, a)
                }
            }
        }
        var u, d;
        for (var p = 0, f = WH.Tooltip.moveTests.length; p < f; ++p) {
            let o = WH.Tooltip.moveTests[p];
            u = WH.Tooltip.moveTest(e, t, a, i, n, r, o.right, o.top);
            if (WH.WAS && !WH.WAS.intersect(u)) {
                d = true;
                break
            } else if (!WH.WAS) {
                break
            }
        }
        if (WH.WAS && !d) {
            WH.WAS.intersect(u, true)
        }
        o.style.left = u.l + "px";
        o.style.top = u.t + "px";
        WH.Tooltip.setTooltipVisibility(o, true);
        if (WH.Tooltip.showSecondary) {
            l.style.left = u.l + s + "px";
            l.style.top = u.t + "px";
            WH.Tooltip.setTooltipVisibility(l, true)
        }
        WH.Tooltip.generateEvent("move")
    },
    moveTest: function (e, t, a, i, n, r, o, s) {
        let l = e;
        let c = t;
        let u = WH.Tooltip.tooltip;
        let d = WH.Tooltip.tooltip.getBoundingClientRect();
        let p = d.width;
        let f = d.height;
        let g = WH.Tooltip.tooltip2.getBoundingClientRect();
        let m = WH.Tooltip.showSecondary ? g.width : 0;
        let h = WH.Tooltip.showSecondary ? g.height : 0;
        let H = WH.getWindowSize();
        let W = WH.getScroll();
        let v = W.x;
        let T = W.y;
        let E = W.x + H.w;
        let b = W.y + H.h;
        if (u.style.position === "fixed") {
            e -= W.x;
            t -= W.y;
            l -= e;
            c -= t;
            W = {x: 0, y: 0};
            v = T = 0;
            E = H.w;
            b = H.h
        }
        if (o == null) {
            o = e + a + p + m <= E
        }
        if (s == null) {
            s = t - Math.max(f, h) >= T
        }
        if (o) {
            e += a + n
        } else {
            e = Math.max(e - (p + m), v) - n
        }
        if (s) {
            t -= Math.max(f, h) + r
        } else {
            t += i + r
        }
        if (e < v) {
            e = v
        } else if (e + p + m > E) {
            e = E - (p + m)
        }
        if (t < T) {
            t = T
        } else if (t + Math.max(f, h) > b) {
            t = Math.max(W.y, b - Math.max(f, h))
        }
        if (WH.Tooltip.iconVisible) {
            if (l >= e - 48 && l <= e && c >= t - 4 && c <= t + 48) {
                t -= 48 - (c - t)
            }
        }
        return WH.createRect(e, t, p, f)
    },
    show: function (e, t, a, i) {
        if (t == null || WH.Tooltip.disabled) {
            return
        }
        i = i || {};
        if (!i.padX || i.padX < 1) i.padX = 1;
        if (!i.padY || i.padY < 1) i.padY = 1;
        if (a) {
            t = '<div class="' + a + '">' + t + "</div>"
        }
        let n = e.getBoundingClientRect();
        WH.Tooltip.prepare(i.dataEnv, i.type, i.fixedPosition, undefined, i.status);
        WH.Tooltip.setIconForShow(i);
        WH.Tooltip.set(t, i.text2, i.image, i.imageClass);
        WH.Tooltip.move(n.left + window.scrollX, n.top + window.scrollY, n.width, n.height, i.padX, i.padY)
    },
    showAtCursor: function (e, t, a, i) {
        if (t == null || WH.Tooltip.disabled) {
            return
        }
        i = i || {};
        if (!i.padX || i.padX < 10) i.padX = 10;
        if (!i.padY || i.padY < 10) i.padY = 10;
        if (a) {
            t = '<div class="' + a + '">' + t + "</div>";
            if (i.text2) {
                i.text2 = '<div class="' + a + '">' + i.text2 + "</div>"
            }
        }
        WH.Tooltip.prepare(i.dataEnv, i.type, e.target && WH.isElementFixedPosition(e.target), undefined, i.status);
        WH.Tooltip.setIconForShow(i);
        WH.Tooltip.set(t, i.text2, i.image, i.imageClass);
        WH.Tooltip.move(e.pageX, e.pageY, 0, 0, i.padX || 0, i.padY || 0)
    },
    showAtPoint: function (e, t, a, i) {
        if (e == null || WH.Tooltip.disabled) {
            return
        }
        i = i || {};
        WH.Tooltip.prepare(i.dataEnv, i.type, i.fixedPosition, undefined, i.status);
        WH.Tooltip.setIconForShow(i);
        WH.Tooltip.set(e, i.text2, i.image, i.imageClass);
        WH.Tooltip.move(t, a, 0, 0, i.padX || 0, i.padY || 0)
    },
    showFadingTooltipAtCursor: function (e, t, a, i, n) {
        e = WH.Tooltip.prepareTooltipHtml(e, i, n, t);
        WH.Tooltip.showAtCursor(t, e, a);
        requestAnimationFrame((function () {
            WH.Tooltip.tooltip.classList.add("fade-out")
        }))
    },
    showInScreen: function (e, t, a) {
        $WowheadPower.clearTouchTooltip(true);
        if (t == null || WH.Tooltip.disabled) {
            return
        }
        a = a || {};
        WH.Tooltip.prepareScreen();
        WH.ee(WH.Tooltip.screenCaption);
        var i = WH.ce("a", {
            innerHTML: WH.isRemote() ? "Tap Link" : WH.TERMS.taplink, onclick: function (e, t) {
                e.setAttribute("data-disable-wowhead-tooltip", "true");
                if (e.fireEvent) {
                    e.fireEvent("on" + t)
                } else if (typeof MouseEvent == "function") {
                    e.dispatchEvent(new MouseEvent(t, {bubbles: true, cancelable: true}))
                } else {
                    var a = document.createEvent("Events");
                    a.initEvent(t, true, true);
                    e.dispatchEvent(a)
                }
                if (e) {
                    e.removeAttribute("data-disable-wowhead-tooltip")
                }
                $WowheadPower.clearTouchTooltip()
            }.bind(null, e, "click")
        });
        var n = WH.ce("i", {className: "fa fa-hand-o-up"});
        WH.aef(i, n);
        WH.ae(WH.Tooltip.screenCaption, i);
        WH.Tooltip.prepare(a.dataEnv, a.type, false, WH.Tooltip.screenInnerBox, a.status);
        WH.Tooltip.setIconForShow(a);
        WH.Tooltip.set(t, a.text2, a.image, a.imageClass);
        WH.Tooltip.move()
    },
    cursorUpdate: function (e, t, a) {
        if (WH.Tooltip.disabled || !WH.Tooltip.tooltip) {
            return
        }
        if (!t || t < 10) t = 10;
        if (!a || a < 10) a = 10;
        WH.Tooltip.move(e.pageX, e.pageY, 0, 0, t, a)
    },
    hide: function () {
        if (WH.Tooltip.tooltip) {
            let e = WH.Tooltip.tooltip;
            WH.Tooltip.showingTooltip = false;
            e.style.display = "none";
            WH.Tooltip.setTooltipVisibility(e, false);
            WH.Tooltip.tooltipTable.className = "";
            let t = (e.whttHeightCap || {}).innerScroll;
            if (t) {
                WH.Tooltip.clearHeightCapping(e, t)
            }
            WH.Tooltip.setIcon();
            if (WH.WAS) {
                WH.WAS.restoreHidden()
            }
            WH.Tooltip.generateEvent("hide")
        }
        if (WH.Tooltip.tooltip2) {
            WH.Tooltip.tooltip2.style.display = "none";
            WH.Tooltip.setTooltipVisibility(WH.Tooltip.tooltip2, false);
            WH.Tooltip.tooltipTable2.className = ""
        }
    },
    setIcon: function (e, t, a) {
        WH.Tooltip.setIconInElement(WH.Tooltip.icon, e ? {icon: e} : undefined, t, a)
    },
    setIconForShow: function (e) {
        let t;
        if (e.showIcon !== false) {
            if (e.entity && e.entity.icon) {
                t = e.entity
            } else if (e.iconName) {
                t = {icon: e.iconName}
            }
        }
        WH.Tooltip.setIconInElement(WH.Tooltip.icon, t, e.type, e.dataEnv)
    },
    setIconInElement: function (e, t, a, i) {
        WH.ee(e);
        if ([WH.Types.DI_EQUIP_ITEM, WH.Types.DI_MISC_ITEM].includes(a)) {
            t = undefined
        }
        if (t && t.icon) {
            WH.ae(e, WH.Icon.createByEntity(t, a, null, {dataEnv: i, size: WH.Icon.MEDIUM}));
            e.style.visibility = "visible";
            WH.Tooltip.iconVisible = true
        } else {
            e.style.visibility = "hidden";
            WH.Tooltip.iconVisible = false
        }
    },
    generateEvent: function (e) {
        if (!WH.Tooltip.tooltip) {
            return
        }
        try {
            WH.Tooltip.tooltip.dispatchEvent(new Event(e))
        } catch (a) {
            try {
                var t = document.createEvent("Event");
                t.initEvent(e, true, true);
                WH.Tooltip.tooltip.dispatchEvent(t)
            } catch (e) {
                void 0
            }
        }
    },
    addTooltipText: function (e, t, a) {
        if (!e) {
            WH.error("Tooltip text addition element not found!", e, t, a);
            return
        }
        e._fixTooltip = function (e, t, a, i) {
            var n = /<\/table>\s*$/;
            var r = typeof a === "function" ? a() : a;
            var o = a ? ' class="' + r + '"' : "";
            var s = typeof t === "function" ? t() : t;
            if (n.test(i)) {
                return i.replace(n, '<tr><td colspan="2"><div' + o + ' style="margin-top:10px">' + s + "</div></td></tr></table>")
            } else {
                return i + "<div" + o + ' style="margin-top:10px">' + s + "</div>"
            }
        }.bind(null, e, t, a)
    },
    prepareTooltipHtml: function (e, t, a, i) {
        e = typeof e === "function" ? e.call(i.target, i) : e;
        if (typeof e === "string") {
            if (t === undefined && e.length < 30) {
                t = true
            }
            let i = [];
            if (t) {
                i.push(' class="no-wrap"')
            }
            if (a && !isNaN(a)) {
                i.push(' style="max-width:' + a + 'px"')
            }
            if (i.length) {
                e = "<div" + i.join("") + ">" + e + "</div>"
            }
        }
        return e
    },
    simple: function (e, t, a, i) {
        i = i || {};
        if (e instanceof jQuery) {
            for (let n = 0, r; r = e[n]; n++) {
                WH.Tooltip.simple(r, t, a, i)
            }
            return
        }
        let n = {dataEnv: i.dataEnv, type: i.type};
        let r = i.stopPropagation ? e => e.stopPropagation() : () => {
        };
        if (i.byCursor) {
            e.onmouseover = function (e) {
                let o = WH.Tooltip.prepareTooltipHtml(t, i.noWrap, i.maxWidth, e);
                WH.Tooltip.showAtCursor(e, o, a, n);
                r(e)
            };
            e.onmousemove = WH.Tooltip.cursorUpdate
        } else {
            e.onmouseover = function (o) {
                let s = WH.Tooltip.prepareTooltipHtml(t, i.noWrap, i.maxWidth, o);
                WH.Tooltip.show(e, s, a, n);
                r(o)
            }
        }
        e.onmouseout = WH.Tooltip.hide
    },
    simpleNonTouch: function (e, t, a, i) {
        if (!WH.Device.isTouch()) {
            WH.Tooltip.simple(e, t, a, i)
        }
    }
};
WH.createButton = function (e, t, a) {
    var i = "btn btn-site";
    var n = "";
    var r = "";
    var o = "";
    var s = "";
    var l = [];
    var c = [];
    if (!a) {
        a = {}
    }
    if (!a["no-margin"]) {
        c.push("margin-left:5px")
    }
    if (typeof t != "string" || t === "") {
        t = "javascript:"
    }
    if (a["new-window"]) {
        n = ' target="_blank"'
    }
    if (typeof a["id"] == "string") {
        r = ' id="' + a["id"] + '"'
    }
    if (typeof a["size"] != "undefined") {
        switch (a["size"]) {
            case"small":
            case"large":
                l.push("btn-" + a["size"]);
                break
        }
    } else {
        l.push("btn-small")
    }
    if (typeof a["class"] == "string") {
        l.push(a["class"])
    }
    if (typeof a["type"] == "string") {
        switch (a["type"]) {
            case"default":
            case"gray":
                i = "btn";
                break;
            default:
                i = "btn btn-" + a["type"]
        }
    }
    if (a["disabled"]) {
        l.push("btn-disabled");
        t = "javascript:"
    }
    if (l.length) {
        i += " " + l.join(" ")
    }
    if (i) {
        i = ' class="' + i + '"'
    }
    if (!(typeof a["float"] != "undefined" && !a["float"])) {
        c.push("float:right")
    }
    if (typeof a["style"] == "string") {
        c.push(a["style"])
    }
    if (c.length) {
        o = ' style="' + c.join(";") + '"'
    }
    var u = '<a href="' + t + '"' + n + r + i + o + ">" + (e || "") + "</a>";
    var d = WH.ce("div");
    d.innerHTML = u;
    var p = d.childNodes[0];
    if (typeof a["click"] == "function" && !a["disabled"]) {
        p.onclick = a["click"]
    }
    if (typeof a["tooltip"] != "undefined") {
        if (a["tooltip"] !== false) {
            p.setAttribute("data-whattach", "true")
        }
        if (a["tooltip"] === false) {
            p.rel = "np"
        } else if (typeof a["tooltip"] == "string") {
            WH.Tooltip.simple(p, a["tooltip"])
        } else if (typeof a["tooltip"] == "object" && a["tooltip"]["text"]) {
            WH.Tooltip.simple(p, a["tooltip"]["text"], a["tooltip"]["class"])
        }
    }
    return p
};
WH.Device = new function () {
    const e = {isMobile: undefined, isTablet: undefined, isTouch: undefined};
    this.isMobile = function () {
        return e.isMobile
    };
    this.isTablet = function () {
        return e.isTablet
    };
    this.isTouch = function () {
        return e.isTouch
    };

    function t() {
        let t = navigator.userAgent || navigator.vendor || window.opera;
        if (/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i.test(t) || /1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i.test(t.substr(0, 4))) {
            e.isMobile = true
        }
        if (!e.isMobile) {
            if (/(android|ipad|playbook|silk)/i.test(t)) {
                e.isTablet = true
            }
        }
        e.isTouch = e.isMobile || e.isTablet
    }

    t()
};
WH.DI = new function () {
};
WH.Game = new function () {
    const e = this;
    this.WOW = 1;
    this.D2 = 2;
    this.DI = 3;
    this.DEFAULT = this.WOW;
    const t = {
        [this.D2]: {
            dataTrees: [WH.dataTree.D2],
            defaultTree: WH.dataTree.D2,
            name: "diablo2Resurrected",
            nameAbbrev: "diablo2"
        },
        [this.DI]: {
            dataTrees: [WH.dataTree.DI],
            defaultTree: WH.dataTree.DI,
            name: "diabloImmortal",
            nameAbbrev: "diabloImmortal_abbrev"
        },
        [this.WOW]: {
            dataTrees: [WH.dataTree.RETAIL, WH.dataTree.CLASSIC, WH.dataTree.TBC, WH.dataTree.WRATH],
            defaultTree: WH.dataTree.RETAIL,
            name: "worldofwarcraft",
            nameAbbrev: "worldOfWarcraft_abbrev"
        }
    };
    const a = this.DI;
    const i = {[this.D2]: "d2", [this.DI]: "di", [this.WOW]: "wow"};
    const n = {[this.D2]: WH.dataEnv.D2, [this.DI]: WH.dataEnv.DI, [this.WOW]: WH.dataEnv.MAIN};
    let r = {
        [WH.dataEnv.BETA]: "beta",
        [WH.dataEnv.D2]: "diablo-2",
        [WH.dataEnv.DI]: "diablo-immortal",
        [WH.dataEnv.WRATH]: "wotlk"
    };
    const o = {
        [WH.dataTree.RETAIL]: this.WOW,
        [WH.dataTree.CLASSIC]: this.WOW,
        [WH.dataTree.TBC]: this.WOW,
        [WH.dataTree.D2]: this.D2,
        [WH.dataTree.DI]: this.DI,
        [WH.dataTree.WRATH]: this.WOW
    };
    this.get = () => e.getByTree(WH.getDataTree());
    this.getAll = () => Object.keys(i).map(Number);
    this.getAllSelectors = () => Object.values(r);
    this.getAllSorted = () => {
        let t = e.getAll();
        t.sort(((t, i) => {
            if (t === e.WOW) {
                return -1
            } else if (i === e.WOW) {
                return 1
            }
            if (t === a) {
                return -1
            } else if (i === a) {
                return 1
            }
            return e.getName(t).localeCompare(e.getName(i))
        }));
        return t
    };
    this.getByKey = e => {
        for (let t of Object.keys(i)) {
            if (i[t] === e) {
                return parseInt(t)
            }
        }
    };
    this.getByEnv = t => e.getByTree(WH.getDataTree(t));
    this.getByTree = e => o[e];
    this.getDataEnvBySelector = function (e) {
        return WH.findKey(r, e, true)
    };
    this.getDataTrees = e => {
        if (t[e]) {
            return t[e].dataTrees
        }
        return undefined
    };
    this.getDefaultEnv = e => n[e];
    this.getEnv = t => {
        t = t || e.DEFAULT;
        return t === WH.Game.get() ? WH.getDataEnv() : WH.Game.getDefaultEnv(t)
    };
    this.getName = e => {
        if (t[e]) {
            return WH.TERMS[t[e].name]
        }
        return undefined
    };
    this.getAbbrev = e => {
        if (t[e]) {
            return WH.TERMS[t[e].nameAbbrev]
        }
        return undefined
    };
    this.getKey = e => i[e];
    this.getRoot = t => WH.getRootEnv(e.getEnv(t));
    this.getSelectorByDataEnv = function (e) {
        return r[e] || null
    };
    this.hasAccess = function (t) {
        if ((WH.PageMeta.restrictedDataEnvs || []).length === 0) {
            return true
        }
        let a = (e.getDataTrees(t) || []).filter((e => {
            for (let [t, a] of Object.entries(WH.dataEnvToTree)) {
                if (a !== e) {
                    continue
                }
                return !WH.PageMeta.restrictedDataEnvs.includes(e)
            }
        }));
        return !!a.length
    }
};
WH.Icon = new function () {
    const e = this;
    const t = WH.Game;
    const i = WH.Types;
    this.TINY = "tiny";
    this.SMALL = "small";
    this.MEDIUM = "medium";
    this.LARGE = "large";
    this.BLIZZARD = "blizzard";
    this.LEGACY_IDS = {0: this.SMALL, 1: this.MEDIUM, 2: this.LARGE, 3: this.BLIZZARD};
    this.WOW_MEDIUM_SELECTED_CLASS = "iconmedium-gold-selected";
    this.UNKNOWN = "inv_misc_questionmark";
    this.UNKNOWN_ZONE = "inv_misc_map08";
    const n = [this.TINY, this.SMALL, this.MEDIUM, this.LARGE, this.BLIZZARD];
    this.create = function (n, r, o, s) {
        s = s || {};
        let l = s.dataEnv || s.type && i.getPreferredDataEnv(s.type) || t.getEnv(s.game);
        let c = t.getByEnv(l);
        if (r === e.TINY) {
            return WH.ce("img", {className: "icontiny", src: e.getIconUrl(n, r, c)})
        }
        let u = WH.ce(s.span ? "span" : "div", {
            className: "icon" + r,
            dataset: {env: WH.getDataEnvKey(l), tree: WH.getDataTreeKey(WH.getDataTree(l)), game: t.getKey(c)}
        });
        WH.ae(u, WH.ce("ins"));
        if (s.border !== false) {
            WH.ae(u, WH.ce("del"))
        }
        let d = s.type && i.getStringId(s.type);
        if (d) {
            u.dataset.type = d
        }
        if (s.simple === true) {
            u.dataset.kind = "simple"
        } else if (s.kind) {
            u.dataset.kind = s.kind
        }
        if (s.color) {
            u.dataset.color = s.color
        }
        WH.cO(u.dataset, s.dataset);
        if (n) {
            if (n.includes("/")) {
                e.setImage(u, n, true)
            } else {
                e.setImage(u, e.getIconUrl(n, r, c))
            }
            if (!WH.isRemote() && s.lazyLoad !== false) {
                WH.DOM.lazyLoadBackground(u.firstChild)
            }
        }
        if (o) {
            let e = WH.ce("a", {href: o});
            if (o.indexOf("wowhead.com") === -1 && /^https?:/.test(o)) {
                e.target = "_blank"
            }
            WH.ae(u, e)
        } else if (n) {
            let e = u.firstChild.style.backgroundImage.indexOf("/avatars/") !== -1;
            if (!e) {
                if (o !== null) {
                    WH.ae(u, WH.ce("a", {href: "javascript:"}));
                    u.onclick = WowheadIcon.onClick
                }
            }
        }
        if (s.rel && typeof a != "undefined") {
            a.rel = s.rel
        }
        e.setText(u, s.number, s.quantity);
        return u
    };
    this.createByEntity = function (t, a, n, r) {
        r = r || {};
        let o = r.size;
        delete r.size;
        r.dataEnv = r.dataEnv || t.dataEnv || (i.getRequiredTrees(a) || [])[0];
        r.type = a;
        const s = WH.DI.GeneralItem;
        switch (a) {
            case i.DI_EQUIP_ITEM:
                r.dataset = r.dataset || {};
                r.dataset.gridType = r.gridType || t.gridType || ([6, 11, 4, 3, 7, 9, 8].includes(t.inventoryPosition) ? s.GRID_TYPE_1x1 : s.GRID_TYPE_2x1);
                delete r.gridType;
                if (t.inventoryColor != null) {
                    r.dataset.inventoryColor = t.inventoryColor
                }
                break;
            case i.DI_MISC_ITEM:
                r.dataset = r.dataset || {};
                r.dataset.gridType = r.gridType || t.gridType || s.GRID_TYPE_2x1;
                delete r.gridType;
                if (t.inventoryColor != null) {
                    r.dataset.inventoryColor = t.inventoryColor
                }
                break;
            case i.DI_PARAGON_SKILL:
                r.dataset = r.dataset || {};
                r.dataset.specSkill = JSON.stringify(!!(r.isSpecSkill || t.isSpecSkill));
                break
        }
        return e.create(t.icon, o || e.MEDIUM, n, r)
    };
    this.getIconUrl = function (a, i, r) {
        if (n.indexOf(i) === -1) {
            i = e.MEDIUM
        }
        if (r === t.DI) {
            return new WH.DI.UiImage(a).getUrl()
        }
        let o = t.getKey(r || t.DEFAULT);
        if (!o) {
            WH.warn('Invalid game provided for "' + a + '" icon: ' + r);
            o = t.getKey(t.WOW);
            a = e.UNKNOWN
        }
        return WH.STATIC_URL + "/images/" + o + "/icons" + "/" + i + "/" + a.toLowerCase() + (i === e.TINY ? ".gif" : ".jpg")
    };
    this.getLink = function (e) {
        return e.querySelector("a")
    };
    this.isValidSize = function (e) {
        return n.indexOf(e) !== -1
    };
    this.setImage = function (e, t, a) {
        let i = e.firstChild;
        i.style.backgroundPosition = "";
        i.style.backgroundImage = t ? 'url("' + t + '")' : "";
        if (a === true) {
            i.style.backgroundSize = "contain"
        }
    };
    this.setLinkUrl = function (t, a) {
        let i = e.getLink(t);
        if (i) {
            i.href = a
        }
    };
    this.setName = function (t, a, i, n) {
        if (!i) {
            e.setImage(t, null);
            return
        }
        if (a === e.BLIZZARD) {
            a = e.LARGE
        }
        e.setImage(t, e.getIconUrl(i, a, n))
    };
    this.setText = function (e, t, a) {
        WH.qsa(".wh-icon-text", e).forEach((e => WH.de(e)));
        if (t != null && (t > 1 && t < 2147483647 || t.length && t !== "0" && t !== "1")) {
            WH.ae(e, WH.ce("span", {className: "wh-icon-text", dataset: {type: "number"}}, WH.ct(t)))
        }
        if (a != null && a > 0) {
            WH.ae(e, WH.ce("span", {className: "wh-icon-text", dataset: {type: "quantity"}}, WH.ct("(" + a + ")")))
        }
    }
};
var WowheadIcon = {
    questionMarkIcon: WH.Icon.UNKNOWN,
    sizes: ["small", "medium", "large", "blizzard"],
    sizes2: [18, 36, 56, 64],
    sizeIds: {small: 0, medium: 1, large: 2, blizzard: 3},
    premiumOffsets: [[-56, -36], [-56, 0], [0, 0], [0, 0]],
    premiumBorderClasses: ["-premium", "-gold", "", "-premiumred", "-red", ""],
    STANDARD_BORDER: 2,
    privilegeBorderClasses: {uncommon: "-q2", rare: "-q3", epic: "-q4", legendary: "-q5"},
    idLookupCache: {},
    create: function (e, t, a, i, n, r, o, s, l, c, u) {
        u = u || {};
        if (t == null) {
            t = WowheadIcon.sizeIds.medium
        }
        return WH.Icon.create(e, WH.Icon.LEGACY_IDS[t], i === false ? null : i, {
            border: !o,
            color: u.color,
            game: u.game,
            lazyLoad: u.lazyLoad,
            number: n,
            quantity: r,
            rel: s,
            simple: c,
            span: l,
            type: u.type
        })
    },
    createUser: function (e, t, a, i, n, r, o) {
        if (e == 2) t = WH.staticUrl + "/uploads/avatars/" + t + ".jpg";
        var s = WowheadIcon.create(t, a, null, i, null, null, r);
        if (n != WowheadIcon.STANDARD_BORDER) {
            if (WowheadIcon.premiumBorderClasses[n]) {
                s.className += " " + s.className + WowheadIcon.premiumBorderClasses[n]
            }
        } else if (o && WowheadIcon.privilegeBorderClasses.hasOwnProperty(o)) s.className += " " + s.className + WowheadIcon.privilegeBorderClasses[o];
        if (e == 2) WowheadIcon.moveTexture(s, a, WowheadIcon.premiumOffsets[a][0], WowheadIcon.premiumOffsets[a][1], true);
        s.classList.add("icon" + WowheadIcon.sizes[a] + "-sprite");
        return s
    },
    getIdFromName: function (e, t) {
        if (WowheadIcon.idLookupCache.hasOwnProperty(e)) {
            window.requestAnimationFrame((function () {
                t(WowheadIcon.idLookupCache[e] || undefined)
            }));
            return
        }
        $.ajax({
            url: WH.Url.generatePath("/icon/get-id-from-name"),
            data: {name: e},
            dataType: "json",
            success: function (a) {
                WowheadIcon.idLookupCache[e] = a;
                t(a || undefined)
            }
        })
    },
    getPrivilegeBorder: function (e) {
        var t = false;
        if (e >= 5e3) t = "uncommon";
        if (e >= 1e4) t = "rare";
        if (e >= 15e3) t = "epic";
        if (e >= 25e3) t = "legendary";
        return t
    },
    setUrl: function (e, t) {
        if (!t) {
            t = "javascript:"
        }
        WowheadIcon.getLink(e).href = t
    },
    setTexture: function (e, t, a, i) {
        var n = e.firstChild.style;
        n.backgroundSize = "";
        n.backgroundPosition = "";
        if (!a) {
            n.backgroundImage = null;
            return
        }
        if (a.indexOf("/") !== -1) {
            n.backgroundImage = "url(" + a + ")";
            n.backgroundSize = "contain"
        } else {
            let e = WowheadIcon.sizes[t];
            if (e === "blizzard") {
                e = "large"
            }
            n.backgroundImage = "url(" + WH.Icon.getIconUrl(a, e, i) + ")"
        }
    },
    moveTexture: function (e, t, a, i, n) {
        var r = e.firstChild.style;
        r.backgroundSize = "";
        if (a || i) {
            if (n) r.backgroundPosition = a + "px " + i + "px"; else r.backgroundPosition = -a * WowheadIcon.sizes2[t] + "px " + -i * WowheadIcon.sizes2[t] + "px"
        } else if (r.backgroundPosition) r.backgroundPosition = ""
    },
    getLink: function (e) {
        return WH.gE(e, "a")[0]
    },
    showIconInfo: function (e) {
        if (e.firstChild) {
            let t = e.firstChild.style;
            if (t.backgroundImage && (!WH.STATIC_URL || t.backgroundImage.indexOf(WH.STATIC_URL) >= 4)) {
                let e = t.backgroundImage.match(/images\/([^/]+)\/icons\/[^/]+\/([^/.]+).(?:jpg|gif)/);
                if (e) {
                    WowheadIcon.displayIcon(e[2], WH.Game.getByKey(e[1]))
                }
            }
        }
    },
    onClick: function () {
        WowheadIcon.showIconInfo(this)
    },
    displayIcon: function (e, t) {
        if (!Dialog.templates.icondisplay) {
            Dialog.templates.icondisplay = {
                title: WH.TERMS.icon,
                width: 500,
                buttons: [["arrow", WH.TERMS.original], ["x", WH.TERMS.close]],
                fields: [{
                    id: "icon",
                    label: WH.TERMS.name_colon,
                    required: 1,
                    type: "text",
                    labelAlign: "left",
                    compute: function (e, a, i, n) {
                        n.classList.add("icon-dialog-content");
                        var r = this.iconDiv = WH.ce("div");
                        r.update = function () {
                            setTimeout((function () {
                                WH.safeSelect(e)
                            }), 10);
                            WH.ee(r);
                            WH.ae(r, WH.Icon.create(e.value, WH.Icon.LARGE, undefined, {game: t}))
                        };
                        WH.ae(r, WH.Icon.create(a, WH.Icon.LARGE, undefined, {game: t}));
                        WH.ae(n, r);
                        WH.ae(n, e)
                    }
                }, {
                    id: "iconId",
                    label: WH.TERMS.id + WH.TERMS.colon_punct,
                    type: "text",
                    labelAlign: "left",
                    compute: function (e, t, a, i) {
                        i.classList.add("icon-dialog-content");
                        e.value = "";
                        this.iconIdField = e
                    }
                }, {
                    id: "location", label: " ", required: 1, type: "caption", compute: function (e, t, a, i, n) {
                        WH.ee(i);
                        i.classList.add("icon-dialog-caption");
                        let r = WH.Strings.escapeHtml(WH.Url.generatePath("/items?filter=142;0;" + this.data.icon));
                        let o = WH.Strings.escapeHtml(WH.Url.generatePath("/spells?filter=15;0;" + this.data.icon));
                        let s = WH.Strings.escapeHtml(WH.Url.generatePath("/achievements?filter=10;0;" + this.data.icon));
                        var l = WH.TERMS.seeallusingicon_format;
                        l = l.replace("$1", '<a href="' + r + '">' + WH.Types.getLowerPlural(WH.Types.ITEM) + "</a>");
                        l = l.replace("$2", '<a href="' + o + '">' + WH.Types.getLowerPlural(WH.Types.SPELL) + "</a>");
                        l = l.replace("$3", '<a href="' + s + '">' + WH.Types.getLowerPlural(WH.Types.ACHIEVEMENT) + "</a>");
                        i.innerHTML = l
                    }
                }],
                onInit: function (e) {
                    this.updateIcon = this.template.updateIcon.bind(this, e)
                },
                onShow: function (e) {
                    this.updateIcon();
                    if (location.hash && location.hash.indexOf("#icon") == -1) this.oldHash = location.hash; else this.oldHash = "";
                    var t = "#icon";
                    let a = window.g_pageInfo && g_pageInfo.type && [WH.Types.ITEM, WH.Types.SPELL, WH.Types.ACHIEVEMENT].includes(g_pageInfo.type);
                    if (!a) {
                        t += ":" + this.data.icon;
                        if (this.data.game && this.data.game !== WH.Game.DEFAULT) {
                            t += ":" + WH.Game.getKey(this.data.game)
                        }
                    }
                    location.hash = t
                },
                onHide: function (e) {
                    if (this.oldHash) WH.setHash(this.oldHash); else WH.clearHash()
                },
                updateIcon: function (e) {
                    this.iconDiv.update();
                    var t = this.iconIdField;
                    WowheadIcon.getIdFromName(e.icon.value, (function (e) {
                        t.value = e || ""
                    }))
                },
                onSubmit: function (e, t, a, i) {
                    if (a === "arrow") {
                        let e = WH.Icon.getIconUrl(t.icon, WH.Icon.LARGE, t.game);
                        let a = window.open(e, "_blank");
                        a.focus();
                        return false
                    }
                    return true
                }
            }
        }
        if (!WowheadIcon.icDialog) WowheadIcon.icDialog = new Dialog;
        WowheadIcon.icDialog.show("icondisplay", {data: {icon: e, game: t}})
    },
    checkPound: function () {
        if (location.hash && location.hash.indexOf("#icon") === 0) {
            let e = location.hash.split(":");
            let t;
            let a;
            if (e.length === 3) {
                t = e[1];
                a = WH.Game.getByKey(e[2])
            } else if (e.length === 2) {
                t = e[1]
            } else if (e.length === 1 && window.g_pageInfo) {
                t = WH.Gatherer.getIconName(g_pageInfo.type, g_pageInfo.typeId)
            }
            if (t) {
                WowheadIcon.displayIcon(t, a)
            }
        }
    }
};
if (!WH.REMOTE) {
    WH.onLoad(WowheadIcon.checkPound)
}
window.$WowheadPower = window.$WowheadPower || new function () {
    const e = this;
    const t = WH.Game;
    const a = WH.Icon;
    const i = WH.Types;
    const n = "nether";
    const r = 550;
    const o = {garrisonability: "mission-ability", itemset: "item-set", petability: "pet-ability"};
    const s = {1: 299204, 2: 299205, 3: 299206, 4: 299207};
    const l = 15;
    const c = 15;
    const u = [i.ACHIEVEMENT, i.AZERITE_ESSENCE, i.AZERITE_ESSENCE_POWER, i.ITEM, i.SPELL, i.DI_EQUIP_ITEM, i.DI_MISC_ITEM, i.DI_PARAGON_SKILL, i.DI_SKILL];
    const d = {
        ["-1000"]: {name: "Mount", path: "mount", mobile: true, data: {}, maxId: 5e4},
        ["-1001"]: {name: "Recipe", path: "recipe", mobile: true, data: {}, maxId: 5e5},
        ["-1002"]: {name: "Battle Pet", path: "battle-pet", mobile: true, data: {}, maxId: 5e4},
        [i.NPC]: {name: "NPC", path: "npc", mobile: false, data: {}, maxId: 5e5},
        [i.OBJECT]: {name: "Object", path: "object", mobile: false, data: {}, maxId: 75e4},
        [i.ITEM]: {name: "Item", path: "item", mobile: true, data: {}, maxId: 5e5},
        [i.ITEM_SET]: {name: "Item Set", path: "item-set", mobile: true, data: {}, maxId: 1e4, minId: -5e3},
        [i.QUEST]: {name: "Quest", path: "quest", mobile: false, data: {}, maxId: 1e5},
        [i.SPELL]: {name: "Spell", path: "spell", mobile: true, data: {}, maxId: 5e5},
        [i.ZONE]: {name: "Zone", path: "zone", mobile: false, data: {}, maxId: 5e4},
        [i.ACHIEVEMENT]: {name: "Achievement", path: "achievement", mobile: true, data: {}, maxId: 5e4},
        [i.EVENT]: {name: "Event", path: "event", mobile: false, data: {}, maxId: 1e4},
        [i.CURRENCY]: {name: "Currency", path: "currency", mobile: false, data: {}, maxId: 1e4},
        [i.BUILDING]: {name: "Building", path: "building", mobile: false, data: {}, maxId: 1e3},
        [i.FOLLOWER]: {name: "Follower", path: "follower", mobile: true, data: {}, maxId: 1e4},
        [i.MISSION_ABILITY]: {name: "Mission Ability", path: "mission-ability", mobile: true, data: {}, maxId: 1e4},
        [i.MISSION]: {name: "Mission", path: "mission", mobile: true, data: {}, maxId: 1e4},
        [i.SHIP]: {name: "Ship", path: "ship", mobile: true, data: {}, maxId: 1e4},
        [i.THREAT]: {name: "Threat", path: "threat", mobile: false, data: {}, maxId: 1e3},
        [i.RESOURCE]: {name: "Resource", path: "resource", mobile: true, data: {}, maxId: 100, minId: 0},
        [i.CHAMPION]: {name: "Champion", path: "champion", mobile: true, data: {}, maxId: 1e4},
        [i.ORDER_ADVANCEMENT]: {
            name: "Order Advancement",
            path: "order-advancement",
            mobile: true,
            data: {},
            maxId: 5e3
        },
        [i.BFA_CHAMPION]: {name: "BFA Champion", path: "bfa-champion", mobile: true, data: {}, maxId: 1e4},
        [i.AFFIX]: {name: "Affix", path: "affix", mobile: true, data: {}, maxId: 1e3},
        [i.AZERITE_ESSENCE_POWER]: {
            name: "Azerite Essence Power",
            path: "azerite-essence-power",
            mobile: true,
            data: {},
            maxId: 1e3
        },
        [i.AZERITE_ESSENCE]: {name: "Azerite Essence", path: "azerite-essence", mobile: false, data: {}, maxId: 100},
        [i.STORYLINE]: {name: "Storyline", path: "storyline", mobile: false, data: {}, maxId: 1e4},
        [i.ADVENTURE_COMBATANT_ABILITY]: {
            name: "Adventure Combatant Ability",
            path: "adventure-combatant-ability",
            mobile: true,
            data: {},
            maxId: 1e4
        },
        [i.GUIDE]: {name: "Guide", path: "guide", mobile: false, data: {}},
        [i.TRANSMOG_SET]: {name: "Transmog Set", path: "transmog-set", mobile: true, data: {}, maxId: 5e4},
        [i.OUTFIT]: {name: "Outfit", path: "outfit", mobile: true, data: {}},
        [i.BATTLE_PET_ABILITY]: {name: "Battle Pet Ability", path: "pet-ability", mobile: true, data: {}, maxId: 1e4},
        [i.DI_EQUIP_ITEM]: {name: "Equipment Item", path: "equip-item", mobile: true, data: {}, embeddedIcons: true},
        [i.DI_MISC_ITEM]: {name: "Miscellaneous Item", path: "misc-item", mobile: true, data: {}, embeddedIcons: true},
        [i.DI_NPC]: {name: "NPC", path: "npc", mobile: true, data: {}},
        [i.DI_PARAGON_SKILL]: {name: "Paragon Skill", path: "paragon-skill", mobile: true, data: {}},
        [i.DI_QUEST]: {name: "Quest", path: "quest", mobile: true, data: {}},
        [i.DI_SET]: {name: "Set", path: "set", mobile: true, data: {}},
        [i.DI_SKILL]: {name: "Skill", path: "skill", mobile: true, data: {}},
        [i.DI_ZONE]: {name: "Zone", path: "zone", mobile: true, data: {}}
    };
    const p = ["achievement", "adventure-combatant-ability", "affix", "azerite-essence", "azerite-essence-power", "battle-pet", "bfa-champion", "building", "champion", "currency", "event", "follower", "garrisonability", "guide", "item", "item-set", "itemset", "mission", "mission-ability", "mount", "npc", "object", "order-advancement", "outfit", "pet-ability", "petability", "quest", "recipe", "resource", "ship", "spell", "statistic", "storyline", "threat", "transmog-set", "zone"];
    const f = ["item", "quest", "spell", "zone", "achievement", "event", "itemset", "item-set", "transmog-set", "outfit", "guide", "statistic", "currency", "npc", "object", "pet-ability", "petability", "resource"];
    const g = {
        traits: {
            agi: ["Agility", "Agi", "Agi"],
            arcres: ["Arcane resistance", "Arcane Resist", "ArcR"],
            arcsplpwr: ["Arcane spell power", "Arcane Power", "ArcP"],
            armor: ["Armor", "Armor", "Armor"],
            armorbonus: ["Additional armor", "Bonus Armor", "AddAr"],
            armorpenrtng: ["Armor penetration rating", "Armor Pen", "Pen"],
            atkpwr: ["Attack power", "AP", "AP"],
            block: ["Block value", "Block Value", "BkVal"],
            blockrtng: ["Block rating", "Block", "Block"],
            critstrkrtng: ["Critical strike rating", "Crit", "Crit"],
            defrtng: ["Defense rating", "Defense", "Def"],
            dmg: ["Weapon damage", "Damage", "Dmg"],
            dmgmax1: ["Maximum damage", "Max Damage", "Max"],
            dmgmin1: ["Minimum damage", "Min Damage", "Min"],
            dodgertng: ["Dodge rating", "Dodge", "Dodge"],
            dps: ["Damage per second", "DPS", "DPS"],
            exprtng: ["Expertise rating", "Expertise", "Exp"],
            firres: ["Fire resistance", "Fire Resist", "FirR"],
            firsplpwr: ["Fire spell power", "Fire Power", "FireP"],
            frores: ["Frost resistance", "Frost Resist", "FroR"],
            frosplpwr: ["Frost spell power", "Frost Power", "FroP"],
            hastertng: ["Haste rating", "Haste", "Haste"],
            health: ["Health", "Health", "Hlth"],
            healthrgn: ["Health regeneration", "HP5", "HP5"],
            hitrtng: ["Hit rating", "Hit", "Hit"],
            holres: ["Holy resistance", "Holy Resist", "HolR"],
            holsplpwr: ["Holy spell power", "Holy Power", "HolP"],
            int: ["Intellect", "Int", "Int"],
            level: ["Level", "Level", "Lvl"],
            mana: ["Mana", "Mana", "Mana"],
            manargn: ["Mana regeneration", "MP5", "MP5"],
            mastrtng: ["Mastery rating", "Mastery", "Mastery"],
            mleatkpwr: ["Melee attack power", "Melee AP", "AP"],
            mlecritstrkrtng: ["Melee critical strike rating", "Melee Crit", "Crit"],
            mledmgmax: ["Melee maximum damage", "Melee Max Damage", "Max"],
            mledmgmin: ["Melee minimum damage", "Melee Min Damage", "Min"],
            mledps: ["Melee DPS", "Melee DPS", "DPS"],
            mlehastertng: ["Melee haste rating", "Melee Haste", "Haste"],
            mlehitrtng: ["Melee hit rating", "Melee Hit", "Hit"],
            mlespeed: ["Melee speed", "Melee Speed", "Speed"],
            natres: ["Nature resistance", "Nature Resist", "NatR"],
            natsplpwr: ["Nature spell power", "Nature Power", "NatP"],
            nsockets: ["Number of sockets", "Sockets", "Sockt"],
            parryrtng: ["Parry rating", "Parry", "Parry"],
            reqarenartng: ["Required personal and team arena rating", "Req Rating", "Rating"],
            reqlevel: ["Required level", "Req Level", "Level"],
            resirtng: ["PvP Resilience rating", "PvP Resilience", "Resil"],
            rgdatkpwr: ["Ranged attack power", "Ranged AP", "RAP"],
            rgdcritstrkrtng: ["Ranged critical strike rating", "Ranged Crit", "Crit"],
            rgddmgmax: ["Ranged maximum damage", "Ranged Max Damage", "Max"],
            rgddmgmin: ["Ranged minimum damage", "Ranged Min Damage", "Min"],
            rgddps: ["Ranged DPS", "Ranged DPS", "DPS"],
            rgdhastertng: ["Ranged haste rating", "Ranged Haste", "Haste"],
            rgdhitrtng: ["Ranged hit rating", "Ranged Hit", "Hit"],
            rgdspeed: ["Ranged speed", "Ranged Speed", "Speed"],
            sepbasestats: "Base stats",
            sepdefensivestats: "Defensive stats",
            sepindividualstats: "Individual stats",
            sepoffensivestats: "Offensive stats",
            sepresistances: "Resistances",
            sepweaponstats: "Weapon stats",
            shares: ["Shadow resistance", "Shadow Resist", "ShaR"],
            shasplpwr: ["Shadow spell power", "Shadow Power", "ShaP"],
            speed: ["Speed", "Speed", "Speed"],
            spi: ["Spirit", "Spi", "Spi"],
            splcritstrkrtng: ["Spell critical strike rating", "Spell Crit", "Crit"],
            spldmg: ["Damage done by spells", "Spell Damage", "Dmg"],
            splheal: ["Healing done by spells", "Healing", "Heal"],
            splpwr: ["Spell power", "Spell Power", "SP"],
            splhastertng: ["Spell haste rating", "Spell Haste", "Haste"],
            splhitrtng: ["Spell hit rating", "Spell Hit", "Hit"],
            splpen: ["Spell penetration", "Spell Pen", "Pen"],
            sta: ["Stamina", "Sta", "Sta"],
            str: ["Strength", "Str", "Str"],
            pvppower: ["PvP Power", "PvPPower", "PvPPower"]
        }
    };
    const m = {colorLinks: "colorlinks", iconizeLinks: "iconizelinks", renameLinks: "renamelinks"};
    const h = WH.TERMS || {
        genericequip_tip: '<span class="q2">Equip: Increases your $1 by \x3c!--rtg$2--\x3e$3&nbsp;<small>(\x3c!--rtg%$2--\x3e0&nbsp;@&nbsp;L\x3c!--lvl--\x3e0)</small>.</span><br />',
        reforged_format: "Reforged ($1 $2 &rarr; $1 $3)"
    };
    const H = {0: "enus", 1: "kokr", 2: "frfr", 3: "dede", 4: "zhcn", 6: "eses", 7: "ruru", 8: "ptbr", 9: "itit"};
    const W = 0;
    const v = 5;
    const T = 3;
    const E = 4;
    const b = 1;
    const y = 2;
    const I = -1;
    const S = 0;
    const w = 1;
    const _ = 0;
    const A = 1;
    const M = 2;
    const L = 3;
    const R = 4;
    const k = 5;
    const C = {[A]: "loading", [k]: "loading", [M]: "error", [_]: "loading", [L]: "error", [R]: "ok"};
    const x = [i.GUIDE];
    const N = {
        0: {
            achievementComplete: "Achievement earned by $1 on $2/$3/$4",
            loading: "Loadingâ€¦",
            noResponse: "No response from server :(",
            notFound: "%s Not Found"
        },
        1: {
            achievementComplete: "$1ì´(ê°€) $2/$3/$4ì— ì—…ì ì„ ë‹¬ì„±í•¨",
            loading: "ë¡œë”© ì¤‘â€¦",
            noResponse: "ì„œë²„ê°€ ì‘ë‹µí•˜ì§€ ì•ŠìŠµë‹ˆë‹¤ :(",
            notFound: "%s ì„(ë¥¼) ì°¾ì„ ìˆ˜ ì—†ìŒ"
        },
        2: {
            achievementComplete: "Haut-fait reÃ§u par $1 le $2/$3/$4",
            loading: "Chargementâ€¦",
            noResponse: "Pas de rÃ©ponse du serveur :(",
            notFound: "%s non trouvÃ©"
        },
        3: {
            achievementComplete: "Erfolg wurde von $1 am $3.$2.$4 errungen",
            loading: "LÃ¤dtâ€¦",
            noResponse: "Keine Antwort vom Server :(",
            notFound: "%s nicht gefunden"
        },
        4: {
            achievementComplete: "$1åœ¨$2/$3/$4ä¸ŠèŽ·å¾—æˆå°±",
            loading: "æ­£åœ¨è½½å…¥â€¦",
            noResponse: "æœåŠ¡å™¨æ²¡æœ‰å“åº” :(",
            notFound: "%sæœªæ‰¾åˆ°"
        },
        6: {
            achievementComplete: "Logro conseguido por $1 el $2/$3/$4",
            loading: "Cargandoâ€¦",
            noResponse: "No hay respuesta del servidor :(",
            notFound: "%s no encontrado/a"
        },
        7: {
            achievementComplete: "$1 Ð¿Ð¾Ð»ÑƒÑ‡Ð¸Ð»(Ð°) ÑÑ‚Ð¾ Ð´Ð¾ÑÑ‚Ð¸Ð¶ÐµÐ½Ð¸Ðµ $2/$3/$4",
            loading: "Ð—Ð°Ð³Ñ€ÑƒÐ·ÐºÐ°â€¦",
            noResponse: "ÐÐµÑ‚ Ð¾Ñ‚Ð²ÐµÑ‚Ð° Ð¾Ñ‚ ÑÐµÑ€Ð²ÐµÑ€Ð° :(",
            notFound: "%s Ð½Ðµ Ð½Ð°Ð¹Ð´ÐµÐ½Ð¾"
        },
        8: {
            achievementComplete: "Conquista conseguida por $1 em $3/$2/$4",
            loading: "Carregandoâ€¦",
            noResponse: "Sem resposta do servidor :(",
            notFound: "%s nÃ£o encontrado(a)"
        },
        9: {
            achievementComplete: "Impresa compiuta da $1 su $2/$3/$4",
            loading: "Caricamentoâ€¦",
            noResponse: "Nessuna risposta dal server :(",
            notFound: "%s Non Trovato"
        }
    };
    const O = WH.Device.isTouch();
    const P = {
        cursorX: undefined,
        cursorY: undefined,
        element: undefined,
        initiatedByUser: false,
        show: {
            dataEnv: undefined,
            fullId: undefined,
            hasLogo: true,
            locale: undefined,
            mode: W,
            params: {},
            type: undefined
        },
        showCharacterCompletion: !WH.REMOTE,
        touchElement: undefined
    };
    this.attachTouchTooltips = function (e) {
        if (!O) {
            return
        }
        if (e && e.nodeType === 1) {
            F(e)
        }
    };
    this.clearTouchTooltip = function (e) {
        if (P.touchElement) {
            if (e !== true) {
                P.touchElement.removeAttribute("data-showing-touch-tooltip")
            }
            P.touchElement.hasWHTouchTooltip = false
        }
        P.touchElement = undefined;
        if (e !== true) {
            WH.qsa("[data-showing-touch-tooltip]").forEach((function (e) {
                delete e.dataset.showingTouchTooltip
            }))
        }
        if (WH.Tooltip.screen) {
            WH.Tooltip.screenInnerWrapper.scrollTop = 0;
            WH.Tooltip.screenInnerWrapper.scrollLeft = 0;
            WH.Tooltip.screen.style.display = "none";
            WH.Tooltip.mobileTooltipShown = false
        }
        let t = e === true ? WH.Tooltip.showingTooltip : false;
        WH.Tooltip.hide();
        WH.Tooltip.showingTooltip = t
    };
    this.getEntity = function (e, t, a, i) {
        if (i === undefined) {
            i = Locale.getId()
        }
        if (!a) {
            a = WH.getDataEnv()
        }
        var n = z(e);
        n[t] = n[t] || {};
        n[t][a] = n[t][a] || {};
        n[t][a][i] = n[t][a][i] || {status: _, callbacks: [], data: {}};
        return n[t][a][i]
    };
    this.init = function () {
        B();
        ue((function () {
            if (Z("renameLinks") || Z("colorLinks") || Z("iconizeLinks") || Z("iconSize")) {
                let e = G();
                for (let t = 0; t < e.length; t++) {
                    He(e[t])
                }
                oe()
            } else if (document.querySelectorAll) {
                let e = ['a[href*="wowhead.com/talent-calc/embed/"]', 'a[href*="wowhead.com/soulbind-calc/embed/"]', 'a[href*="wowhead.com/diablo-2/skill-calc/embed/"]'].join(",");
                let t = document.querySelectorAll(e);
                for (let e = 0; e < t.length; e++) {
                    He(t[e])
                }
            }
        }))
    };
    this.onScalesAvailable = function (e, t, a) {
        be.registerCallback(e, t, a)
    };
    this.refreshLinks = function (e) {
        if (e === true || Z("renameLinks") || Z("colorLinks") || Z("iconizeLinks")) {
            let e = G();
            for (let i, n = 0; i = e[n]; n++) {
                var t = i.parentNode;
                var a = false;
                while (t != null) {
                    let e = t.getAttribute && t.getAttribute("class") || "";
                    if ((" " + e + " ").replace(/[\n\t]/g, " ").indexOf(" wowhead-tooltip ") > -1) {
                        a = true;
                        break
                    }
                    t = t.parentNode
                }
                if (!a) {
                    He(i);
                    if (O) {
                        U(i)
                    }
                }
            }
        }
        WH.Tooltip.hide()
    };
    this.register = function (t, a, n, r, o) {
        let s = this.getEntity(t, a, n, r);
        {
            let a = o.additionalIds || [];
            delete o.additionalIds;
            a.forEach((a => e.register(t, a, n, r, o)))
        }
        {
            if (!be.isLoaded(t, n)) {
                s.status = k;
                be.registerCallback(t, n, e.register.bind(this, t, a, n, r, o));
                return
            }
            if (typeof a === "string" && (a.indexOf("lvl") === 0 || a.match(/[^i]lvl/)) && !be.isLoaded(i.SPELL, n)) {
                s.status = k;
                be.registerCallback(i.SPELL, n, e.register.bind(this, t, a, n, r, o));
                return
            }
        }
        if (s.timer) {
            clearTimeout(s.timer);
            delete s.timer
        }
        if (!WH.REMOTE && o.map) {
            if (!s.data.map) {
                s.data.map = new Mapper({parent: WH.ce("div"), zoom: 3, zoomable: false, buttons: false})
            }
            s.data.map.update(o.map);
            delete o.map
        }
        for (var l in o) {
            if (!o.hasOwnProperty(l)) {
                continue
            }
            s.data[l] = o[l]
        }
        switch (s.status) {
            case A:
            case k:
            case M:
            case _:
                if (s.data[ae()]) {
                    s.status = R
                } else {
                    s.status = L
                }
        }
        if (WH.Tooltip.showingTooltip && P.show.type === t && P.show.fullId === a && P.show.dataEnv === n && P.show.locale === r) {
            Te()
        }
        while (s.callbacks.length) {
            s.callbacks.shift()()
        }
    };
    this.replaceWithTooltip = function (e, t, a, i, n, r, o) {
        r = r || {};
        if (n === undefined) {
            n = Locale.getId()
        }
        if (!i) {
            i = WH.getDataEnv()
        }
        if (typeof e === "string") {
            e = document.getElementById(e)
        }
        if (!e) {
            return false
        }
        var s = K(t, a, r);
        var l = this.getEntity(t, s, i, n);
        switch (l.status) {
            case R:
                if (!e.parentNode) {
                    return true
                }
                while (e.hasChildNodes()) {
                    e.removeChild(e.firstChild)
                }
                var c = ["wowhead-tooltip-inline"];
                let p = d[t].embeddedIcons ? undefined : l.data.icon;
                if (p) {
                    c.push("wowhead-tooltip-inline-icon")
                }
                D(e, c);
                var u = l.data[ae()];
                let f = function (a) {
                    if (typeof o === "function") {
                        a = o(a)
                    }
                    WH.Tooltip.append(e, l.data, a, i, t)
                };
                me(u, l.data[te()], f, {type: t, fullId: s, dataEnv: i, locale: n, params: r});
                return true;
            case A:
            case _:
                l.callbacks.push(this.replaceWithTooltip.bind(this, e, t, a, i, n, r, o));
                this.request(t, a, i, n, r);
                return true
        }
        return false
    };
    this.request = function (e, t, a, i, n) {
        n = n || {};
        if (i === undefined) {
            i = Locale.getId()
        }
        if (!a) {
            a = WH.getDataEnv()
        }
        var r = K(e, t, n);
        this.getEntity(e, r, a, i);
        he(e, t, a, i, true, n)
    };
    this.setScales = function (e, t, a) {
        be.setData(e, t, a)
    };
    this.triggerTooltip = function (e, t) {
        He(e, t || {target: e}, true)
    };

    function D(e, t) {
        if (e.classList) {
            for (let a = 0, i = t.length; a < i; a++) {
                e.classList.add(t[a])
            }
        } else {
            for (var a = 0; a < t.length; a++) {
                let i = e.getAttribute && e.getAttribute("class") || "";
                if ((" " + i + " ").indexOf(" " + t[a] + " ") < 0) {
                    e.setAttribute("class", (i ? i + " " : "") + t[a])
                }
            }
        }
    }

    function B() {
        WH.aE(document, "keydown", (function (e) {
            switch (e.keyCode) {
                case 27:
                    $WowheadPower.clearTouchTooltip();
                    WH.Tooltip.hide();
                    break
            }
        }));
        if (O) {
            F()
        } else {
            WH.aE(document, "mouseover", fe)
        }
        B = () => {
        }
    }

    function U(e) {
        if (!e.dataset || e.dataset.hasWhTouchEvent === "true") {
            return
        }
        if (e.onclick == null) {
            e.onclick = ge
        } else {
            WH.aE(e, "click", ge)
        }
        e.dataset.hasWhTouchEvent = "true"
    }

    function F(e) {
        if (!O) {
            return
        }
        ue((function () {
            e = e || document.body;
            var t = WH.gE(e, "a");
            for (var a = 0, i = t.length; a < i; a++) {
                U(t[a])
            }
        }))
    }

    function q(e, t, a, i, n) {
        n = n || {};
        var r = K(e, t, n);
        P.show.type = e;
        P.show.fullId = r;
        P.show.dataEnv = a;
        P.show.locale = i;
        P.show.params = n;
        be.isLoaded(e, a);
        var o = $WowheadPower.getEntity(e, r, a, i);
        if (o.status === R || o.status === L) {
            Te()
        } else if (o.status === A || o.status === k) {
            if (WH.inArray(x, e) === -1) {
                ve(o.status, i, X(i, "loading"))
            }
        } else {
            he(e, t, a, i, WH.inArray(x, e) !== -1, n)
        }
    }

    function G() {
        let e = [];
        for (let t = 0; t < document.links.length; t++) {
            e.push(document.links[t])
        }
        return e
    }

    function z(e) {
        if (typeof d[e] !== "object") {
            throw new Error("Wowhead tooltips could not find config for entity type [" + e + "].")
        }
        return d[e].data
    }

    function j(e) {
        if (typeof d[e] !== "object") {
            WH.error("Wowhead tooltips could not find config for entity type.", e);
            return undefined
        }
        if (!WH.REMOTE || !d[e].hasOwnProperty("maxId")) {
            return undefined
        }
        return {min: d[e].hasOwnProperty("minId") ? d[e].minId : 1, max: d[e].maxId}
    }

    function $(e) {
        if (typeof d[e] !== "object") {
            WH.error("Wowhead tooltips could not find config for entity type.", e);
            return "Entity"
        }
        return d[e].name
    }

    function V(e) {
        if (typeof d[e] !== "object") {
            WH.error("Wowhead tooltips could not find config for entity type.", e);
            return "unknown"
        }
        return d[e].path
    }

    function K(e, t, a) {
        if (a.build) {
            return t + "build" + a.build
        }
        return t + (a.rand ? "r" + a.rand : "") + (a.ench ? "e" + a.ench : "") + (a.gems ? "g" + a.gems.join(",") : "") + (a.sock ? "s" : "") + (a.upgd ? "u" + a.upgd : "") + (a.twtbc ? "twtbc" : "") + (a.twwotlk ? "twwotlk" : "") + (a.twcata ? "twcata" : "") + (a.twmists ? "twmists" : "") + (a.twwod ? "twwod" : "") + (a.ilvl ? "ilvl" + a.ilvl : "") + (a.lvl ? "lvl" + a.lvl : "") + (a.gem1lvl ? "g1lvl" + a.gem1lvl : "") + (a.gem2lvl ? "g2lvl" + a.gem2lvl : "") + (a.gem3lvl ? "g3lvl" + a.gem3lvl : "") + (a.artk ? "ak" + a.artk : "") + (a.nlc ? "nlc" + a.nlc : "") + (a.transmog ? "transmog" + a.transmog : "") + (a.tink ? "tink" + a.tink : "") + (a.pvp ? "pvp" : "") + (a.bonus ? "b" + a.bonus.join(",") : "") + (a.gem1bonus ? "g1b" + a.gem1bonus.join(",") : "") + (a.gem2bonus ? "g2b" + a.gem2bonus.join(",") : "") + (a.gem3bonus ? "g3b" + a.gem3bonus.join(",") : "") + (a["crafted-stats"] ? "craftedStats" + a["crafted-stats"].join(",") : "") + (a.q ? "q" + a.q : "") + (a.level ? "level" + a.level : "") + (a.abil ? "abil" + a.abil.join(",") : "") + (a.dd ? "dd" + a.dd : "") + (a.ddsize ? "ddsize" + a.ddsize : "") + (a.diff === i.SPELL ? "diff" + a.diff : "") + (a.def ? "def" + a.def : "") + (a.rank ? "rank" + a.rank : "") + (a.awakened ? "awakened" + a.awakened : "") + (a["class"] ? "class" + a["class"] : "") + (e !== i.SPELL && a.spec ? "spec" + a.spec : "") + (a.rewards ? "rewards" + a.rewards.join(":") : "") + (a["azerite-powers"] ? "azPowers" + a["azerite-powers"] : "") + (a["azerite-essence-powers"] ? "aePowers" + a["azerite-essence-powers"] : "") + (a.nomajor ? "nomajor" : "") + (a.stars ? "stars" + a.stars : "")
    }

    function J() {
        return P.show.params && P.show.params.text ? "text_icon" : "icon"
    }

    function Y(e) {
        if (typeof e === "undefined") {
            return "image_NONE"
        }
        return "image" + e
    }

    function Q(e, t, a) {
        if (WH.REMOTE) {
            return false
        }
        if (!WH.User.isPremium()) {
            return false
        }
        if (WH.Tooltip.hideScreenshots) {
            return false
        }
        let n = WH.Gatherer.get(e, t, a, true);
        if (n) {
            if (n.screenshot && n.screenshot.id) {
                return [WH.getScreenshotUrl(n.screenshot.id, "small", {imageType: n.screenshot.imageType}), "screenshot"]
            } else if (!WH.REMOTE && e === i.ITEM && n.jsonequip && n.jsonequip.displayid) {
                let e = n.jsonequip.displayid;
                let t = n.reqrace || n.jsonequip.races;
                let a = WH.Wow.Models.getRaceIdFromMask(t);
                if (n.classs && n.classs !== WH.Wow.Item.CLASS_ARMOR || n.jsonequip.slotbak && !WH.Wow.Item.isArmorInvType(n.jsonequip.slotbak)) {
                    a = undefined
                }
                let i = Listview.funcBox.getCurrentItemBonuses.call(this, n);
                let r = g_items.getAppearance(n.id, i);
                if (r && r[0]) {
                    e = r[0]
                }
                if (e) {
                    return [WH.Wow.Item.getThumbUrl(e, a), "screenshot"]
                }
            }
        }
        return false
    }

    function X(e, t) {
        return (N[e] || N[0])[t] || ""
    }

    function Z(e) {
        var t = ee();
        if (!t) {
            return null
        }
        if (!t.hasOwnProperty(e)) {
            if (m[e] && t.hasOwnProperty(m[e])) {
                return t[m[e]]
            }
            return null
        }
        return t[e]
    }

    function ee() {
        if (typeof whTooltips === "object") {
            return whTooltips
        }
        if (typeof wowhead_tooltips === "object") {
            return wowhead_tooltips
        }
        return null
    }

    function te() {
        return (P.show.params && P.show.params.buff ? "buff" : "") + "spells"
    }

    function ae(e) {
        var t = "tooltip";
        if (P.show.params && P.show.params.buff) t = "buff";
        if (P.show.params && P.show.params.text) t = "text";
        if (P.show.params && P.show.params.premium) t = "tooltip_premium";
        return t + (e || "")
    }

    function ie(e) {
        if (!WH.isDev()) {
            return "https://" + e + ".wowhead.com"
        }
        var t = document.location.hostname.split(".");
        t = t[t.length - 3];
        if (e === "www") {
            e = t
        } else {
            e = e + "." + t
        }
        var a = "https://" + e + ".wowhead.com";
        if (document.location.port !== "") {
            a += (document.location.port.indexOf(":") < 0 ? ":" : "") + document.location.port
        }
        if (document.location.protocol !== "https:") {
            a = a.replace(/^https:/, "http:")
        }
        return a
    }

    function ne(e, n, r, o) {
        if (!o || !a.isValidSize(o)) {
            o = "tiny"
        }
        let s = r.icon.toLocaleLowerCase();
        if (o === "tiny") {
            let r = i.getGame(n);
            let o = a.getIconUrl(s, a.TINY, r);
            D(e, ["icontinyl"]);
            e.dataset.game = t.getKey(r);
            e.dataset.type = i.getStringId(n);
            e.style.backgroundImage = "url(" + o + ")"
        } else {
            if (e.getAttribute("data-wh-icon-added") === "true") {
                return
            }
            WH.aef(e, a.createByEntity(r, n, null, {size: o, span: true}))
        }
        e.setAttribute("data-wh-icon-added", "true")
    }

    function re() {
        if (WH.REMOTE) {
            WH.ae(document.head, WH.ce("link", {
                type: "text/css",
                href: WH.STATIC_URL + "/css/universal.css?19",
                rel: "stylesheet"
            }));
            e.init()
        } else {
            B();
            ue((function () {
                be.fetch(i.ITEM, WH.getDataEnv());
                be.fetch(i.SPELL, WH.getDataEnv())
            }))
        }
    }

    function oe() {
        var e = Z("hide");
        if (!e) {
            return
        }
        if (!document.styleSheets) {
            return
        }
        var t = document.createElement("style");
        t.type = "text/css";
        document.head.appendChild(t);
        if (!window.createPopup) {
            document.head.appendChild(WH.ct(""))
        }
        let a = document.styleSheets[document.styleSheets.length - 1];
        for (var i in e) {
            if (!e.hasOwnProperty(i) || !e[i]) {
                continue
            }
            if (a.insertRule) {
                a.insertRule(".wowhead-tooltip .whtt-" + i + "{display: none}", a.cssRules.length)
            } else if (a.addRule) {
                a.addRule(".wowhead-tooltip .whtt-" + i, "display: none", -1)
            }
        }
        oe = () => {
        }
    }

    function se(e) {
        if (typeof d[e] !== "object") {
            WH.error("Wowhead tooltips could not find config for entity type.", e);
            return false
        }
        return d[e].mobile
    }

    function le(e, t, a, i) {
        let n = $WowheadPower.getEntity(e, t, a, i);
        n.status = M;
        if (P.show.type === e && P.show.fullId === t && P.show.dataEnv === a && P.show.locale === i) {
            ve(n.status, i, X(i, "noResponse"))
        }
    }

    function ce(e, t, a, n, r, s, l, c) {
        if (!c.ctrlKey || c.button !== 2) {
            return
        }
        c.preventDefault();
        c.stopPropagation();
        let u = WH.DOM.getData(this, "menu");
        if (u) {
            Menu.show(u, this);
            return
        }
        u = [];
        let d = $WowheadPower.getEntity(a, K(a, r, s), e, t);
        if (d.data.name) {
            u.push(Menu.createItem({
                label: WH.term("copy_format", WH.TERMS.name),
                url: WH.copyToClipboard.bind(undefined, d.data.name)
            }))
        }
        u.push(Menu.createItem({
            label: WH.term("copy_format", WH.TERMS.id),
            url: WH.copyToClipboard.bind(undefined, r)
        }));
        let p = l;
        if (!p && i.existsInDataEnv(a)) {
            p = WH.Entity.getUrl(a, r, undefined, undefined, e, t)
        }
        if (p) {
            u.push(Menu.createItem({
                label: WH.term("copy_format", WH.TERMS.url),
                url: WH.copyToClipboard.bind(undefined, l)
            }))
        }
        let f = o[n] || n;
        if (WH.markup.tags[f]) {
            u.push(Menu.createItem({
                label: WH.term("copy_format", WH.TERMS.wowheadMarkupTag),
                url: WH.copyToClipboard.bind(undefined, "[" + f + "=" + r + "]")
            }))
        }
        Menu.add(this, u, {noEvents: true, showAtElement: true, showImmediately: true}, c)
    }

    function ue(e) {
        if (document.readyState === "loading") {
            document.addEventListener("DOMContentLoaded", e)
        } else {
            e()
        }
    }

    function de(e) {
        Ee(e);
        WH.Tooltip.move(P.cursorX, P.cursorY, 0, 0, l, c)
    }

    function pe() {
        P.show.type = undefined;
        P.element = undefined;
        WH.Tooltip.hide()
    }

    function fe(e) {
        let t = e.target;
        let a = 0;
        while (t && a < 5 && He(t, e) === I) {
            t = t.parentNode;
            a++
        }
    }

    function ge(e) {
        let t = this;
        if (t.hasWHTouchTooltip === true) {
            return
        }
        let a = 0;
        let i;
        while (t && a < 5 && (i = He(t, e)) === I) {
            t = t.parentNode;
            a++
        }
        if (i === w) {
            if (P.touchElement) {
                P.touchElement.removeAttribute("data-showing-touch-tooltip");
                P.touchElement.hasWHTouchTooltip = false
            }
            P.touchElement = t;
            P.touchElement.hasWHTouchTooltip = true;
            if (e.stopPropagation) {
                e.stopPropagation()
            }
            if (e.preventDefault) {
                e.preventDefault()
            }
            return false
        }
    }

    function me(e, t, a, n) {
        switch (n.type) {
            case i.AZERITE_ESSENCE_POWER:
                let r = $WowheadPower.getEntity(n.type, n.fullId, n.dataEnv, n.locale);
                if (n.params.spec && !(n.params.know && n.params.know.length)) {
                    be.getSpellsBySpec(n.params.spec, (function (t) {
                        e = e.replace(/<!--embed:([^>]+)-->/g, (function (e, a) {
                            return WH.setTooltipSpells(r.data.embeds[a].tooltip, t, r.data.embeds[a].spells)
                        }));
                        a(e)
                    }));
                    break
                } else {
                    e = e.replace(/<!--embed:([^>]+)-->/g, (function (e, t) {
                        return WH.setTooltipSpells(r.data.embeds[t].tooltip, n.params.know, r.data.embeds[t].spells)
                    }))
                }
                window.requestAnimationFrame(a.bind(null, e));
                break;
            case i.SPELL:
                if (n.params.spec && !(n.params.know && n.params.know.length)) {
                    be.getSpellsBySpec(n.params.spec, (function (i) {
                        e = WH.setTooltipSpells(e, i, t);
                        a(e)
                    }));
                    break
                }
                window.requestAnimationFrame(a.bind(null, e));
                break;
            default:
                window.requestAnimationFrame(a.bind(null, e))
        }
    }

    function he(e, t, a, r, o, s) {
        var l = K(e, t, s);
        var c = $WowheadPower.getEntity(e, l, a, r);
        if (c.status !== _ && c.status !== M) {
            return
        }
        c.status = A;
        var u = j(e);
        if (u && (parseInt(t, 10) < u.min || parseInt(t, 10) > u.max)) {
            $WowheadPower.register(e, t, a, r, {error: "ID is out of range"});
            return
        }
        if (!o) {
            c.timer = setTimeout(We.bind(this, e, l, a, r), 333)
        }
        var d = [];
        for (var p in s) {
            switch (p) {
                case"spec":
                    if (e === i.SPELL || e === i.AZERITE_ESSENCE_POWER) {
                        break
                    }
                case"abil":
                case"artk":
                case"awakened":
                case"azerite-essence-powers":
                case"azerite-powers":
                case"bonus":
                case"build":
                case"class":
                case"covenant":
                case"crafted-stats":
                case"dd":
                case"ddsize":
                case"def":
                case"diff":
                case"diffnew":
                case"ench":
                case"gem1bonus":
                case"gem1lvl":
                case"gem2bonus":
                case"gem2lvl":
                case"gem3bonus":
                case"gem3lvl":
                case"gems":
                case"gender":
                case"ilvl":
                case"level":
                case"lvl":
                case"nlc":
                case"nomajor":
                case"pvp":
                case"q":
                case"rand":
                case"rank":
                case"rewards":
                case"sock":
                case"stars":
                case"tink":
                case"transmog":
                case"twcata":
                case"twmists":
                case"twtbc":
                case"twwod":
                case"twwotlk":
                case"upgd":
                    if (typeof s[p] === "object") {
                        d.push(p + "=" + s[p].join(":"))
                    } else if (s[p] === true) {
                        d.push(p)
                    } else {
                        d.push(p + "=" + s[p])
                    }
                    break
            }
        }
        d.push("dataEnv=" + a);
        d.push("locale=" + r);
        if (a === WH.dataEnv.PTR || a === WH.dataEnv.BETA) {
            if (WH.getDataCacheVersion(a) !== "0") {
                d.push(WH.getDataCacheVersion(a))
            }
        }
        if (!be.isLoaded(e, a)) {
            be.fetch(e, a)
        }
        if (e === i.ITEM && s && s.hasOwnProperty("lvl") && !be.isLoaded(i.SPELL, a)) {
            be.fetch(i.SPELL, a)
        }
        let f = d.length ? "?" + d.join("&") : "";
        let g = ie(n);
        let m = g + "/tooltip/" + V(e) + "/" + t + f;
        WH.xhrJsonRequest(m, function (e, t, a, i, n, r) {
            if (!r) {
                WH.error("Wowhead tooltips failed to load entity data.", $(e) + " #" + t);
                return
            } else if (r.error) {
                if (!x.includes(e)) {
                    WH.error("Wowhead tooltip request responded with an error.", r.error, $(e) + " #" + t)
                }
            }
            $WowheadPower.register(e, a, i, n, r)
        }.bind(null, e, t, l, a, r))
    }

    function He(e, a, n) {
        if (a && e.dataset && e.dataset.simpleTooltip) {
            if (!O && !e.onmouseout) {
                if (e.dataset.tooltipMode !== "attach") {
                    e.onmousemove = de
                }
                e.onmouseout = pe
            }
            WH.Tooltip.show(e, e.dataset.simpleTooltip.length < 30 ? '<div class="no-wrap">' + e.dataset.simpleTooltip + "</div>" : e.dataset.simpleTooltip);
            return w
        }
        if (e.nodeName !== "A" && e.nodeName !== "AREA") {
            return I
        }
        var o = e.rel;
        try {
            if (e.dataset && e.dataset.hasOwnProperty("wowhead")) {
                o = e.dataset.wowhead
            } else if (e.getAttribute && e.getAttribute("data-wowhead")) {
                o = e.getAttribute("data-wowhead")
            }
        } catch (e) {
        }
        if (!e.href.length && !o || o && /^np\b/.test(o) || e.getAttribute("data-disable-wowhead-tooltip") === "true" || O && e.getAttribute("data-disable-wowhead-touch-tooltip") === "true") {
            return S
        }
        let s = /^https?:\/\/(?:[^/]+\.)?(classic|tbc)\.(?:[^/]+\.)?wowhead\.com\/talent-calc\/embed\/[^#]+/;
        let l = e.href.match(s);
        if (!l) {
            l = e.href.match(/^https?:\/\/(?:[^/]+\.)?wowhead\.com\/(classic|tbc|wotlk)\/talent-calc\/embed\/[^#]+/)
        }
        if (WH.REMOTE && l) {
            let t = 513;
            let a = 750;
            if (l[1] === "tbc") {
                t += 120
            } else if (l[1] === "wotlk") {
                t += 517
            }
            let i = t / a * 100 + "%";
            e.parentNode.replaceChild(WH.ce("div", {
                style: {
                    margin: "10px auto",
                    maxHeight: t + "px",
                    maxWidth: a + "px"
                }, className: "wowhead-embed wowhead-embed-talent-calc"
            }, WH.ce("div", {
                style: {
                    height: 0,
                    paddingTop: i,
                    position: "relative",
                    width: "100%"
                }
            }, WH.ce("iframe", {
                src: l[0],
                width: "100%",
                height: "100%",
                style: {border: 0, left: 0, position: "absolute", top: 0, borderRadius: "6px"},
                sandbox: "allow-scripts allow-top-navigation"
            }))), e);
            return w
        }
        let c = /^https?:\/\/(?:[^/]+\.)?wowhead\.com\/soulbind-calc\/embed\/.+/;
        let d = e.href.match(c);
        if (WH.REMOTE && d) {
            e.parentNode.replaceChild(WH.ce("div", {
                style: {
                    maxWidth: "734px",
                    maxHeight: "1060px",
                    margin: "10px auto"
                }, className: "wowhead-embed wowhead-embed-soulbind-calc"
            }, WH.ce("div", {
                style: {
                    position: "relative",
                    width: "100%",
                    height: 0,
                    paddingTop: "144.5%"
                }
            }, WH.ce("iframe", {
                src: d[0],
                width: "100%",
                height: "100%",
                style: {border: 0, left: 0, position: "absolute", top: 0, borderRadius: "6px"},
                sandbox: "allow-scripts allow-top-navigation"
            }))), e);
            return w
        }
        let g = /^https?:\/\/(?:[^/]+\.)?wowhead\.com\/diablo-2\/skill-calc\/embed\/.+/;
        let m = e.href.match(g);
        if (WH.REMOTE && m) {
            e.parentNode.replaceChild(WH.ce("div", {
                style: {margin: "10px auto"},
                className: "wowhead-embed wowhead-embed-diablo-2-skill-calc"
            }, WH.ce("div", {
                style: {
                    position: "relative",
                    width: "100%",
                    height: 0,
                    paddingTop: "50%"
                }
            }, WH.ce("iframe", {
                src: m[0],
                width: "100%",
                height: "100%",
                style: {border: 0, left: 0, position: "absolute", top: 0, borderRadius: "6px"},
                sandbox: "allow-scripts allow-top-navigation"
            }))), e);
            return w
        }
        let h = {};
        P.show.params = h;
        let H = function (e, t, a) {
            switch (t) {
                case"awakened":
                case"buff":
                case"map":
                case"noimage":
                case"nomajor":
                case"notip":
                case"premium":
                case"pvp":
                case"sock":
                case"text":
                case"twcata":
                case"twmists":
                case"twtbc":
                case"twwod":
                case"twwotlk":
                    h[t] = true;
                    break;
                case"artk":
                case"c":
                case"class":
                case"covenant":
                case"dd":
                case"ddsize":
                case"def":
                case"diff":
                case"diffnew":
                case"ench":
                case"gem1lvl":
                case"gem2lvl":
                case"gem3lvl":
                case"ilvl":
                case"level":
                case"lvl":
                case"nlc":
                case"pwr":
                case"q":
                case"rand":
                case"rank":
                case"spec":
                case"stars":
                case"tink":
                case"upgd":
                    h[t] = parseInt(a);
                    break;
                case"abil":
                case"azerite-essence-powers":
                case"azerite-powers":
                case"bonus":
                case"crafted-stats":
                case"cri":
                case"forg":
                case"gem1bonus":
                case"gem2bonus":
                case"gem3bonus":
                case"gems":
                case"know":
                case"pcs":
                case"rewards":
                    h[t] = a.split(":");
                    break;
                case"build":
                case"domain":
                case"gender":
                case"who":
                    h[t] = a;
                    break;
                case"image":
                    if (a === "premium") {
                        h[a] = true
                    } else {
                        h[t] = a ? "_" + a : ""
                    }
                    break;
                case"transmog":
                    if (a === "hidden") {
                        h[t] = a
                    } else {
                        h[t] = parseInt(a)
                    }
                    break;
                case"when":
                    h[t] = new Date(parseInt(a));
                    break
            }
        };
        let M;
        let L;
        let C;
        let x;
        if (e.href.indexOf("http://") === 0 || e.href.indexOf("https://") === 0) {
            let t = e.href.match(/^https?:\/\/(.+?)?\.?(?:wowhead)\.com(?:\:\d+)?\/\??(item|quest|spell|zone|achievement|event|itemset|item-set|transmog-set|outfit|guide|statistic|currency|npc|object|pet-ability|petability|building|follower|champion|bfa-champion|garrisonability|mission-ability|mission|ship|threat|resource|order-advancement|affix|azerite-essence|azerite-essence-power|storyline|adventure-combatant-ability|mount|recipe|battle-pet)[=/](?:[^/?&#]+-)?(-?\d+(?:\.\d+)?)/);
            if (!t) {
                t = e.href.match(/^https?:\/\/(.+?)?\.?(?:wowhead)\.com(?:\:\d+)?\/(guide)s\/([^\?&#]+)/)
            }
            if (t) {
                M = t[1];
                L = t[2];
                C = t[3];
                x = e.href
            } else {
                let t = [{
                    path: "beta",
                    env: WH.dataEnv.BETA,
                    entityTypeNames: p,
                    prefixedEntityTypeNames: [],
                    typeNamePrefix: ""
                }, {
                    path: "diablo-immortal",
                    env: WH.dataEnv.DI,
                    entityTypeNames: ["equip-item", "misc-item", "npc", "paragon-skill", "set", "skill"],
                    prefixedEntityTypeNames: ["equip-item", "misc-item", "npc", "paragon-skill", "set", "skill"],
                    typeNamePrefix: "di-"
                }, {
                    path: "diablo-2",
                    env: WH.dataEnv.D2,
                    entityTypeNames: [],
                    prefixedEntityTypeNames: [],
                    typeNamePrefix: "d2-"
                }, {
                    path: "wotlk",
                    env: WH.dataEnv.WRATH,
                    entityTypeNames: f,
                    prefixedEntityTypeNames: [],
                    typeNamePrefix: ""
                }];
                t.some((t => {
                    let a = false;
                    if (t.entityTypeNames.length) {
                        a = e.href.match(new RegExp("^https?:\\/\\/(?:\\w+\\.)*wowhead\\.com(?:\\:\\d+)?\\/" + t.path + "\\/(?:(\\w\\w)\\/)?(" + t.entityTypeNames.join("|") + ")\\/(?:[^/?&#]+-)?(\\d+)")) || e.href.match(new RegExp("^https?:\\/\\/(?:\\w+\\.)*wowhead\\.com(?:\\:\\d+)?\\/" + t.path + "\\/(?:(\\w\\w)\\/)?(" + t.entityTypeNames.join("|") + ")=(-?\\d+(?:\\.\\d+)?)"))
                    }
                    if (!a) {
                        a = e.href.match(new RegExp("^https?:\\/\\/(?:\\w+\\.)*wowhead\\.com(?:\\:\\d+)?\\/" + t.path + "\\/(?:(\\w\\w)\\/)?(guide)s\\/([^\\?&#]+)"))
                    }
                    if (a) {
                        M = (a[1] ? a[1] + "." : "") + WH.getDataEnvKey(t.env);
                        L = (t.prefixedEntityTypeNames.includes(a[2]) ? t.typeNamePrefix : "") + a[2];
                        C = a[3];
                        x = e.href
                    }
                    return !!a
                }))
            }
            P.show.hasLogo = false
        } else {
            let t = e.href.match(/\/\??(item|quest|spell|zone|achievement|event|itemset|item-set|transmog-set|outfit|statistic|currency|npc|object|pet-ability|petability|building|follower|champion|bfa-champion|garrisonability|mission-ability|mission|ship|threat|resource|order-advancement|affix|azerite-essence|azerite-essence-power|storyline|adventure-combatant-ability|guide|mount|recipe|battle-pet)[=/](?:[^/?&#]+-)?(-?\d+(?:\.\d+)?)/);
            if (!t) {
                t = e.href.match(/\/(guide)s\/([^\?&#]+)/)
            }
            if (t) {
                L = t[1];
                C = t[2];
                x = e.href
            }
            P.show.hasLogo = true
        }
        if (o && (!L || /\bignore-url\b/.test(o))) {
            let e = o.match(/(item|quest|spell|zone|achievement|event|itemset|item-set|transmog-set|outfit|statistic|currency|npc|object|pet-ability|petability|building|follower|champion|bfa-champion|garrisonability|mission-ability|mission|ship|threat|resource|order-advancement|affix|azerite-essence|azerite-essence-power|storyline|adventure-combatant-ability|guide|mount|recipe|battle-pet|di-equip-item|di-misc-item|di-npc|di-paragon-skill|di-quest|di-set|di-skill|di-zone).?(-?\d+(?:\.\d+)?)/);
            if (e) {
                L = e[1];
                C = e[2]
            }
            P.show.hasLogo = true
        }
        if (!L) {
            return S
        }
        let N = WH.getTypeIdFromTypeString(L);
        if (O && !n && !se(N)) {
            return S
        }
        e.href.replace(/([a-zA-Z0-9-]+)=?([^&?#]*)/g, H);
        if (o) {
            o.replace(/([a-zA-Z0-9-]+)=?([^&?#]*)/g, H)
        }
        if (h.gems && h.gems.length > 0) {
            var B;
            for (B = Math.min(3, h.gems.length - 1); B >= 0; --B) {
                if (parseInt(h.gems[B])) {
                    break
                }
            }
            ++B;
            if (B === 0) {
                delete h.gems
            } else if (B < h.gems.length) {
                h.gems = h.gems.slice(0, B)
            }
        }
        var U = ["bonus", "gem1bonus", "gem2bonus", "gem3bonus"];
        for (var F = 0, G; G = U[F]; F++) {
            if (h[G] && h[G].length > 0) {
                for (B = Math.min(16, h[G].length - 1); B >= 0; --B) {
                    if (parseInt(h[G][B])) {
                        break
                    }
                }
                ++B;
                if (B === 0) {
                    delete h[G]
                } else if (B < h[G].length) {
                    h[G] = h[G].slice(0, B)
                }
            }
        }
        if (h["crafted-stats"] && h["crafted-stats"].length > 0) {
            let e = [];
            for (let t = 0; t < Math.min(2, h["crafted-stats"].length); t++) {
                let a = parseInt(h["crafted-stats"][t]);
                if (!isNaN(a)) {
                    e.push(a)
                }
            }
            if (e.length === 0) {
                delete h["crafted-stats"]
            } else {
                h["crafted-stats"] = e
            }
        }
        if (h.abil && h.abil.length > 0) {
            var B, z = [], j;
            for (B = 0; B < Math.min(8, h.abil.length); B++) {
                if (j = parseInt(h.abil[B])) {
                    z.push(j)
                }
            }
            if (z.length === 0) {
                delete h.abil
            } else {
                h.abil = z
            }
        }
        if (h.rewards && h.rewards.length > 0) {
            var B;
            for (B = Math.min(3, h.rewards.length - 1); B >= 0; --B) {
                if (/^\d+.\d+$/.test(h.rewards[B])) {
                    break
                }
            }
            ++B;
            if (B === 0) {
                delete h.rewards
            } else if (B < h.rewards.length) {
                h.rewards = h.rewards.slice(0, B)
            }
        }
        P.element = e;
        {
            var $ = null;
            var V = Locale.getId();
            var J = WH.getDataEnv();
            if (h.domain) {
                $ = h.domain.toLowerCase()
            } else if (M) {
                $ = M.toLowerCase().replace(/(?:^|\.)(staging|dev)$/, "")
            } else {
                let e = WH.Types.getRequiredTrees(N) || [];
                if (e.length && !e.includes(WH.getDataTree(J))) {
                    J = WH.getRootByTree(e[0])
                }
            }
            if ($ !== null) {
                J = WH.dataEnv.MAIN;
                V = WH.getLocaleFromDomain($);
                for (let e in WH.dataEnv) {
                    if (!WH.dataEnv.hasOwnProperty(e) || !WH.dataEnvKey.hasOwnProperty(WH.dataEnv[e])) {
                        continue
                    }
                    if (new RegExp("\\b(" + [WH.dataEnvTerm[WH.dataEnv[e]], WH.dataEnvKey[WH.dataEnv[e]], t.getSelectorByDataEnv(WH.dataEnv[e])].filter((e => !!e)).join("|") + ")\\b").test($)) {
                        J = WH.dataEnv[e];
                        break
                    }
                }
            }
            if (J === WH.dataEnv.PTR && !WH.isPtrActive()) {
                J = WH.dataEnv.MAIN
            }
            if (J === WH.dataEnv.BETA && !WH.isBetaActive()) {
                J = WH.dataEnv.MAIN
            }
            if ([WH.dataEnv.BETA, WH.dataEnv.PTR].indexOf(J) >= 0) {
                V = 0
            }
        }
        if (e.href.indexOf("#") !== -1 && document.location.href.indexOf(L + "=" + C) !== -1) {
            return S
        }
        P.show.mode = W;
        if (O && !e.dataset.noTouchLightbox && document.documentElement.offsetWidth < r) {
            P.show.mode = y
        } else if ((e.parentNode.getAttribute && e.parentNode.getAttribute("class") || "").indexOf("icon") === 0 && e.parentNode.nodeName === "DIV" || e.dataset.whattach === "icon" || e.dataset.tooltipMode === "icon") {
            P.show.mode = b
        } else {
            if (O || e.dataset.whattach === "true" || e.dataset.tooltipMode === "attach") {
                P.show.mode = T
            } else if (!WH.REMOTE) {
                var Y = e.parentNode;
                var Q = 0;
                while (Y) {
                    if ((Y.getAttribute && Y.getAttribute("class") || "").indexOf("menu-inner") === 0) {
                        P.show.mode = E;
                        break
                    }
                    Q++;
                    if (Q > 9) {
                        break
                    }
                    Y = Y.parentNode
                }
            }
        }
        if (!O && !e.onmouseout) {
            if (P.show.mode === W) {
                e.onmousemove = de
            }
            e.onmouseout = pe
        }
        if (P.show.mode === W && e.dataset.whtticon === "false") {
            P.show.mode = v
        }
        if (!WH.REMOTE && !e.whContextMenuAttached) {
            e.whContextMenuAttached = true;
            WH.aE(e, "contextmenu", ce.bind(e, J, V, N, L, C, h, x))
        }
        if (a) {
            P.initiatedByUser = true;
            Ee(a);
            WH.Tooltip.showingTooltip = true;
            q(N, C, J, V, h)
        }
        if (a || !ee()) {
            return w
        }
        var X = $WowheadPower.getEntity(N, K(N, C, h), J, V);
        var te = [];
        if (Z("renameLinks") && e.getAttribute("data-wh-rename-link") !== "false" || e.getAttribute("data-wh-rename-link") === "true") {
            te.push((function () {
                delete e.dataset.whIconAdded;
                e.innerHTML = "<span>" + X.data.name + "</span>"
            }))
        }
        var ae = e.getAttribute("data-wh-icon-size");
        if ((ae || Z("iconizeLinks")) && u.indexOf(N) !== -1) {
            if (!ae) {
                ae = Z("iconSize")
            }
            te.push((function () {
                if (X.data.icon && e.dataset.whIconAdded !== "true") {
                    ne(e, N, X.data, ae)
                }
            }))
        }
        if (Z("colorLinks")) {
            switch (t.getByEnv(J)) {
                case t.DI:
                    switch (N) {
                        case i.DI_EQUIP_ITEM:
                        case i.DI_MISC_ITEM:
                            te.push((() => {
                                if (X.data.inventoryColor != null) {
                                    D(e, ["di-ic" + X.data.inventoryColor])
                                }
                                if (X.data.dropRank != null) {
                                    D(e, ["q" + X.data.dropRank])
                                }
                            }));
                            break;
                        case i.DI_SET:
                            te.push((() => {
                                if (X.data.inventoryColor != null) {
                                    D(e, ["di-ic" + X.data.inventoryColor])
                                }
                            }));
                            break
                    }
                    break;
                case t.WOW:
                    te.push((() => {
                        if (X.data.quality != null && X.data.quality > -1) {
                            D(e, ["q" + X.data.quality])
                        }
                    }));
                    break
            }
        }
        if (te.length) {
            if (X.status === _ || X.status === A) {
                X.callbacks = X.callbacks.concat(te);
                if (X.status === _) {
                    he(N, C, J, V, true, h)
                }
            } else if (X.status === R || X.status === k) {
                while (te.length) {
                    te.shift()()
                }
            }
        }
        return w
    }

    function We(e, t, a, i) {
        if (P.show.type === e && P.show.fullId === t && P.show.dataEnv === a && P.show.locale === i) {
            ve(A, i, X(i, "loading"));
            let n = $WowheadPower.getEntity(e, t, a, i);
            n.timer = setTimeout(le.bind(this, e, t, a, i), 3850)
        }
    }

    function ve(e, t, n, r, o, u, d, p, f, m) {
        oe();
        if (!P.initiatedByUser) {
            return
        }
        if (P.element) {
            if (P.element._fixTooltip) {
                n = P.element._fixTooltip(n, P.show.type, P.show.fullId, P.element)
            }
            if (P.element._fixTooltip2) {
                p = P.element._fixTooltip2(p, P.show.type, P.show.fullId, P.element)
            }
        }
        if (!n) {
            n = X(t, "notFound").replace("%s", $(P.show.type));
            e = L;
            o = a.UNKNOWN
        } else if (P.show.params) {
            let e = P.show.params;
            if (WH.reforgeStats && e.forg && WH.reforgeStats[e.forg]) {
                var I = WH.reforgeStats[e.forg];
                var S = [I.i1];
                for (var w in WH.individualToGlobalStat) {
                    if (WH.individualToGlobalStat[w] === S[0]) {
                        S.push(w)
                    }
                }
                var _;
                if ((_ = n.match(new RegExp("(\x3c!--(stat|rtg)(" + S.join("|") + ")--\x3e)[+-]?([0-9]+)"))) && !n.match(new RegExp("\x3c!--(stat|rtg)" + I.i2 + "--\x3e[+-]?[0-9]+"))) {
                    var A = Math.floor(_[4] * I.v), M = g.traits[I.s2][0];
                    if (I.i2 == 6) {
                        n = n.replace("\x3c!--rs--\x3e", "<br />+" + A + " " + M)
                    } else {
                        n = n.replace("\x3c!--rr--\x3e", WH.sprintfGlobal(h.genericequip_tip, M.toLowerCase(), I.i2, A))
                    }
                    n = n.replace(_[0], _[1] + (_[4] - A));
                    n = n.replace("\x3c!--rf--\x3e", '<span class="q2">' + WH.sprintfGlobal(h.reforged_format, A, g.traits[I.s1][2], g.traits[I.s2][2]) + "</span><br />")
                }
            }
            if (e.pcs && e.pcs.length) {
                var k = 0;
                for (var w = 0, C = e.pcs.length; w < C; ++w) {
                    var _;
                    var x = new RegExp("<span>\x3c!--si([0-9]+:)*" + e.pcs[w] + "(:[0-9]+)*--\x3e" + '<a href="/??item=(\\d+)[^"]*">(.+?)</a></span>');
                    if (_ = n.match(x)) {
                        let t = !isNaN(parseInt(P.show.locale)) ? H[P.show.locale] : "enus";
                        var N = WH.isSet("g_items") && g_items[e.pcs[w]] ? g_items[e.pcs[w]]["name_" + t] : _[4];
                        let a = WH.REMOTE ? "javascript:" : WH.Entity.getUrl(WH.Types.ITEM, _[3]);
                        var O = '<a href="' + a + '">' + N + "</a>";
                        var D = '<span class="q13">\x3c!--si' + e.pcs[w] + "--\x3e" + O + "</span>";
                        n = n.replace(_[0], D);
                        ++k
                    }
                }
                if (k > 0) {
                    n = n.replace("(0/", "(" + k + "/");
                    n = n.replace(new RegExp("<span>\\(([0-" + k + "])\\)", "g"), '<span class="q2">($1)')
                }
            }
            if (e.know && e.know.length) {
                n = WH.setTooltipSpells(n, e.know, d)
            }
            if (e.lvl && !e.ilvl) {
                n = WH.setTooltipLevel(n, e.lvl ? e.lvl : WH.maxLevel, e.buff)
            }
            if (e.covenant) {
                n = WH.setTooltipSpells(n, [s[e.covenant]], d)
            }
            if (e.who && e.when) {
                n = n.replace("<table><tr><td><br />", '<table><tr><td><br /><span class="q2">' + WH.sprintf(X(t, "achievementComplete"), e.who, e.when.getMonth() + 1, e.when.getDate(), e.when.getFullYear()) + "</span><br /><br />");
                n = n.replace(/class="q0"/g, 'class="r3"')
            }
            if (e.notip && f) {
                n = "";
                o = undefined
            }
            if (P.show.type === i.BATTLE_PET_ABILITY && e.pwr) {
                n = n.replace(/<!--sca-->(\d+)<!--sca-->/g, (function (t, a) {
                    return Math.floor(parseInt(a) * (1 + .05 * e.pwr))
                }))
            }
            if (P.show.type === i.ACHIEVEMENT && e.cri) {
                for (var w = 0; w < e.cri.length; w++) {
                    n = n.replace(new RegExp("\x3c!--cr" + parseInt(e.cri[w]) + ":[^<]+", "g"), '<span class="q2">$&</span>')
                }
            }
        }
        if (P.showCharacterCompletion && window.g_user && (WH.isRetailTree(P.show.dataEnv) && g_user.lists || !WH.isRetailTree(P.show.dataEnv) && g_user.characterProfiles && g_user.characterProfiles.length)) {
            var B = "";
            let t = WH.isRetailTree(P.show.dataEnv) ? WH.User.Completion.getByType(P.show.type) : false;
            let a = $WowheadPower.getEntity(P.show.type, P.show.fullId, P.show.dataEnv, P.show.locale);
            if (t && P.show.type === i.QUEST) {
                if (e !== R || a.worldquesttype || a.daily || a.weekly) {
                    t = false
                }
            }
            let r = !(t && P.show.type in g_completion_categories && WH.inArray(g_completion_categories[P.show.type], a.completion_category) === -1);
            let o = /^-?\d+(?:\.\d+)?/.exec(P.show.fullId);
            o = o && o.length ? o[0] : P.show.fullId;
            if (t) {
                for (var U in g_user.lists) {
                    var F = g_user.lists[U];
                    if (!(F.id in t)) {
                        continue
                    }
                    let e = WH.inArray(t[F.id], o) !== -1;
                    if (!e && !r) {
                        continue
                    }
                    B += '<br><span class="progress-icon ' + (e ? "progress-8" : "progress-0") + '"></span> ';
                    B += F.character + " - " + F.realm + " " + F.region
                }
            }
            if (!WH.isRetailTree(P.show.dataEnv) && P.show.type === i.QUEST) {
                for (var q, w = 0; q = g_user.characterProfiles[w]; w++) {
                    let e = WH.inArray(q.quests, o) !== -1;
                    if (!e && !r) {
                        continue
                    }
                    B += '<br><span class="progress-icon ' + (e ? "progress-8" : "progress-0");
                    B += '"></span> ' + q.name + " - " + q.realm
                }
            }
            if (WH.isRetailTree(P.show.dataEnv) && P.show.type === i.TRANSMOG_SET) {
                (g_user.lists || []).forEach((function (e) {
                    let t = WH.Wow.TransmogSet.getCompletionAmount(a.data.completionData || {}, e.id);
                    if (t > 0) {
                        B += '<br><span class="progress-icon progress-' + Math.max(1, Math.floor(t * 8)) + '"></span> ';
                        B += e.character + " - " + e.realm + " " + e.region
                    }
                }))
            }
            if (B !== "") {
                n += '<br><span class="q">' + WH.TERMS.completion + ":</span>" + B
            }
        }
        if (!WH.REMOTE && [i.TRANSMOG_SET, i.ITEM_SET].includes(P.show.type) && typeof WH.getPreferredTransmogRace !== "undefined") {
            let e = WH.getPreferredTransmogRace();
            let t = e.race;
            let a = e.gender - 1;
            let r = WH.ce("div", {innerHTML: n});
            let o = WH.qs("picture", r);
            if (o) {
                if (o.dataset.requiredRace && !P.element.dataset.tooltipIgnoreRequiredRace) {
                    t = o.dataset.requiredRace
                }
                let e = P.show.type === i.ITEM_SET ? WH.Wow.ItemSet : WH.Wow.TransmogSet;
                o.parentNode.replaceChild(WH.ce("img", {
                    src: e.getThumbUrl(P.show.fullId, t, a, P.show.dataEnv),
                    width: 260,
                    height: 440,
                    style: {display: "block", margin: "0 auto"}
                }), o);
                n = r.innerHTML
            }
        }
        if (!WH.REMOTE && n && (P.show.params.diff || P.show.params.diffnew || P.show.params.noimage)) {
            f = "";
            m = ""
        }
        n = n.replace("http://", "https://");
        if (P.show.params.map && u && u.getMap) {
            p = u.getMap()
        }
        let G = function (e, t, a) {
            if (P.show.type !== t.type || P.show.fullId !== t.fullId || P.show.dataEnv !== t.dataEnv || P.show.locale !== t.locale || P.show.params !== t.params) {
                return
            }
            let i = WH.isElementFixedPosition(P.element);
            switch (P.show.mode) {
                case y:
                    WH.Tooltip.showInScreen(P.element, a, {
                        dataEnv: t.dataEnv,
                        entity: r,
                        iconName: o,
                        image: f,
                        imageClass: m,
                        showIcon: true,
                        status: e,
                        text2: p,
                        type: t.type
                    });
                    break;
                case b:
                    WH.Tooltip.show(P.element, a, undefined, {
                        dataEnv: t.dataEnv,
                        entity: r,
                        fixedPosition: i,
                        image: f,
                        imageClass: m,
                        showIcon: false,
                        status: e,
                        text2: p,
                        type: t.type
                    });
                    break;
                case T:
                    WH.Tooltip.show(P.element, a, undefined, {
                        dataEnv: t.dataEnv,
                        entity: r,
                        fixedPosition: i,
                        iconName: o,
                        image: f,
                        imageClass: m,
                        showIcon: true,
                        status: e,
                        text2: p,
                        type: t.type
                    });
                    break;
                case E:
                    WH.Tooltip.show(P.element, a, undefined, {
                        dataEnv: t.dataEnv,
                        entity: r,
                        fixedPosition: i,
                        iconName: o,
                        showIcon: true,
                        text2: p,
                        type: t.type
                    });
                    break;
                case v:
                    WH.Tooltip.showAtPoint(a, P.cursorX, P.cursorY, {
                        dataEnv: t.dataEnv,
                        entity: r,
                        fixedPosition: i,
                        image: f,
                        imageClass: m,
                        padX: l,
                        padY: c,
                        showIcon: false,
                        status: e,
                        text2: p,
                        type: t.type
                    });
                    break;
                case W:
                default:
                    WH.Tooltip.showAtPoint(a, P.cursorX, P.cursorY, {
                        dataEnv: t.dataEnv,
                        entity: r,
                        fixedPosition: i,
                        iconName: o,
                        image: f,
                        imageClass: m,
                        padX: l,
                        padY: c,
                        showIcon: true,
                        status: e,
                        text2: p,
                        type: t.type
                    })
            }
            if (WH.REMOTE && WH.Tooltip.logo) {
                WH.Tooltip.logo.style.display = P.show.hasLogo ? "block" : "none"
            }
        };
        let z = {
            type: P.show.type,
            fullId: P.show.fullId,
            dataEnv: P.show.dataEnv,
            locale: P.show.locale,
            params: P.show.params
        };
        me(n, d, G.bind(this, e, z), z)
    }

    function Te() {
        let e = $WowheadPower.getEntity(P.show.type, P.show.fullId, P.show.dataEnv, P.show.locale);
        if (x.includes(P.show.type) && !e.data[ae()]) {
            pe();
            return
        }
        let t = e.data[Y(P.show.params["image"])];
        let a = e.data["image" + P.show.params["image"] + "_class"];
        let i = Q(P.show.type, P.show.fullId, P.show.dataEnv);
        if (i) {
            t = i[0];
            a = i[1]
        }
        ve(e.status, P.show.locale, e.data[ae()], e.data, e.data[J()], e.data.map, e.data[te()], e.data[ae(2)], t, a)
    }

    function Ee(e) {
        P.cursorX = e.pageX;
        P.cursorY = e.pageY
    }

    window.Locale = window.Locale || {
        getId: function () {
            return 0
        }, getName: function () {
            return "enus"
        }
    };
    let be = new function () {
        const e = this;
        var t = {};
        var a = {};
        var r = {};
        var o = {};
        this.fetch = function (e, i) {
            if (!o.hasOwnProperty(e) || o[e].hasOwnProperty(i)) {
                return
            }
            o[e][i] = A;
            t[e][i] = [];
            let r;
            if (WH.REMOTE) {
                r = ie(n) + a[e]
            } else {
                r = WH.Url.getDataPageUrl(a[e].replace("/data/", ""))
            }
            r += "&json";
            WH.xhrJsonRequest(r, function (e, t, a) {
                if (!a) {
                    WH.error("Wowhead tooltips failed to load entity scaling data.", $(e));
                    return
                }
                be.setData(e, t, a)
            }.bind(null, e, i))
        };
        this.getSpellsBySpec = function (e, t) {
            let a = P.show.dataEnv || WH.getDataEnv();
            this.registerCallback(i.PLAYER_CLASS, a, (function () {
                var n = r[i.PLAYER_CLASS][a];
                var o = [];
                if (n.specMap.hasOwnProperty(e)) {
                    o = n["class"][n.specMap[e]].concat(n.spec[e] || [])
                }
                t(o)
            }))
        };
        this.isLoaded = function (e, t) {
            if (!o.hasOwnProperty(e)) {
                return true
            }
            if (o[e][t] === R) {
                l();
                return true
            }
            return false
        };
        this.registerCallback = function (a, i, n) {
            if (e.isLoaded(a, i)) {
                window.requestAnimationFrame(n);
                return
            }
            if (!t[a].hasOwnProperty(i)) {
                e.fetch(a, i)
            }
            t[a][i].push(n)
        };
        this.setData = function (e, a, i) {
            o[e][a] = R;
            t[e][a] = t[e][a] || [];
            r[e][a] = i;
            l();
            let n = t[e][a];
            while (n.length) {
                n.shift()()
            }
        };

        function s() {
            a[i.ITEM] = "/data/item-scaling";
            a[i.SPELL] = "/data/spell-scaling";
            a[i.PLAYER_CLASS] = "/data/spec-spells";
            for (var e in a) {
                if (!a.hasOwnProperty(e)) {
                    continue
                }
                o[e] = {};
                t[e] = {};
                r[e] = {}
            }
        }

        function l() {
            let e = P.show.dataEnv || WH.getDataEnv();
            var t;
            if (t = r[i.ITEM][e]) {
                WH.staminaFactor = t.staminaByIlvl;
                WH.convertRatingToPercent.RM = t.ratingsToPercentRM;
                WH.convertRatingToPercent.LT = t.ratingsToPercentLT;
                WH.convertScalingFactor.SV = t.itemScalingValue;
                WH.convertScalingFactor.SD = t.scalingFactors;
                WH.curvePoints = t.curvePoints;
                WH.applyStatModifications.ScalingData = t.scalingData;
                WH.Tooltip.ARTIFACT_KNOWLEDGE_MULTIPLIERS = t.artifactKnowledgeMultiplier;
                WH.Tooltip.BONUS_ITEM_EFFECTS = t.bonusEffects.bonus;
                WH.Tooltip.ITEM_EFFECT_NAMES = t.bonusEffects.effectSpellName;
                WH.Tooltip.ITEM_EFFECT_TOOLTIP_HTML = t.bonusEffects.effect;
                WH.contentTuningLevels = t.contentTuningLevels
            }
            if (t = r[i.SPELL][e]) {
                WH.convertScalingSpell.SV = t.scalingValue;
                WH.convertScalingSpell.SpellInformation = t.spellInformation;
                WH.convertScalingSpell.RandPropPoints = t.randPropPoints
            }
        }

        s()
    };
    if (!WH.REMOTE) {
        this.disableCompletion = function () {
            P.showCharacterCompletion = false
        }
    }
    re()
};
WH.WebP = new function () {
    const e = this;
    var t = 10;
    var a = {
        lossy: "UklGRiIAAABXRUJQVlA4IBYAAAAwAQCdASoBAAEADsD+JaQAA3AAAAAA",
        lossless: "UklGRhoAAABXRUJQVlA4TA0AAAAvAAAAEAcQERGIiP4HAA==",
        alpha: "UklGRkoAAABXRUJQVlA4WAoAAAAQAAAAAAAAAAAAQUxQSAwAAAARBxAR/Q9ERP8DAABWUDggGAAAABQBAJ0BKgEAAQAAAP4AAA3AAP7mtQAAAA==",
        animation: "UklGRlIAAABXRUJQVlA4WAoAAAASAAAAAAAAAAAAQU5JTQYAAAD/////AABBTk1GJgAAAAAAAAAAAAAAAAAAAGQAAABWUDhMDQAAAC8AAAAQBxAREYiI/gcA"
    };
    this.feature = Object.freeze({lossy: "lossy", lossless: "lossless", alpha: "alpha", animation: "animation"});
    const i = {
        bodyFrameWaitCount: 0,
        supports: {lossy: undefined, lossless: undefined, alpha: undefined, animation: undefined}
    };
    this.getImageExtension = function () {
        return i.supports.alpha ? ".webp" : ".png"
    };
    this.supportsFeature = function (e, t) {
        if (typeof i.supports[e] === "boolean") {
            requestAnimationFrame((function () {
                t(e, i.supports[e])
            }));
            return
        }
        var n = new Image;
        n.onload = function () {
            var a = n.width > 0 && n.height > 0;
            i.supports[e] = a;
            t(e, a)
        };
        n.onerror = function () {
            i.supports[e] = false;
            t(e, false)
        };
        n.src = "data:image/webp;base64," + a[e]
    };

    function n() {
        e.supportsFeature(e.feature.alpha, (function (e, t) {
            r(t)
        }))
    }

    function r(e) {
        if (!document.body) {
            if (i.bodyFrameWaitCount > t) {
                window.addEventListener("DOMContentLoaded", r.bind(this, e));
                return
            }
            i.bodyFrameWaitCount++;
            requestAnimationFrame(r.bind(this, e));
            return
        }
        document.body.classList.add(e ? "webp" : "no-webp");
        document.body.dataset.whWebp = JSON.stringify(e)
    }

    n()
};
WH.DI.GeneralItem = new function () {
    this.GRID_TYPE_1x1 = 1;
    this.GRID_TYPE_2x1 = 2
};
WH.DI.UiImage = function (e) {
    const t = this;
    const a = WH.WebP;
    const i = "/di/ui/";
    const n = {baseName: ""};
    this.getBaseName = function () {
        return n.baseName
    };
    this.getSubDirectory = function () {
        let e = "";
        let t = n.baseName.split("_");
        while (t.length > 1) {
            let a = t.shift();
            if (/\d/.test(a)) {
                break
            }
            e += a + "/"
        }
        return e
    };
    this.getUrl = function () {
        return [WH.STATIC_URL, i, t.getSubDirectory(), t.getBaseName(), a.getImageExtension()].join("")
    };

    function r(e) {
        n.baseName = e
    }

    r.apply(this, arguments)
};
