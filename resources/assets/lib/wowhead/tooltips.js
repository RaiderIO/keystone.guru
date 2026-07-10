// https://www.wowhead.com/tooltips
const whTooltips = {colorLinks: true, iconizeLinks: true, renameLinks: false};
window.WH = new function () {
    this.REMOTE = !("." + location.hostname).endsWith(".wowhead.com") && location.hostname !== "wh-site" || location.pathname === "/widgets/power/demo.html";
    this.STATIC_URL = "https://wow.zamimg.com";
    this.staticUrl = this.STATIC_URL;
    this.PageMeta = {};
    const e = {resizeEventObserver: undefined};
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
    };
    this.triggerResizeEvents = function (t) {
        if (!(t instanceof Element)) {
            return
        }
        if (!window.ResizeObserver) {
            return
        }
        e.resizeEventObserver = e.resizeEventObserver || new ResizeObserver((e => e.forEach((e => requestAnimationFrame((() => e.target.dispatchEvent(new CustomEvent("resize", {detail: e}))))))));
        e.resizeEventObserver.observe(t)
    }
};
WH.dataEnv = {
    MAIN: 1,
    PTR: 2,
    BETA: 3,
    CLASSIC: 4,
    TBC: 5,
    D2: 6,
    DI: 7,
    WRATH: 8,
    D4: 9,
    PTR2: 10,
    CATA: 11,
    D4PTR: 12,
    D4BETA: 13,
    CLASSICPTR: 14,
    MISTS: 15
};
WH.dataEnvKey = {
    1: "live",
    2: "ptr",
    3: "beta",
    4: "classic",
    5: "tbc",
    6: "d2",
    7: "di",
    8: "wrath",
    9: "d4",
    10: "ptr2",
    11: "cata",
    12: "d4ptr",
    13: "d4beta",
    14: "classicptr",
    15: "mists"
};
WH.dataEnvTerm = {
    1: "live",
    2: "ptr",
    3: "beta",
    4: "classic",
    5: "burningCrusade",
    6: "diablo2",
    7: "diabloImmortal",
    8: "wrathofthelichking",
    9: "diablo4",
    10: "ptr2",
    11: "cataclysm",
    12: "diablo4ptr",
    13: "diablo4beta",
    14: "classicptr",
    15: "mistsofpandaria"
};
WH.dataTree = {RETAIL: 1, CLASSIC: 4, TBC: 5, D2: 6, DI: 7, WRATH: 8, D4: 9, CATA: 11, MISTS: 15};
WH.dataTreeShortTerm = {
    [WH.dataTree.RETAIL]: "retail",
    [WH.dataTree.CLASSIC]: "classic",
    [WH.dataTree.TBC]: "theburningcrusade_short",
    [WH.dataTree.D2]: "diablo2",
    [WH.dataTree.DI]: "diabloImmortal_short",
    [WH.dataTree.WRATH]: "wrathofthelichking_short",
    [WH.dataTree.D4]: "diablo4",
    [WH.dataTree.CATA]: "cataclysm_short",
    [WH.dataTree.MISTS]: "mistsofpandaria_short"
};
WH.dataTreeTerm = {
    1: "retail",
    4: "classic",
    5: "burningCrusade",
    6: "diablo2",
    7: "diabloImmortal",
    8: "wrathofthelichking",
    9: "diablo4",
    11: "cataclysm",
    15: "mistsofpandaria"
};
WH.dataEnvToTree = {};
WH.dataEnvToTree[WH.dataEnv.MAIN] = WH.dataTree.RETAIL;
WH.dataEnvToTree[WH.dataEnv.PTR] = WH.dataTree.RETAIL;
WH.dataEnvToTree[WH.dataEnv.PTR2] = WH.dataTree.RETAIL;
WH.dataEnvToTree[WH.dataEnv.BETA] = WH.dataTree.RETAIL;
WH.dataEnvToTree[WH.dataEnv.CLASSIC] = WH.dataTree.CLASSIC;
WH.dataEnvToTree[WH.dataEnv.TBC] = WH.dataTree.TBC;
WH.dataEnvToTree[WH.dataEnv.D2] = WH.dataTree.D2;
WH.dataEnvToTree[WH.dataEnv.DI] = WH.dataTree.DI;
WH.dataEnvToTree[WH.dataEnv.WRATH] = WH.dataTree.WRATH;
WH.dataEnvToTree[WH.dataEnv.D4] = WH.dataTree.D4;
WH.dataEnvToTree[WH.dataEnv.CATA] = WH.dataTree.CATA;
WH.dataEnvToTree[WH.dataEnv.D4PTR] = WH.dataTree.D4;
WH.dataEnvToTree[WH.dataEnv.D4BETA] = WH.dataTree.D4;
WH.dataEnvToTree[WH.dataEnv.CLASSICPTR] = WH.dataTree.CLASSIC;
WH.dataEnvToTree[WH.dataEnv.MISTS] = WH.dataTree.MISTS;
WH.dataTreeToRoot = {};
WH.dataTreeToRoot[WH.dataTree.RETAIL] = WH.dataEnv.MAIN;
WH.dataTreeToRoot[WH.dataTree.CLASSIC] = WH.dataEnv.CLASSIC;
WH.dataTreeToRoot[WH.dataTree.TBC] = WH.dataEnv.TBC;
WH.dataTreeToRoot[WH.dataTree.D2] = WH.dataEnv.D2;
WH.dataTreeToRoot[WH.dataTree.DI] = WH.dataEnv.DI;
WH.dataTreeToRoot[WH.dataTree.WRATH] = WH.dataEnv.WRATH;
WH.dataTreeToRoot[WH.dataTree.D4] = WH.dataEnv.D4;
WH.dataTreeToRoot[WH.dataTree.CATA] = WH.dataEnv.CATA;
WH.dataTreeToRoot[WH.dataTree.MISTS] = WH.dataEnv.MISTS;
WH.EFFECT_SCALING_CLASS_1 = -1;
WH.EFFECT_SCALING_CLASS_2 = -2;
WH.EFFECT_SCALING_CLASS_3 = -3;
WH.EFFECT_SCALING_CLASS_4 = -4;
WH.EFFECT_SCALING_CLASS_5 = -5;
WH.EFFECT_SCALING_CLASS_6 = -6;
WH.EFFECT_SCALING_CLASS_7 = -7;
WH.EFFECT_SCALING_CLASS_8 = -8;
WH.EFFECT_SCALING_CLASS_9 = -9;
WH.EFFECT_SCALING_CLASS_10 = -10;
WH.EFFECT_SCALING_CLASS_ITEM = 15;
WH.EFFECT_SCALING_CLASS_CATA_ITEM = 12;
WH.EFFECT_SCALING_CLASS_DAMAGEREPLACESTAT = 21;
WH.EFFECT_SCALING_CLASS_MANA_CONSUMABLE = 23;
WH.EFFECT_AURA_DUMMY = 4;
WH.EFFECT_AURA_PROC_TRIGGER_SPELL = 42;
WH.EFFECT_AURA_PERIODIC_DUMMY = 226;
WH.EFFECT_TYPE_DUMMY = 3;
WH.ITEM_SQUISH_ERA_2 = 2;
WH.Timewalking = new function () {
    const e = this;
    this.MODE_TBC = 1;
    this.MODE_WOTLK = 2;
    this.MODE_CATA = 3;
    this.MODE_MISTS = 4;
    this.MODE_WOD = 5;
    this.MODE_LEGION = 6;
    const t = [{id: e.MODE_TBC, charLevel: 30, gearIlvl: 75, stringId: "twtbc"}, {
        id: e.MODE_WOTLK,
        charLevel: 30,
        gearIlvl: 75,
        stringId: "twwotlk"
    }, {id: e.MODE_CATA, charLevel: 35, gearIlvl: 90, stringId: "twcata"}, {
        id: e.MODE_MISTS,
        charLevel: 35,
        gearIlvl: 90,
        stringId: "twmists"
    }, {id: e.MODE_WOD, charLevel: 40, gearIlvl: 105, stringId: "twwod"}, {
        id: e.MODE_LEGION,
        charLevel: 45,
        gearIlvl: 120,
        stringId: "twlegion"
    }];
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
    this.PROFESSION_TRAIT = 65;
    this.TRADING_POST_ACTIVITY = 67;
    this.D4_PLAYER_CLASS = 63;
    this.D4_SKILL = 64;
    this.D4_ITEM = 66;
    this.D4_AFFIX = 68;
    this.D4_PARAGON_NODE = 69;
    this.D4_ASPECT = 70;
    this.D4_PARAGON_GLYPH = 71;
    this.D4_VAMPIRIC_POWER = 74;
    this.D4_SENESCHAL_STONE = 78;
    this.D4_MERCENARY = 79;
    this.D4_WITCH_POWER = 81;
    this.D4_BOSS_POWER = 82;
    this.D4_HORADRIC_COMPONENT = 83;
    this.D4_CHAOS_PERK = 84;
    this.D4_DIVINE_GIFT = 85;
    this.GATHERER_SCREENSHOT = 91;
    this.GATHERER_GUIDE_IMAGE = 98;
    this.GUIDE = 100;
    this.TRANSMOG_SET = 101;
    this.OUTFIT = 110;
    this.GEAR_SET = 111;
    this.D4_BUILD = 112;
    this.HOUSE_BUILD = 113;
    this.DECOR_COLLECTION = 114;
    this.GATHERER_LISTVIEW = 158;
    this.GATHERER_SURVEY_COVENANTS = 161;
    this.NEWS_POST = 162;
    this.GATHERER_HERO_TALENTS = 165;
    this.COUNTDOWN_TIMER = 166;
    this.BATTLE_PET_ABILITY = 200;
    this.DECOR = 201;
    const t = {
        [this.BATTLE_PET_ABILITY]: ["pet-ability", "petability"],
        [this.BFA_CHAMPION_ALLIANCE]: ["bfa-champion"],
        [this.BFA_CHAMPION_HORDE]: ["bfa-champion"],
        [this.CHAMPION_ALLIANCE]: ["champion"],
        [this.CHAMPION_HORDE]: ["champion"],
        [this.D4_AFFIX]: ["affix"],
        [this.D4_ASPECT]: ["aspect"],
        [this.D4_BOSS_POWER]: ["boss-power"],
        [this.D4_BUILD]: ["build"],
        [this.D4_CHAOS_PERK]: ["chaos-perk"],
        [this.D4_DIVINE_GIFT]: ["divine-gift"],
        [this.D4_HORADRIC_COMPONENT]: ["horadric-component"],
        [this.D4_ITEM]: ["item"],
        [this.D4_PARAGON_GLYPH]: ["paragon-glyph"],
        [this.D4_PARAGON_NODE]: ["paragon-node"],
        [this.D4_SENESCHAL_STONE]: ["seneschal-stone"],
        [this.D4_SKILL]: ["skill"],
        [this.D4_VAMPIRIC_POWER]: ["vampiric-power"],
        [this.D4_WITCH_POWER]: ["witch-power"],
        [this.DI_EQUIP_ITEM]: ["equip-item"],
        [this.DI_MISC_ITEM]: ["misc-item"],
        [this.DI_NPC]: ["npc"],
        [this.DI_OBJECT]: ["object"],
        [this.DI_PARAGON_SKILL]: ["paragon-skill"],
        [this.DI_QUEST]: ["quest"],
        [this.DI_SET]: ["set"],
        [this.DI_SKILL]: ["skill"],
        [this.DI_ZONE]: ["zone"],
        [this.FOLLOWER_ALLIANCE]: ["follower"],
        [this.FOLLOWER_HORDE]: ["follower"],
        [this.HOUSE_BUILD]: ["housing-builds"],
        [this.ITEM_SET]: ["item-set", "itemset"],
        [this.MISSION_ABILITY]: ["mission-ability", "missionability", "garrisonability"],
        [this.SHIP_ALLIANCE]: ["ship"],
        [this.SHIP_HORDE]: ["ship"]
    };
    const a = [this.NPC, this.OBJECT, this.ITEM, this.ITEM_SET, this.QUEST, this.SPELL, this.ZONE, this.FACTION, this.HUNTER_PET, this.ACHIEVEMENT, this.TITLE, this.EVENT, this.PLAYER_CLASS, this.RACE, this.SKILL, this.CURRENCY, this.SOUND, this.BUILDING, this.FOLLOWER, this.MISSION_ABILITY, this.MISSION, this.SHIP, this.THREAT, this.RESOURCE, this.CHAMPION, this.ICON, this.ORDER_ADVANCEMENT, this.BFA_CHAMPION, this.AFFIX, this.AZERITE_ESSENCE_POWER, this.AZERITE_ESSENCE, this.STORYLINE, this.ADVENTURE_COMBATANT_ABILITY, this.PROFESSION_TRAIT, this.BATTLE_PET_ABILITY, this.TRADING_POST_ACTIVITY, this.DECOR];
    const i = {
        [this.ACHIEVEMENT]: "achievement",
        [this.ADVENTURE_COMBATANT_ABILITY]: "adventure-combatant-ability",
        [this.AFFIX]: "affix",
        [this.AZERITE_ESSENCE]: "azerite-essence",
        [this.AZERITE_ESSENCE_POWER]: "azerite-essence-power",
        [this.BATTLE_PET_ABILITY]: "pet-ability",
        [this.BFA_CHAMPION]: "bfa-champion",
        [this.BFA_CHAMPION_ALLIANCE]: "bfa-champion_a",
        [this.BFA_CHAMPION_HORDE]: "bfa-champion_h",
        [this.BUILDING]: "building",
        [this.CHAMPION]: "champion",
        [this.CHAMPION_ALLIANCE]: "champion_a",
        [this.CHAMPION_HORDE]: "champion_h",
        [this.COUNTDOWN_TIMER]: "countdown-timer",
        [this.COVENANT]: "covenant",
        [this.CURRENCY]: "currency",
        [this.D4_AFFIX]: "d4-affix",
        [this.D4_ASPECT]: "d4-aspect",
        [this.D4_BOSS_POWER]: "d4-boss-power",
        [this.D4_BUILD]: "d4-build",
        [this.D4_CHAOS_PERK]: "d4-chaos-perk",
        [this.D4_DIVINE_GIFT]: "d4-divine-gift",
        [this.D4_HORADRIC_COMPONENT]: "d4-horadric-component",
        [this.D4_ITEM]: "d4-item",
        [this.D4_PARAGON_GLYPH]: "d4-paragon-glyph",
        [this.D4_PARAGON_NODE]: "d4-paragon-node",
        [this.D4_SENESCHAL_STONE]: "d4-seneschal-stone",
        [this.D4_SKILL]: "d4-skill",
        [this.D4_VAMPIRIC_POWER]: "d4-vampiric-power",
        [this.D4_WITCH_POWER]: "d4-witch-power",
        [this.DECOR]: "decor",
        [this.DECOR_COLLECTION]: "decor-collection",
        [this.DI_EQUIP_ITEM]: "di-equip-item",
        [this.DI_MISC_ITEM]: "di-misc-item",
        [this.DI_NPC]: "di-npc",
        [this.DI_OBJECT]: "di-object",
        [this.DI_PARAGON_SKILL]: "di-paragon-skill",
        [this.DI_QUEST]: "di-quest",
        [this.DI_SET]: "di-set",
        [this.DI_SKILL]: "di-skill",
        [this.DI_ZONE]: "di-zone",
        [this.ENCOUNTER]: "encounter",
        [this.EVENT]: "event",
        [this.FACTION]: "faction",
        [this.FOLLOWER]: "follower",
        [this.FOLLOWER_ALLIANCE]: "follower_a",
        [this.FOLLOWER_HORDE]: "follower_h",
        [this.GEAR_SET]: "gear-set",
        [this.GUIDE]: "guide",
        [this.HOUSE_BUILD]: "house-build",
        [this.HUNTER_PET]: "pet",
        [this.ICON]: "icon",
        [this.ITEM]: "item",
        [this.ITEM_SET]: "item-set",
        [this.MISSION]: "mission",
        [this.MISSION_ABILITY]: "mission-ability",
        [this.NEWS_POST]: "news",
        [this.NPC]: "npc",
        [this.OBJECT]: "object",
        [this.ORDER_ADVANCEMENT]: "order-advancement",
        [this.OUTFIT]: "outfit",
        [this.PLAYER_CLASS]: "class",
        [this.PROFESSION_TRAIT]: "profession-trait",
        [this.QUEST]: "quest",
        [this.RACE]: "race",
        [this.RESOURCE]: "resource",
        [this.SHIP]: "ship",
        [this.SHIP_ALLIANCE]: "ship_a",
        [this.SHIP_HORDE]: "ship_h",
        [this.SKILL]: "skill",
        [this.SOULBIND]: "soulbind",
        [this.SOUND]: "sound",
        [this.SPELL]: "spell",
        [this.STORYLINE]: "storyline",
        [this.THREAT]: "threat",
        [this.TITLE]: "title",
        [this.TRANSMOG_SET]: "transmog-set",
        [this.TRADING_POST_ACTIVITY]: "trading-post-activity",
        [this.ZONE]: "zone"
    };
    const n = {
        [WH.dataTree.RETAIL]: [this.ACHIEVEMENT, this.ADVENTURE_COMBATANT_ABILITY, this.AFFIX, this.AZERITE_ESSENCE, this.AZERITE_ESSENCE_POWER, this.BATTLE_PET_ABILITY, this.BFA_CHAMPION, this.BUILDING, this.CHAMPION, this.CURRENCY, this.DECOR, this.DECOR_COLLECTION, this.EVENT, this.FACTION, this.FOLLOWER, this.GATHERER_GUIDE_IMAGE, this.GATHERER_LISTVIEW, this.GATHERER_SCREENSHOT, this.GUIDE, this.HOUSE_BUILD, this.HUNTER_PET, this.ICON, this.ITEM, this.ITEM_SET, this.MISSION, this.MISSION_ABILITY, this.NPC, this.OBJECT, this.ORDER_ADVANCEMENT, this.OUTFIT, this.PLAYER_CLASS, this.PROFESSION_TRAIT, this.QUEST, this.RACE, this.RESOURCE, this.SHIP, this.SKILL, this.SOUND, this.SPELL, this.STORYLINE, this.THREAT, this.TITLE, this.TRADING_POST_ACTIVITY, this.TRANSMOG_SET, this.ZONE],
        [WH.dataTree.CLASSIC]: [this.FACTION, this.GATHERER_GUIDE_IMAGE, this.GATHERER_LISTVIEW, this.GATHERER_SCREENSHOT, this.GEAR_SET, this.GUIDE, this.HUNTER_PET, this.ICON, this.ITEM, this.ITEM_SET, this.NPC, this.OBJECT, this.OUTFIT, this.PLAYER_CLASS, this.QUEST, this.RACE, this.RESOURCE, this.SKILL, this.SOUND, this.SPELL, this.TRANSMOG_SET, this.ZONE],
        [WH.dataTree.TBC]: [this.CURRENCY, this.FACTION, this.GATHERER_GUIDE_IMAGE, this.GATHERER_LISTVIEW, this.GATHERER_SCREENSHOT, this.GEAR_SET, this.GUIDE, this.HUNTER_PET, this.ICON, this.ITEM, this.ITEM_SET, this.NPC, this.OBJECT, this.OUTFIT, this.PLAYER_CLASS, this.QUEST, this.RACE, this.RESOURCE, this.SKILL, this.SOUND, this.SPELL, this.TRANSMOG_SET, this.ZONE],
        [WH.dataTree.WRATH]: [this.ACHIEVEMENT, this.CURRENCY, this.EVENT, this.FACTION, this.GATHERER_GUIDE_IMAGE, this.GATHERER_LISTVIEW, this.GATHERER_SCREENSHOT, this.GEAR_SET, this.GUIDE, this.HUNTER_PET, this.ICON, this.ITEM, this.ITEM_SET, this.NPC, this.OBJECT, this.OUTFIT, this.PLAYER_CLASS, this.QUEST, this.RACE, this.RESOURCE, this.SKILL, this.SOUND, this.SPELL, this.TRANSMOG_SET, this.ZONE],
        [WH.dataTree.CATA]: [this.ACHIEVEMENT, this.CURRENCY, this.EVENT, this.FACTION, this.GATHERER_GUIDE_IMAGE, this.GATHERER_LISTVIEW, this.GATHERER_SCREENSHOT, this.GEAR_SET, this.GUIDE, this.HUNTER_PET, this.ICON, this.ITEM, this.ITEM_SET, this.NPC, this.OBJECT, this.OUTFIT, this.PLAYER_CLASS, this.QUEST, this.RACE, this.RESOURCE, this.SKILL, this.SOUND, this.SPELL, this.TRANSMOG_SET, this.ZONE],
        [WH.dataTree.MISTS]: [this.ACHIEVEMENT, this.CURRENCY, this.EVENT, this.FACTION, this.GATHERER_GUIDE_IMAGE, this.GATHERER_LISTVIEW, this.GATHERER_SCREENSHOT, this.GEAR_SET, this.GUIDE, this.HUNTER_PET, this.ICON, this.ITEM, this.ITEM_SET, this.NPC, this.OBJECT, this.OUTFIT, this.PLAYER_CLASS, this.QUEST, this.RACE, this.RESOURCE, this.SKILL, this.SOUND, this.SPELL, this.TRANSMOG_SET, this.ZONE],
        [WH.dataTree.D2]: [this.GUIDE],
        [WH.dataTree.D4]: [this.D4_AFFIX, this.D4_ASPECT, this.D4_BOSS_POWER, this.D4_BUILD, this.D4_CHAOS_PERK, this.D4_DIVINE_GIFT, this.D4_HORADRIC_COMPONENT, this.D4_ITEM, this.D4_PARAGON_GLYPH, this.D4_PARAGON_NODE, this.D4_PLAYER_CLASS, this.D4_SENESCHAL_STONE, this.D4_SKILL, this.D4_VAMPIRIC_POWER, this.D4_WITCH_POWER, this.GUIDE],
        [WH.dataTree.DI]: [this.DI_EQUIP_ITEM, this.DI_MISC_ITEM, this.DI_NPC, this.DI_OBJECT, this.DI_PARAGON_SKILL, this.DI_QUEST, this.DI_SET, this.DI_SKILL, this.DI_ZONE, this.GUIDE]
    };
    const s = 0;
    const r = 1;
    const o = 2;
    const l = 3;
    const c = {typeNames: undefined};
    this.existsInDataEnv = function (e, t) {
        return n[WH.getDataTree(t)].includes(e)
    };
    this.getIdByString = function (e) {
        return WH.findKey(i, e, true)
    };
    this.getDetailPageName = e => (t[e] || [])[0] || i[e];
    this.getHistoricalDetailPageNames = e => {
        let a = t[e] || i[e] && [i[e]];
        if (!a) {
            throw new Error(`The given type has no detail pages or string IDs. [${e}]`)
        }
        return a
    };
    this.getGame = t => {
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
        return d(e)[l]
    };
    this.getLowerSingular = function (e) {
        return d(e)[r]
    };
    this.getUpperPlural = function (e) {
        return d(e)[o]
    };
    this.getUpperSingular = function (e) {
        return d(e)[s]
    };

    function d(e) {
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
        WH.Track.nonInteractiveEvent({
            category: "Error",
            action: arguments[0],
            label: arguments[1],
            value: arguments[2]
        })
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
        if (e.hasOwnProperty(t)) {
            return e[t]
        }
        if (WH.REMOTE) {
            return undefined
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
        for (var s = i || 0; s < n; ++s) {
            if (a(e[s]) == t) {
                return s
            }
        }
        return -1
    }
    n = e.indexOf(t, i);
    if (n >= 0) {
        return n
    }
    n = e.length;
    for (var r = i || 0; r < n; ++r) {
        if (e[r] == t) {
            return r
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
        var s = t(e[i], a, e, i);
        if (s != null) {
            e[i] = s
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
}(document.createElement.bind(document));
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
WH.aea = (e, t) => e.parentNode.insertBefore(t, e.nextSibling);
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
        for (let s of t) {
            if (s === "resize") {
                WH.triggerResizeEvents(e[n])
            }
            e[n].addEventListener(s, a, i || false)
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
WH.setCookie = function (e, t, a, i, n, s) {
    var r = new Date;
    var o = e + "=" + encodeURI(a) + "; ";
    r.setDate(r.getDate() + t);
    o += "expires=" + r.toUTCString() + "; ";
    if (i) {
        o += "path=" + i + "; "
    }
    if (n) {
        o += "domain=" + n + "; "
    }
    if (s === true) {
        o += "secure;"
    }
    document.cookie = o;
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
            var n = t[a].indexOf("="), s, r;
            if (n != -1) {
                s = t[a].substr(0, n);
                r = t[a].substr(n + 1)
            } else {
                s = t[a];
                r = ""
            }
            WH.getCookies.C[s] = r
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
        if (a.top >= WH.Layout.getHeaderBottom() + i && (t.allowScrollingDown ?? true ? a.top + a.height + i < window.innerHeight : true) && a.left >= i && a.left + a.width + i < window.innerWidth) {
            return
        }
    }
    e.scrollIntoView({behavior: t.animated === false ? "auto" : "smooth", block: t.position || "start"})
};
WH.isElementPositionFixedOrSticky = e => {
    let t = ["fixed", "sticky"];
    while (e && e.nodeType === Node.ELEMENT_NODE) {
        if (t.includes(getComputedStyle(e).position)) {
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
WH.getLocaleFromDomain.L = {ko: 1, fr: 2, de: 3, cn: 4, es: 6, ru: 7, pt: 8, it: 9, tw: 10, mx: 11};
WH.getDomainFromLocale = function (e) {
    var t;
    if (WH.getDomainFromLocale.L) {
        t = WH.getDomainFromLocale.L
    } else {
        t = WH.getDomainFromLocale.L = WH.createReverseLookupJson(WH.getLocaleFromDomain.L)
    }
    return t[e] ? t[e] : ""
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
            let s = (i.getResponseHeader("content-type") || "").indexOf("application/json") === 0;
            let r = null;
            if (i.status < 200 || i.status > 399) {
                r = "Legacy WH.fetch call got a bad response code."
            } else if (s) {
                try {
                    n = JSON.parse(n)
                } catch (e) {
                    n = undefined;
                    r = "Could not process Legacy WH.fetch JSON response. " + e.message
                }
            }
            if (r) {
                WH.error(r, e, i.status, i.responseText, i);
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
            let s = i.responseText || undefined;
            if (!t.errorExpected) {
                WH.error(n, e, i.status, i.responseText, i)
            }
            if (t.error) {
                t.error(s, i.status)
            }
            if (t.complete) {
                t.complete(s, i.status)
            }
        };
        return function (n, s) {
            s = s || {};
            if (s.query) {
                n += (n.indexOf("?") > -1 ? "&" : "?") + WH.Url.buildQuery(s.query)
            }
            let r = s.method || "GET";
            if (s.hasOwnProperty("data") || typeof s.body === "string") {
                r = s.method || "POST"
            }
            let o = new XMLHttpRequest;
            WH.aE(o, "load", a.bind(o, n, s));
            WH.aE(o, "error", i.bind(o, n, s));
            o.overrideMimeType("text/plain");
            o.open(r, n, true);
            let l = t(s);
            if (l) {
                o.setRequestHeader("Content-Type", l)
            }
            if (typeof s.form === "object") {
                o.send(e(s.form))
            } else if (s.hasOwnProperty("json")) {
                o.send(JSON.stringify(s.json))
            } else if (typeof s.body === "string") {
                o.send(s.body)
            } else {
                o.send()
            }
        }
    }
    let a = function (e, t, a, i) {
        if (!a.ok) {
            if (!t.errorExpected) {
                WH.error("WH.fetch call got a bad response code.", e, a.status, i, a)
            }
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
        let s = (n.headers.get("content-type") || "").indexOf("application/json") === 0;
        (s ? n.json() : n.text()).then(a.bind(null, e, t, n))["catch"](i.bind(null, e, t, n))
    };
    let s = function (e, t, a) {
        if (a.name !== "AbortError") {
            let t = "WH.fetch call could not complete. " + a.message;
            WH.error(t, e, 0, "", a)
        }
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
        let r = typeof i.cookies === "boolean" ? i.cookies : true;
        let o = {
            credentials: r ? "same-origin" : "omit",
            headers: new Headers,
            method: i.method || "GET",
            mode: i.mode || "same-origin",
            signal: i.signal
        };
        let l = t(i);
        if (l) {
            o.headers.set("Content-Type", l)
        }
        if (typeof i.form === "object") {
            o.method = i.method || "POST";
            o.body = e(i.form)
        } else if (i.hasOwnProperty("json")) {
            o.method = i.method || "POST";
            o.body = JSON.stringify(i.json)
        } else if (typeof i.body === "string") {
            o.method = i.method || "POST";
            o.body = i.body
        }
        if (location.hostname === "testing.wowhead.com" && new URL(a).hostname.endsWith(".wowhead.com")) {
            o.credentials = "include"
        }
        fetch(a, o).then(n.bind(null, a, i))["catch"](s.bind(null, a, i))
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
    if (location.hostname === "testing.wowhead.com" && new URL(e).hostname.endsWith(".wowhead.com")) {
        a.withCredentials = true
    }
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
WH.getDataTreeFromKey = function (e) {
    const t = WH.getDataEnvFromKey(e);
    if (t == null) {
        return undefined
    }
    return WH.getDataTree(t)
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
WH.isCataTree = function (e) {
    return WH.getDataTree(e) === WH.dataTree.CATA
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
        case WH.dataEnv.PTR2:
            return WH.isPtr2Active();
        case WH.dataEnv.D4PTR:
            return WH.isRemote() || !!WH.PageMeta?.dataEnv?.active.d4ptr;
        case WH.dataEnv.D4BETA:
            return WH.isRemote() || !!WH.PageMeta?.dataEnv?.active.d4beta;
        case WH.dataEnv.CLASSICPTR:
            return WH.isRemote() || !!WH.PageMeta?.dataEnv?.active.classicptr;
        default:
            return true
    }
};
WH.isDataEnvRestricted = function (e) {
    return !WH.isRemote() && WH.PageMeta.restrictedDataEnvs.includes(e)
};
WH.isEntityRestricted = function (e) {
    return !WH.isRemote() && WH.PageMeta.restrictedEntities.includes(e)
};
WH.isMistsTree = function (e) {
    return WH.getDataTree(e) === WH.dataTree.MISTS
};
WH.isPtr = function () {
    return WH.getDataEnv() === WH.dataEnv.PTR
};
WH.isPtr2 = function () {
    return WH.getDataEnv() === WH.dataEnv.PTR2
};
WH.isPtrActive = function () {
    if (WH.PageMeta.hasOwnProperty("dataEnv")) {
        return WH.PageMeta.dataEnv.active.ptr
    }
    return !!WH.REMOTE
};
WH.isPtr2Active = function () {
    if (WH.PageMeta.hasOwnProperty("dataEnv")) {
        return WH.PageMeta.dataEnv.active.ptr2
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
    Menu.onLoad((() => ["help", "tools", "about"].forEach((e => {
        const t = `footer-${e}-menu`;
        const a = `footer_${e}`;
        const i = WH.ge(t);
        if (i) {
            i.classList.add("hassubmenu");
            Menu.add(i, Menu.getMenu(a))
        }
    }))))
};
WH.getScreenshotUrl = function (e, t, a) {
    if (!t) {
        t = "normal"
    }
    a = a || {};
    var i = t == "normal" && typeof a.description == "string" && a.description ? "-" + WH.Strings.slug(a.description, true) : "";
    var n = {2: ".jpg", 3: ".png", 18: ".webp"};
    var s = n[a.imageType || 2] || n[2];
    return a.staffOnly ? "/admin/screenshots/view/" + e + "?ext=" + s.replace(/\./, "") : WH.staticUrl + "/uploads/screenshots/" + t + "/" + e + i + s
};
WH.getWowMaxLevel = () => WH.Wow?.getMaxPlayerLevel?.() ?? 90;
WH.convertRatingToPercent = function (e, t, a, i) {
    let n = (WH.convertRatingToPercent.LT || {})[t] || {};
    let s = WH.findSparseKey(n, e);
    let r = n[s] || 0;
    if (i != null && (WH.isWrathTree() || WH.isCataTree() || WH.isMistsTree()) && !WH.isRemote()) {
        const e = WH.Wow.Item.Stat;
        const a = WH.Wow.PlayerClass;
        if ([e.ID_HASTE_RATING, e.ID_HASTE_MELEE_RATING].includes(t)) {
            if ([a.PALADIN, a.DEATH_KNIGHT, a.SHAMAN, a.DRUID].includes(i)) {
                r /= 1.3
            }
        }
    }
    return r ? a / r : 0
};
WH.specToSpells = {
    251: [137006],
    252: [137007, 462064],
    250: [137008, 462061],
    104: [137010],
    103: [137011],
    105: [137012, 462073],
    102: [137013],
    253: [137015],
    254: [137016],
    255: [137017],
    63: [137019],
    64: [137020],
    62: [137021],
    268: [137023, 462087],
    270: [137024, 428200, 462090],
    269: [137025, 462091, 1222923],
    70: [137027, 412314],
    66: [137028, 462095],
    65: [137029, 428076],
    257: [137031],
    256: [137032, 462098],
    258: [137033],
    261: [137035, 462105],
    260: [137036],
    259: [137037],
    264: [137039],
    262: [137040],
    263: [137041, 1214207],
    265: [137043, 462111],
    266: [137044],
    267: [137046],
    73: [137048, 462119],
    71: [137049],
    72: [137050],
    577: [212612],
    581: [212613, 462067],
    1467: [356809],
    1468: [356810, 462078],
    1473: [396186]
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
    74: "strint",
    75: "",
    76: "",
    77: "",
    78: "",
    79: "",
    80: "",
    81: ""
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
    var s = WH.convertScalingFactor.SV;
    var r = WH.convertScalingFactor.SD.stats;
    if (!s || !s[e]) {
        WH.error("There are no item scaling values for level " + e);
        return n ? {} : 0
    }
    const o = 10;
    var l = {}, c = s[e], d = r[a];
    if (!d || !(i >= 0 && i < o)) {
        l.v = c[t]
    } else {
        let e = WH.findSparseKey(d, i);
        let a = WH.findSparseKey(d, i + o);
        l.n = WH.statToJson[d[e]];
        l.s = d[e];
        l.v = Math.floor(c[t] * d[a] / 1e4)
    }
    return n ? l : l.v
};
WH.getScalingDistributionCurve = function (e) {
    let t = ((WH.convertScalingFactor.SD || {}).curves || {})[e];
    return t ? {minLevel: t[0], maxLevel: t[1], curve: t[2]} : undefined
};
g_itemScalingCallbacks = [];
WH.getSpellScalingIndexFromScalingClass = function (e, t) {
    if (e === WH.EFFECT_SCALING_CLASS_1 && (WH.isCataTree() || WH.isMistsTree())) {
        return WH.EFFECT_SCALING_CLASS_CATA_ITEM
    }
    let a = WH.isCataTree() || WH.isMistsTree() ? WH.EFFECT_SCALING_CLASS_CATA_ITEM : WH.EFFECT_SCALING_CLASS_ITEM;
    switch (e) {
        case WH.EFFECT_SCALING_CLASS_2:
            if (t == 463) {
                return a
            }
            break;
        case WH.EFFECT_SCALING_CLASS_7:
            return a;
        case WH.EFFECT_SCALING_CLASS_8:
        case WH.EFFECT_SCALING_CLASS_9:
            return WH.EFFECT_SCALING_CLASS_DAMAGEREPLACESTAT;
        case WH.EFFECT_SCALING_CLASS_10:
            return WH.EFFECT_SCALING_CLASS_MANA_CONSUMABLE
    }
    if (e < 0) {
        return Math.abs(e) + (a - 1)
    }
    return e
};
WH.effectAverage = function (e, t, a, i) {
    let n = WH.convertScalingSpell.RandPropPoints;
    let s = e["scalingClass"];
    if (e?.["effectScalingClass"]?.[i] !== undefined) {
        s = e["effectScalingClass"][i]
    }
    let r = e?.["effect"]?.[i] ?? 0;
    let o = e["coefficient"][i] ?? e["coefficient"][0] ?? 0;
    let l = 1;
    let c = 0;
    if (o != 0 && s != 0) {
        let d = a;
        if (e["maxLevelScaling"] !== 0) {
            d = Math.min(d, e["maxLevelScaling"])
        }
        if (e["scalesWithItemLevel"]) {
            if (s == WH.EFFECT_SCALING_CLASS_8) {
                c = n[d][0]
            } else if (s == WH.EFFECT_SCALING_CLASS_9) {
                c = n[d][2]
            } else {
                c = n[d][1]
            }
        } else {
            let e = WH.getSpellScalingIndexFromScalingClass(s);
            c = WH.convertScalingSpell.SV[t]?.[e - 1] ?? 1
        }
        if (s === WH.EFFECT_SCALING_CLASS_7 && (r === WH.EFFECT_TYPE_DUMMY || e["aura"] && (e["aura"][i] === WH.EFFECT_AURA_DUMMY || e["aura"][i] === WH.EFFECT_AURA_PROC_TRIGGER_SPELL || e["aura"][i] === WH.EFFECT_AURA_PERIODIC_DUMMY))) {
            l = WH.getCombatRatingMult(d, 12)
        }
        return o * c * l
    }
    return e["effectBasePoints"][i]
};
WH.convertScalingSpell = function (e, t, a, i, n, s) {
    var r = WH.convertScalingSpell.SpellInformation;
    if (!r || !r[t]) {
        return e
    }
    a = a - 1;
    if (e.effects == undefined) e.effects = {};
    if (!e.effects.hasOwnProperty(a + 1)) {
        e.effects[a + 1] = {}
    }
    var o = r[t];
    var l = 0;
    var c = WH.effectAverage(o, n, s, a);
    if (o["deltaCoefficient"][a] ?? 0 != 0) {
        var d = o["deltaCoefficient"][a];
        var f = Math.ceil(c - c * d / 2);
        var u = Math.floor(c + c * d / 2);
        if (i == 0) {
            l = (f + u) / 2
        } else if (i == 1) {
            l = f
        } else if (i == 2) {
            l = u
        }
    } else if ((o["coefficient"][a] ?? 0) != 0 || c != 0) {
        l = c
    } else {
        l = o["effectBasePoints"][a] ?? 0
    }
    l = Math.abs(l);
    var p = "avg";
    switch (parseInt(i)) {
        case 0:
        case 3:
            p = "avg";
            break;
        case 1:
            p = "min";
            break;
        case 2:
            p = "max";
            break;
        case 4:
            p = "pts";
            break;
        default:
            p = "avg"
    }
    var h = 5;
    var g = h;
    if (window.g_pageInfo && window.g_pageInfo.type == WH.Types.AZERITE_ESSENCE_POWER) {
        g = WH.Wow.Item.INVENTORY_TYPE_NECK
    }
    if (o.scalesWithItemLevel && o.appliesRatingAura && o.appliesRatingAura[a] && o.effectScalingClass && o.effectScalingClass[a] === WH.EFFECT_SCALING_CLASS_7) {
        l *= WH.getCombatRatingMult(s, g)
    }
    e.effects[a + 1][p] = l;
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
        for (var s = 1; s < 3; ++s) {
            var r = a["itemenchspell" + s];
            var o = a["itemenchtype" + s];
            var l = WH.statToJson[r];
            if (o == 5 && e[l]) {
                var c = a["damage" + s];
                if (c) {
                    e[l] = Math.round(n * c)
                }
            }
        }
        if (a.allstats) {
            for (var d in e) {
                e[d] = Math.round(n * a["damage1"])
            }
        }
    }
    if (!e.scadist || !e.scaflags) {
        return
    }
    e.bonuses = e.bonuses || {};
    var f = e.scaflags & 255, u = e.scaflags >> 8 & 255, p = (e.scaflags & 1 << 16) != 0,
        h = (e.scaflags & 1 << 17) != 0, g = (e.scaflags & 1 << 18) != 0, m;
    switch (f) {
        case 5:
        case 1:
        case 7:
        case 17:
            m = 7;
            break;
        case 3:
        case 12:
            m = 8;
            break;
        case 16:
        case 11:
        case 14:
            m = 9;
            break;
        case 15:
            m = 10;
            break;
        case 23:
        case 21:
        case 22:
        case 13:
            m = 11;
            break;
        default:
            m = -1
    }
    if (m >= 0) {
        for (var s = 0; s < 10; ++s) {
            var W = WH.convertScalingFactor(t, m, e.scadist, s, 1);
            if (W.n) {
                e[W.n] = W.v
            }
            e.bonuses[W.s] = W.v
        }
    }
    if (g) {
        e.splpwr = e.bonuses[45] = WH.convertScalingFactor(t, 6)
    }
    if (p) {
        switch (f) {
            case 3:
                e.armor = WH.convertScalingFactor(t, 11 + u);
                break;
            case 5:
                e.armor = WH.convertScalingFactor(t, 15 + u);
                break;
            case 1:
                e.armor = WH.convertScalingFactor(t, 19 + u);
                break;
            case 7:
                e.armor = WH.convertScalingFactor(t, 23 + u);
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
    if (h) {
        var H = e.mledps ? "mle" : "rgd", E;
        switch (f) {
            case 23:
            case 21:
            case 22:
            case 13:
                e.dps = e[H + "dps"] = WH.convertScalingFactor(t, g ? 2 : 0);
                E = .3;
                break;
            case 17:
                e.dps = e[H + "dps"] = WH.convertScalingFactor(t, g ? 3 : 1);
                E = .2;
                break;
            case 15:
                e.dps = e[H + "dps"] = WH.convertScalingFactor(t, u == 19 ? 5 : 4);
                E = .3;
                break;
            default:
                e.dps = e[H + "dps"] = 0;
                E = 0
        }
        e.dmgmin = e[H + "dmgmin"] = Math.floor(e.dps * e.speed * (1 - E));
        e.dmgmax = e[H + "dmgmax"] = Math.floor(e.dps * e.speed * (1 + E))
    }
};
WH.clampContentTuningLevel = function (e, t) {
    let a = WH.getContentTuningLevels(e);
    if (a) {
        if (a.minLevel > 0) {
            t = Math.max(a.minLevel, t)
        }
        if (a.maxLevel > 0) {
            t = Math.min(a.maxLevel, t)
        }
    }
    return t
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
            var s = WH.getSpellScalingValue(e.scalinginfo.scalingcategory, n);
            for (var r = 0; r < i.length; ++r) {
                var o = e.scalinginfo["damage" + (r + 1)];
                if (o) {
                    a = a.replace(i[r], Math.round(s * o))
                }
            }
        }
    }
    return a
};
WH.getItemRandPropPointsType = function (e, t = false) {
    const a = e.slotbak ? e.slotbak : e.slot;
    if (t) {
        switch (a) {
            case 14:
            case 23:
                return 2;
            case 15:
            case 26:
            case 25:
                return 4;
            default:
        }
    }
    switch (a) {
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
        default:
            return -1
    }
};
WH.getItemProfessionPropPointsType = function (e) {
    switch (e.slotbak || e.slot) {
        case WH.Wow.Item.INVENTORY_TYPE_PROFESSION_TOOL:
            return 0;
        case WH.Wow.Item.INVENTORY_TYPE_PROFESSION_ACCESSORY:
            return 1;
        default:
            return -1
    }
};
WH.getItemSquishEraCurveIdFromPatch = function (e) {
    let t = WH.getPageData("wow.item.itemSquishEra") || [];
    let a = 0;
    t.forEach((t => {
        if (t.patch > e) {
            return
        }
        a = t.curveId
    }));
    return a
};
WH.getItemSquishEraItemLevel = function (e) {
    if (e > 0) {
        let t = WH.getItemSquishEraCurveIdFromPatch(WH.Wow.getVersionStrFromNum(WH.Wow.getVersionString()));
        if (t > 0) {
            e = Math.round(WH.getCurveValue(t, e) ?? e)
        }
    }
    return e
};
WH.scaleItemLevel = function (e, t) {
    let a = e.level;
    let i = WH.curvePoints;
    if (!i) {
        return a
    }
    let n = null;
    let s = null;
    let r = null;
    if (e.scadist) {
        let t = WH.getScalingDistributionCurve(e.scadist);
        if (t && t.curve) {
            s = t.minLevel;
            r = t.maxLevel;
            n = t.curve
        }
    } else {
        if (e.contenttuning) {
            let t = WH.getContentTuningLevels(e.contenttuning);
            if (t) {
                s = t.minLevel;
                r = t.maxLevel
            }
        }
        n = e.playercurve
    }
    if (n) {
        let e = t ? t : WH.Wow.getMaxPlayerLevel();
        if (s && e < s) {
            e = s
        }
        if (r && e > r) {
            e = r
        }
        let o = i[n];
        if (o && o.length > 0) {
            let t = -1;
            for (let a in o) {
                let i = o[a];
                if (i[1] >= e) {
                    t = a;
                    break
                }
            }
            let i = o[t != -1 ? t : o.length - 1];
            let n = null;
            let s = 0;
            if (t > 0) {
                n = o[t - 1];
                let a = i[1] - n[1];
                if (a > 0) {
                    let t = e - n[1];
                    let r = t / a;
                    let o = i[2] - n[2];
                    let l = r * o;
                    s = n[2] + l
                }
            } else {
                s = i[2]
            }
            if (s > 0) {
                a = Math.round(s)
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
WH.applyStatModifications = function (e, t, a, i, n, s, r, o) {
    const l = WH.Wow.Item;
    var c = {};
    if (e.hasOwnProperty("level")) {
        c = WH.dO(e)
    } else {
        WH.cOr(c, e, "__")
    }
    if (n && n.length) {
        var d = false;
        for (var f = 0; f < n.length; ++f) {
            var u = n[f];
            if (u > 0 && WH.isSet("g_itembonuses") && g_itembonuses[u]) {
                var p = g_itembonuses[u];
                for (var h = 0; h < p.length; ++h) {
                    var g = p[h];
                    switch (g[0]) {
                        case 11:
                        case 13:
                            if (d === false || g[2] < d) {
                                c.scadist = g[1];
                                c.scadistbonus = u;
                                c.scadistbonustype = g[0];
                                c.contenttuning = g[3];
                                c.playercurve = g[4];
                                d = g[2]
                            }
                            break;
                        default:
                            break
                    }
                }
            }
        }
    }
    c.level = WH.scaleItemLevel(c, s);
    if (a == "pvp" && e.pvpUpgrade) {
        c.level += e.pvpUpgrade
    }
    if (c.subitems && c.subitems[t]) {
        for (var m in c.subitems[t].jsonequip) {
            if (!c.hasOwnProperty(m)) {
                c[m] = 0
            }
            c[m] += c.subitems[t].jsonequip[m]
        }
    }
    c.extraStats = [];
    let W = true;
    if (n && n.length) {
        if (e.statsInfo) {
            c.statsInfo = {};
            for (var f in e.statsInfo) {
                c.statsInfo[f] = {
                    alloc: parseInt(e.statsInfo[f].alloc),
                    qty: e.statsInfo[f].qty,
                    socketMult: e.statsInfo[f].socketMult
                }
            }
        }
        var H = [0, 0, 0, 0, 2147483647, 2147483647, 2147483647, 2147483647];
        var E = c.scadistbonus ? false : 0;
        let t = [24, 25];
        let a = 0;
        let i = 0;
        let r = false;
        let o = 0;
        let l = null;
        let d = null;
        let m = null;
        let A = null;
        for (var f = 0; f < n.length; ++f) {
            var u = n[f];
            if (u > 0 && WH.isSet("g_itembonuses") && g_itembonuses[u]) {
                var p = g_itembonuses[u];
                for (var h = 0; h < p.length; ++h) {
                    var g = p[h];
                    if (g[0] == 25) {
                        let e = c.statsInfo[t[a]];
                        if (e && e.alloc) {
                            g[0] = 2;
                            g[2] = e.alloc;
                            delete c.statsInfo[t[a]];
                            a = Math.min(a + 1, t.length - 1)
                        } else {
                            continue
                        }
                    }
                    switch (g[0]) {
                        case 1:
                            i += g[1];
                            E = false;
                            break;
                        case 2:
                            if (c.statsInfo) {
                                if (c.statsInfo.hasOwnProperty(g[1])) {
                                    c.statsInfo[g[1]].alloc += g[2]
                                } else {
                                    c.extraStats.push(g[1]);
                                    c.statsInfo[g[1]] = {alloc: parseInt(g[2]), qty: 0, socketMult: 0}
                                }
                            }
                            break;
                        case 3:
                            c.quality = parseInt(g[1]);
                            break;
                        case 4:
                            var T = g[1];
                            var v = g[2];
                            var S = 4;
                            var I = 4;
                            do {
                                if (v <= H[S]) {
                                    var _ = T;
                                    T = H[S - 4];
                                    H[S - 4] = _;
                                    var b = v;
                                    v = H[S];
                                    H[S] = b
                                }
                                ++S;
                                --I
                            } while (I);
                            break;
                        case 5:
                            c.nameSuffix = WH.Wow.Item.getNameDescription(g[1]) || c.nameSuffix;
                            break;
                        case 6:
                            var w = c.nsockets ? c.nsockets : 0;
                            c.nsockets = w + g[1];
                            for (var y = w; y < w + g[1]; ++y) {
                                c["socket" + (y + 1)] = g[2]
                            }
                            break;
                        case 8:
                            c.reqlevel += g[1];
                            break;
                        case 13:
                            c.reqlevel = WH.getCurveKey(g[4], c.level);
                            if (g[3] > 0) {
                                c.reqlevel = WH.clampContentTuningLevel(g[3], c.reqlevel)
                            }
                            break;
                        case 14:
                            if (E !== false) {
                                E = c.level
                            }
                            break;
                        case 16:
                            c.bond = parseInt(g[1]);
                            break;
                        case 35:
                            c.limitcategory = parseInt(g[1]);
                            break;
                        case 42:
                            if (l == null || d > g[2]) {
                                l = g[1];
                                d = g[2];
                                E = false
                            }
                            break;
                        case 43:
                            if (m == null || A > g[2]) {
                                c.levelSetPvp = m = g[1];
                                A = g[2];
                                E = false
                            }
                            break;
                        case 27:
                            if (WH.curvePoints) {
                                let e = WH.curvePoints[g[1]];
                                if (e && e[0] && e[0][2]) {
                                    c.reqlevel = e[0][2]
                                }
                                if (g[2] > 0) {
                                    c.reqlevel = WH.clampContentTuningLevel(g[2], c.reqlevel)
                                }
                            } else {
                                WH.error("Could not apply item stat modifications without scaling curve points.", e.id, e.name)
                            }
                            break;
                        case 44:
                            if (!c.itemNameDescStats) {
                                c.itemNameDescStats = []
                            }
                            c.itemNameDescStats.push({qty: g[1], nameDescId: g[2]});
                            break;
                        case 48:
                            if (g[1] > 0) {
                                c.level = Math.round(WH.getCurveValue(g[1], g[2]) ?? g[2])
                            }
                            r = true;
                            break;
                        case 49:
                        case 51:
                            let t = WH.getPageData("wow.item.bonuses.itemScalingConfigs") || {};
                            let a = t[g[1]];
                            if (a) {
                                let e = g[0] === 51 ? s || WH.Wow.getMaxPlayerLevel() : a["itemLevel"] || c.level;
                                if (a["curveId"]) {
                                    e = WH.getCurveValue(a["curveId"], e) ?? e
                                }
                                e += a["offset"];
                                if (e) {
                                    c.level = e
                                }
                                if (a["requiredLevel"]) {
                                    c.reqlevel = a["requiredLevel"]
                                }
                            }
                            r = true;
                            W = false;
                            break;
                        case 52:
                            o += g[1];
                            c.craftingQualityId = g[2];
                            let n = (WH.getPageData("wow.item.craftingQualityData") || {})[c.craftingQualityId];
                            if (n) {
                                c.craftingQualityTierTexture = n["craftingQualityTierTexture"]
                            }
                            break;
                        case 53:
                            o += g[1];
                            break;
                        default:
                            break
                    }
                }
            }
        }
        if (r) {
            i = 0
        }
        c.reqlevel = Math.round(c.reqlevel);
        if (!c.scadistbonus) {
            c.level = m || (l || c.level) + i + o
        }
        if (E) {
            c.level = E;
            c.previewLevel = E
        }
        c.namedesc = c.namedesc ? c.namedesc : "";
        for (var h = 0; h < 4; ++h) {
            let e = WH.Wow.Item.getNameDescription(H[h]);
            if (e) {
                let t = WH.Wow.Item.getNameDescriptionColor(H[h]);
                if (t > 0) {
                    let e = parseInt(t).toString(16);
                    while (e.length < 6) {
                        e = "0" + e
                    }
                    c.namedesc += WH.sprintf('<span style="color: #$1">', e)
                }
                c.namedesc += (!c.namedesc ? "" : " ") + e;
                if (t > 0) {
                    c.namedesc += "</span>"
                }
            }
        }
    }
    if (W && c.level > 0 && (c.itemSquishEraId ?? 0) !== WH.ITEM_SQUISH_ERA_2) {
        c.level = WH.getItemSquishEraItemLevel(c.level)
    }
    (function () {
        if (!o || !o.length || !c.statsInfo) {
            return
        }
        for (let t, a = 0; t = WH.Wow.Item.Stat.CRAFTING_STAT_FROM[a]; a++) {
            let i = o[a];
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
    let A = WH.Wow.Expansion.available(WH.Wow.Expansion.WRATH) && c.scadist > 0;
    if (A && c.statsInfo) {
        let e = WH.convertScalingFactor.SD.stats[c.scadist];
        if (e) {
            let t = l.SCALING_STATS_DISTRIBUTION_STAT_MAX;
            for (f = 0; f < t; f++) {
                if (e[f] > 0 && e[f + t] > 0) {
                    c.statsInfo[e[f]] = {qty: 0, alloc: parseInt(e[f + t]), socketMult: 0}
                }
            }
        }
    }
    if (e.statsInfo && e.level && WH.applyStatModifications.ScalingData && (WH.applyStatModifications.ScalingData.AL.length > 1 || A)) {
        let t = WH.applyStatModifications.ScalingData.armor.total;
        let n = WH.applyStatModifications.ScalingData.armor.shield;
        let o = WH.applyStatModifications.ScalingData.armor.quality;
        let d = WH.applyStatModifications.ScalingData.SV;
        let u = WH.applyStatModifications.ScalingData.AL;
        let p = WH.applyStatModifications.ScalingData.socketCost;
        let h = WH.applyStatModifications.ScalingData.PPP;
        let g = WH.convertScalingFactor.SV;
        c.level = i ? i : a && e.upgrades && e.upgrades[a - 1] ? c.level + e.upgrades[a - 1] : c.level;
        var C = c.level - e.level;
        var R = Math.pow(1.15, C / 15);
        let m = c.slot === WH.Wow.Item.INVENTORY_TYPE_PROFESSION_TOOL || c.slot === WH.Wow.Item.INVENTORY_TYPE_PROFESSION_ACCESSORY;
        let W = m ? WH.getItemProfessionPropPointsType(c) : WH.getItemRandPropPointsType(c, !WH.Wow.Expansion.available(WH.Wow.Expansion.MOP));
        let H;
        var L = [];
        for (H = c.level; H >= 0; H--) {
            if (d.hasOwnProperty(H)) {
                L = d[H];
                break
            }
        }
        let E = 0;
        if (W != -1) {
            let e = 0;
            if (m) {
                switch (c.quality) {
                    case WH.Wow.Item.QUALITY_EPIC:
                        e = 0;
                        break;
                    case WH.Wow.Item.QUALITY_RARE:
                        e = 2;
                        break;
                    case WH.Wow.Item.QUALITY_UNCOMMON:
                        e = 4;
                        break;
                    default:
                        e = -1;
                        break
                }
                if (e !== -1) {
                    e += W;
                    let t = WH.findSparseKey(h, H);
                    let a = WH.findSparseKey(h[t] || {}, e);
                    E = (h[t] || {})[a] || 0
                }
            } else if (A) {
                let e = null;
                switch (c.slot) {
                    case WH.Wow.Item.INVENTORY_TYPE_BACK:
                        e = WH.isCataTree() || WH.isMistsTree() ? 9 : 7;
                        break;
                    case WH.Wow.Item.INVENTORY_TYPE_SHOULDERS:
                        e = 30;
                        break;
                    case WH.Wow.Item.INVENTORY_TYPE_TRINKET:
                        e = 31;
                        break;
                    case WH.Wow.Item.INVENTORY_TYPE_FINGER:
                        e = 9;
                        break;
                    case WH.Wow.Item.INVENTORY_TYPE_ONE_HAND:
                        e = 11;
                        break;
                    case WH.Wow.Item.INVENTORY_TYPE_MAIN_HAND:
                        e = WH.isCataTree() || WH.isMistsTree() ? 11 : 7;
                        break;
                    case WH.Wow.Item.INVENTORY_TYPE_RANGED:
                        e = 10;
                        break;
                    default:
                        e = 7;
                        break
                }
                if (g[s] && g[s][e]) {
                    E = g[s][e]
                }
            } else {
                switch (c.quality) {
                    case WH.Wow.Item.QUALITY_LEGENDARY:
                    case WH.Wow.Item.QUALITY_EPIC:
                        e = 0;
                        break;
                    case WH.Wow.Item.QUALITY_HEIRLOOM:
                    case WH.Wow.Item.QUALITY_RARE:
                        e = 1;
                        break;
                    case WH.Wow.Item.QUALITY_UNCOMMON:
                        e = 2;
                        break;
                    default:
                        break
                }
                let t = WH.findSparseKey(L, e);
                let a = WH.findSparseKey(L[t] || {}, W);
                E = (L[t] || {})[a] || 0
            }
        }
        let T = WH.findSparseKey(p, H);
        let v = p[T] || 0;
        for (var f in WH.statToJson) {
            var M = WH.statToJson[f];
            if (c[M] || c.statsInfo && c.statsInfo[f]) {
                var O = 0;
                var D = 0;
                if (c.statsInfo.hasOwnProperty(f)) {
                    O = parseFloat(c.statsInfo[f].socketMult);
                    D = parseInt(c.statsInfo[f].alloc)
                }
                var N = Math.round(O * v);
                if (D && (E > 0 || c.contenttuning > 0)) {
                    c[M] = D * 1e-4 * E - N
                } else {
                    c[M] = (c[M] + N) * R - N
                }
                if (M == "sta") {
                    c[M] = c[M] * WH.getStaminaRatingMult(c.level, c.slot || g_items[c.id].slot)
                } else if (r && WH.inArray(WH.applyStatModifications.BASE_STATS, f) < 0) {
                    c[M] = c[M] * WH.getCombatRatingMult(c.level, c.slot || g_items[c.id].slot)
                } else if (M === "corruption" || M === "corruptionres") {
                    c[M] = D
                }
                switch (M) {
                    case"agistrint":
                        c["agi"] = c["str"] = c["int"] = c[M];
                        break;
                    case"agistr":
                        c["agi"] = c["str"] = c[M];
                        break;
                    case"agiint":
                        c["agi"] = c["int"] = c[M];
                        break;
                    case"strint":
                        c["str"] = c["int"] = c[M];
                        break;
                    default:
                        break
                }
            }
        }
        if (c.scadist > 0 && (c.dbcFlags ?? 0) & l.DBCITEM_FLAGS_SCALING_SPELL_POWER) {
            let e = g[s]?.[l.ITEM_SCALING_VALUE_SPELL_POWER_INDEX] ?? 0;
            c.statsInfo[WH.Wow.Item.Stat.ID_SPELL_POWER] = e;
            c.splpwr = e
        }
        if (c["armor"] || A) {
            let e = c.quality === l.QUALITY_HEIRLOOM ? l.QUALITY_RARE : c.quality;
            let a = c.subclass === l.ARMOR_SUBCLASS_CLOAKS ? l.ARMOR_SUBCLASS_CLOTH : c.subclass;
            if (A) {
                let e = null;
                if (l.WRATH_ARMOR_SCALING_INDEXES[c.subclass] && l.WRATH_ARMOR_SCALING_INDEXES[c.subclass][c.slot]) {
                    e = l.WRATH_ARMOR_SCALING_INDEXES[c.subclass][c.slot]
                }
                if (g[s] && g[s][e]) {
                    c["armor"] = g[s][e]
                }
            } else if (l.isBodyArmor(l.CLASS_ARMOR, a)) {
                let i = WH.findSparseKey(o, c.level);
                let n = WH.findSparseKey(o[i] || {}, e);
                let s = (o[i] || {})[n] || 0;
                let r = WH.findSparseKey(t, c.level);
                let l = WH.findSparseKey(t[r] || {}, a - 1);
                let d = (t[r] || {})[l] || 0;
                let f = u[c.slot][a - 1];
                c["armor"] = Math.floor(d * s * f + .5)
            }
            if (c.subclass === l.ARMOR_SUBCLASS_SHIELDS) {
                let t = WH.findSparseKey(n, c.level);
                let a = WH.findSparseKey(n[t] || {}, e);
                c["armor"] = Math.round((n[t] || {})[a] || 0)
            }
        }
        if (c["dps"] || A) {
            var P = ["dps", "mledps", "rgddps"];
            var x = ["dmgmin1", "mledmgmin", "rgddmgmin", "dmgmax1", "mledmgmax", "rgddmgmax"];
            var k = WH.getEffectiveWeaponDamage(c, false);
            var B = WH.getEffectiveWeaponDamage(c, true);
            k = Math.floor(Math.max(1, k));
            B = Math.max(1, B);
            if (!WH.isRetailTree()) {
                k = c.damagemin || c.dmgmin1 || k;
                B = c.damagemax || c.dmgmax1 || B
            }
            let e = 0;
            if (A) {
                let t = null;
                switch (c.subclass) {
                    case l.WEAPON_SUBCLASS_DAGGER:
                    case l.WEAPON_SUBCLASS_FIST_WEAPON:
                    case l.WEAPON_SUBCLASS_ONE_HANDED_AXE:
                    case l.WEAPON_SUBCLASS_ONE_HANDED_SWORD:
                        t = 0;
                        break;
                    case l.WEAPON_SUBCLASS_FISHING_POLE:
                    case l.WEAPON_SUBCLASS_POLEARM:
                    case l.WEAPON_SUBCLASS_TWO_HANDED_AXE:
                    case l.WEAPON_SUBCLASS_TWO_HANDED_MACE:
                    case l.WEAPON_SUBCLASS_TWO_HANDED_SWORD:
                        t = 1;
                        break;
                    case l.WEAPON_SUBCLASS_ONE_HANDED_MACE:
                        t = WH.isCataTree() || WH.isMistsTree() ? 0 : 2;
                        break;
                    case l.WEAPON_SUBCLASS_STAFF:
                        t = WH.isCataTree() || WH.isMistsTree() ? 4 : 3;
                        break;
                    case l.WEAPON_SUBCLASS_BOW:
                    case l.WEAPON_SUBCLASS_CROSSBOW:
                    case l.WEAPON_SUBCLASS_GUN:
                    case l.WEAPON_SUBCLASS_THROWN:
                        t = 4;
                        break;
                    case l.WEAPON_SUBCLASS_WAND:
                        t = 5;
                        break;
                    default:
                        t = -1;
                        break
                }
                if (g[s] && g[s][t]) {
                    e = g[s][t];
                    k = Math.floor(e * c.speed * (1 - c.dmgrange / 2));
                    B = Math.floor(e * c.speed * (1 + c.dmgrange / 2) + .5);
                    c["dmgmin1"] = k;
                    c["dmgmax1"] = B
                }
            } else {
                e = (k + B) / 2 / c.speed
            }
            var F = e >= 1e3 ? 0 : WH.isRetailTree() ? 1 : 2;
            e = parseFloat(e.toFixed(F));
            for (var f in P) {
                if (c[P[f]] || A) {
                    c[P[f]] = e
                }
            }
            for (var f in x) {
                if (c[x[f]]) {
                    if (x[f].indexOf("max") != -1) {
                        c[x[f]] = B
                    } else {
                        c[x[f]] = k
                    }
                }
            }
        }
    }
    return c
};
WH.applyStatModifications.BASE_STATS = [4, 3, 5, 71, 72, 73, 74, 7, 1, 0, 8, 9, 2, 10];
WH.checkSpellModifierCheckbox = (e, t, a = true) => {
    let i = WH.qs(`.tooltip-options #ks${e} input[type="checkbox"][data-spell-id="${t}"]`);
    if (i) {
        i.checked = a
    }
};
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
    var s = e.slotbak ? e.slotbak : e.slot;
    var r = 0;
    var o = false;
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
    if (s > 22) {
        if (s == 24) {
            r = 0;
            o = true
        }
        if (!o && (s <= 24 || s > 26)) {
            o = true
        }
    } else {
        if (s == 21 || s == 22 || s == 13) {
            if (!l) {
                r = WH.getItemDamageValue(a, n, 0)
            } else {
                r = WH.getItemDamageValue(a, n, 1)
            }
            o = true
        }
        if (!o && s != 15) {
            if (s != 17) {
                o = true
            } else {
                if (!l) {
                    r = WH.getItemDamageValue(a, n, 2)
                } else {
                    r = WH.getItemDamageValue(a, n, 3)
                }
                o = true
            }
        }
    }
    if (!o && i >= 2) {
        if (i == 2 || i == 3 || i == 18) {
            if (!l) {
                r = WH.getItemDamageValue(a, n, 2)
            } else {
                r = WH.getItemDamageValue(a, n, 3)
            }
            o = true
        }
        if (!o && i == 19) {
            r = WH.getItemDamageValue(a, n, 1)
        }
    }
    if (r > 0) {
        var c = e.dmgrange || 0;
        if (!t) {
            return r * e.speed * (1 - c / 2)
        } else {
            return Math.floor(r * e.speed * (1 + c / 2) + .5)
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
    let t = WH.Tooltips.getScalingData(WH.Types.ITEM, "artifactKnowledgeMultiplier") || {};
    let a = WH.findSparseKey(t, e);
    return t[a] || 1
};
WH.getCurveKey = function (e, t) {
    let a;
    if (!WH.curvePoints || !(a = WH.curvePoints[e])) {
        return undefined
    }
    let i = a[0][1];
    let n = a[0][2];
    if (n > t) {
        return i
    }
    for (let e = 0, s; s = a[e]; e++) {
        if (t == s[2]) {
            return s[1]
        }
        if (t < s[2]) {
            return (s[1] - i) / (s[2] - n) * (t - n) + i
        }
        i = s[1];
        n = s[2]
    }
    return i
};
WH.getCurveValue = function (e, t) {
    let a;
    if (!WH.curvePoints || !(a = WH.curvePoints[e])) {
        return undefined
    }
    let i = a[0][1];
    let n = a[0][2];
    if (i > t) {
        return n
    }
    for (let e = 0, s; s = a[e]; e++) {
        if (t == s[1]) {
            return s[2]
        }
        if (t < s[1]) {
            return (s[2] - n) / (s[1] - i) * (t - i) + n
        }
        i = s[1];
        n = s[2]
    }
    return n
};
WH.setItemModifications = function (e, t, a, i, n, s, r) {
    if (!WH.isSet("g_items") || !g_items[t] || !g_items[t].jsonequip) {
        return e
    }
    const o = WH.Wow.Item.Gems;
    if (!n) {
        n = WH.Wow.getMaxPlayerLevel()
    }
    a = a ? a.split(":") : null;
    if (!s) {
        s = WH.Timewalking.getGearIlvlByStringId(i) || 0
    }
    i = !s ? i : null;
    var l = WH.applyStatModifications(g_items[t].jsonequip, 0, i, s, a, n, undefined, r);
    if (!l.name && g_items[t].hasOwnProperty("name_" + Locale.getName())) {
        l.name = g_items[t]["name_" + Locale.getName()];
        l.quality = g_items[t].quality
    }
    e = e.replace(/(<!--ilvl-->)\d+\+?/, (function (e, t) {
        return t + l.level + (l.previewLevel ? "+" : "")
    }));
    let c = false;
    let d = 1;
    let f = WH.Wow.getMaxPlayerLevel();
    if (l.scadist) {
        let e = WH.getScalingDistributionCurve(l.scadist);
        if (e && e.maxLevel) {
            c = true;
            d = e.minLevel || 1;
            f = e.maxLevel
        }
    } else if (l.contenttuning) {
        let e = WH.getContentTuningLevels(l.contenttuning);
        if (e) {
            c = true;
            d = e.minLevel;
            f = e.maxLevel
        }
    } else if (l.scadistbonus && l.scadistbonustype === 13 && l.playercurve) {
        let e = WH.curvePoints[l.playercurve];
        d = e[0][1];
        f = Math.min(e[e.length - 1][1], WH.Wow.getMaxPlayerLevel());
        c = true
    }
    if (c) {
        n = n && n <= f ? n : f;
        e = e.replace(/(<!--lvl-->)\d+/g, (function (e, t) {
            return t + (n && n <= f ? n : f)
        }));
        e = e.replace(/(<!--minlvl-->)\d+/, (function (e, t) {
            return t + d
        }));
        e = e.replace(/(<!--maxlvl-->)\d+/, (function (e, t) {
            return t + f
        }));
        let a = false;
        e = e.replace(/<!--i\?(\d+):(\d+):(\d+):(\d+)(?::(\d+):(\d+))?/, (function (e, t, i, s, r, o, c) {
            a = true;
            return "\x3c!--i?" + t + ":" + d + ":" + f + ":" + n + ":" + (l.scadist || l.contenttuning) + ":" + (c || 0)
        }));
        if (!a) {
            e += "\x3c!--i?" + t + ":" + d + ":" + f + ":" + n + ":" + (l.scadist || l.contenttuning) + ":0--\x3e"
        }
        e = e.replace(/(<!--huindex-->)\d+/, (function (e, t) {
            let a = 0;
            if (l.scadistbonus && l.heirloombonuses) {
                for (let e = 0, t; t = l.heirloombonuses[e]; e++) {
                    if (parseInt(l.scadistbonus) === t) {
                        a = e + 1;
                        break
                    }
                }
            }
            return t + a
        }))
    } else {
        e = e.replace(/<!--i\?(\d+):(\d+):(\d+):(\d+)(?::(\d+):(\d+))?/, (function (e, t, a, i, s, r, o) {
            return "\x3c!--i?" + t + ":" + a + ":" + i + ":" + (n ? n : i)
        }))
    }
    var u;
    if (u = WH.ge("sl" + t)) {
        u.style.display = c ? "block" : "none"
    }
    e = e.replace(/(<!--pvpilvl-->)\d+/, (function (e, t) {
        return t + (l.level + (i != "pvp" ? l.pvpUpgrade : 0))
    }));
    e = e.replace(/(<!--ilvldelta-->)\d+/, (function (e, t) {
        var a = 1718;
        var i = Math.floor(WH.getCurveValue(a, l.level) || 2);
        return t + i
    }));
    e = e.replace(/(<!--rlvl-->)\d+/, (function (e, t) {
        return t + l.reqlevel
    }));
    if (l.craftingQualityId > 0) {
        e = e.replace(/(<!--rlvl-->\d+<br>)/, (function (e, t) {
            let a = WH.ce("img", {
                src: `${WH.STATIC_URL}/images/wow/TextureAtlas/${WH.getDataEnvKey()}` + `/${l.craftingQualityTierTexture}.png`,
                style: "vertical-align: middle"
            });
            return t + WH.Strings.sprintf(WH.GlobalStrings.PROFESSIONS_CRAFTING_QUALITY, a.outerHTML) + "<br>"
        }))
    }
    e = e.replace(/(<!--uindex-->)\d+/, (function (e, t) {
        return i && i != "pvp" ? t + i : e
    }));
    var p = typeof l.dmgrange != "undefined" && l.dmgrange;
    var h = new RegExp("(\x3c!--dmg--\x3e)[\\d,]+" + (p ? "(\\D*?)[\\d,]+" : "") + "");
    e = e.replace(h, (function (e, t, a) {
        return t + WH.numberFormat(l.dmgmin1) + (p ? a + WH.numberFormat(l.dmgmax1) : "")
    }));
    e = e.replace(/(<!--dps-->\D*?)([\d,]+(?:\.\d+)?)/, (function (e, t) {
        var a = l.dps >= 1e3 ? 0 : WH.isRetailTree() ? 1 : 2;
        return t + (l.dps ? WH.numberFormat(l.dps.toFixed(a)) : "0")
    }));
    e = e.replace(/(<!--amr-->)\d+/, (function (e, t) {
        return t + l.armor
    }));
    var g = WH.getCombatRatingMult(l.level, g_items[t].slot);
    e = function (e) {
        let t = WH.ce("div", {innerHTML: e});
        WH.qsa("span", t).forEach((function (e) {
            let t;
            let a;
            let i;
            let n;
            e.childNodes.forEach((function (e) {
                if (e.nodeType === Node.COMMENT_NODE) {
                    let s;
                    if (s = (e.nodeValue || "").match(/^stat(\d+)$/)) {
                        t = parseInt(s[1]);
                        i = e
                    }
                    if (s = (e.nodeValue || "").match(/^rtg(\d+)$/)) {
                        a = parseInt(s[1]);
                        n = e
                    }
                }
            }));
            if (t === undefined && a === undefined) {
                return
            }
            let s = false;
            if (a) {
                let e = l[WH.statToJson[a]] ? l[WH.statToJson[a]] : 0;
                let t = e < 0 ? "-" : "+";
                if (e) {
                    e = Math.round(e * g)
                } else {
                    s = true;
                    e = 0
                }
                let i = n.previousSibling;
                if (i && i.nodeType === Node.TEXT_NODE) {
                    i.nodeValue = i.nodeValue.replace(/[-+]$/, t)
                }
                let r = n.nextSibling;
                if (r && r.nodeType === Node.TEXT_NODE) {
                    r.nodeValue = r.nodeValue.replace(/[-\d\.,]+/, e)
                }
            } else {
                let e = l[WH.statToJson[t]] ? l[WH.statToJson[t]] : 0;
                if (e) {
                    let t = Math.round(e);
                    e = (t > 0 ? "+" : "-") + WH.numberLocaleFormat(t)
                } else {
                    s = true;
                    e = "+0"
                }
                let a = i.nextSibling;
                if (a && a.nodeType === Node.TEXT_NODE) {
                    a.nodeValue = a.nodeValue.replace(/[-+][-\d\.,]+/, e)
                }
            }
            if (s) {
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
    if (l.extraStats && l.extraStats.length) {
        e = e.replace(/<!--re--><span[^<]*?<\/span>(<br \/>)?/, "");
        var m = WH.applyStatModifications.BASE_STATS;
        e = e.replace(/<!--ebstats-->/, (function (e) {
            var t = "";
            for (var a = 0; a < l.extraStats.length; ++a) {
                var i = l.extraStats[a];
                if (m.indexOf(i) == -1) {
                    continue
                }
                var n = "$1$2 " + (WH.statToJson && WH.statToJson[i] && WH.Wow.Item.Stat.jsonToName(WH.statToJson[i]) || "Unknown");
                var s = WH.statToJson && WH.statToJson[i] ? l[WH.statToJson[i]] : 0;
                var r = Math.round(s * g);
                var o = WH.numberLocaleFormat(r);
                t += "<br /><span>\x3c!--stat" + i + "--\x3e" + WH.sprintf(n, r < 0 ? "-" : "+", o) + "</span>"
            }
            return t + e
        }));
        e = e.replace(/<!--egstats-->/, (function (e) {
            var t = "";
            for (var a = 0; a < l.extraStats.length; ++a) {
                var i = l.extraStats[a];
                if (m.indexOf(i) != -1) {
                    continue
                }
                var n = g;
                var s = "q2";
                switch (WH.statToJson[i]) {
                    case"corruption":
                        s = "stat-corruption";
                        n = 1;
                        break;
                    case"corruptionres":
                        s = "q6";
                        n = 1;
                        break
                }
                var r = "$1$2 " + (WH.statToJson && WH.statToJson[i] && WH.Wow.Item.Stat.jsonToName(WH.statToJson[i]) || "Unknown");
                var o = WH.statToJson && WH.statToJson[i] ? l[WH.statToJson[i]] : 0;
                var c = Math.round(o * n);
                var d = WH.numberLocaleFormat(c);
                var f = WH.sprintf("\x3c!--rtg$1--\x3e$2", i, d);
                var u = "";
                if (WH.statToRating && WH.statToRating[i]) {
                    u = WH.sprintf("&nbsp;<small>(\x3c!--rtg%$1--\x3e0&nbsp;@&nbsp;L$2" + WH.Wow.getMaxPlayerLevel() + ")</small>", i, "\x3c!--lvl--\x3e")
                }
                var p = "";
                if (i == 50) {
                    p = "\x3c!--stat%d--\x3e"
                }
                if (i == 64) {
                    r = r.substr(5);
                    u = ""
                }
                t += '<br /><span class="' + s + '">' + p + WH.sprintf(r, c >= 0 ? "+" : "-", f) + u + "</span>"
            }
            return t + e
        }))
    }
    if (l.itemNameDescStats?.length) {
        e = e.replace(/<!--nameDescStats-->/, (e => {
            let t = "";
            l.itemNameDescStats.forEach((e => {
                let a = WH.Wow.Item.getNameDescription(e.nameDescId);
                if (a) {
                    let i = WH.Wow.Item.getNameDescriptionColor(e.nameDescId);
                    let n = parseInt(i).toString(16);
                    while (n.length < 6) {
                        n = "0" + n
                    }
                    t += WH.sprintf('<br><span style="color: $1">$2$3 $4</span>', "#" + n, e.qty >= 0 ? "+" : "", e.qty, a)
                }
            }));
            return t + e
        }))
    }
    e = e.replace(/(<!--nstart-->)(.*)(<!--nend-->)/, (function (e, t, a, i) {
        var n = l.quality;
        var s = l.name;
        var r = l.nameSuffix ? " " + l.nameSuffix : "";
        return t + WH.sprintf('<b class="q$1">$2</b>', n, s + r) + i
    }));
    e = e.replace(/(<!--ndstart-->)(.*)(<!--ndend-->)/, (function (e, t, a, i) {
        if (!l.namedesc) {
            return t + i
        }
        return t + "<br />" + l.namedesc + i
    }));
    var W = g_items[t].jsonequip.nsockets | 0;
    if (!W && l.nsockets || W && l.nsockets > W) {
        const t = 81;
        const a = 225;
        const i = 1;
        const n = 2;
        const s = 3;
        const r = 4;
        const c = 5;
        const d = 6;
        const f = 7;
        const u = 8;
        const p = 9;
        const h = 10;
        const g = 11;
        const m = 12;
        const H = 13;
        const E = 14;
        const T = 15;
        const v = 16;
        const S = 17;
        const I = 18;
        const _ = 19;
        const b = 20;
        const w = 21;
        e = e.replace(/<!--ps-->(<br(?: \/)?>)?/, (function (e, f) {
            var u = "";
            for (var S = W; S < l.nsockets; ++S) {
                if (!l["socket" + (S + 1)]) {
                    continue
                }
                let e = l["socket" + (S + 1)];
                var I = "socket-unknown";
                let f = t;
                var _ = e;
                switch (e) {
                    case o.SOCKET_META:
                        I = "socket-meta";
                        f = t;
                        _ = i;
                        break;
                    case o.SOCKET_RED:
                        I = "socket-red";
                        f = t;
                        _ = n;
                        break;
                    case o.SOCKET_YELLOW:
                        I = "socket-yellow";
                        f = t;
                        _ = s;
                        break;
                    case o.SOCKET_BLUE:
                        I = "socket-blue";
                        f = t;
                        _ = r;
                        break;
                    case o.SOCKET_SHA_TOUCHED:
                        I = "socket-hydraulic";
                        f = t;
                        _ = c;
                        break;
                    case o.SOCKET_COGWHEEL:
                        I = "socket-cogwheel";
                        f = t;
                        _ = d;
                        break;
                    case o.SOCKET_PRISMATIC:
                        I = "socket-prismatic";
                        f = t;
                        _ = p;
                        break;
                    case o.SOCKET_RELIC_IRON:
                        I = "socket-relic-iron";
                        f = a;
                        _ = 64;
                        break;
                    case o.SOCKET_RELIC_BLOOD:
                        I = "socket-relic-blood";
                        f = a;
                        _ = 128;
                        break;
                    case o.SOCKET_RELIC_SHADOW:
                        I = "socket-relic-shadow";
                        f = a;
                        _ = 256;
                        break;
                    case o.SOCKET_RELIC_FEL:
                        I = "socket-relic-fel";
                        f = a;
                        _ = 512;
                        break;
                    case o.SOCKET_RELIC_ARCANE:
                        I = "socket-relic-arcane";
                        f = a;
                        _ = 1024;
                        break;
                    case o.SOCKET_RELIC_FROST:
                        I = "socket-relic-frost";
                        f = a;
                        _ = 2048;
                        break;
                    case o.SOCKET_RELIC_FIRE:
                        I = "socket-relic-fire";
                        f = a;
                        _ = 4096;
                        break;
                    case o.SOCKET_RELIC_WATER:
                        I = "socket-relic-water";
                        f = a;
                        _ = 8192;
                        break;
                    case o.SOCKET_RELIC_LIFE:
                        I = "socket-relic-life";
                        f = a;
                        _ = 16384;
                        break;
                    case o.SOCKET_RELIC_STORM:
                        I = "socket-relic-storm";
                        f = a;
                        _ = 32768;
                        break;
                    case o.SOCKET_RELIC_HOLY:
                        I = "socket-relic-holy";
                        f = a;
                        _ = 65536;
                        break;
                    case o.SOCKET_PUNCHCARD_RED:
                        I = "socket-red";
                        f = t;
                        _ = h;
                        break;
                    case o.SOCKET_PUNCHCARD_YELLOW:
                        I = "socket-yellow";
                        f = t;
                        _ = g;
                        break;
                    case o.SOCKET_PUNCHCARD_BLUE:
                        I = "socket-blue";
                        f = t;
                        _ = m;
                        break;
                    case o.SOCKET_DOMINATION:
                        I = "socket-domination";
                        f = t;
                        _ = H;
                        break;
                    case o.SOCKET_CRYSTALLIC:
                        I = "socket-crystallic";
                        f = t;
                        _ = E;
                        break;
                    case o.SOCKET_TINKER:
                        I = "socket-tinker";
                        f = t;
                        _ = T;
                        break;
                    case o.SOCKET_PRIMORDIAL:
                        I = "socket-primordial";
                        f = t;
                        _ = v;
                        break;
                    default:
                        break
                }
                let W = WH.Url.generatePath(WH.sprintf("/items/gems?filter=$1;$2;0", f, _));
                var b = WH.sprintf('<a href="' + WH.Strings.escapeHtml(W) + '" class="$1 q0">', I);
                b += o.getSocketName(e) || "Unknown Socket";
                b += "</a>";
                u += "<br>" + b
            }
            return (W == 0 ? "<br>" : "") + u + "<br><br>"
        }))
    }
    if (a) {
        let t = WH.Tooltips.getScalingData(WH.Types.ITEM, "bonusEffects");
        let i = t && t.bonus;
        if (i) {
            e = e.replace(/<!--itemEffects:(\d)-->/, (function (e, n) {
                let s = l.extraStats && l.extraStats.indexOf(parseInt(WH.jsonToStat.corruption)) >= 0;
                let r = "";
                for (let e, n = 0; e = a[n]; n++) {
                    let a = i[e] || [];
                    for (let e, i = 0; e = a[i]; i++) {
                        let a = t.effect[e];
                        if (a) {
                            if (s) {
                                a = a.replace(/\b(class=")q2\b/g, "$1stat-corruption")
                            }
                            r += (r ? "<br>" : "") + a
                        }
                    }
                }
                return r + (r && n ? "<br>" : "") + e
            }))
        }
    }
    if (WH.applyStatModifications && WH.convertScalingSpell.SpellInformation) {
        var H;
        var E = {effects: {}};
        var T = /(<!--pts(\d):(\d):(\d+(?:\.\d+)?):(\d+)(:\d+(?:\.\d+)?)?(:crm)?-->(?:<!--rtg\d+-->)?)(\d+(?:\.\d+)?)(<!---->(%?))?/g;
        while ((H = T.exec(e)) !== null) {
            var v = H[2];
            var S = H[3];
            var I = H[5];
            if (I <= 0) {
                continue
            }
            E[I] = E[I] || {};
            let e = l.scadistbonus && l.scadistbonustype === 13 ? g_items[t].level : l.level;
            WH.cO(E[I], WH.convertScalingSpell(E[I], I, v, S, n, e))
        }
        e = WH.adjustSpellPoints(e, E, l.level, g_items[t].jsonequip.slot)
    }
    let b = WH.Timewalking.getCharLevelFromIlvl(s) || 0;
    if (b) {
        e = e.replace(/<!--ee(\d+):(\d+):(\d+):(\d+):(\d+):(\d+)-->([^<]*)<\/span>/gi, (function (e, t, a, i, n, s, r, o) {
            var l = {
                enchantment: o,
                scalinginfo: {
                    scalingcategory: t,
                    minlvlscaling: a,
                    maxlvlscaling: i,
                    damage1: n / 1e3,
                    damage2: s / 1e3,
                    damage3: r / 1e3
                }
            };
            var c = WH.scaleItemEnchantment(l, b);
            return "\x3c!--ee--\x3e" + c + "</span>"
        }))
    }
    e = e.replace(/(<!--rtg%(\d+)-->)([\.,0-9]+)%?/g, (function (t, a, i, s) {
        _ = e.match(new RegExp("\x3c!--rtg" + i + "--\x3e([\\d\\.,]+)(-[\\d\\.,]+)?"));
        if (!_) {
            return t
        }
        if (_[2]) {
            _[2] = _[2].replace(/\D/, "")
        }
        _[1] = _[1].replace(/\D/, "");
        var r = _[2] ? (Math.abs(parseInt(_[2])) + parseInt(_[1])) / 2 : _[1];
        return a + (_[2] ? "~" : "") + Math.round(WH.convertRatingToPercent(n ? n : WH.Wow.getMaxPlayerLevel(), i, r) * 100) / 100 + (i != 49 ? "%" : "")
    }));
    e = e.replace(/<!--bo-->(<br(?: \/)?>)?([^<]+)/, (function (e, t, a) {
        if (l.bond) {
            a = WH.Wow.Item.getBondTypeName(l.bond)
        }
        return "\x3c!--bo--\x3e" + t + a
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
                WH.ae(a, WH.ce("span", {className: "q"}, WH.ct(t[e][2] ? WH.Strings.sprintf(WH.GlobalStrings.ITEM_UPGRADE_TOOLTIP_FORMAT_STRING, t[e][2], t[e][0], t[e][1]) : WH.Strings.sprintf(WH.GlobalStrings.ITEM_UPGRADE_TOOLTIP_FORMAT, t[e][0], t[e][1]))));
                i = a.innerHTML;
                return true
            }
        }));
        return i + e
    }));
    e = e.replace(/<!--ue-->/, (function () {
        if (!l.limitcategory) {
            return ""
        }
        let e = "";
        let t = (WH.getPageData("wow.item.bonusLimitCategoryNames") || {})[l.limitcategory];
        if (t) {
            let a = t.uniqueEquipped ? WH.GlobalStrings.ITEM_UNIQUE_EQUIPPABLE : WH.GlobalStrings.ITEM_UNIQUE;
            e = WH.Strings.escapeHtml(a + WH.TERMS.colon_punct + WH.Strings.sprintf(WH.TERMS.parens_format, t.name, t.maxCount));
            e = "<br />" + e
        }
        return e
    }));
    e = e.replace(/<!--pvpEquip-->.*?<!--pvpEquip-->/, (function () {
        if (!l.levelSetPvp) {
            return ""
        }
        return '<br><span class="q2">' + WH.Strings.sprintf(WH.GlobalStrings.PVP_ITEM_LEVEL_TOOLTIP, l.levelSetPvp) + "</span><br><br>"
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
            if (l.level) {
                t += (t ? "&" : "") + "ilvl=" + l.level
            }
            e.dataset.wowhead = t
        }));
        let i = WH.getPageData("item.sellprice." + t);
        let s = a.querySelector(".whtt-sellprice");
        if (i && s) {
            let e = s.firstChild;
            WH.ee(s);
            WH.ae(s, e);
            let t = i.itemLevel;
            let a = t[l.level] || t[Math.max.apply(null, Object.keys(t))];
            let n = i.quality[l.quality] || 0;
            let r = Math.floor(i.base * a * n);
            WH.ae(s, WH.Wow.buildMoney({copper: r}))
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
    e = e.replace(/<!--(gem|ee)(\d+):(\d+):(\d+):(\d+):(\d+):(\d+)-->([^<]*)<\/span>/gi, (function (e, a, i, n, s, r, o, l, c) {
        var d = {
            enchantment: c,
            scalinginfo: {
                scalingcategory: i,
                minlvlscaling: n,
                maxlvlscaling: s,
                damage1: r / 1e3,
                damage2: o / 1e3,
                damage3: l / 1e3
            }
        };
        var f = WH.scaleItemEnchantment(d, t);
        return "\x3c!--" + a + "--\x3e" + f + "</span>"
    }));
    var s = e.match(/<!--i?\?([0-9-:]*)-->/);
    var r;
    var o;
    if (s) {
        r = s[1].split(":").map(Number);
        t = Math.min(r[2], Math.max(r[1], t));
        o = r[4] || 0
    }
    if (o) {
        if (!e.match(/<!--pts\d:\d:\d+(?:\.\d+)?:\d+-->/g) && !(o < 0) && !a) {
            e = WH.setItemModifications(e, r[0], null, null, t);
            WH.updateItemStringLink.call(this)
        } else {
            if (o > 0) {
                if (!r[7] && WH.isSet("g_pageInfo") && g_pageInfo.type == 3 && g_items[g_pageInfo.typeId] && g_items[g_pageInfo.typeId].quality != 7) {
                    t = Math.min(g_items[g_pageInfo.typeId].reqlevel, t)
                }
                var l = {scadist: o};
                e = e.replace(/<!--cast-->\d+\.\d+/, "\x3c!--cast--\x3e" + l.cast);
                var c = /<!--pts([0-9-:]*)-->/g;
                var d = c.exec(e);
                l.effects = true;
                while (d != null) {
                    var f = d[1].split(":").map(Number);
                    var u = f[0];
                    var p = f[1];
                    var h = f[3];
                    if (h > 0) {
                        if (l[h] == undefined) {
                            l[h] = {};
                            l[h].effects = {}
                        }
                        WH.cO(l[h], WH.convertScalingSpell(l[h], h, u, p, t, t))
                    }
                    d = c.exec(e)
                }
                if (l.effects) {
                    var g = 5;
                    var m = g;
                    if (window.g_pageInfo && window.g_pageInfo.type == WH.Types.AZERITE_ESSENCE_POWER) {
                        m = WH.Wow.Item.INVENTORY_TYPE_NECK
                    }
                    e = WH.adjustSpellPoints(e, l, t, m);
                    if (this.modified) {
                        for (var W in this.modified[1]) {
                            var H = this.modified[1][W];
                            for (var E = 0; E < H.length; ++E) {
                                H[E][0] = WH.adjustSpellPoints(H[E][0], l, t, m);
                                H[E][1] = WH.adjustSpellPoints(H[E][1], l, t, m)
                            }
                        }
                    }
                }
            } else {
                var T = -o;
                var v = WH.getSpellScalingValue(T, t);
                for (var S = 0; S < 3; ++S) {
                    var I = r[5 + S] / 1e3;
                    e = e.replace(new RegExp("\x3c!--gem" + (S + 1) + "--\x3e(.+?)<"), "\x3c!--gem" + (S + 1) + "--\x3e" + Math.round(v * I) + "<")
                }
            }
        }
    }
    e = e.replace(/<!--ppl(\d+):(\d+):(\d+):(\d+):(\d+)(?::(1))?-->\s*\d+/gi, (function (e, a, i, n, s, r, o) {
        var l = o ? Math.ceil : Math.floor;
        return "\x3c!--ppl" + a + ":" + i + ":" + n + ":" + s + ":" + r + "--\x3e" + l(parseInt(s) + (Math.min(Math.max(t, i), n) - i) * r / 100)
    }));
    e = e.replace(/(<!--rtg%(\d+)-->)([\.0-9]+)%?/g, (function (a, i, n, s) {
        _ = e.match(new RegExp("\x3c!--rtg" + n + "--\x3e(\\d+)"));
        if (!_) {
            return a
        }
        return i + Math.round(WH.convertRatingToPercent(t, n, _[1]) * 100) / 100 + (n != 49 ? "%" : "")
    }));
    e = e.replace(/<!--pl(\d+):(\d+):(\d+)-->\s?(\d+)/gi, (function (e, a, i, n, s) {
        t = Math.min(t, WH.Wow.getMaxPlayerLevel());
        return "\x3c!--pl" + a + ":" + i + ":" + n + "--\x3e" + t
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
    for (var s = 1; s <= 20; ++s) {
        e = e.replace(new RegExp("\x3c!--pts" + s + ":0:0:(\\d+)(:\\d+(?:\\.\\d+)?)?(:crm)?--\x3e(\x3c!--rtg\\d+--\x3e)?(.+?)<", "g"), (function (e, a, i, r, o) {
            var l = t[a] && t[a].hasOwnProperty("effects") ? t[a].effects[s] : t.effects[s];
            if (!l) {
                return e
            }
            var c = Math.round(l.avg * (r ? n : 1));
            return "\x3c!--pts" + s + ":0:0:" + a + (i || "") + (r || "") + "--\x3e" + (o ? o : "") + c + "<"
        }));
        e = e.replace(new RegExp("\x3c!--pts" + s + ":1:0:(\\d+)(:\\d+(?:\\.\\d+)?)?(:crm)?--\x3e(\x3c!--rtg\\d+--\x3e)?(.+?)<", "g"), (function (e, a, i, r, o) {
            var l = t[a] && t[a].hasOwnProperty("effects") ? t[a].effects[s] : t.effects[s];
            if (!l) {
                return e
            }
            var c = Math.round(l.min * (r ? n : 1));
            return "\x3c!--pts" + s + ":1:0:" + a + (i || "") + (r || "") + "--\x3e" + (o ? o : "") + c + "<"
        }));
        e = e.replace(new RegExp("\x3c!--pts" + s + ":2:0:(\\d+)(:\\d+(?:\\.\\d+)?)?(:crm)?--\x3e(\x3c!--rtg\\d+--\x3e)?(.+?)<", "g"), (function (e, a, i, r, o) {
            var l = t[a] && t[a].hasOwnProperty("effects") ? t[a].effects[s] : t.effects[s];
            if (!l) {
                return e
            }
            var c = Math.round(l.max * (r ? n : 1));
            return "\x3c!--pts" + s + ":2:0:" + a + (i || "") + (r || "") + "--\x3e" + (o ? o : "") + c + "<"
        }));
        e = e.replace(new RegExp("\x3c!--pts" + s + ":3:(\\d+(?:\\.\\d+)?):(\\d+)(:\\d+(?:\\.\\d+)?)?(:crm)?--\x3e(\x3c!--rtg\\d+--\x3e)?(.+?)<", "g"), (function (e, a, i, r, o, l) {
            var c = t[i] && t[i].hasOwnProperty("effects") ? t[i].effects[s] : t.effects[s];
            if (!c) {
                return e
            }
            var d = Math.round(c.avg * a * (o ? n : 1));
            return "\x3c!--pts" + s + ":3:" + a + ":" + i + (r || "") + (o || "") + "--\x3e" + (l ? l : "") + d + "<"
        }));
        e = e.replace(new RegExp("\x3c!--pts" + s + ":4:0:(\\d+)(:\\d+(?:\\.\\d+)?)?(:crm)?--\x3e(\x3c!--rtg\\d+--\x3e)?(.+?)<", "g"), (function (e, a, i, r, o) {
            var l = t[a] && t[a].hasOwnProperty("effects") ? t[a].effects[s] : t.effects[s];
            if (!l) {
                return e
            }
            var c = Math.round(l.pts * (r ? n : 1));
            return "\x3c!--pts" + s + ":4:0:" + a + (i || "") + (r || "") + "--\x3e" + (o ? o : "") + c + "<"
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
WH.setTooltipSpells = function (e, t, a, i, n = 0) {
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
        var s = function (e) {
            var t = [];
            if (e.hasOwnProperty("data")) {
                t.push(e.data)
            }
            for (var a = 0; a < e.children.length; a++) {
                t = t.concat(s(e.children[a]))
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
                for (var l = 0; l <= 1; l++) {
                    var c = -1;
                    while ((c = a[e][r].data[l].indexOf("\x3c!--sp" + e + "--\x3e", c + 1)) >= 0) {
                        o++
                    }
                }
                var d = r - o;
                if (d < 0) {
                    continue
                }
                while (o-- > 0) {
                    var f = a[e].splice(d, 1);
                    r--;
                    a[e][r].children.push(f[0])
                }
            }
            a[e] = s({children: a[e]})
        }
    }
    i = i || {};
    var u = function (e) {
        i[e] = (i[e] || 0) + 1;
        if (i[e] >= (a[e] || []).length) {
            i[e] = 0
        }
    };
    var p = [];
    var h = /<!--sp([0-9]+):[01]-->/g;
    var g;
    while (g = h.exec(e)) {
        var m = g[0];
        var W = g.index + m.length;
        var H = "\x3c!--sp" + g[1] + "--\x3e";
        var E = e.indexOf(H, W);
        if (E < 0) {
            WH.warn("Could not find closing end tag for tooltip spell.", H, e);
            return e
        }
        var T = new RegExp("\x3c!--sp" + g[1] + ":[01]--\x3e", "g");
        T.lastIndex = W;
        var v = T.exec(e);
        while (v && v.index < E) {
            E = e.indexOf(H, E + H.length);
            if (E < 0) {
                WH.warn("Could not find nested closing end tag for tooltip spell.", H, e);
                return e
            }
            v = T.exec(e)
        }
        p.push(e.substring(g.index, E + H.length));
        h.lastIndex = E + H.length
    }
    var S = 0;
    var I = /^(<!--sp([0-9]+):[01]-->).*(<!--sp\2-->)$/;
    for (var _ = 0; _ < p.length; ++_) {
        var b = p[_].match(I)[2];
        var w = WH.inArray(t, parseInt(b)) >= 0 ? 1 : 0;
        if (a[b] == null) {
            continue
        }
        if (i[b] == null) {
            i[b] = 0
        }
        var y = a[b][i[b]];
        if (y == null || y[w] == null) {
            continue
        }
        u(b);
        if (w && (g = y[2].match(/^(!?)(\d+)$/))) {
            if (g[1]) {
                if (WH.inArray(t, parseInt(g[2])) >= 0) {
                    w = 0
                }
            } else {
                t.push(parseInt(g[2]))
            }
        }
        let s = y[w];
        s = WH.setTooltipSpells(s, t, a, i, n + 1);
        let r = e.substr(0, S);
        let o = r.lastIndexOf("<a ");
        if (o > -1 && r.lastIndexOf("</a>") < o) {
            s = s.replace(/<a [^>].*>(.*?)<\/a>/gi, "$1")
        }
        let l = "\x3c!--sp" + b + ":" + w + "--\x3e" + s + "\x3c!--sp" + b + "--\x3e";
        e = r + e.substr(S).replace(p[_], l);
        S = e.indexOf(l, S) + l.length;
        if (w) {
            for (var A = _ + 1; A < p.length; A++) {
                if (e.indexOf(p[A], S) !== S) {
                    break
                }
                g = p[A].match(I);
                l = g[1] + g[3];
                e = e.substr(0, S) + e.substr(S).replace(p[A], l);
                u(g[2]);
                S += l.length;
                _++
            }
        }
    }
    e = WH.Tooltips.evalFormulas(e, n);
    let C = new RegExp("\x3c!--useEffect:0:([0-9]+)--\x3e(.*?)\x3c!--useEffect:[0-9]+--\x3e", "gs");
    let R;
    while (R = C.exec(e)) {
        let t = WH.ce("div", {innerHTML: R[2]});
        let a = new RegExp("\x3c!--useText:0:" + R[1] + '--\x3e<span id="useText' + R[1] + '" class="q2".*?>(.*?</span>\x3c!--useText:' + R[1] + "--\x3e)", "s");
        e = e.replace(a, "\x3c!--useText:0:" + R[1] + '--\x3e<span id="useText' + R[1] + '" class="q2"' + (t.innerText === "" ? ' style="display:none"' : "") + ">$1")
    }
    if (n === 0) {
        e = WH.updateTooltipCooldownAndCharges(e, t)
    }
    return e
};
WH.enhanceTooltip = function (e, t, a, i, n, s, r, o, l, c, d, f, u) {
    if ((!WH.applyStatModifications || !WH.applyStatModifications.ScalingData) && (u || o)) {
        g_itemScalingCallbacks.push(function (p) {
            return function () {
                var h = WH.enhanceTooltip.call(p, e, t, a, i, n, s, r, o, l, c, d, f, u);
                WH.updateTooltip.call(p, h)
            }
        }(this));
        return WH.TERMS.loading_ellipsis
    }
    var p = typeof e, h, g;
    var m = WH.getDataSource();
    var W = WH.isSet("g_pageInfo") ? g_pageInfo.type : null;
    g = WH.isSet("g_pageInfo") ? g_pageInfo.typeId : null;
    this._spellModifiers = s;
    if (p == "number") {
        g = e;
        var H = "tooltip_";
        if (n) H = "buff_";
        if (d) H = "tooltip_premium_";
        if (f) H = "text_";
        if (m[g] && m[g][H + Locale.getName()]) {
            e = m[g][H + Locale.getName()];
            h = m[g][(n ? "buff" : "") + "spells_" + Locale.getName()];
            this._rppmModList = m[g]["rppmmod"];
            if (h) {
                e = WH.setTooltipSpells(e, s, h)
            }
        } else {
            return e
        }
    } else if (p != "string") {
        return e
    }
    let E;
    let T;
    if (a) {
        var v = WH.getGets();
        if (v.lvl) {
            T = Math.min(v.lvl, WH.Wow.getMaxPlayerLevel());
            e = WH.setTooltipLevel(e, T, n)
        } else {
            let e = WH.Wow.Season.MAX_LEVEL_BY_PHASE[WH.getDataEnv()]?.[WH.Wow.Season.getCurrentPhase()];
            if (e) {
                T = e
            }
        }
        if (T) {
            e = WH.setTooltipLevel(e, T, n)
        }
        E = v.ilvl
    }
    let S = WH.parseQueryString(WH.getQueryString());
    let I = function () {
        if (!S["crafted-stats"]) {
            return []
        }
        return S["crafted-stats"].split(":").map((function (e) {
            return parseInt(e)
        })).filter((function (e) {
            return WH.Wow.Item.Stat.CRAFTING_STAT_TO.includes(e)
        }))
    };
    let _ = I();
    if ((u || o || _.length) && g) {
        e = WH.setItemModifications(e, g, u, o, this._selectedLevel ? this._selectedLevel : null, E, _)
    }
    if (t) {
        e = e.replace(/\(([^\)]*?<!--lvl-->[^\(]*?)\)/gi, (function (e, t) {
            return '(<a href="javascript:" onmousedown="return false" class="tip" style="color: white; cursor: pointer" onclick="WH.staticTooltipLevelClick(this, null, 0)" onmouseover="WH.Tooltips.showAtCursor(event, \'<span class=\\\'q2\\\'>\' + WH.TERMS.clicktochangelevel_stc + \'</span>\')" onmousemove="WH.Tooltips.cursorUpdate(event)" onmouseout="WH.Tooltips.hide()">' + t + "</a>)"
        }));
        if (e.indexOf("\x3c!--artpow:") > 0) {
            if (!this.hasOwnProperty("_knowledgeLevel")) {
                var b = /(&|\?)artk=(\d+)/.exec(location.href);
                if (b && parseInt(b[2]) <= g_artifact_knowledge_max_level) {
                    this._knowledgeLevel = parseInt(b[2])
                }
            }
            var w = this._knowledgeLevel ? parseInt(this._knowledgeLevel) : 0;
            e = e.replace(/(<!--ndstart-->)?<!--ndend-->/i, (function (e, t) {
                return (t ? t + "<br />" : " ") + '<a href="javascript:" onmousedown="return false" class="tip" style="color: white; cursor: pointer" onclick="WH.staticTooltipKnowledgeLevelClick(this, null, ' + g + ')" onmouseover="WH.Tooltips.showAtCursor(event, \'<span class=\\\'q2\\\'>\' + WH.TERMS.clicktochangelevel_stc + \'</span>\')" onmousemove="WH.Tooltips.cursorUpdate(event)" onmouseout="WH.Tooltips.hide()">' + WH.sprintf(WH.TERMS.knowledge_format.replace("%d", "$1"), w) + "</a>"
            }));
            e = e.replace(/(<!--artpow:(\d+)-->)[\d\.\,]+/, (function (e, t, a) {
                return t + WH.numberLocaleFormat(WH.roundArtifactPower(parseInt(a) * WH.getArtifactKnowledgeMultiplier(w)))
            }))
        }
    }
    if (i && Slider) {
        var y = WH.groupSizeScalingShouldShow(g);
        if (n) {
            n.bufftip = this;
            if (y && WH.isSet("g_difficulties") && g_difficulties[y]) {
                e = WH.groupSizeScalingOnChange.call(n, this, g_difficulties[y].maxplayers, 1, true)
            }
        } else {
            var A = new RegExp("\x3c!--" + (W && W == 3 ? "i" : "") + "\\?(\\d+):(\\d+):(\\d+):(\\d+)");
            var C = e.match(A);
            if (typeof C == "undefined" && W == 3) {
                A = new RegExp("\x3c!--\\?(\\d+):(\\d+):(\\d+):(\\d+)");
                C = e.match(A)
            }
            if (!C && !WH.isRetailTree()) {
                A = new RegExp("\x3c!--ppl(\\d+):(\\d+):(\\d+):(\\d+):(\\d+)");
                let t = e.match(A);
                if (t) {
                    C = [null, null, t[2], WH.Wow.getMaxPlayerLevel(), T ?? WH.Wow.getMaxPlayerLevel()]
                }
            }
            if (!C) {
                A = new RegExp("\x3c!--pl(\\d+):(\\d+):(\\d+)--\x3e\\s?(\\d+)");
                let t = e.match(A);
                if (t) {
                    C = [null, null, t[2], t[3], T ?? t[4]]
                }
            }
            if (y && WH.isSet("g_difficulties") && g_difficulties[y]) {
                var R = WH.ce("label");
                R.innerHTML = WH.TERMS.difficulty + ": ";
                this._difficultyBtn = WH.ce("a");
                this._difficultyBtn.ttId = g;
                WH.difficultyBtnBuildMenu.call(this, g);
                Menu.add(this._difficultyBtn, this._difficultyBtn.menu);
                let t = WH.ge("dd" + g);
                WH.ae(t, R);
                WH.ae(t, this._difficultyBtn);
                t.style.display = "block";
                WH.difficultyBtnOnChange.call(this, m[g].initial_dd || y, m[g].initial_ddSize);
                e = WH.groupSizeScalingOnChange.call(this, this, g_difficulties[y].maxplayers, 0, true)
            } else if (C) {
                if (C[2] != C[3]) {
                    this.slider = Slider.init(i, {
                        maxValue: parseInt(C[3]),
                        minValue: Math.max(parseInt(C[2]), 1),
                        onMove: WH.tooltipSliderMove.bind(this),
                        title: WH.GlobalStrings.LEVEL
                    });
                    i.style.display = "block";
                    Slider.setValue(this.slider, parseInt(C[4]));
                    this.slider.onmouseover = function (e) {
                        WH.Tooltips.showAtCursor(e, WH.TERMS.dragtochangelevel_stc, "q2")
                    };
                    this.slider.onmousemove = WH.Tooltips.cursorUpdate;
                    this.slider.onmouseout = WH.Tooltips.hide;
                    WH.Tooltips.attach(Slider.getInput(this.slider), WH.TERMS.clicktochangelevel_stc, "q2")
                }
            }
        }
    }
    if (r && !r.dataset.initialized) {
        if (n && n.modified) {
            n.bufftip = this
        } else {
            let e = WH.getPageData("WH.Wow.Covenant.data");
            for (let t in h) {
                let a = Object.keys(e).find((a => e[a].spellId === parseInt(t)));
                if ((!WH.Gatherer.get(WH.Types.SPELL, t) || s.includes(t)) && !a) {
                    continue
                }
                let i = WH.Gatherer.get(WH.Types.SPELL, t);
                let n = i["name_" + Locale.getName()];
                let o = i["rank_" + Locale.getName()] || "";
                let l = o ? WH.term("parens_format", n, o) : n;
                let c = WH.ce("label");
                let d = WH.ce("input", {type: "checkbox", dataset: {spellId: t}});
                WH.ae(c, d);
                WH.aE(d, "click", WH.tooltipSpellsChange.bind(this));
                let f = WH.ce("a", undefined, WH.ct(l));
                if (a) {
                    f.classList.add("covenant-" + WH.Wow.Covenant.getSlug(a))
                } else {
                    f.href = WH.Entity.getUrl(WH.Types.SPELL, t, n);
                    WH.aE(f, "click", (function (e) {
                        e.preventDefault();
                        d.click()
                    }))
                }
                WH.ae(c, f);
                c.setAttribute("unselectable", "");
                WH.ae(r, c);
                WH.ae(r, WH.ce("br"))
            }
        }
        WH.onLoad((() => {
            let e = WH.Url.parseQueryString(location.search);
            if (e.covenant) {
                let t = ((WH.getPageData("WH.Wow.Covenant.data") || {})[e.covenant] || {}).spellId;
                if (t) {
                    WH.checkSpellModifierCheckbox(g, t)
                }
            }
            if (e.spellModifier) {
                e.spellModifier.split(":").forEach((e => {
                    WH.checkSpellModifierCheckbox(g, e)
                }))
            }
            WH.tooltipSpellsChange.call(this)
        }));
        this.modified = [r, h, s];
        r.style.display = WH.DOM.isEmpty(r) ? "none" : "inline-block";
        r.dataset.initialized = "true"
    }
    if (c) {
        var L = e.match(/<!--rppm-->(\d+(?:\.\d+)?)<!--rppm-->/);
        if (L) {
            var M = $("#rppm" + g);
            if (this._rppmModList.hasOwnProperty(4)) {
                this._rppmModBase = parseFloat(L[1]);
                if (M.is(":empty")) {
                    this._rppmSpecModList = this._rppmModList[4];
                    this._rppmSpecModList.splice(0, 0, {spec: -1, modifiervalue: 0, filename: ""});
                    M.append(WH.getMajorHeading(WH.TERMS.realppmmodifiers, 2, 3));
                    for (var O in this._rppmSpecModList) {
                        var D = WHIcon.create(this._rppmSpecModList[O]["filename"], 0, null);
                        D.style.display = "inline-block";
                        D.style.verticalAlign = "middle";
                        var N = $('<input name="rppmmod" type="radio" id="rppm-' + O + '" />');
                        N.get(0).checked = this._rppmSpecModList[O]["spec"] == -1;
                        M.append(N).append(this._rppmSpecModList[O]["spec"] == -1 ? "" : D).append('<label for="rppm-' + O + '"> <a>' + (this._rppmSpecModList[O]["spec"] == -1 ? WH.TERMS.none : WH.Wow.PlayerClass.Specialization.getName(this._rppmSpecModList[O]["spec"])) + "</a></label>").append("<br />");
                        var P = this;
                        $("#rppm-" + O).change((function () {
                            WH.tooltipRPPMChange.call(this, P)
                        }))
                    }
                } else {
                    var x = this._rppmModBase;
                    var k = this._rppmSpecModList;
                    e = e.replace(/<!--rppm-->(\[?)(\d+(?:\.\d+)?)([^<]*)<!--rppm-->/, (function (e, t, a, i) {
                        return "\x3c!--rppm--\x3e" + t + (x * (1 + parseFloat(k[$('input[name="rppmmod"]:checked', M).attr("id").match(/\d+$/)[0]].modifiervalue))).toFixed(2) + i + "\x3c!--rppm--\x3e"
                    }))
                }
            }
            M.toggle(!M.is(":empty"));
            var B = "";
            if (this._rppmModList.hasOwnProperty(1)) {
                B += " + " + WH.Wow.Item.Stat.jsonToAbbr("hastertng")
            } else if (this._rppmModList.hasOwnProperty(2)) {
                B += " + " + WH.Wow.Item.Stat.jsonToAbbr("critstrkrtng")
            }
            if (g_pageInfo.type == 6 && this._rppmModList.hasOwnProperty(6)) {
                B += " + " + "Budget"
            }
            if (B.length > 0) {
                e = e.replace(/<!--rppm-->\[?(\d+(?:\.\d+)?)([^<]*)<!--rppm-->/, (function (e, t, a) {
                    return "\x3c!--rppm--\x3e[" + t + B + "]" + a + "\x3c!--rppm--\x3e"
                }))
            }
        }
    }
    if (l) {
        if (m[g] && m[g].hasOwnProperty("tooltip_" + Locale.getName() + "_pvp")) {
            $(l).append('<input type="checkbox" id="item-upgrade-pvp" />').append('<label for="item-upgrade-pvp"><a>' + WH.TERMS.pvpmode + "</a></label>").append("<br />");
            $("#item-upgrade-pvp").change(WH.upgradeItemTooltip.bind(this, l, "pvp"))
        }
        let e = WH.Timewalking.getConfigs();
        if (e.length > 0) {
            let t = WH.ce("span", undefined, WH.ct(WH.TERMS.timewalkingScaling));
            WH.Tooltips.attach(t, WH.TERMS.timewalkingScaling_tip, "q");
            let a = WH.ce("label", undefined, [t, WH.ct(WH.TERMS.colon_punct)]);
            let i = WH.ce("a", undefined, WH.ct(WH.TERMS.none));
            WH.ae(l, a);
            WH.ae(l, i);
            let n = [];
            n.push(Menu.createItem({
                label: WH.TERMS.none, url: () => {
                    delete l.dataset.selected;
                    WH.upgradeItemTooltip.bind(this, l, undefined, true)();
                    WH.st(i, WH.TERMS.none)
                }, options: {checkedFunc: () => !l.dataset.selected}
            }));
            e.forEach((e => {
                let t = `tooltip_${Locale.getName()}_${e.stringId}`;
                if (!m[g] || !m[g].hasOwnProperty(t)) {
                    return
                }
                let a = WH.Wow.Expansion.getName(e.id);
                n.push(Menu.createItem({
                    label: a,
                    crumb: e.stringId,
                    url: () => {
                        if (l.dataset.selected !== e.stringId) {
                            delete l.dataset.selected;
                            l.dataset.selected = e.stringId;
                            WH.upgradeItemTooltip.bind(this, l, e.stringId, true)();
                            WH.st(i, a)
                        }
                    },
                    options: {
                        checkedFunc: () => e.stringId === l.dataset.selected,
                        className: `item-upgrade-${e.stringId}`
                    }
                }));
                $(l).toggle(!$(l).is(":empty"))
            }));
            Menu.add(i, n)
        }
    }
    let F;
    if (W == 3) {
        var U = $("#cs" + g);
        if (U && WH.Wow.Item.tooltipHasSpecStats(e)) {
            if (!this._classSpecBtn) {
                var G = WH.ce("label");
                G.innerHTML = WH.TERMS.showingtooltipfor_stc + " ";
                this._classSpecBtn = WH.ce("a");
                this._classSpecBtn.ttId = g;
                WH.classSpecBtnBuildMenu.call(this, m[g].hasOwnProperty("validMenuSpecs") ? m[g].validMenuSpecs : false);
                Menu.add(this._classSpecBtn, this._classSpecBtn.menu);
                U.append(G).append(this._classSpecBtn).show()
            }
            F = WH.LocalStorage.get(WH.LocalStorage.KEY_WOW_DATABASE_SPEC_FILTER);
            if (typeof F !== "object") {
                F = null
            }
            let t = /(&|\?)class=(\d+)/.exec(location.href);
            if (t) {
                F = {classId: parseInt(t[2]), specId: 0}
            }
            let a = /(&|\?)spec=(\d+)/.exec(location.href);
            let i, n;
            if (a) {
                i = parseInt(a[2]);
                n = WH.Wow.PlayerClass.getBySpec(i);
                if (n) {
                    F = {classId: n, specId: i};
                    let e = /(&|\?)hero=(\d+)/.exec(location.href);
                    if (e) {
                        F.heroTreeId = parseInt(e[2])
                    }
                }
            }
            if (F) {
                e = WH.classSpecBtnOnChange.call(this, F.classId, F.specId, F.heroTreeId ?? 0, e, true)
            } else {
                $(this._classSpecBtn).text(WH.isRetailTree() ? WH.TERMS.chooseaspec_stc : WH.TERMS.chooseAClass_stc)
            }
        }
    }
    if (W === WH.Types.ITEM && m[g]) {
        WH.Page.Wow.Item.initBonuses(this, u)
    }
    (function () {
        let e = WH.ge("craftedStatsSelector" + g);
        if (!m[g] || !e || e.dataset.initialized) {
            return
        }
        const t = this;
        let a = 0;
        let i;
        let n = function (e) {
            let t = I();
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
            s();
            if (m[g]["tooltip_" + Locale.getName()]) {
                let e = this._bonusesBtn && this._bonusesBtn.selectedBonus ? this._bonusesBtn.selectedBonus : null;
                let t = WH.enhanceTooltip.call(this, g, true, true, false, null, this._spellModifiers, WH.ge("ks" + g), o, null, true, null, null, e);
                WH.updateTooltip.call(this, t)
            }
        };
        let s = function () {
            let e = "";
            let t = I();
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
        let r = m[g].jsonequip && m[g].jsonequip.statsInfo || {};
        WH.Wow.Item.Stat.CRAFTING_STAT_FROM.forEach((function (e) {
            if (r.hasOwnProperty(e)) {
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
                        return I().includes(parseInt(e[Menu.ITEM_CRUMB]))
                    }
                }
            }))
        }));
        l.sort((function (e, t) {
            return e[Menu.ITEM_LABEL].localeCompare(t[Menu.ITEM_LABEL])
        }));
        Menu.add(i, l);
        s()
    }).call(this);
    let q = this.slider ? this.slider._max : WH.Wow.getMaxPlayerLevel();
    let z = this._selectedLevel || q;
    let Y = F ? F.classId : WH.Wow.PlayerClass.WARRIOR;
    e = WH.addRatingPercent(e, z, q, Y);
    if (W === WH.Types.ITEM) {
        WH.updateItemStringLink.call(this)
    }
    e = WH.updateTooltipSingular(e);
    if (S["spec"]) {
        e = WH.Tooltips.parseItemEffectTooltipForSpec(e, parseInt(S["spec"]))
    }
    if (S["hero"]) {
        e = WH.Tooltips.parseItemEffectTooltipForHero(e, parseInt(S["hero"]))
    }
    return e
};
WH.addRatingPercent = function (e, t, a, i) {
    let n = WH.ce("div", {innerHTML: e});
    WH.qsa("span", n).forEach((function (e) {
        let n;
        let s;
        e.childNodes.forEach((function (e) {
            if (e.nodeType === Node.COMMENT_NODE) {
                let t = (e.nodeValue || "").match(/^rtg(\d+)$/);
                if (t) {
                    n = parseInt(t[1]);
                    s = e
                }
            }
        }));
        if (n === undefined) {
            return
        }
        let r = s.nextSibling.nodeValue.match(/(\d+)(.*)$/);
        if (!r) {
            return
        }
        let o = WH.qs("small.rating-percent");
        if (o) {
            WH.de(o)
        }
        let l = parseInt(r[0]);
        let c = r[2];
        let d = WH.convertRatingToPercent(t, n, l, i);
        let f = WH.TERMS ? WH.term("valueAtLevel_format", d.toFixed(2), t) : " (" + d.toFixed(2) + "% @ L" + t + ")";
        let u = s.nextSibling;
        let p = WH.ce("small", {className: "rating-percent"}, WH.ct(f));
        if (c === ".") {
            u.parentNode.insertBefore(WH.ct(l), u);
            u.parentNode.insertBefore(p, u);
            u.parentNode.insertBefore(WH.ct("."), u)
        } else {
            u.parentNode.insertBefore(WH.ce("span", null, WH.ct(l + c)), u);
            u.parentNode.insertBefore(p, u)
        }
        u.parentNode.removeChild(u);
        p.setAttribute("onclick", "WH.tooltipLevelPrompt(" + t + ", " + a + ");")
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
    let s = this._difficultyBtn.selectedDD;
    let r = a.value;
    WH.Url.replacePageQuery((function (e) {
        if (s != WH.groupSizeScalingShouldShow(n) || r != g_difficulties[WH.groupSizeScalingShouldShow(n)].maxplayers) {
            e.dd = s;
            e.ddsize = r
        } else {
            delete e.dd;
            delete e.ddsize
        }
    }));
    WH.groupSizeScalingOnChange.call(this, this, a.value, 0);
    if (this.bufftip) {
        WH.groupSizeScalingOnChange.call(this, this.bufftip, a.value, 1)
    }
    WH.Tooltips.hide()
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
    var s = WH.getDataSource();
    var r = WH.isSet("g_pageInfo") ? g_pageInfo["typeId"] : null;
    if (!s[r]) {
        return
    }
    var o = this._difficultyBtn.selectedDD;
    var l = Locale.getName();
    var c = "server_" + (a ? "buff_" : "tooltip_") + l;
    var d = "dd" + o + "ddsize" + t;
    WH.groupSizeScalingOnChange.lastCall = d;
    if (!s[r][c]) {
        s[r]["server_tooltip_" + l] = {};
        s[r]["server_buff_" + l] = {};
        var f = "dd" + s[r].initial_dd + "ddsize" + s[r].initial_ddSize;
        s[r]["server_tooltip_" + l][f] = s[r]["tooltip_" + l];
        s[r]["server_buff_" + l][f] = s[r]["buff_" + l]
    }
    if (s[r][c][d]) {
        var u = s[r][c][d];
        if (i) {
            return u
        }
        WH.updateTooltip.call(e, u);
        return
    }
    if (i) {
        return s[r][c.substr(7)]
    }
    if (a) {
        return
    }
    if (s[r][c].hasOwnProperty(d)) {
        return
    }
    s[r][c][d] = "";
    var p = WH.Entity.getUrl(WH.Types.SPELL, r) + "?dd=" + o + "&ddsize=" + t;
    if (WH.isBeta() || WH.isPtr() || WH.isPtr2()) {
        p += "&" + WH.getDataCacheVersion()
    }
    WH.xhrJsonRequest(p, (function (a) {
        if (!a) {
            return
        }
        s[r]["server_tooltip_" + l][d] = a["tooltip"];
        s[r]["server_buff_" + l][d] = a["buff"];
        if (WH.groupSizeScalingOnChange.lastCall === d) {
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
        var s = [n, WH.Wow.Difficulty.getName(n), WH.difficultyBtnOnChange.bind(this, n, false)];
        t.push(s)
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
    var n = g_difficulties[e].minplayers, s = g_difficulties[e].maxplayers, r = g_difficulties[e].maxplayers;
    if (i) {
        if (i > s) {
            r = s
        } else if (i < n) {
            r = n
        } else {
            r = i
        }
    }
    n = s;
    var o = $("#sl" + this._difficultyBtn.ttId);
    o.html("").hide();
    this.slider = null;
    if (n != s) {
        o.show();
        this.slider = Slider.init(o.get(0), {
            maxValue: parseInt(s),
            minValue: parseInt(n),
            onMove: WH.groupSizeScalingSliderMove.bind(this),
            title: WH.TERMS.players
        });
        Slider.setValue(this.slider, parseInt(r));
        this.slider.onmouseover = function (e) {
            WH.Tooltips.showAtCursor(e, WH.TERMS.dragtochangeplayers_stc, "q2")
        };
        this.slider.onmousemove = WH.Tooltips.cursorUpdate;
        this.slider.onmouseout = WH.Tooltips.hide;
        WH.Tooltips.attach(Slider.getInput(this.slider), WH.TERMS.clicktochangeplayers_stc, "q2")
    }
    WH.groupSizeScalingSliderMove.call(this, null, null, {value: r})
};
WH.classSpecBtnOnChange = function (e, t, a, i, n) {
    const s = WH.LocalStorage;
    e = parseInt(e);
    t = t ? parseInt(t) : null;
    a = a ? parseInt(a) : null;
    WH.ee(this._classSpecBtn);
    this._classSpecBtn.selectedSpec = t;
    let r = Menu.findItem(this._classSpecBtn.menu, [e, t]);
    if (r && r[Menu.ITEM_OPTIONS] && r[Menu.ITEM_OPTIONS].tinyIcon) {
        let e = r[Menu.ITEM_OPTIONS].tinyIcon;
        let t = WH.WHIcon.create(e, WH.WHIcon.SMALL, "javascript:");
        t.style.display = "inline-block";
        t.style.verticalAlign = "middle";
        WH.ae(this._classSpecBtn, t)
    }
    let o = WH.Wow.PlayerClass.Specialization.getName(t);
    WH.ae(this._classSpecBtn, WH.ce("span", undefined, WH.ct(" " + (!WH.isRetailTree() || !o ? WH.Wow.PlayerClass.getName(e) : WH.Strings.sprintf(WH.TERMS.specclass_format, o, WH.Wow.PlayerClass.getName(e))))));
    if (!n) {
        s.set(s.KEY_WOW_DATABASE_SPEC_FILTER, {classId: e, specId: t, heroTreeId: a})
    }
    let l = i ? i : this.innerHTML;
    l = l.replace(/<!--scstart(\d+):(\d+)--><span class="q(\d+)">(<!--asc\d+-->)?(.*?)<\/span><!--scend-->/i, (function (t, a, i, n, s, r) {
        n = 1;
        let o = a == 2 && (!g_classes_allowed_weapon[e] || WH.inArray(g_classes_allowed_weapon[e], i) == -1);
        let l = a == 4 && (!g_classes_allowed_armor[e] || WH.inArray(g_classes_allowed_armor[e], i) == -1);
        if (o || l) {
            n = 10
        }
        return "\x3c!--scstart" + a + ":" + i + '--\x3e<span class="q' + n + '">' + (s ? s : "") + r + "</span>\x3c!--scend--\x3e"
    }));
    if (WH.isRetailTree()) {
        l = l.replace(/<span[^>]*?><!--stat(\d+)-->([-+][\d\.,]+(?:-[\d\.,]+)?)(\D*?)<\/span>/gi, (function (a, i, n, s) {
            let r = WH.ce("div", {innerHTML: a});
            let o = WH.qs("span", r);
            o.classList.remove("q0", "q2");
            i = parseInt(i);
            if (i === 50) {
                o.classList.add("q2")
            }
            if (g_grayedOutStats[i] && g_grayedOutStats[i].indexOf(t) != -1) {
                o.classList.remove("q2");
                o.classList.add("q0")
            }
            let l = t ? WH.getStatForSpec(i, t) : WH.getStatForClass(i, e);
            if (l !== i && WH.statToJson[l]) {
                let e = WH.Wow.Item.Stat.jsonToName(WH.statToJson[l]);
                if (e) {
                    s = " " + e
                }
            }
            o.innerHTML = "\x3c!--stat" + i + "--\x3e";
            WH.ae(o, WH.ct(n + s));
            return o.outerHTML
        }));
        l = l.replace(/(<!--traitspecstart:(\d+)(?::(\d+))?-->)[\w\W]*?(<!--traitspecend-->)/g, (function (e, a, i, n, s) {
            var r = "";
            if (WH.isSet("g_pageInfo") && g_pageInfo.hasOwnProperty("typeId") && g_pageInfo.type == 3 && g_items.hasOwnProperty(g_pageInfo.typeId) && g_items[g_pageInfo.typeId].hasOwnProperty("affectsArtifactPowerTypesData") && g_items[g_pageInfo.typeId].affectsArtifactPowerTypesData.hasOwnProperty(i) && g_items[g_pageInfo.typeId].affectsArtifactPowerTypesData[i].hasOwnProperty(t)) {
                r = g_items[g_pageInfo.typeId].affectsArtifactPowerTypesData[i][t]
            } else if (n) {
                r = '<span style="color: #00FF00">' + WH.term("relicrank" + (n != 1 ? "s" : "") + "increase_format", n) + ": </span>" + WH.TERMS.relic_minortrait
            }
            return a + r + s
        }))
    }
    if (t) {
        l = WH.Tooltips.parseItemEffectTooltipForSpec(l, t);
        let a = WH.isSet("g_pageInfo") ? g_pageInfo.typeId : null;
        let i = WH.Wow.PlayerClass.Specialization.getSpecSpellsBySpec(t);
        let n = this.modified?.[1] ?? null;
        let s = this.modified?.[2] ?? [];
        if (a && i && n) {
            WH.Wow.PlayerClass.Specialization.getByClass(e).forEach((e => {
                let t = WH.Wow.PlayerClass.Specialization.getSpecSpellsBySpec(e);
                let n = t.every((e => i.includes(e)));
                t.forEach((function (e) {
                    WH.checkSpellModifierCheckbox(a, e, n);
                    if (n) {
                        s.push(e)
                    } else {
                        let t = s.indexOf(e);
                        if (t !== -1) {
                            s.splice(t, 1)
                        }
                    }
                }))
            }));
            l = WH.setTooltipSpells(l, s, n);
            WH.Url.replacePageQuery((function (e) {
                delete e.spellModifier;
                e.spellModifier = s.join(":")
            }))
        }
    }
    l = WH.Tooltips.parseItemEffectTooltipForHero(l, a);
    WH.Url.replacePageQuery((function (i) {
        if (e) {
            i["class"] = e
        } else {
            delete i["class"]
        }
        if (WH.isRetailTree() && t) {
            i.spec = t
        } else {
            delete i.spec
        }
        if (WH.Wow.Expansion.available(WH.Wow.Expansion.TWW) && a) {
            i.hero = a
        } else {
            delete i.hero
        }
    }));
    if (!i) {
        this.innerHTML = WH.Tooltips.evalFormulas(l)
    }
    return l
};
WH.classSpecBtnBuildMenu = function (e) {
    const t = WH.Wow.PlayerClass;
    const a = WH.Wow.PlayerClass.Specialization;
    const i = WH.isRetailTree();
    let n = [Menu.createHeading({label: i ? WH.TERMS.chooseaspec_stc : WH.TERMS.chooseAClass_stc})];
    if (e) {
        e = e.map((e => parseInt(`${e}`)))
    }
    t.getAll().forEach((s => {
        const r = Menu.createItem({
            crumb: `${s}`,
            label: t.getName(s),
            options: {className: `c{$classId}`, tinyIcon: t.getIconName(s)}
        });
        if (!i) {
            Menu.setItemUrl(r, WH.classSpecBtnOnChange.bind(this, s, 0, 0, false))
        } else {
            const t = [];
            a.getByClass(s).forEach((i => {
                const n = !e || e.includes(i);
                const r = Menu.createItem({
                    crumb: `${i}`,
                    label: a.getName(i),
                    url: n ? WH.classSpecBtnOnChange.bind(this, s, i, 0, false) : "javascript:",
                    options: n ? {tinyIcon: a.getIconName(i)} : {className: "q0"}
                });
                t.push(r);
                if (WH.Wow.Expansion.available(WH.Wow.Expansion.TWW)) {
                    let e = [];
                    let t = a.getHeroTreeNames(i);
                    for (let a in t) {
                        e.push(Menu.createItem({
                            crumb: a,
                            label: t[a],
                            url: WH.classSpecBtnOnChange.bind(this, s, i, a, false)
                        }))
                    }
                    Menu.setSubmenu(r, e)
                }
            }));
            Menu.setSubmenu(r, t)
        }
        n.push(r)
    }));
    this._classSpecBtn.menu = n
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
    var s = 71;
    var r = 72;
    var o = 73;
    var l = 74;
    var c;
    var d;
    var f = g_specPrimaryStatOrders[t];
    var u = g_specPrimaryStatOrders[t].length;
    if (e === s) {
        d = 0;
        if (!u) {
            return n
        }
        while (1) {
            c = f[d];
            if (c >= a && c <= n) {
                break
            }
            d++;
            if (d >= u) {
                return n
            }
        }
    } else {
        if (e !== r) {
            if (e !== o) {
                if (e !== l) {
                    return e
                }
                d = 0;
                if (u) {
                    while (1) {
                        c = f[d];
                        if (c >= i && c <= n) {
                            break
                        }
                        d++;
                        if (d >= u) {
                            return n
                        }
                    }
                    return c
                }
                return n
            }
            d = 0;
            if (u) {
                while (1) {
                    c = f[d];
                    if (f[d] === a) {
                        break
                    }
                    if (f[d] === n) {
                        break
                    }
                    d++;
                    if (d >= u) {
                        return n
                    }
                }
                return c
            }
            return n
        }
        d = 0;
        if (!u) {
            return a
        }
        while (1) {
            c = f[d];
            if (c >= a && c <= i) {
                break
            }
            d++;
            if (d >= u) {
                return a
            }
        }
    }
    return c
};
WH.getItemBonusChanceType = function (e) {
    var t = 0;
    if (e > 0 && WH.isSet("g_itembonuses") && g_itembonuses && g_itembonuses[e]) {
        var a = g_itembonuses[e];
        for (var i = 0; i < a.length; ++i) {
            var n = a[i];
            var s = 0;
            switch (n[0]) {
                case 1:
                case 3:
                case 4:
                case 5:
                case 11:
                    s = 1;
                    break;
                case 2:
                    s = 2;
                    break;
                case 6:
                    s = 4;
                    break;
                default:
                    break
            }
            if (s && (!t || s < t)) {
                t = s
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
WH.bonusesGetItem = function () {
    var e = WH.getDataSource();
    var t = this._bonusesBtn.ttId;
    return e[t]
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
        var n = "";
        var s = this._selectedLevel ? this._selectedLevel : WH.Wow.getMaxPlayerLevel();
        var r = this._knowledgeLevel ? this._knowledgeLevel : 0;
        var o = this._classSpecBtn && this._classSpecBtn.selectedSpec ? this._classSpecBtn.selectedSpec : "";
        var l = 0;
        var c = "";
        if (n) {
            l |= 4;
            c = (c ? ":" : "") + n
        } else if (i.length && g_itembonuses) {
            e:for (var d = 0, f; f = i[d]; d++) {
                if (!g_itembonuses[f]) {
                    continue
                }
                for (var u = 0, p; p = g_itembonuses[f][u]; u++) {
                    if (p[0] == 11 || p[0] == 13) {
                        l |= 512;
                        c = (c ? ":" : "") + s;
                        break e
                    }
                }
            }
        }
        if (r) {
            l |= 8388608;
            c = (c ? ":" : "") + (r + 1)
        }
        var h = "" + (l ? l : "") + "::" + (i.length ? i.length + ":" : "") + a + ":" + c;
        var g = WH.ge("open-links-button");
        if (g) {
            var m = {
                type: 3,
                typeId: t,
                linkColor: "ff" + WH.Wow.Item.getQualityColor(e[t].quality, true).replace(/^#/, ""),
                linkId: "item:" + t + "::::::::" + s + ":" + o + ":" + h,
                linkName: e[t]["name_" + Locale.getName()],
                bonuses: i,
                slot: e[t].slot
            };
            if (s != WH.Wow.getMaxPlayerLevel()) {
                m.lvl = s
            }
            if (o) {
                m.spec = o
            }
            if (sliderControl = WH.ge("sl" + t)) {
                m.dropLevel = $(sliderControl).find("input").val()
            }
            g.onclick = WH.Links.show.bind(WH.Links, g, m)
        }
    }
};
WH.upgradeItemTooltip = function (e, t, a) {
    let i = WH.getDataSource();
    let n = g_pageInfo["typeId"];
    if (i[n]) {
        if (a) {
            s(this);
            return
        }
        let r = $("#" + e.id + " > input");
        let o = null;
        if (typeof t !== "number") {
            r.each((function (e, a) {
                if (a.id.indexOf(t) != -1) {
                    o = a;
                    return false
                }
            }))
        } else {
            o = r.get(t - 1)
        }
        let l = o.checked;
        r.each((function (e, t) {
            t.checked = false
        }));
        o.checked = l;
        if (!l) {
            t = null
        }
        s(this);

        function s(e) {
            this._selectedUpgrade = t;
            WH.updateItemStringLink.call(e);
            if (i[n]["tooltip_" + Locale.getName()]) {
                let a = e._bonusesBtn && e._bonusesBtn.selectedBonus ? e._bonusesBtn.selectedBonus : null;
                let i = WH.enhanceTooltip.call(e, n, true, true, false, null, e._spellModifiers, WH.ge("ks" + n), t, null, true, null, null, a);
                WH.updateTooltip.call(e, i)
            }
        }
    }
};
WH.updateTooltip = function (e) {
    e = WH.updateTooltipSingular(e);
    if (this.classList.contains("partial-sub-tooltip")) {
        this.innerHTML = WH.Tooltips.evalFormulas(e);
        return
    }
    this.innerHTML = "<table><tr><td>" + WH.Tooltips.evalFormulas(e) + '</td><th style="background-position: top right"></th></tr><tr><th style="background-position: bottom left"></th><th style="background-position: bottom right"></th></tr></table>';
    WH.Tooltips.finalizeSizeAndReveal(this)
};
WH.staticTooltipLevelClick = function (e, t, a, i) {
    while (e.className.indexOf("tooltip") == -1) {
        e = e.parentNode
    }
    var n = e.innerHTML;
    var s = n.match(/<!--i\?(\d+):(\d+):(\d+):(\d+)/);
    if (!s) {
        s = n.match(/<!--\?(\d+):(\d+):(\d+):(\d+)/)
    }
    if (!s && !WH.isRetailTree()) {
        s = n.match(/<!--ppl(\d+):(\d+):(\d+):(\d+):(\d+)/);
        if (s) {
            s = [null, s[1], s[2], WH.Wow.getMaxPlayerLevel(), 0]
        }
    }
    if (!s) {
        s = n.match(/<!--pl(\d+):(\d+):(\d+)-->\s?(\d+)/);
        if (s) {
            if (WH.isSet("g_pageInfo") && g_pageInfo.type == WH.Types.ITEM) {
                s[1] = g_pageInfo.typeId
            }
            s = [null, s[1], s[2], s[3], s[4]]
        }
    }
    if (!s) {
        return
    }
    var r = parseInt(s[1]), o = parseInt(s[2]), l = parseInt(s[3]), c = parseInt(s[4]);
    if (o >= l) {
        return
    }
    if (isNaN(t)) {
        t = prompt(WH.sprintf(WH.TERMS.ratinglevel_format, o, l), c)
    }
    t = parseInt(t);
    if (isNaN(t)) {
        return
    }
    if (t == c || t < o || t > l) {
        return
    }
    e._selectedLevel = t;
    var d = WH.getDataSource();
    s = WH.setTooltipLevel.bind(e, d[r][(i ? "buff_" : "tooltip_") + Locale.getName()], t, i)();
    var f = e._bonusesBtn && e._bonusesBtn.selectedBonus ? e._bonusesBtn.selectedBonus : null;
    var u = e._selectedUpgrade ? e._selectedUpgrade : 0;
    s = WH.enhanceTooltip.call(e, s, true, null, null, null, null, null, u, null, null, null, null, f);
    WH.updateTooltip.call(e, s);
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
        WH.Tooltips.hide();
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
    var s = e._bonusesBtn && e._bonusesBtn.selectedBonus ? e._bonusesBtn.selectedBonus : null;
    var r = e._selectedUpgrade ? e._selectedUpgrade : 0;
    i = WH.enhanceTooltip.call(e, i, true, null, null, null, null, null, r, null, null, null, null, s);
    WH.updateTooltip.call(e, i)
};
WH.tooltipSliderMove = function (e, t, a) {
    WH.staticTooltipLevelClick(this, a.value, 1);
    if (this.bufftip) {
        WH.staticTooltipLevelClick(this.bufftip, a.value, 1, 1)
    }
    WH.Tooltips.hide()
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
    e.innerHTML = WH.Tooltips.evalFormulas(e.innerHTML.replace(/<!--rppm-->(\[?)(\d+(?:\.\d+)?)([^<]*)<!--rppm-->/, (function (a, i, n, s) {
        return "\x3c!--rppm--\x3e" + i + (e._rppmModBase * (1 + parseFloat(e._rppmSpecModList[t].modifiervalue))).toFixed(2) + s + "\x3c!--rppm--\x3e"
    })))
};
WH.updateTooltipCooldownAndCharges = function (e, t) {
    if (e.search("\x3c!--cooldown:") === -1 && e.search("\x3c!--charges:") === -1) {
        return e
    }
    let a = "";
    chargesText = "";
    let i;
    if (i = e.match(/<!--baseCooldown:([^>]+)-->/)) {
        a = i[1]
    }
    if (i = e.match(/<!--baseCharges:([^>]+)-->/)) {
        chargesText = i[1]
    }
    t.forEach((t => {
        if ((i = e.match(new RegExp("\x3c!--cooldown:" + t + ":([^>]+)--\x3e"))) !== null) {
            a = i[1]
        }
        if ((i = e.match(new RegExp("\x3c!--charges:" + t + ":([^>]+)--\x3e"))) !== null) {
            chargesText = i[1]
        }
    }));
    e = e.replace(/<!--cooldownText-->(.*?)<!--cooldownText-->/, `\x3c!--cooldownText--\x3e${a}\x3c!--cooldownText--\x3e`);
    e = e.replace(/<!--chargesText-->(.*?)<!--chargesText-->/, `\x3c!--chargesText--\x3e${chargesText}\x3c!--chargesText--\x3e`);
    return e
};
WH.validateBpet = function (e, t) {
    var a = 1, i = 25, n = 25, s = 0, r = 4, o = 3, l = (1 << 10) - 1, c = 3, d = $.extend({}, t);
    if (e.minlevel) {
        a = e.minlevel
    }
    if (e.maxlevel) {
        i = e.maxlevel
    }
    if (e.companion) {
        i = a
    }
    if (!d.level) {
        d.level = n
    }
    d.level = Math.min(Math.max(d.level, a), i);
    if (e.minquality) {
        s = e.minquality;
        if (e.untameable) {
            r = s
        }
    }
    if (e.maxquality) {
        r = e.maxquality
    }
    if (d.quality == null) {
        d.quality = o
    }
    d.quality = Math.min(Math.max(d.quality, s), r);
    if (e.companion) {
        delete d.quality
    }
    if (e.breeds > 0) {
        l = e.breeds & l
    }
    if (!(l & 1 << c - 3)) {
        c = Math.floor(3 + Math.log(l) / Math.LN2)
    }
    if (d.breed && d.breed >= 13) {
        d.breed -= 10
    }
    if (!d.breed || !(l & 1 << d.breed - 3)) {
        d.breed = c
    }
    return d
};
WH.calcBattlePetStats = function (e, t, a, i, n) {
    if (!WH.battlePetBreedStats[t]) {
        t = 3
    }
    var s = e.health;
    if (isNaN(s)) {
        s = 0
    }
    var r = e.power;
    if (isNaN(r)) {
        r = 0
    }
    var o = e.speed;
    if (isNaN(o)) {
        o = 0
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
    s = (s + l[0]) * 5 * i * c + 100;
    r = (r + l[1]) * i * c;
    o = (o + l[2]) * i * c;
    if (n) {
        s = s * 5 / 6;
        r = r * 4 / 5
    }
    return {health: Math.round(s), power: Math.round(r), speed: Math.round(o)}
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
WH.createButton = function (e, t, a) {
    var i = "btn btn-site";
    var n = "";
    var s = "";
    var r = "";
    var o = "";
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
        s = ' id="' + a["id"] + '"'
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
        r = ' style="' + c.join(";") + '"'
    }
    var d = '<a href="' + t + '"' + n + s + i + r + ">" + (e || "") + "</a>";
    var f = WH.ce("div");
    f.innerHTML = d;
    var u = f.childNodes[0];
    if (typeof a["click"] == "function" && !a["disabled"]) {
        u.onclick = a["click"]
    }
    if (typeof a["tooltip"] != "undefined") {
        if (a["tooltip"] !== false) {
            u.setAttribute("data-whattach", "true")
        }
        if (a["tooltip"] === false) {
            u.rel = "np"
        } else if (typeof a["tooltip"] == "string") {
            WH.Tooltips.attach(u, a["tooltip"])
        } else if (typeof a["tooltip"] == "object" && a["tooltip"]["text"]) {
            WH.Tooltips.attach(u, a["tooltip"]["text"], a["tooltip"]["class"])
        }
    }
    return u
};
WH.D4 = new function () {
    this.getImageUrlFromHash = (e, t) => {
        if (typeof e === "string") {
            e = parseInt(e)
        }
        t ??= WH.getDataEnv();
        if (WH.Game.getByEnv(t) !== WH.Game.D4) {
            t = WH.Game.getEnv(WH.Game.D4)
        }
        const a = WH.isDataEnvRestricted(t);
        let i = `/d4/${WH.getDataEnvKey(t)}/texture/`;
        let n = WH.WebP.getImageExtension();
        let s = e ? `${i}hash/${e & 255}/${e}${n}` : `${i}hash/74/582516298${n}`;
        return a ? WH.Url.generatePrivate(s) : `${WH.STATIC_URL}${s}`
    };
    this.getStringHash = (e, t = true) => {
        let a = Array.from(t ? e.toLowerCase() : e).map((e => e.charCodeAt(0))).reduce(((e, t) => (e << 5) + e + t & 4294967295), 0);
        return a < 0 ? a + 4294967296 : a
    }
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
    this.D4 = 4;
    this.DEFAULT = this.WOW;
    const t = [WH.dataTree.RETAIL, WH.dataTree.CLASSIC, WH.dataTree.CATA, WH.dataTree.MISTS, WH.dataTree.D4];
    const a = {
        [this.D2]: {
            dataTrees: [WH.dataTree.D2],
            defaultTree: WH.dataTree.D2,
            term: "diablo2Resurrected",
            termAbbrev: "diablo2_abbrev",
            termSeo: "diablo2Resurrected"
        },
        [this.DI]: {
            dataTrees: [WH.dataTree.DI],
            defaultTree: WH.dataTree.DI,
            term: "diabloImmortal",
            termAbbrev: "diabloImmortal_abbrev",
            termSeo: "diabloImmortal"
        },
        [this.D4]: {
            dataTrees: [WH.dataTree.D4],
            defaultTree: WH.dataTree.D4,
            term: "diablo4",
            termAbbrev: "diablo4_abbrev",
            termSeo: "diablo4_seo"
        },
        [this.WOW]: {
            dataTrees: [WH.dataTree.RETAIL, WH.dataTree.CLASSIC, WH.dataTree.TBC, WH.dataTree.WRATH, WH.dataTree.CATA, WH.dataTree.MISTS],
            defaultTree: WH.dataTree.RETAIL,
            term: "worldofwarcraft",
            termAbbrev: "worldOfWarcraft_abbrev",
            termSeo: "worldofwarcraft"
        }
    };
    const i = {[this.D2]: "d2", [this.DI]: "di", [this.WOW]: "wow", [this.D4]: "d4"};
    const n = {
        [this.D2]: WH.dataEnv.D2,
        [this.DI]: WH.dataEnv.DI,
        [this.WOW]: WH.dataEnv.MAIN,
        [this.D4]: WH.dataEnv.D4
    };
    let s = {
        [WH.dataEnv.BETA]: "beta",
        [WH.dataEnv.D2]: "diablo-2",
        [WH.dataEnv.DI]: "diablo-immortal",
        [WH.dataEnv.WRATH]: "wotlk",
        [WH.dataEnv.CLASSIC]: "classic",
        [WH.dataEnv.PTR]: "ptr",
        [WH.dataEnv.PTR2]: "ptr-2",
        [WH.dataEnv.TBC]: "tbc",
        [WH.dataEnv.D4]: "diablo-4",
        [WH.dataEnv.CATA]: "cata",
        [WH.dataEnv.D4PTR]: "diablo-4-ptr",
        [WH.dataEnv.D4BETA]: "diablo-4-beta",
        [WH.dataEnv.CLASSICPTR]: "classic-ptr",
        [WH.dataEnv.MISTS]: "mop-classic"
    };
    const r = {
        [WH.dataTree.RETAIL]: this.WOW,
        [WH.dataTree.CLASSIC]: this.WOW,
        [WH.dataTree.TBC]: this.WOW,
        [WH.dataTree.D2]: this.D2,
        [WH.dataTree.DI]: this.DI,
        [WH.dataTree.WRATH]: this.WOW,
        [WH.dataTree.D4]: this.D4,
        [WH.dataTree.CATA]: this.WOW,
        [WH.dataTree.MISTS]: this.WOW
    };
    const o = [this.D2, this.DI];
    const l = {
        [this.WOW]: "wow_token01",
        [this.D2]: "inv_diablostone",
        [this.DI]: "inv_cape_special_treasuregoblin_d_01",
        [this.D4]: "diabloanniversary_achievement"
    };
    this.get = () => e.getByTree(WH.getDataTree());
    this.getAll = () => Object.keys(i).map(Number);
    this.getAllSelectors = () => Object.values(s);
    this.getAllSorted = () => {
        let t = e.getAll();
        t.sort(((t, a) => {
            if (t === e.WOW) {
                return -1
            } else if (a === e.WOW) {
                return 1
            }
            return a - t
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
    this.getByTree = e => r[e];
    this.getDataEnvBySelector = e => WH.findKey(s, e, true);
    this.getDataEnvs = e => {
        let t = [];
        let i = a[e].dataTrees;
        Object.entries(WH.dataEnvToTree).forEach((([e, a]) => {
            if (i.includes(a)) {
                t.push(parseInt(e))
            }
        }));
        return t
    };
    this.getDataTrees = e => {
        let t = a[e]?.dataTrees || [];
        if (!WH.REMOTE) {
            const e = WH.PageMeta.availableDataEnvs.map((e => WH.getDataTree(e)));
            t = t.filter((t => e.includes(t)))
        }
        return t.length ? t : undefined
    };
    this.getDefaultEnv = e => n[e];
    this.getEnv = t => {
        t = t || e.DEFAULT;
        return t === WH.Game.get() ? WH.getDataEnv() : WH.Game.getDefaultEnv(t)
    };
    this.getEnvByEnv = (t, a) => e.getByEnv(a) === t ? a : e.getEnv(t);
    this.getFeaturedTrees = () => t;
    this.getName = e => a[e] ? WH.TERMS[a[e].term] : undefined;
    this.getAbbrev = e => a[e] ? WH.TERMS[a[e].termAbbrev] : undefined;
    this.getKey = e => i[e];
    this.getRoot = t => WH.getRootEnv(e.getEnv(t));
    this.getSelectorByDataEnv = e => s[e];
    this.getSeoName = e => a[e] ? WH.TERMS[a[e].termSeo] : undefined;
    this.getWowIconName = e => l[e];
    this.hasAccess = t => (e.getDataTrees(t) || []).length > 0;
    this.isUnderstated = e => o.includes(e);
    this.supportsRandomPages = t => [e.WOW, e.DI].includes(t)
};
WH.Fonts = new function () {
    const e = WH.Game;
    const t = "https://use.typekit.net/kxf7wgk.css";
    const a = `${WH.STATIC_URL}/css/fonts/d4.css`;
    const i = "https://use.typekit.net/qwt0uqi.css";
    const n = {[e.D2]: [i], [e.D4]: [t, a], [e.DI]: [i]};
    const s = {requestedUrls: new Set};
    this.load = e => {
        if (n[e]) {
            n[e].forEach((e => r(e)))
        }
    };

    function r(e) {
        if (!e || s.requestedUrls.has(e)) {
            return
        }
        s.requestedUrls.add(e);
        if (e === t) {
            s.requestedUrls.add(i)
        }
        if (document.head.querySelector('link[rel="stylesheet"][href="' + e + '"]')) {
            return
        }
        WH.ae(document.head, WH.ce("link", {href: e, rel: "stylesheet", type: "text/css"}))
    }
};
WH.WHIcon = new function () {
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
    this.create = function (n, s, r, o) {
        o = o || {};
        if (n) {
            n = `${n}`
        }
        let l = o.dataEnv || o.type && i.getPreferredDataEnv(o.type) || t.getEnv(o.game);
        let c = t.getByEnv(l);
        if (s === e.TINY) {
            return WH.ce("img", {className: "icontiny", src: e.getIconUrl(n, s, l)})
        }
        let d = WH.ce(o.span ? "span" : "div", {
            className: "icon" + s,
            dataset: {env: WH.getDataEnvKey(l), tree: WH.getDataTreeKey(WH.getDataTree(l)), game: t.getKey(c)}
        });
        WH.ae(d, WH.ce("ins"));
        if (o.border !== false) {
            WH.ae(d, WH.ce("del"))
        }
        let f = o.type && i.getStringId(o.type);
        if (f) {
            d.dataset.type = f
        }
        if (o.simple === true) {
            d.dataset.kind = "simple"
        } else if (o.kind) {
            d.dataset.kind = o.kind
        }
        if (o.color) {
            d.dataset.color = o.color
        }
        WH.cO(d.dataset, o.dataset);
        if (n) {
            if (n.includes("/")) {
                e.setImage(d, n, true, o.isMask)
            } else {
                e.setImage(d, e.getIconUrl(n, s, l), false, o.isMask)
            }
            if (!WH.isRemote() && o.lazyLoad !== false) {
                WH.DOM.lazyLoadBackground(d.firstChild)
            }
        }
        const u = o.ariaLabel || WH.TERMS?.icon || "Icon";
        if (r) {
            let e = WH.ce("a", {href: r, tabIndex: -1});
            if (r.indexOf("wowhead.com") === -1 && /^https?:/.test(r)) {
                e.target = "_blank"
            }
            WH.ae(d, e)
        } else if (n) {
            let e = d.firstChild.style.backgroundImage.indexOf("/avatars/") !== -1;
            if (!e) {
                if (r !== null) {
                    WH.ae(d, WH.ce("a", {ariaLabel: u, href: "javascript:"}));
                    d.onclick = WHIcon.onClick
                }
            }
        }
        if (o.rel && typeof a != "undefined") {
            a.rel = o.rel
        }
        e.setText(d, o.number, o.quantity);
        return d
    };
    this.createByEntity = function (t, a, n, s) {
        s = s || {};
        let r = s.size;
        delete s.size;
        s.dataEnv = s.dataEnv || t.dataEnv || (i.getRequiredTrees(a) || [])[0];
        s.type = a;
        s.ariaLabel ??= t.name;
        const o = WH.DI.GeneralItem;
        switch (a) {
            case i.DI_EQUIP_ITEM:
                s.dataset = s.dataset || {};
                s.dataset.gridType = s.gridType || t.gridType || ([6, 11, 4, 3, 7, 9, 8].includes(t.inventoryPosition) ? o.GRID_TYPE_1x1 : o.GRID_TYPE_2x1);
                delete s.gridType;
                if (t.inventoryColor != null) {
                    s.dataset.inventoryColor = t.inventoryColor
                }
                break;
            case i.DI_MISC_ITEM:
                s.dataset = s.dataset || {};
                s.dataset.gridType = s.gridType || t.gridType || o.GRID_TYPE_2x1;
                delete s.gridType;
                if (t.inventoryColor != null) {
                    s.dataset.inventoryColor = t.inventoryColor
                }
                break;
            case i.DI_PARAGON_SKILL:
                s.dataset = s.dataset || {};
                s.dataset.specSkill = JSON.stringify(!!(s.isSpecSkill || t.isSpecSkill));
                break;
            case i.D4_PARAGON_NODE:
                s.dataset = s.dataset || {};
                s.isMask = !!t.iconImageHash;
                s.dataset.nodeType = `${t.type}`;
                s.dataset.isGate = `${!!t.isGate}`;
                s.dataset.hasSocket = `${!!t.hasSocket}`;
            case i.D4_ITEM:
            case i.D4_PARAGON_GLYPH:
            case i.D4_SENESCHAL_STONE:
            case i.D4_WITCH_POWER:
            case i.D4_BOSS_POWER:
            case i.D4_HORADRIC_COMPONENT:
            case i.D4_CHAOS_PERK:
            case i.D4_DIVINE_GIFT:
                if (t.quality != null) {
                    s.dataset = s.dataset || {};
                    s.dataset.quality = t.quality
                }
                break;
            case i.D4_SKILL:
                if ((s.active ?? t.active) === false) {
                    s = {...s, dataset: {skillType: "passive"}};
                    delete s.active
                }
        }
        return e.create(t.icon || t.iconImageHash, r || e.MEDIUM, n, s)
    };
    this.getIconUrl = function (a, i, s = t.getEnv(t.DEFAULT)) {
        if (n.indexOf(i) === -1) {
            i = e.MEDIUM
        }
        let r = t.getByEnv(s);
        let o = WH.isDataEnvRestricted(s);
        switch (r) {
            case t.DI:
                return new WH.DI.UiImage(a).getUrl(o);
            case t.D4:
                return WH.D4.getImageUrlFromHash(a, s);
            case t.WOW:
            default:
                let n = t.getKey(r);
                if (!n) {
                    WH.warn('Invalid game provided for "' + a + '" icon: ' + r);
                    n = t.getKey(t.WOW);
                    a = e.UNKNOWN
                }
                let l = i === e.TINY ? ".gif" : ".jpg";
                let c = `/images/${n}/icons/${i}/${a.toLowerCase()}${l}`;
                return o ? WH.Url.generatePrivate(c) : `${WH.STATIC_URL}${c}`
        }
    };
    this.getLink = function (e) {
        return e.querySelector("a")
    };
    this.getTextureUrl = e => WH.qs("ins", e).style.backgroundImage.replace(/^url\(['"]?/, "").replace(/['"]?\)$/, "");
    this.isValidSize = function (e) {
        return n.indexOf(e) !== -1
    };
    this.setImage = function (e, t, a, i) {
        let n = e.firstChild;
        n.style.backgroundPosition = "";
        if (i) {
            n.style["-webkit-mask-image"] = t ? 'url("' + t + '")' : ""
        } else {
            n.style.backgroundImage = t ? 'url("' + t + '")' : ""
        }
        if (a === true) {
            n.style.backgroundSize = "contain"
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
var WHIcon = {
    questionMarkIcon: WH.WHIcon.UNKNOWN,
    sizes: ["small", "medium", "large", "blizzard"],
    sizes2: [18, 36, 56, 64],
    sizeIds: {small: 0, medium: 1, large: 2, blizzard: 3},
    premiumOffsets: [[-56, -36], [-56, 0], [0, 0], [0, 0]],
    premiumBorderClasses: ["-premium", "-gold", "", "-premiumred", "-red", ""],
    STANDARD_BORDER: 2,
    privilegeBorderClasses: {uncommon: "-q2", rare: "-q3", epic: "-q4", legendary: "-q5"},
    idLookupCache: {},
    create: function (e, t, a, i, n, s, r, o, l, c, d) {
        d = d || {};
        if (t == null) {
            t = WHIcon.sizeIds.medium
        }
        return WH.WHIcon.create(e, WH.WHIcon.LEGACY_IDS[t], i === false ? null : i, {
            border: !r,
            color: d.color,
            game: d.game,
            lazyLoad: d.lazyLoad,
            number: n,
            quantity: s,
            rel: o,
            simple: c,
            span: l,
            type: d.type
        })
    },
    createUser: function (e, t, a, i, n, s, r) {
        if (e == 2) t = WH.staticUrl + "/uploads/avatars/" + t + ".jpg";
        var o = WHIcon.create(t, a, null, i, null, null, s);
        if (n != WHIcon.STANDARD_BORDER) {
            if (WHIcon.premiumBorderClasses[n]) {
                o.className += " " + o.className + WHIcon.premiumBorderClasses[n]
            }
        } else if (r && WHIcon.privilegeBorderClasses.hasOwnProperty(r)) o.className += " " + o.className + WHIcon.privilegeBorderClasses[r];
        if (e == 2) WHIcon.moveTexture(o, a, WHIcon.premiumOffsets[a][0], WHIcon.premiumOffsets[a][1], true);
        o.classList.add("icon" + WHIcon.sizes[a] + "-sprite");
        return o
    },
    getIdFromName: function (e, t) {
        if (WHIcon.idLookupCache.hasOwnProperty(e)) {
            window.requestAnimationFrame((function () {
                t(WHIcon.idLookupCache[e] || undefined)
            }));
            return
        }
        $.ajax({
            url: WH.Url.generatePath("/icon/get-id-from-name"),
            data: {name: e},
            dataType: "json",
            success: function (a) {
                WHIcon.idLookupCache[e] = a;
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
        WHIcon.getLink(e).href = t
    },
    setTexture: function (e, t, a) {
        var i = e.firstChild.style;
        i.backgroundSize = "";
        i.backgroundPosition = "";
        if (!a) {
            i.backgroundImage = null;
            return
        }
        if (a.indexOf("/") !== -1) {
            i.backgroundImage = "url(" + a + ")";
            i.backgroundSize = "contain"
        } else {
            let e = WHIcon.sizes[t];
            if (e === "blizzard") {
                e = "large"
            }
            i.backgroundImage = `url("${WH.WHIcon.getIconUrl(a, e)}")`
        }
    },
    moveTexture: function (e, t, a, i, n) {
        var s = e.firstChild.style;
        s.backgroundSize = "";
        if (a || i) {
            if (n) s.backgroundPosition = a + "px " + i + "px"; else s.backgroundPosition = -a * WHIcon.sizes2[t] + "px " + -i * WHIcon.sizes2[t] + "px"
        } else if (s.backgroundPosition) s.backgroundPosition = ""
    },
    getLink: function (e) {
        return WH.gE(e, "a")[0]
    },
    showIconInfo: function (e) {
        const t = e.dataset.env ? WH.getDataEnvFromKey(e.dataset.env) : null;
        if (e.firstChild && t) {
            let a = e.firstChild.style;
            if (a.backgroundImage && (!WH.STATIC_URL || a.backgroundImage.indexOf(WH.STATIC_URL) >= 4)) {
                let e = a.backgroundImage.match(/images\/([^/]+)\/icons\/[^/]+\/([^/.]+).(?:jpg|gif)/);
                if (e) {
                    WHIcon.displayIcon(e[2], t)
                }
            }
        }
    },
    onClick: function () {
        WHIcon.showIconInfo(this)
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
                        var s = this.iconDiv = WH.ce("div");
                        s.update = function () {
                            setTimeout((function () {
                                WH.safeSelect(e)
                            }), 10);
                            WH.ee(s);
                            WH.ae(s, WH.WHIcon.create(e.value, WH.WHIcon.LARGE, undefined, {dataEnv: t}))
                        };
                        WH.ae(s, WH.WHIcon.create(a, WH.WHIcon.LARGE, undefined, {dataEnv: t}));
                        WH.ae(n, s);
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
                        let s = WH.Strings.escapeHtml(WH.Url.generatePath("/items?filter=142;0;" + this.data.icon));
                        let r = WH.Strings.escapeHtml(WH.Url.generatePath("/spells?filter=15;0;" + this.data.icon));
                        let o = WH.Strings.escapeHtml(WH.Url.generatePath("/achievements?filter=10;0;" + this.data.icon));
                        var l = WH.TERMS.seeallusingicon_format;
                        l = l.replace("$1", '<a href="' + s + '">' + WH.Types.getLowerPlural(WH.Types.ITEM) + "</a>");
                        l = l.replace("$2", '<a href="' + r + '">' + WH.Types.getLowerPlural(WH.Types.SPELL) + "</a>");
                        l = l.replace("$3", '<a href="' + o + '">' + WH.Types.getLowerPlural(WH.Types.ACHIEVEMENT) + "</a>");
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
                        t += `:${this.data.icon}`;
                        if (this.data.env && this.data.env !== WH.getDataEnv()) {
                            t += `:${WH.getDataEnvKey(this.data.env)}`
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
                    WHIcon.getIdFromName(e.icon.value, (function (e) {
                        t.value = e || ""
                    }))
                },
                onSubmit: function (e, t, a, i) {
                    if (a === "arrow") {
                        let e = WH.WHIcon.getIconUrl(t.icon, WH.WHIcon.LARGE, t.env);
                        let a = window.open(e, "_blank");
                        a.focus();
                        return false
                    }
                    return true
                }
            }
        }
        if (!WHIcon.icDialog) WHIcon.icDialog = new Dialog;
        WHIcon.icDialog.show("icondisplay", {data: {icon: e, env: t}})
    },
    checkPound: function () {
        if (location.hash && location.hash.indexOf("#icon") === 0) {
            let e = location.hash.split(":");
            let t;
            let a = WH.getDataEnv();
            if (e.length === 3) {
                t = e[1];
                a = WH.getDataEnvFromKey(e[2]) ?? a
            } else if (e.length === 2) {
                t = e[1]
            } else if (e.length === 1 && window.g_pageInfo) {
                t = WH.Gatherer.getIconName(g_pageInfo.type, g_pageInfo.typeId)
            }
            if (t) {
                WHIcon.displayIcon(t, a)
            }
        }
    }
};
if (!WH.REMOTE) {
    WH.onLoad(WHIcon.checkPound)
}
WH.Tooltips = WH.Tooltips || new function () {
    const e = this;
    const t = WH.Game;
    const a = WH.WHIcon;
    const i = WH.Types;
    const n = "nether";
    const s = 550;
    const r = {garrisonability: "mission-ability", itemset: "item-set", petability: "pet-ability"};
    const o = {1: 299204, 2: 299205, 3: 299206, 4: 299207};
    const l = 15;
    const c = 15;
    const d = [i.ACHIEVEMENT, i.AFFIX, i.AZERITE_ESSENCE, i.AZERITE_ESSENCE_POWER, i.ITEM, i.SPELL, i.DI_EQUIP_ITEM, i.DI_MISC_ITEM, i.DI_PARAGON_SKILL, i.DI_SKILL, i.D4_AFFIX, i.D4_ASPECT, i.D4_BOSS_POWER, i.D4_CHAOS_PERK, i.D4_DIVINE_GIFT, i.D4_HORADRIC_COMPONENT, i.D4_ITEM, i.D4_PARAGON_GLYPH, i.D4_PARAGON_NODE, i.D4_SENESCHAL_STONE, i.D4_SKILL, i.D4_VAMPIRIC_POWER, i.D4_WITCH_POWER];
    const f = {
        ["-1000"]: {name: "Mount", path: "mount", mobile: true, data: {}, maxId: 5e4},
        ["-1001"]: {name: "Recipe", path: "recipe", mobile: true, data: {}, maxId: 5e5},
        ["-1002"]: {name: "Battle Pet", path: "battle-pet", mobile: true, data: {}, maxId: 5e4},
        [i.NPC]: {name: "NPC", path: "npc", mobile: false, data: {}, maxId: 5e5},
        [i.OBJECT]: {name: "Object", path: "object", mobile: false, data: {}, maxId: 75e4},
        [i.ITEM]: {name: "Item", path: "item", mobile: true, data: {}, maxId: 5e5},
        [i.ITEM_SET]: {name: "Item Set", path: "item-set", mobile: true, data: {}, maxId: 1e4, minId: -5e3},
        [i.QUEST]: {name: "Quest", path: "quest", mobile: false, data: {}, maxId: 1e5},
        [i.SPELL]: {name: "Spell", path: "spell", mobile: true, data: {}},
        [i.ZONE]: {name: "Zone", path: "zone", mobile: false, data: {}, maxId: 5e4},
        [i.ACHIEVEMENT]: {name: "Achievement", path: "achievement", mobile: true, data: {}},
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
        [i.HOUSE_BUILD]: {name: "House Build", path: "housing-builds", mobile: true, data: {}},
        [i.DECOR_COLLECTION]: {name: "Decor Collection", path: "decor-collection", mobile: true, data: {}},
        [i.BATTLE_PET_ABILITY]: {name: "Battle Pet Ability", path: "pet-ability", mobile: true, data: {}, maxId: 1e4},
        [i.DI_EQUIP_ITEM]: {name: "Equipment Item", path: "equip-item", mobile: true, data: {}, embeddedIcons: true},
        [i.DI_MISC_ITEM]: {name: "Miscellaneous Item", path: "misc-item", mobile: true, data: {}, embeddedIcons: true},
        [i.DI_NPC]: {name: "NPC", path: "npc", mobile: true, data: {}},
        [i.DI_PARAGON_SKILL]: {name: "Paragon Skill", path: "paragon-skill", mobile: true, data: {}},
        [i.DI_QUEST]: {name: "Quest", path: "quest", mobile: true, data: {}},
        [i.DI_SET]: {name: "Set", path: "set", mobile: true, data: {}},
        [i.DI_SKILL]: {name: "Skill", path: "skill", mobile: true, data: {}},
        [i.DI_ZONE]: {name: "Zone", path: "zone", mobile: true, data: {}},
        [i.DECOR]: {name: "Decor", path: "decor", mobile: true, data: {}},
        [i.D4_AFFIX]: {name: "Affix", path: "affix", mobile: true, data: {}, embeddedIcons: true},
        [i.D4_ASPECT]: {name: "Aspect", path: "aspect", mobile: true, data: {}, embeddedIcons: true},
        [i.D4_BOSS_POWER]: {name: "Boss Power", path: "boss-power", mobile: true, data: {}, embeddedIcons: true},
        [i.D4_CHAOS_PERK]: {name: "Chaos Perk", path: "chaos-perk", mobile: true, data: {}, embeddedIcons: true},
        [i.D4_DIVINE_GIFT]: {name: "Divine Gift", path: "divine-gift", mobile: true, data: {}, embeddedIcons: true},
        [i.D4_HORADRIC_COMPONENT]: {
            name: "Horadric Component",
            path: "horadric-component",
            mobile: true,
            data: {},
            embeddedIcons: true
        },
        [i.D4_PARAGON_GLYPH]: {
            name: "Paragon Glyph",
            path: "paragon-glyph",
            mobile: true,
            data: {},
            embeddedIcons: true
        },
        [i.D4_PARAGON_NODE]: {name: "Paragon Node", path: "paragon-node", mobile: true, data: {}, embeddedIcons: true},
        [i.D4_SKILL]: {name: "Skill", path: "skill", mobile: true, data: {}, embeddedIcons: true},
        [i.PROFESSION_TRAIT]: {name: "Profession Trait", path: "profession-trait", mobile: true, data: {}, maxId: 5e5},
        [i.D4_ITEM]: {name: "Item", path: "item", mobile: true, data: {}, embeddedIcons: true},
        [i.TRADING_POST_ACTIVITY]: {
            name: "Trading Post Activity",
            path: "trading-post-activity",
            mobile: true,
            data: {},
            maxId: 1e4
        },
        [i.D4_SENESCHAL_STONE]: {
            name: "Seneschal Stone",
            path: "seneschal-stone",
            mobile: true,
            data: {},
            embeddedIcons: true
        },
        [i.D4_VAMPIRIC_POWER]: {
            name: "Vampiric Power",
            path: "vampiric-power",
            mobile: true,
            data: {},
            embeddedIcons: true
        },
        [i.D4_WITCH_POWER]: {name: "Witch Power", path: "witch-power", mobile: true, data: {}, embeddedIcons: true}
    };
    const u = (() => {
        let e = {
            [WH.dataTree.D2]: ["guide"],
            [WH.dataTree.D4]: ["affix", "aspect", "boss-power", "guide", "chaos-perk", "divine-gift", "horadric-component", "item", "paragon-glyph", "paragon-node", "seneschal-stone", "skill", "vampiric-power", "witch-power"],
            [WH.dataTree.DI]: ["equip-item", "guide", "misc-item", "npc", "paragon-skill", "set", "skill"],
            [WH.dataTree.RETAIL]: ["achievement", "adventure-combatant-ability", "affix", "azerite-essence", "azerite-essence-power", "battle-pet", "bfa-champion", "building", "champion", "currency", "decor", "event", "follower", "garrisonability", "guide", "housing-builds", "item", "item-set", "itemset", "mission", "mission-ability", "mount", "npc", "object", "order-advancement", "outfit", "pet-ability", "petability", "profession-trait", "quest", "recipe", "resource", "ship", "spell", "statistic", "storyline", "threat", "trading-post-activity", "transmog-set", "zone"],
            [WH.dataTree.CLASSIC]: ["currency", "event", "guide", "item", "item-set", "itemset", "npc", "object", "outfit", "pet-ability", "petability", "quest", "resource", "spell", "statistic", "transmog-set", "zone"]
        };
        e[WH.dataTree.TBC] = e[WH.dataTree.CLASSIC];
        e[WH.dataTree.WRATH] = e[WH.dataTree.TBC].concat(["achievement"]);
        e[WH.dataTree.CATA] = e[WH.dataTree.WRATH];
        e[WH.dataTree.MISTS] = e[WH.dataTree.CATA];
        return e
    })();
    const p = {
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
    const h = {colorLinks: "colorlinks", iconizeLinks: "iconizelinks", renameLinks: "renamelinks"};
    const g = WH.TERMS || {
        genericequip_tip: '<span class="q2">Equip: Increases your $1 by \x3c!--rtg$2--\x3e$3.</span><br />',
        reforged_format: "Reforged ($1 $2 &rarr; $1 $3)"
    };
    const m = {
        0: "enus",
        1: "kokr",
        2: "frfr",
        3: "dede",
        4: "zhcn",
        6: "eses",
        7: "ruru",
        8: "ptbr",
        9: "itit",
        10: "zhtw",
        11: "esmx"
    };
    const W = 320;
    const H = 0;
    const E = 5;
    const T = 3;
    const v = 4;
    const S = 1;
    const I = 2;
    const _ = [{}, {top: false}, {right: false}, {right: false, top: false}];
    const b = /([a-zA-Z0-9-]+)=?([^&?#]*)/g;
    const w = (() => {
        let e = {};
        Object.entries(f).forEach((([a, n]) => {
            let s = parseInt(a);
            if (s < 0) {
                e[n.path] = s;
                return
            }
            let r = i.getGame(s) ?? t.WOW;
            let o = r === t.WOW ? "" : `${t.getKey(r)}-`;
            i.getHistoricalDetailPageNames(s).forEach((t => e[o + t] = s))
        }));
        return e
    })();
    const y = "(?![^/?&#])";
    const A = "^https?://(.+?)?\\.?(?:wowhead)\\.com(?:\\:\\d+)?/";
    const C = new RegExp(A + "\\??(" + u[WH.dataTree.RETAIL].join("|") + ")[=/](?:[^/?&#]*[^/?&#-]+-)?(-?\\d+(?:\\.\\d+)?)" + y);
    const R = new RegExp(A + "(guide)s?/([^?&#]+)");
    const L = (() => [WH.dataEnv.MAIN, WH.dataEnv.PTR, WH.dataEnv.PTR2, WH.dataEnv.BETA, WH.dataEnv.MISTS, WH.dataEnv.CATA, WH.dataEnv.D4, WH.dataEnv.D4PTR, WH.dataEnv.D4BETA, WH.dataEnv.WRATH, WH.dataEnv.DI, WH.dataEnv.TBC, WH.dataEnv.CLASSIC, WH.dataEnv.CLASSICPTR, WH.dataEnv.D2].map((e => {
        let a = WH.getDataTree(e);
        let i = t.getByTree(a);
        let n = u[a];
        let s = t.getSelectorByDataEnv(e);
        let r = "^https?://(?:\\w+\\.)*wowhead\\.com(?:\\:\\d+)?" + (s ? `/${s}` : "") + "/(?:(\\w\\w)/)?";
        let o = "(" + n.join("|") + ")";
        return {
            detailPagePrefix: i === t.WOW ? "" : `${t.getKey(i)}-`,
            envId: e,
            prefixedDetailPageNames: i === t.WOW ? [] : n.filter((e => e !== "guide")),
            regexFrontPaths: new RegExp(r + o + "/(?:[^/?&#]+-)?(\\d+)" + y),
            regexGuidePaths: new RegExp(r + "(guide)s?/([^?&#]+)"),
            regexLegacyPaths: new RegExp(r + o + "=(-?\\d+(?:\\.\\d+)?)"),
            treeId: a
        }
    })))();
    const M = -1;
    const O = 0;
    const D = 1;
    const N = 0;
    const P = 1;
    const x = 2;
    const k = 3;
    const B = 4;
    const F = 5;
    const U = {[P]: "loading", [F]: "loading", [x]: "error", [N]: "loading", [k]: "error", [B]: "ok"};
    const G = [i.GUIDE];
    const q = {
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
    const $ = WH.Device.isTouch();
    const z = {
        cursorX: undefined,
        cursorY: undefined,
        enabled: true,
        element: undefined,
        elements: {
            icon: undefined,
            logo: undefined,
            screen: undefined,
            screenCaption: undefined,
            screenInnerBox: undefined,
            screenInnerWrapper: undefined,
            tooltip: undefined,
            tooltip2: undefined,
            tooltipTable: undefined,
            tooltipTable2: undefined,
            tooltipTd: undefined,
            tooltipTd2: undefined
        },
        showScreenshots: false,
        initiatedByUser: false,
        iScroll: null,
        mobileScrollInitialized: false,
        show: {
            dataEnv: undefined,
            fullId: undefined,
            hasLogo: true,
            locale: undefined,
            mode: H,
            params: {},
            type: undefined
        },
        showCharacterCompletion: !WH.REMOTE,
        showIcon: false,
        showSecondary: false,
        showingTooltip: false,
        triggeringElementRemovalObserver: undefined,
        triggeringElementVisibilityObserver: undefined,
        touchElement: undefined,
        usingScreen: false
    };
    this.attachTouchTooltips = function (e) {
        if (!$) {
            return
        }
        if (e && e.nodeType === 1) {
            Z(e)
        }
    };
    this.clearTouchTooltip = function (e) {
        if (z.touchElement) {
            if (e !== true) {
                z.touchElement.removeAttribute("data-showing-touch-tooltip")
            }
            z.touchElement.hasWHTouchTooltip = false
        }
        z.touchElement = undefined;
        if (e !== true) {
            WH.qsa("[data-showing-touch-tooltip]").forEach((function (e) {
                delete e.dataset.showingTouchTooltip
            }))
        }
        if (z.elements.screen) {
            z.elements.screenInnerWrapper.scrollTop = 0;
            z.elements.screenInnerWrapper.scrollLeft = 0;
            z.elements.screen.style.display = "none";
            z.usingScreen = false
        }
        let t = e === true ? z.showingTooltip : false;
        Ie();
        z.showingTooltip = t
    };
    this.evalFormulas = function (e, t = 0) {
        if (typeof e !== "string") {
            return e
        }
        let a = /<span class="wh-tooltip-formula" style="display:none">(\[[\w\W]*?\])<\/span>(?:\d+(?:\.\d+)?)?/g;
        e = e.replace(a, "$1");
        let i = 0;
        let n = 0;
        let s = "";
        let r = 0;
        for (let a = 0; a < e.length; a++) {
            let o = e.substr(a, 1);
            switch (o) {
                case"[":
                    i++;
                    n = 0;
                    s = "";
                    break;
                case"]":
                    i--;
                    if (i < 0) {
                        i = 0
                    }
                    n = 0;
                    s = "";
                    break;
                case"(":
                    if (i > 0) {
                        break
                    }
                    s += o;
                    n++;
                    break;
                case")":
                    if (i > 0) {
                        break
                    }
                    if (n > 0) {
                        s += o;
                        n--
                    }
                    break;
                default:
                    if (i == 0 && n > 0) {
                        s += o
                    }
            }
            if (i == 0 && n == 0 && s) {
                r = a - s.length + 1;
                if (/[^ ()0-9\+\*\/\.\-\%]/.test(s.replace(/<!--[\w\W]*?-->/g, "").replace(/\b(floor|ceil|abs)\b/gi, ""))) {
                    s = "";
                    continue
                }
                if (/^\([0-9\.]*\)$/.test(s)) {
                    s = "";
                    continue
                }
                if (!/<!--[\w\W]*?-->/g.test(s)) {
                    s = "";
                    continue
                }
                e = e.substr(0, r) + (t === 0 ? "[" : "(") + e.substring(r, a + 1) + (t === 0 ? "]" : ")") + e.substr(a + 1);
                a += 2;
                s = ""
            }
        }
        e = e.replace(/\[([^\]]+)\]/g, (function (e, t) {
            let a;
            t = t.replace(/<!--[\w\W]*?-->/g, "");
            t = t.replace(/\b(floor|ceil|abs|max|min)\b/gi, "Math.$1");
            t = t.replace(/&lt;/g, "<");
            t = t.replace(/&gt;/g, ">");
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
    };
    this.getEntity = function (e, t, a, i) {
        if (i === undefined) {
            i = Locale.getId()
        }
        if (!a) {
            a = WH.getDataEnv()
        }
        var n = le(e);
        n[t] = n[t] || {};
        n[t][a] = n[t][a] || {};
        n[t][a][i] = n[t][a][i] || {status: N, callbacks: [], data: {}};
        return n[t][a][i]
    };
    this.init = function () {
        Q();
        De((function () {
            if (We("renameLinks") || We("colorLinks") || We("iconizeLinks") || We("iconSize")) {
                let e = oe();
                for (let t = 0; t < e.length; t++) {
                    Ye(e[t])
                }
                ye()
            } else if (document.querySelectorAll) {
                let e = ['a[href*="wowhead.com/talent-calc/embed/"]', 'a[href*="wowhead.com/soulbind-calc/embed/"]', 'a[href*="wowhead.com/diablo-2/skill-calc/embed/"]'].join(",");
                let t = document.querySelectorAll(e);
                for (let e = 0; e < t.length; e++) {
                    Ye(t[e])
                }
            }
        }))
    };
    this.onScalesAvailable = function (e, t, a) {
        ct.registerCallback(e, t, a)
    };
    this.parseItemEffectTooltip = function (e, t, a) {
        let i = new RegExp(`\x3c!--itemeffect${t}(\\d+):0--\x3e<span(?: hidden.*?)?>(.*?)</span>\x3c!--itemeffect${t}--\x3e(<br>|<br />|</span>)?`, "g");
        return e.replace(i, (function (e, i, n, s) {
            i = parseInt(i);
            let r = `\x3c!--itemeffect${t}${i}:0--\x3e<span`;
            if (i === a || a === null) {
                if (s !== "</span>") {
                    s = "<br>"
                }
            } else {
                r += " hidden";
                if (s !== "</span>") {
                    s = ""
                }
            }
            r += `>${n}</span>\x3c!--itemeffect${t}--\x3e${s}`;
            return r
        }))
    };
    this.parseItemEffectTooltipForHero = (e, t) => this.parseItemEffectTooltip(e, "hero", t);
    this.parseItemEffectTooltipForSpec = (e, t) => this.parseItemEffectTooltip(e, "spec", t);
    this.refreshLinks = function (e) {
        if (e === true || We("renameLinks") || We("colorLinks") || We("iconizeLinks")) {
            let e = oe();
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
                    Ye(i);
                    if ($) {
                        X(i)
                    }
                }
            }
        }
        Ie()
    };
    this.register = function (t, a, n, s, r) {
        let o = this.getEntity(t, a, n, s);
        {
            let a = r.additionalIds || [];
            delete r.additionalIds;
            a.forEach((a => e.register(t, a, n, s, r)))
        }
        {
            if (!ct.isLoaded(t, n)) {
                o.status = F;
                ct.registerCallback(t, n, e.register.bind(this, t, a, n, s, r));
                return
            }
            if (typeof a === "string" && (a.indexOf("lvl") === 0 || a.match(/[^i]lvl/)) && !ct.isLoaded(i.SPELL, n)) {
                o.status = F;
                ct.registerCallback(i.SPELL, n, e.register.bind(this, t, a, n, s, r));
                return
            }
        }
        if (o.timer) {
            clearTimeout(o.timer);
            delete o.timer
        }
        if (!WH.REMOTE && r.map) {
            if (!o.data.map) {
                o.data.map = new Mapper({parent: WH.ce("div"), zoom: 3, zoomable: false, buttons: false})
            }
            o.data.map.update(r.map);
            delete r.map
        }
        for (var l in r) {
            if (!r.hasOwnProperty(l)) {
                continue
            }
            o.data[l] = r[l]
        }
        switch (o.status) {
            case P:
            case F:
            case x:
            case N:
                if (o.data[ve()]) {
                    o.status = B
                } else {
                    o.status = k
                }
        }
        if (z.showingTooltip && z.show.type === t && z.show.fullId === a && z.show.dataEnv === n && z.show.locale === s) {
            st()
        }
        while (o.callbacks.length) {
            o.callbacks.shift()()
        }
    };
    this.replaceWithTooltip = function (a, i, n, s, r, o, l) {
        o = o || {};
        if (r === undefined) {
            r = Locale.getId()
        }
        if (!s) {
            s = WH.getDataEnv()
        }
        if (typeof a === "string") {
            a = document.getElementById(a)
        }
        if (!a) {
            return false
        }
        var c = ue(i, n, o);
        var d = this.getEntity(i, c, s, r);
        switch (d.status) {
            case B:
                if (!a.parentNode) {
                    return true
                }
                while (a.hasChildNodes()) {
                    a.removeChild(a.firstChild)
                }
                var u = ["wowhead-tooltip-inline", "exclude-units"];
                if (!f[i].embeddedIcons && ie(d.data)) {
                    u.push("wowhead-tooltip-inline-icon")
                }
                Y(a, u);
                let p = t.getByEnv(s);
                if (p !== t.get()) {
                    WH.Fonts.load(p)
                }
                let h = d.data[ve()];
                let g = [];
                if (o.know) {
                    g = g.concat(o.know)
                }
                if (o.spellModifier) {
                    g = g.concat(o.spellModifier)
                }
                h = WH.setTooltipSpells(h, g, d.data[Te()]);
                if (o.spec) {
                    h = WH.Tooltips.parseItemEffectTooltipForSpec(h, o.spec)
                }
                if (o.hero) {
                    h = WH.Tooltips.parseItemEffectTooltipForHero(h, o.hero)
                }
                let m = function (n) {
                    if (typeof l === "function") {
                        n = l(n, d)
                    }
                    if (p === t.WOW) {
                        if (o.forg) {
                            n = K(n, o.forg)
                        }
                        n = e.evalFormulas(n)
                    }
                    if (n) {
                        V(a, d.data, n, s, i)
                    }
                };
                Ue(h, d.data[Te()], m, {type: i, fullId: c, dataEnv: s, locale: r, params: o});
                return true;
            case P:
            case N:
                d.callbacks.push(this.replaceWithTooltip.bind(this, a, i, n, s, r, o, l));
                this.request(i, n, s, r, o);
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
        var s = ue(e, t, n);
        this.getEntity(e, s, a, i);
        ze(e, t, a, i, true, n)
    };
    this.setScales = function (e, t, a) {
        ct.setData(e, t, a)
    };
    this.triggerTooltip = function (e, t) {
        Ye(e, t || {target: e}, true)
    };
    if (!WH.REMOTE) {
        this.addText = function (e, t, a) {
            if (!e) {
                WH.error("Tooltip text addition element not found!", e, t, a);
                return
            }
            e._fixTooltip = function (e, t, a, i) {
                let n = /<\/table>\s*$/;
                let s = typeof a === "function" ? a() : a;
                let r = a ? ' class="' + s + '"' : "";
                let o = typeof t === "function" ? t() : t;
                if (n.test(i)) {
                    return i.replace(n, '<tr><td colspan="2"><div' + r + ' style="margin-top: 10px">' + o + "</div></td></tr></table>")
                } else {
                    return i + "<div" + r + ' style="margin-top: 10px">' + o + "</div>"
                }
            }.bind(null, e, t, a)
        };
        this.attach = function (t, a, i, n) {
            n = n || {};
            if (t instanceof jQuery) {
                for (let s = 0, r; r = t[s]; s++) {
                    e.attach(r, a, i, n)
                }
                return
            }
            let s = {dataEnv: n.dataEnv, type: n.type, iconName: n.iconName};
            let r = n.stopPropagation ? e => e.stopPropagation() : () => {
            };
            if (n.byCursor) {
                t.onmouseover = e => {
                    let t = $e(a, n.noWrap, n.maxWidth, e);
                    et(e, t, i, s);
                    r(e)
                };
                t.onmousemove = e.cursorUpdate
            } else {
                t.onmouseover = e => {
                    let o = $e(a, n.noWrap, n.maxWidth, e);
                    Ze(t, o, i, s);
                    r(e)
                }
            }
            t.onmouseout = Ie
        };
        this.attachNonTouch = function (t, a, i, n) {
            if (!WH.Device.isTouch()) {
                e.attach(t, a, i, n)
            }
        };
        this.cursorUpdate = function (e, t, a) {
            if (!z.enabled || !z.elements.tooltip) {
                return
            }
            if (!t || t < 10) t = 10;
            if (!a || a < 10) a = 10;
            Le(e.pageX, e.pageY, 0, 0, t, a)
        };
        this.disableCompletion = function () {
            z.showCharacterCompletion = false
        };
        this.getScalingData = (e, t) => ct.getDataByKey(e, t);
        this.getScreenshotsEnabled = () => z.showScreenshots;
        this.getTd = () => z.elements.tooltipTd;
        this.isTypeSupported = e => f.hasOwnProperty(e);
        this.isVisible = function () {
            return z.showingTooltip || z.elements.tooltip && WH.DOM.isVisible(z.elements.tooltip)
        };
        this.on = (e, t) => {
            if (!z.elements.tooltip) {
                Ge()
            }
            WH.aE(z.elements.tooltip, e, t)
        };
        this.relToParams = e => {
            let t = {};
            e.forEach((e => e.replace(b, ((e, a, i) => Be(t, a, i) || ""))));
            return t
        };
        this.setEnabled = e => z.enabled = e;
        this.setScreenshotsEnabled = e => z.showScreenshots = e;
        this.showFadingTooltipAtCursor = function (e, t, a, i, n) {
            e = $e(e, i, n, t);
            et(t, e, a);
            requestAnimationFrame((function () {
                z.elements.tooltip.classList.add("fade-out")
            }))
        };
        this.titlesToTooltips = function (t, a) {
            if (typeof t === "string") {
                t = WH.qsa(t)
            }
            t.forEach((t => {
                e.attach(t, t.title, "q", {noWrap: true});
                t.removeAttribute("title");
                if (!a) {
                    t.classList.add("tip")
                }
            }))
        };
        this.finalizeSizeAndReveal = se;
        this.hide = Ie;
        this.prepare = Ge;
        this.prepareContent = $e;
        this.setIcon = Ke;
        this.show = Ze;
        this.showAtCursor = et
    }

    function Y(e, t) {
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

    function V(e, t, a, i, n) {
        let s = te(a);
        let r = s.tooltip;
        j(r, i, n);
        Qe(s.icon, t, n, i);
        WH.ae(e, r);
        se(r)
    }

    function K(e, t) {
        let a = WH.reforgeStats?.[t];
        if (!a) {
            return e
        }
        let i = [a.i1];
        for (let e in WH.individualToGlobalStat) {
            if (WH.individualToGlobalStat[e] === i[0]) {
                i.push(e)
            }
        }
        let n;
        if ((n = e.match(new RegExp("(\x3c!--(stat|rtg)(" + i.join("|") + ")--\x3e)[+-]?([0-9]+)"))) && !e.match(new RegExp("\x3c!--(stat|rtg)" + a.i2 + "--\x3e[+-]?[0-9]+"))) {
            let t = Math.floor(n[4] * a.v);
            let i = p.traits[a.s2][0];
            if (a.i2 == 6) {
                e = e.replace("\x3c!--rs--\x3e", "<br />+" + t + " " + i)
            } else {
                e = e.replace("\x3c!--rr--\x3e", WH.sprintfGlobal(g.genericequip_tip, i.toLowerCase(), a.i2, t))
            }
            e = e.replace(n[0], n[1] + (n[4] - t));
            e = e.replace("\x3c!--rf--\x3e", '<span class="q2">' + WH.sprintfGlobal(g.reforged_format, t, p.traits[a.s1][2], p.traits[a.s2][2]) + "</span><br />")
        }
        return e
    }

    function j(e, t, a, i) {
        if (i) {
            e.dataset.status = U[i]
        } else {
            delete e.dataset.status
        }
        let n = t && WH.Game.getKey(WH.Game.getByEnv(t));
        if (n) {
            e.dataset.game = n
        } else {
            delete e.dataset.game
        }
        let s = t && WH.getDataTreeKey(WH.getDataTree(t));
        if (s) {
            e.dataset.tree = s
        } else {
            delete e.dataset.tree
        }
        let r = t && WH.getDataEnvKey(t);
        if (r) {
            e.dataset.env = r
        } else {
            delete e.dataset.env
        }
        let o = a && WH.Types.getReferenceName(a);
        if (o) {
            e.dataset.type = o
        } else {
            delete e.dataset.type
        }
    }

    function Q() {
        WH.aE(document, "keydown", (function (t) {
            switch (t.keyCode) {
                case 27:
                    e.clearTouchTooltip();
                    Ie();
                    break
            }
        }));
        if ($) {
            Z()
        } else {
            WH.aE(document, "mouseover", xe)
        }
        Q = () => {
        }
    }

    function J(e, t) {
        WH.qsa(":scope > .image", z.elements.tooltipTable.parentNode).forEach((e => WH.de(e)));
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
        z.elements.tooltipTable.parentNode.insertBefore(i, z.elements.tooltipTable.nextSibling)
    }

    function X(e) {
        if (!e.dataset || e.dataset.hasWhTouchEvent === "true") {
            return
        }
        if (e.onclick == null) {
            e.onclick = ke
        } else {
            WH.aE(e, "click", ke)
        }
        e.dataset.hasWhTouchEvent = "true"
    }

    function Z(e) {
        if (!$) {
            return
        }
        De((function () {
            e = e || document.body;
            var t = WH.gE(e, "a");
            for (var a = 0, i = t.length; a < i; a++) {
                X(t[a])
            }
        }))
    }

    function ee(e, t) {
        t.style.maxHeight = null;
        delete e.whttHeightCap;
        delete e.dataset.height;
        e.style.maxHeight = null
    }

    function te(e, t) {
        let a = WH.ce("div", {className: "wowhead-tooltip"});
        let i = WH.ce("table");
        let n = WH.ce("tbody");
        let s = WH.ce("tr");
        let r = WH.ce("tr");
        let o = WH.ce("td");
        let l = WH.ce("th", {style: {backgroundPosition: "top right"}});
        let c = WH.ce("th", {style: {backgroundPosition: "bottom left"}});
        let d = WH.ce("th", {style: {backgroundPosition: "bottom right"}});
        let f = {tooltip: a};
        if (e) {
            o.innerHTML = e
        }
        WH.ae(s, o);
        WH.ae(s, l);
        WH.ae(n, s);
        WH.ae(r, c);
        WH.ae(r, d);
        WH.ae(n, r);
        WH.ae(i, n);
        if (!t) {
            f.icon = WH.ce("div", {className: "whtt-tooltip-icon", style: {visibility: "hidden"}});
            WH.ae(a, f.icon)
        }
        WH.ae(a, i);
        if (!t) {
            f.logo = WH.ce("div", {className: "wowhead-tooltip-powered"});
            WH.ae(a, f.logo)
        }
        return f
    }

    function ae(t, a, i, n, s) {
        s = s || {};
        var r = ue(t, a, s);
        z.show.type = t;
        z.show.fullId = r;
        z.show.dataEnv = i;
        z.show.locale = n;
        z.show.params = s;
        ct.isLoaded(t, i);
        let o = e.getEntity(t, r, i, n);
        if (o.status === B || o.status === k) {
            st()
        } else if (o.status === P || o.status === F) {
            if (WH.inArray(G, t) === -1) {
                nt(o.status, n, me(n, "loading"))
            }
        } else {
            ze(t, a, i, n, WH.inArray(G, t) !== -1, s)
        }
    }

    function ie(e) {
        return !!(e && (e.icon || e.iconImageHash))
    }

    function ne(e, t) {
        const a = 20;
        let i = WH.qs("table", e);
        let n = WH.qs("td", i);
        let s = n.childNodes;
        e.classList.remove("tooltip-slider");
        if (s.length >= 2 && s[0].nodeName === "TABLE" && s[1].nodeName === "TABLE") {
            let r = s[0];
            let o = s[1];
            r.style.whiteSpace = "nowrap";
            let l = parseInt(e.style.width);
            if (!e.slider || !l) {
                l = Math.max(r.getBoundingClientRect().width, o.getBoundingClientRect().width) + a
            }
            if (l > W) {
                r.style.whiteSpace = null
            }
            for (let e = 2; e < s.length; e++) {
                if (s[e].nodeName === "BLOCKQUOTE") {
                    l = Math.max(l, s[e].getBoundingClientRect().width + a)
                }
            }
            l = Math.min(W, l);
            if (l > 20) {
                if (e.slider) {
                    Slider.setSize(e.slider, l - 6);
                    e.classList.add("tooltip-slider")
                }
                e.classList.add("wowhead-tooltip-width-restriction");
                e.classList.add("wowhead-tooltip-width-" + l);
                e.style.width = l + "px";
                WH.qsa(":scope > table", n).forEach((e => e.style.width = "100%"));
                if (t && e.offsetHeight > Ee()) {
                    i.classList.add("shrink")
                }
            }
        } else if (s.length && e.slider) {
            let n = s[0];
            let r = n.nodeName === "TABLE";
            if (r) {
                n.style.whiteSpace = "nowrap"
            }
            let o = parseInt(e.style.width);
            if (!o && r) {
                o = n.getBoundingClientRect().width + a;
                if (o > W) {
                    n.style.whiteSpace = null
                }
            } else {
                o = i.getBoundingClientRect().width + a
            }
            o = Math.min(W, o);
            if (o > 20) {
                e.style.width = o + "px";
                if (r) {
                    n.style.width = "100%"
                }
                if (e.slider) {
                    Slider.setSize(e.slider, o - 6);
                    e.classList.add("tooltip-slider")
                }
                if (t && e.offsetHeight > Ee()) {
                    i.classList.add("shrink")
                }
            }
        }
    }

    function se(e) {
        ne(e, false);
        Xe(e, true)
    }

    function re(e) {
        if (!z.elements.tooltip) {
            return
        }
        try {
            z.elements.tooltip.dispatchEvent(new Event(e))
        } catch (t) {
            try {
                let t = document.createEvent("Event");
                t.initEvent(e, true, true);
                z.elements.tooltip.dispatchEvent(t)
            } catch (e) {
                void 0
            }
        }
    }

    function oe() {
        let e = [];
        for (let t = 0; t < document.links.length; t++) {
            e.push(document.links[t])
        }
        return e
    }

    function le(e) {
        if (typeof f[e] !== "object") {
            throw new Error("Wowhead tooltips could not find config for entity type [" + e + "].")
        }
        return f[e].data
    }

    function ce(e) {
        if (typeof f[e] !== "object") {
            WH.error("Wowhead tooltips could not find config for entity type.", e);
            return undefined
        }
        if (!WH.REMOTE || !f[e].hasOwnProperty("maxId")) {
            return undefined
        }
        return {min: f[e].hasOwnProperty("minId") ? f[e].minId : 1, max: f[e].maxId}
    }

    function de(e) {
        if (typeof f[e] !== "object") {
            WH.error("Wowhead tooltips could not find config for entity type.", e);
            return "Entity"
        }
        return f[e].name
    }

    function fe(e) {
        if (typeof f[e] !== "object") {
            WH.error("Wowhead tooltips could not find config for entity type.", e);
            return "unknown"
        }
        return f[e].path
    }

    function ue(e, t, a) {
        if (a.build) {
            return t + "build" + a.build
        }
        return t + (a.rand ? "r" + a.rand : "") + (a.ench ? "e" + a.ench.join(",") : "") + (a.gems ? "g" + a.gems.join(",") : "") + (a.sock ? "s" : "") + (a.upgd ? `u${a.upgd}` : "") + (a.twtbc ? "twtbc" : "") + (a.twwotlk ? "twwotlk" : "") + (a.twcata ? "twcata" : "") + (a.twmists ? "twmists" : "") + (a.twwod ? "twwod" : "") + (a.ilvl ? "ilvl" + a.ilvl : "") + (a.lvl ? "lvl" + a.lvl : "") + (a.gem1lvl ? "g1lvl" + a.gem1lvl : "") + (a.gem2lvl ? "g2lvl" + a.gem2lvl : "") + (a.gem3lvl ? "g3lvl" + a.gem3lvl : "") + (a.artk ? "ak" + a.artk : "") + (a.nlc ? "nlc" + a.nlc : "") + (a.transmog ? "transmog" + a.transmog : "") + (a.tink ? "tink" + a.tink : "") + (a.pvp ? "pvp" : "") + (a.bonus ? "b" + a.bonus.join(",") : "") + (a.gem1bonus ? "g1b" + a.gem1bonus.join(",") : "") + (a.gem2bonus ? "g2b" + a.gem2bonus.join(",") : "") + (a.gem3bonus ? "g3b" + a.gem3bonus.join(",") : "") + (a["crafted-stats"] ? "craftedStats" + a["crafted-stats"].join(",") : "") + (a["crafting-quality"] ? "craftingQuality" + a["crafting-quality"] : "") + (a.q ? "q" + a.q : "") + (a.level ? "level" + a.level : "") + (a.abil ? "abil" + a.abil.join(",") : "") + (a.dd ? "dd" + a.dd : "") + (a.ddsize ? "ddsize" + a.ddsize : "") + (a.diff === i.SPELL ? "diff" + a.diff : "") + (a.def ? "def" + a.def : "") + (a.rank ? "rank" + a.rank : "") + (a.alt ? "alt" + a.alt.join(",") : "") + (a.talent ? "talent" + a.talent : "") + (a.awakened ? "awakened" + a.awakened : "") + (a["class"] ? "class" + a["class"] : "") + (e !== i.SPELL && a.spec ? "spec" + a.spec : "") + (a.rewards ? "rewards" + a.rewards.join(":") : "") + (a["azerite-powers"] ? "azPowers" + a["azerite-powers"] : "") + (a["azerite-essence-powers"] ? "aePowers" + a["azerite-essence-powers"] : "") + (a.nomajor ? "nomajor" : "") + (a.affixes ? "affixes" + a.affixes.join(",") : "") + (a.board ? "board" + a.board : "") + (a.glyph ? "glyph" + a.glyph : "") + (a.greaterAffixes ? "greaterAffixes" + a.greaterAffixes.join(",") : "") + (a.itemPower ? "itemPower" + a.itemPower : "") + (a.itemRanks ? "itemRanks" + a.itemRanks : "") + (a.itemType ? "itemType" + a.itemType : "") + (a.masterworking ? "masterworking" + a.masterworking.join(",") : "") + (a.hasOwnProperty("mod") ? "mod" + a.mod : "") + (a.mods ? "mods" + a.mods.join(",") : "") + (a.nodes ? "nodes" + a.nodes.join(",") : "") + (a.slot != null ? "slot" + a.slot : "") + (a.sockets ? "sockets" + a.sockets.join(",") : "") + (a.stars ? "stars" + a.stars : "")
    }

    function pe() {
        return z.show.params && z.show.params.text ? "text_icon" : "icon"
    }

    function he(e) {
        if (typeof e === "undefined") {
            return "image_NONE"
        }
        return "image" + e
    }

    function ge(e, t, a) {
        if (WH.REMOTE) {
            return false
        }
        if (!WH.User.isPremium()) {
            return false
        }
        if (!z.showScreenshots) {
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
                let s = g_items.getAppearance(n.id, i);
                if (s && s[0]) {
                    e = s[0]
                }
                if (e) {
                    return [WH.Wow.Item.getThumbUrl(e, a), "screenshot"]
                }
            }
        }
        return false
    }

    function me(e, t) {
        return (q[e] || q[0])[t] || ""
    }

    function We(e) {
        var t = He();
        if (!t) {
            return null
        }
        if (!t.hasOwnProperty(e)) {
            if (h[e] && t.hasOwnProperty(h[e])) {
                return t[h[e]]
            }
            return null
        }
        return t[e]
    }

    function He() {
        if (typeof whTooltips === "object") {
            return whTooltips
        }
        if (typeof wowhead_tooltips === "object") {
            return wowhead_tooltips
        }
        return null
    }

    function Ee() {
        let e = document.documentElement;
        let t = document.body;
        return Math.max(t.offsetHeight, t.scrollHeight, e.clientHeight, e.offsetHeight, e.scrollHeight)
    }

    function Te() {
        return (z.show.params && z.show.params.buff ? "buff" : "") + "spells"
    }

    function ve(e) {
        var t = "tooltip";
        if (z.show.params && z.show.params.buff) t = "buff";
        if (z.show.params && z.show.params.text) t = "text";
        if (z.show.params && z.show.params.premium) t = "tooltip_premium";
        return t + (e || "")
    }

    function Se(e) {
        e = e || "www";
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

    function Ie() {
        rt();
        if (z.elements.tooltip) {
            let e = z.elements.tooltip;
            z.showingTooltip = false;
            e.style.display = "none";
            Xe(e, false);
            z.elements.tooltipTable.className = "";
            let t = (e.whttHeightCap || {}).innerScroll;
            if (t) {
                ee(e, t)
            }
            Ke();
            if (WH.WAS) {
                WH.WAS.restoreHidden()
            }
            re("hide")
        }
        if (z.elements.tooltip2) {
            z.elements.tooltip2.style.display = "none";
            Xe(z.elements.tooltip2, false);
            z.elements.tooltipTable2.className = ""
        }
    }

    function _e(e, t) {
        if (WH.REMOTE || !WH.isRetailTree(t.dataEnv)) {
            return e
        }
        let a = WH.Profiler.getFavorite(true);
        if (!a) {
            return e
        }
        let i = WH.User.Completion.getByType(WH.Types.ACHIEVEMENT)[a.id];
        if ((i || []).includes(parseInt(t.fullId))) {
            e = e.replace(new RegExp("\x3c!--cr\\d+:[^<]+", "g"), '<span class="q2">$&</span>')
        } else {
            (WH.User.Completion.getAchievementCriteria()[t.fullId] || []).forEach((t => {
                e = e.replace(new RegExp("\x3c!--cr" + t + ":[^<]+", "g"), '<span class="q2">$&</span>')
            }))
        }
        return e
    }

    function be(e, n, s, r) {
        if (!r || !a.isValidSize(r)) {
            r = "tiny"
        }
        let o = s.icon;
        switch (WH.Types.getGame(n)) {
            case t.WOW:
                o = o.toLowerCase();
                break;
            case t.D4:
                o = s.iconImageHash;
                break
        }
        if (r === "tiny") {
            let s = i.getGame(n);
            let r = a.getIconUrl(o, a.TINY, t.getEnv(s));
            Y(e, ["icontinyl"]);
            e.dataset.game = t.getKey(s);
            e.dataset.type = i.getStringId(n);
            e.style.backgroundImage = "url(" + r + ")"
        } else {
            if (e.getAttribute("data-wh-icon-added") === "true") {
                return
            }
            WH.aef(e, a.createByEntity(s, n, null, {size: r, span: true}))
        }
        e.setAttribute("data-wh-icon-added", "true")
    }

    function we() {
        if (WH.REMOTE) {
            WH.ae(document.head, WH.ce("link", {
                type: "text/css",
                href: WH.STATIC_URL + "/css/universal.css?19",
                rel: "stylesheet"
            }));
            e.init()
        } else {
            Q();
            De((function () {
                ct.fetch(i.ITEM, WH.getDataEnv());
                ct.fetch(i.SPELL, WH.getDataEnv())
            }))
        }
    }

    function ye() {
        var e = We("hide");
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
        ye = () => {
        }
    }

    function Ae() {
        if (!z.mobileScrollInitialized) {
            let e = function (e) {
                if (z.usingScreen) {
                    if (!document.getElementById("wowhead-tooltip-screen-inner").contains(e.target)) {
                        e.preventDefault()
                    }
                }
            };
            WH.aE(document.body, "touchmove", e);
            WH.aE(document.body, "mousewheel", e);
            z.mobileScrollInitialized = true
        }
        if (typeof IScroll !== "function") {
            return
        }
        setTimeout((function () {
            if (z.iScroll) {
                z.iScroll.destroy();
                z.iScroll = null
            }
            z.iScroll = new IScroll(z.elements.screenInnerWrapper, {mouseWheel: true, tap: true})
        }), 1)
    }

    function Ce(e) {
        if (typeof f[e] !== "object") {
            WH.error("Wowhead tooltips could not find config for entity type.", e);
            return false
        }
        return f[e].mobile
    }

    function Re(t, a, i, n) {
        let s = e.getEntity(t, a, i, n);
        s.status = x;
        if (z.show.type === t && z.show.fullId === a && z.show.dataEnv === i && z.show.locale === n) {
            nt(s.status, n, me(n, "noResponse"))
        }
    }

    function Le(e, t, a, i, n, s) {
        if (!z.elements.tooltip) {
            return
        }
        let r = z.elements.tooltip;
        r.style.left = "-1000px";
        r.style.top = "-1000px";
        r.style.width = null;
        r.style.maxWidth = W + "px";
        let o = r.getBoundingClientRect().width;
        let l = z.elements.tooltip2;
        l.style.left = "-1000px";
        l.style.top = "-1000px";
        l.style.width = null;
        l.style.maxWidth = W + "px";
        let c = z.showSecondary ? l.getBoundingClientRect().width : 0;
        r.style.maxWidth = null;
        l.style.maxWidth = null;
        r.style.width = o ? o + "px" : "auto";
        l.style.width = c + "px";
        if (e || t) {
            let e = r.whttHeightCap;
            let t = (e || {}).maxHeight || window.innerHeight;
            let a = (e || {}).innerScroll;
            if (r.offsetHeight >= t) {
                if (a = a || WH.qs(".whtt-scroll", r)) {
                    r.dataset.height = "restricted";
                    r.style.maxHeight = t + "px";
                    if (!e) {
                        let e = r.scrollHeight - r.offsetHeight;
                        a.style.maxHeight = a.scrollHeight - e + "px";
                        r.whttHeightCap = {innerScroll: a, maxHeight: r.offsetHeight}
                    }
                }
            } else {
                if (a) {
                    ee(r, a)
                }
            }
        }
        let d = Me(e, t, a, i, n, s, _[0].right, _[0].top);
        r.style.left = d.l + "px";
        r.style.top = d.t + "px";
        Xe(r, true);
        if (z.showSecondary) {
            l.style.left = d.l + o + "px";
            l.style.top = d.t + "px";
            Xe(l, true)
        }
        re("move")
    }

    function Me(e, t, a, i, n, s, r, o) {
        let l = e;
        let c = t;
        let d = z.elements.tooltip;
        let f = z.elements.tooltip.getBoundingClientRect();
        let u = f.width;
        let p = f.height;
        let h = z.elements.tooltip2.getBoundingClientRect();
        let g = z.showSecondary ? h.width : 0;
        let m = z.showSecondary ? h.height : 0;
        let W = WH.getWindowSize();
        let H = WH.getScroll();
        let E = H.x;
        let T = H.y;
        let v = H.x + W.w;
        let S = H.y + W.h;
        if (d.style.position === "fixed") {
            e -= H.x;
            t -= H.y;
            l -= e;
            c -= t;
            H = {x: 0, y: 0};
            E = T = 0;
            v = W.w;
            S = W.h
        }
        if (window.ZUL?.getEnabled()) {
            T += ZUL.HEIGHT
        }
        if (r == null) {
            r = e + a + u + g <= v
        }
        if (o == null) {
            o = t - Math.max(p, m) >= T
        }
        if (r) {
            e += a + n
        } else {
            e = Math.max(e - (u + g), E) - n
        }
        if (o) {
            t -= Math.max(p, m) + s
        } else {
            t += i + s
        }
        if (e < E) {
            e = E
        } else if (e + u + g > v) {
            e = v - (u + g)
        }
        if (t < T) {
            t = T
        } else if (t + Math.max(p, m) > S) {
            t = Math.max(H.y, S - Math.max(p, m))
        }
        if (z.showIcon) {
            if (l >= e - 48 && l <= e && c >= t - 4 && c <= t + 48) {
                t -= 48 - (c - t)
            }
        }
        return WH.createRect(e, t, u, p)
    }

    function Oe(t, a, n, s, o, l, c, d) {
        if (!d.ctrlKey || d.button !== 2) {
            return
        }
        d.preventDefault();
        d.stopPropagation();
        let f = WH.DOM.getData(this, "menu");
        if (f) {
            Menu.show(f, this);
            return
        }
        f = [];
        let u = e.getEntity(n, ue(n, o, l), t, a);
        if (u.data.name) {
            f.push(Menu.createItem({
                label: WH.term("copy_format", WH.TERMS.name),
                url: WH.copyToClipboard.bind(undefined, u.data.name)
            }))
        }
        f.push(Menu.createItem({
            label: WH.term("copy_format", WH.TERMS.id),
            url: WH.copyToClipboard.bind(undefined, o)
        }));
        let p = c;
        if (!p && i.existsInDataEnv(n)) {
            p = WH.Entity.getUrl(n, o, undefined, undefined, t, a)
        }
        if (p) {
            f.push(Menu.createItem({
                label: WH.term("copy_format", WH.TERMS.url),
                url: WH.copyToClipboard.bind(undefined, c)
            }))
        }
        let h = r[s] || s;
        if (WH.markup.tags[h]) {
            let e = "";
            if (n === WH.Types.SPELL) {
                if (l.def && l.rank) {
                    e += ` def=${l.def} rank=${l.rank}`
                }
            }
            f.push(Menu.createItem({
                label: WH.term("copy_format", WH.TERMS.wowheadMarkupTag),
                url: WH.copyToClipboard.bind(undefined, `[${h}=${o}${e}]`)
            }))
        }
        Menu.add(this, f, {noEvents: true, showAtElement: true, showImmediately: true}, d)
    }

    function De(e) {
        if (document.readyState === "loading") {
            document.addEventListener("DOMContentLoaded", e)
        } else {
            e()
        }
    }

    function Ne(e) {
        lt(e);
        Le(z.cursorX, z.cursorY, 0, 0, l, c)
    }

    function Pe() {
        z.show.type = undefined;
        Ie()
    }

    function xe(e) {
        let t = e.target;
        let a = 0;
        while (t && a < 5 && Ye(t, e) === M) {
            t = t.parentNode;
            a++
        }
    }

    function ke(e) {
        let t = this;
        if (t.hasWHTouchTooltip === true) {
            return
        }
        let a = 0;
        let i;
        while (t && a < 5 && (i = Ye(t, e)) === M) {
            t = t.parentNode;
            a++
        }
        if (i === D) {
            if (z.touchElement) {
                z.touchElement.removeAttribute("data-showing-touch-tooltip");
                z.touchElement.hasWHTouchTooltip = false
            }
            z.touchElement = t;
            z.touchElement.hasWHTouchTooltip = true;
            if (e.preventDefault) {
                e.preventDefault()
            }
            return false
        }
    }

    function Be(e, t, a) {
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
                e[t] = true;
                break;
            case"artk":
            case"board":
            case"c":
            case"class":
            case"covenant":
            case"crafting-quality":
            case"dd":
            case"ddsize":
            case"def":
            case"diff":
            case"diffnew":
            case"gem1lvl":
            case"gem2lvl":
            case"gem3lvl":
            case"glyph":
            case"hero":
            case"ilvl":
            case"itemPower":
            case"itemRanks":
            case"itemType":
            case"level":
            case"lvl":
            case"mod":
            case"nlc":
            case"pwr":
            case"q":
            case"rand":
            case"rank":
            case"slot":
            case"spec":
            case"stars":
            case"talent":
            case"tink":
            case"upgd":
                e[t] = parseInt(a);
                break;
            case"abil":
            case"affixes":
            case"alt":
            case"azerite-essence-powers":
            case"azerite-powers":
            case"bonus":
            case"crafted-stats":
            case"cri":
            case"ench":
            case"forg":
            case"gem1bonus":
            case"gem2bonus":
            case"gem3bonus":
            case"gems":
            case"greaterAffixes":
            case"know":
            case"masterworking":
            case"mods":
            case"nodes":
            case"pcs":
            case"rewards":
            case"sockets":
            case"spellModifier":
                e[t] = a.split(":");
                break;
            case"build":
            case"domain":
            case"gender":
            case"who":
                e[t] = a;
                break;
            case"image":
                if (a === "premium") {
                    e[a] = true
                } else {
                    e[t] = a ? "_" + a : ""
                }
                break;
            case"transmog":
                if (a === "hidden") {
                    e[t] = a
                } else {
                    e[t] = parseInt(a)
                }
                break;
            case"when":
                e[t] = new Date(parseInt(a));
                break
        }
    }

    function Fe(e, a) {
        let i = {dataEnv: WH.getDataEnv(), locale: Locale.getId()};
        let n;
        if (a) {
            n = a.toLowerCase()
        } else if (e) {
            n = e.toLowerCase().replace(/(?:^|\.)(staging|dev)$/, "")
        }
        if (n !== undefined) {
            i.dataEnv = WH.dataEnv.MAIN;
            i.locale = 0;
            let e = n.split(".");
            let a = WH.getLocaleFromDomain.L[e[0]];
            if (a) {
                i.locale = a;
                e.shift()
            }
            if (e[0]) {
                Object.values(WH.dataEnv).some((a => {
                    if ([WH.dataEnvKey[a], t.getSelectorByDataEnv(a)].includes(e[0])) {
                        i.dataEnv = a;
                        return true
                    }
                }))
            }
        }
        if (!WH.isDataEnvActive(i.dataEnv)) {
            i.dataEnv = WH.getRootEnv(i.dataEnv)
        }
        if ([WH.dataEnv.BETA, WH.dataEnv.PTR, WH.dataEnv.PTR2].indexOf(i.dataEnv) >= 0) {
            i.locale = 0
        }
        return i
    }

    function Ue(t, a, n, s) {
        switch (s.type) {
            case i.ACHIEVEMENT:
                t = _e(t, s);
                window.requestAnimationFrame(n.bind(null, t));
                break;
            case i.AZERITE_ESSENCE_POWER:
                let r = e.getEntity(s.type, s.fullId, s.dataEnv, s.locale);
                if (s.params.spec && !(s.params.know && s.params.know.length)) {
                    ct.getSpellsBySpec(s.params.spec, (function (e) {
                        t = t.replace(/<!--embed:([^>]+)-->/g, (function (t, a) {
                            return WH.setTooltipSpells(r.data.embeds[a].tooltip, e, r.data.embeds[a].spells)
                        }));
                        n(t)
                    }));
                    break
                } else {
                    t = t.replace(/<!--embed:([^>]+)-->/g, (function (e, t) {
                        return WH.setTooltipSpells(r.data.embeds[t].tooltip, s.params.know, r.data.embeds[t].spells)
                    }))
                }
                window.requestAnimationFrame(n.bind(null, t));
                break;
            case i.SPELL:
                if (s.params.spec && !(s.params.know && s.params.know.length)) {
                    ct.getSpellsBySpec(s.params.spec, (function (e) {
                        t = WH.setTooltipSpells(t, e, a);
                        n(t)
                    }));
                    break
                }
                window.requestAnimationFrame(n.bind(null, t));
                break;
            default:
                window.requestAnimationFrame(n.bind(null, t))
        }
    }

    function Ge(e, a, i, n, s) {
        if (!z.elements.tooltip) {
            let e = te();
            z.elements.icon = e.icon;
            z.elements.logo = e.logo;
            let t = e.tooltip;
            t.style.left = t.style.top = "-2323px";
            z.elements.tooltip = t;
            z.elements.tooltipTable = WH.gE(t, "table")[0];
            z.elements.tooltipTd = WH.gE(t, "td")[0];
            let a = te(undefined, true).tooltip;
            a.style.left = a.style.top = "-2323px";
            z.elements.tooltip2 = a;
            z.elements.tooltipTable2 = WH.gE(a, "table")[0];
            z.elements.tooltipTd2 = WH.gE(a, "td")[0]
        }
        j(z.elements.tooltip, e, a, s);
        j(z.elements.tooltip2, e, a, s);
        let r = i === true ? "fixed" : "absolute";
        z.elements.tooltip.style.position = r;
        z.elements.tooltip2.style.position = r;
        let o = n || document.fullscreenElement || document.body;
        WH.ae(o, z.elements.tooltip);
        WH.ae(o, z.elements.tooltip2);
        let l = t.getByEnv(e);
        if (l !== t.get()) {
            WH.Fonts.load(l)
        }
    }

    function qe() {
        if (z.elements.screen) {
            z.elements.screen.style.display = "block"
        } else {
            z.elements.screen = WH.ce("div", {id: "wowhead-tooltip-screen", className: "wowhead-tooltip-screen"});
            let t = WH.ce("a", {
                id: "wowhead-tooltip-screen-close",
                className: "wowhead-tooltip-screen-close",
                onclick: WH.Tooltips.clearTouchTooltip
            });
            z.elements.screenInnerWrapper = WH.ce("div", {
                id: "wowhead-tooltip-screen-inner-wrapper",
                className: "wowhead-tooltip-screen-inner-wrapper"
            });
            let a = WH.ce("div", {id: "wowhead-tooltip-screen-inner", className: "wowhead-tooltip-screen-inner"});
            z.elements.screenInnerBox = WH.ce("div", {
                id: "wowhead-tooltip-screen-inner-box",
                className: "wowhead-tooltip-screen-inner-box"
            });
            z.elements.screenCaption = WH.ce("div", {
                id: "wowhead-tooltip-screen-caption",
                className: "wowhead-tooltip-screen-caption"
            });
            WH.ae(z.elements.screen, t);
            WH.ae(a, z.elements.screenInnerBox);
            WH.ae(z.elements.screenInnerWrapper, a);
            WH.ae(z.elements.screen, z.elements.screenInnerWrapper);
            WH.ae(z.elements.screen, z.elements.screenCaption);
            WH.ae(document.body, z.elements.screen);
            WH.aE(z.elements.screenInnerWrapper, "click", (t => {
                if (!t.target.closest(".wowhead-tooltip")) {
                    e.clearTouchTooltip()
                }
            }))
        }
        z.usingScreen = true;
        Ae()
    }

    function $e(e, t, a, i) {
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
                e = `<div${i.join("")}>${e}</div>`
            }
        }
        return e
    }

    function ze(t, a, s, r, o, l) {
        var c = ue(t, a, l);
        let d = e.getEntity(t, c, s, r);
        if (d.status !== N && d.status !== x) {
            return
        }
        d.status = P;
        var f = ce(t);
        if (f && (parseInt(a, 10) < f.min || parseInt(a, 10) > f.max)) {
            e.register(t, a, s, r, {error: "ID is out of range"});
            return
        }
        if (!o) {
            d.timer = setTimeout(it.bind(this, t, c, s, r), 333)
        }
        var u = [];
        for (var p in l) {
            switch (p) {
                case"spec":
                    if (t === i.SPELL || t === i.AZERITE_ESSENCE_POWER) {
                        break
                    }
                case"abil":
                case"affixes":
                case"alt":
                case"artk":
                case"awakened":
                case"azerite-essence-powers":
                case"azerite-powers":
                case"board":
                case"bonus":
                case"build":
                case"class":
                case"covenant":
                case"crafted-stats":
                case"crafting-quality":
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
                case"glyph":
                case"greaterAffixes":
                case"ilvl":
                case"itemPower":
                case"itemRanks":
                case"itemType":
                case"level":
                case"lvl":
                case"masterworking":
                case"mod":
                case"mods":
                case"nlc":
                case"nodes":
                case"nomajor":
                case"pvp":
                case"q":
                case"rand":
                case"rank":
                case"rewards":
                case"slot":
                case"sock":
                case"sockets":
                case"stars":
                case"talent":
                case"tink":
                case"transmog":
                case"twcata":
                case"twmists":
                case"twtbc":
                case"twwod":
                case"twwotlk":
                case"upgd":
                    if (typeof l[p] === "object") {
                        u.push(p + "=" + l[p].join(":"))
                    } else if (l[p] === true) {
                        u.push(p)
                    } else {
                        u.push(p + "=" + l[p])
                    }
                    break
            }
        }
        u.push("dataEnv=" + s);
        u.push("locale=" + r);
        if (s === WH.dataEnv.PTR || s === WH.dataEnv.PTR2 || s === WH.dataEnv.BETA) {
            if (WH.getDataCacheVersion(s) !== "0") {
                u.push(WH.getDataCacheVersion(s))
            }
        }
        if (!ct.isLoaded(t, s)) {
            ct.fetch(t, s)
        }
        if (t === i.ITEM && l && l.hasOwnProperty("lvl") && !ct.isLoaded(i.SPELL, s)) {
            ct.fetch(i.SPELL, s)
        }
        let h = u.length ? "?" + u.join("&") : "";
        let g = WH.isDataEnvRestricted(WH.getDataEnv()) || WH.isEntityRestricted(t) ? Se() : Se(n);
        let m = g + "/tooltip/" + fe(t) + "/" + a + h;
        WH.xhrJsonRequest(m, function (t, a, i, n, s, r) {
            if (!r) {
                WH.error("Wowhead tooltips failed to load entity data.", de(t) + " #" + a);
                return
            } else if (r.error) {
                if (!G.includes(t)) {
                    WH.error("Wowhead tooltip request responded with an error.", r.error, de(t) + " #" + a)
                }
            }
            e.register(t, i, n, s, r)
        }.bind(null, t, a, c, s, r))
    }

    function Ye(a, n, r) {
        if (n && a.dataset && a.dataset.simpleTooltip) {
            if (!$ && !a.onmouseout) {
                if (a.dataset.tooltipMode ? a.dataset.tooltipMode === "cursor" : getComputedStyle(a).display === "inline") {
                    a.onmousemove = Ne
                }
                a.onmouseout = Pe
            }
            Ze(a, a.dataset.simpleTooltip.length < 30 ? '<div class="no-wrap">' + a.dataset.simpleTooltip + "</div>" : a.dataset.simpleTooltip);
            return D
        }
        if (a.nodeName !== "A" && a.nodeName !== "AREA") {
            return M
        }
        var o = a.rel;
        try {
            if (a.dataset && a.dataset.hasOwnProperty("wowhead")) {
                o = a.dataset.wowhead
            } else if (a.getAttribute && a.getAttribute("data-wowhead")) {
                o = a.getAttribute("data-wowhead")
            }
        } catch (e) {
        }
        let l = a.href;
        if (!l.length && !o || o && /^np\b/.test(o) || a.getAttribute("data-disable-wowhead-tooltip") === "true" || $ && a.getAttribute("data-disable-wowhead-touch-tooltip") === "true") {
            return O
        }
        if (/(?:^|\.)wow(?:classic)?db\.com$/.test(new URL(l).hostname)) {
            return O
        }
        let c = /^https?:\/\/(?:[^/]+\.)?(classic|tbc)\.(?:[^/]+\.)?wowhead\.com\/talent-calc\/embed\/[^#]+/;
        let f = l.match(c);
        if (!f) {
            f = l.match(/^https?:\/\/(?:[^/]+\.)?wowhead\.com\/(classic|tbc|wotlk)\/talent-calc\/embed\/[^#]+/)
        }
        if (WH.REMOTE && f) {
            let e = 513;
            let t = 750;
            if (f[1] === "tbc") {
                e += 120
            } else if (f[1] === "wotlk") {
                e += 517
            }
            let i = e / t * 100 + "%";
            a.parentNode.replaceChild(WH.ce("div", {
                style: {
                    margin: "10px auto",
                    maxHeight: e + "px",
                    maxWidth: t + "px"
                }, className: "wowhead-embed wowhead-embed-talent-calc"
            }, WH.ce("div", {
                style: {
                    height: 0,
                    paddingTop: i,
                    position: "relative",
                    width: "100%"
                }
            }, WH.ce("iframe", {
                src: f[0],
                width: "100%",
                height: "100%",
                style: {border: 0, left: 0, position: "absolute", top: 0, borderRadius: "6px"},
                sandbox: "allow-scripts allow-top-navigation"
            }))), a);
            return D
        }
        let p = /^https?:\/\/(?:[^/]+\.)?wowhead\.com\/soulbind-calc\/embed\/.+/;
        let h = l.match(p);
        if (WH.REMOTE && h) {
            a.parentNode.replaceChild(WH.ce("div", {
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
                src: h[0],
                width: "100%",
                height: "100%",
                style: {border: 0, left: 0, position: "absolute", top: 0, borderRadius: "6px"},
                sandbox: "allow-scripts allow-top-navigation"
            }))), a);
            return D
        }
        if (WH.REMOTE) {
            let e = /^https?:\/\/(?:[^/]+\.)?wowhead\.com\/(?:(?:ptr|ptr-2|beta)\/)?(?:\w\w\/)?talent-calc\/embed\/.+/;
            let t = l.match(e);
            if (t) {
                a.parentNode.replaceChild(WH.ce("div", {
                    style: {minHeight: "680px", position: "relative"},
                    className: "wowhead-embed wowhead-embed-talent-calc"
                }, WH.ce("div", {
                    style: {
                        width: "100%",
                        height: 0,
                        paddingTop: "calc(55.555% + 200px)"
                    }
                }, WH.ce("iframe", {
                    src: t[0],
                    width: "100%",
                    height: "100%",
                    style: {border: 0, left: 0, position: "absolute", top: 0, borderRadius: "6px"},
                    sandbox: "allow-scripts allow-top-navigation"
                }))), a);
                return D
            }
        }
        let g = /^https?:\/\/(?:[^/]+\.)?wowhead\.com\/diablo-2\/skill-calc\/embed\/.+/;
        let m = l.match(g);
        if (WH.REMOTE && m) {
            a.parentNode.replaceChild(WH.ce("div", {
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
            }))), a);
            return D
        }
        let W = {};
        z.show.params = W;
        let _;
        let y;
        let A;
        let x;
        if (l.startsWith("https://") || l.startsWith("http://")) {
            let e = l.match(C);
            if (!e) {
                e = l.match(R)
            }
            if (e) {
                _ = e[1];
                y = e[2];
                A = e[3];
                x = l
            } else {
                L.some((e => {
                    let t = l.match(e.regexGuidePaths) || u[e.treeId].length && (l.match(e.regexFrontPaths) || l.match(e.regexLegacyPaths));
                    if (t) {
                        _ = (t[1] ? t[1] + "." : "") + WH.getDataEnvKey(e.envId);
                        y = (e.prefixedDetailPageNames.includes(t[2]) ? e.detailPagePrefix : "") + t[2];
                        A = t[3];
                        x = l
                    }
                    return !!t
                }))
            }
            z.show.hasLogo = false
        }
        if (o && (!y || /\bignore-url\b/.test(o))) {
            let e = [];
            L.forEach((t => {
                if (t.prefixedDetailPageNames.length) {
                    e = e.concat(t.prefixedDetailPageNames.map((e => t.detailPagePrefix + e)))
                } else {
                    e = e.concat(u[t.treeId])
                }
            }));
            e = [...new Set(e)];
            let t = o.match(new RegExp("(" + e.join("|") + ").?(-?\\d+(?:\\.\\d+)?)"));
            if (t) {
                y = t[1];
                A = t[2]
            }
            z.show.hasLogo = true
        }
        if (!y) {
            return O
        }
        let k = w[y];
        if ($ && !r && !Ce(k)) {
            return O
        }
        l.replace(b, ((e, t, a) => Be(W, t, a) || ""));
        if (o) {
            o.replace(b, ((e, t, a) => Be(W, t, a) || ""))
        }
        Je(a);
        let U = Fe(_, W.domain);
        let G = U.dataEnv;
        let q = U.locale;
        let V = WH.Types.getRequiredTrees(k) || [];
        if (V.length && !V.includes(WH.getDataTree(G))) {
            G = WH.getRootByTree(V[0])
        }
        let K = t.getByEnv(G);
        if (K === t.WOW) {
            if (W.gems && W.gems.length > 0) {
                var j;
                for (j = Math.min(3, W.gems.length - 1); j >= 0; --j) {
                    if (parseInt(W.gems[j])) {
                        break
                    }
                }
                ++j;
                if (j === 0) {
                    delete W.gems
                } else if (j < W.gems.length) {
                    W.gems = W.gems.slice(0, j)
                }
            }
            var Q = ["bonus", "gem1bonus", "gem2bonus", "gem3bonus"];
            for (var J = 0, X; X = Q[J]; J++) {
                if (W[X] && W[X].length > 0) {
                    for (j = Math.min(16, W[X].length - 1); j >= 0; --j) {
                        if (parseInt(W[X][j])) {
                            break
                        }
                    }
                    ++j;
                    if (j === 0) {
                        delete W[X]
                    } else if (j < W[X].length) {
                        W[X] = W[X].slice(0, j)
                    }
                }
            }
            if (W["crafted-stats"] && W["crafted-stats"].length > 0) {
                let e = [];
                for (let t = 0; t < Math.min(2, W["crafted-stats"].length); t++) {
                    let a = parseInt(W["crafted-stats"][t]);
                    if (!isNaN(a)) {
                        e.push(a)
                    }
                }
                if (e.length === 0) {
                    delete W["crafted-stats"]
                } else {
                    W["crafted-stats"] = e
                }
            }
            if (W.abil && W.abil.length > 0) {
                var j, Z = [], ee;
                for (j = 0; j < Math.min(8, W.abil.length); j++) {
                    if (ee = parseInt(W.abil[j])) {
                        Z.push(ee)
                    }
                }
                if (Z.length === 0) {
                    delete W.abil
                } else {
                    W.abil = Z
                }
            }
            if (W.alt && W.alt.length > 0) {
                W.alt = W.alt.map((e => parseInt(e))).filter((e => !isNaN(e)));
                if (W.alt.length === 0) {
                    delete W.alt
                }
            }
            if (W.rewards && W.rewards.length > 0) {
                var j;
                for (j = Math.min(3, W.rewards.length - 1); j >= 0; --j) {
                    if (/^\d+.\d+$/.test(W.rewards[j])) {
                        break
                    }
                }
                ++j;
                if (j === 0) {
                    delete W.rewards
                } else if (j < W.rewards.length) {
                    W.rewards = W.rewards.slice(0, j)
                }
            }
        }
        if (l.indexOf("#") !== -1 && document.location.href.indexOf(y + "=" + A) !== -1) {
            return O
        }
        z.show.mode = H;
        if ($ && !a.dataset.noTouchLightbox && document.documentElement.offsetWidth < s) {
            z.show.mode = I
        } else if (["iconmedium", "iconsmall", "iconlarge", "iconblizzard"].some((e => a.parentNode?.classList.contains(e))) || a.dataset.whattach === "icon" || a.dataset.tooltipMode === "icon") {
            z.show.mode = S
        } else {
            if ($ || a.dataset.whattach === "true" || a.dataset.tooltipMode === "attach") {
                z.show.mode = T
            } else if (!WH.REMOTE) {
                var te = a.parentNode;
                var ne = 0;
                while (te) {
                    if ((te.getAttribute && te.getAttribute("class") || "").indexOf("menu-inner") === 0) {
                        z.show.mode = v;
                        break
                    }
                    ne++;
                    if (ne > 9) {
                        break
                    }
                    te = te.parentNode
                }
            }
            if (z.show.mode === H && getComputedStyle(a).display !== "inline") {
                z.show.mode = T
            }
        }
        if (!$ && !a.onmouseout) {
            if (z.show.mode === H) {
                a.onmousemove = Ne
            }
            a.onmouseout = Pe
        }
        if (z.show.mode === H && a.dataset.whtticon === "false") {
            z.show.mode = E
        }
        if (!WH.REMOTE && !a.whContextMenuAttached) {
            a.whContextMenuAttached = true;
            WH.aE(a, "contextmenu", Oe.bind(a, G, q, k, y, A, W, x))
        }
        if (n) {
            z.initiatedByUser = true;
            lt(n);
            z.showingTooltip = true;
            ae(k, A, G, q, W)
        }
        if (n || !He()) {
            return D
        }
        let se = e.getEntity(k, ue(k, A, W), G, q);
        var re = [];
        if (We("renameLinks") && a.getAttribute("data-wh-rename-link") !== "false" || a.getAttribute("data-wh-rename-link") === "true") {
            re.push((function () {
                delete a.dataset.whIconAdded;
                a.innerHTML = "<span>" + se.data.name + "</span>"
            }))
        }
        var oe = a.getAttribute("data-wh-icon-size");
        let le = a.dataset.whIconizeLink;
        if ((le === "true" || oe || We("iconizeLinks")) && le !== "false" && d.includes(k)) {
            if (!oe) {
                oe = We("iconSize")
            }
            re.push((function () {
                if (ie(se.data) && a.dataset.whIconAdded !== "true") {
                    be(a, k, se.data, oe)
                }
            }))
        }
        if (We("colorLinks")) {
            switch (K) {
                case t.D4:
                    re.push((() => {
                        if (se.data.quality != null) {
                            Y(a, ["d4-q" + se.data.quality])
                        }
                    }));
                    break;
                case t.DI:
                    switch (k) {
                        case i.DI_EQUIP_ITEM:
                        case i.DI_MISC_ITEM:
                            re.push((() => {
                                if (se.data.inventoryColor != null) {
                                    Y(a, ["di-ic" + se.data.inventoryColor])
                                }
                                if (se.data.dropRank != null) {
                                    Y(a, ["q" + se.data.dropRank])
                                }
                            }));
                            break;
                        case i.DI_SET:
                            re.push((() => {
                                if (se.data.inventoryColor != null) {
                                    Y(a, ["di-ic" + se.data.inventoryColor])
                                }
                            }));
                            break
                    }
                    break;
                case t.WOW:
                    re.push((() => {
                        if (se.data.quality != null && se.data.quality > -1) {
                            Y(a, ["q" + se.data.quality])
                        }
                    }));
                    break
            }
        }
        if (re.length) {
            if (se.status === N || se.status === P) {
                se.callbacks.push((() => {
                    if (se.status !== B) {
                        re = []
                    }
                    while (re.length) {
                        re.shift()()
                    }
                }));
                if (se.status === N) {
                    ze(k, A, G, q, true, W)
                }
            } else if (se.status === B || se.status === F) {
                while (re.length) {
                    re.shift()()
                }
            }
        }
        return D
    }

    function Ve(t, a, i, n) {
        z.elements.tooltip.style.width = "550px";
        z.elements.tooltip.style.left = "-2323px";
        z.elements.tooltip.style.top = "-2323px";
        z.elements.tooltip.className = "wowhead-tooltip";
        if (t.nodeName) {
            WH.ee(z.elements.tooltipTd);
            WH.ae(z.elements.tooltipTd, t)
        } else {
            if (z.elements.tooltip.dataset.game === "wow") {
                t = e.evalFormulas(t)
            }
            z.elements.tooltipTd.innerHTML = t
        }
        z.elements.tooltip.style.display = "";
        Xe(z.elements.tooltip, true);
        ne(z.elements.tooltip, true);
        if (a) {
            z.showSecondary = true;
            z.elements.tooltip2.style.width = "550px";
            z.elements.tooltip2.style.left = "-2323px";
            z.elements.tooltip2.style.top = "-2323px";
            if (a.nodeName) {
                WH.ee(z.elements.tooltipTd2);
                WH.ae(z.elements.tooltipTd2, a)
            } else {
                z.elements.tooltipTd2.innerHTML = e.evalFormulas(a)
            }
            z.elements.tooltip2.style.display = "";
            ne(z.elements.tooltip2, true)
        } else {
            z.showSecondary = false
        }
        if (WH.Device.isTouch()) {
            let e = z.showSecondary ? z.elements.tooltipTd2 : z.elements.tooltipTd;
            let t = WH.ce("a");
            t.href = "javascript:";
            t.className = "wowhead-touch-tooltip-closer";
            t.onclick = WH.Tooltips.clearTouchTooltip;
            WH.ae(e, t)
        }
        z.elements.tooltipTable.style.display = t == "" ? "none" : "";
        J(i, n);
        re("show")
    }

    function Ke(e, t, a) {
        Qe(z.elements.icon, e ? {icon: e} : undefined, t, a)
    }

    function je(e) {
        let t;
        if (e.showIcon !== false) {
            if (ie(e.entity)) {
                t = e.entity
            } else if (e.iconName) {
                t = {icon: e.iconName}
            }
        }
        Qe(z.elements.icon, t, e.type, e.dataEnv)
    }

    function Qe(e, t, a, i) {
        WH.ee(e);
        if (f[a]?.embeddedIcons) {
            t = undefined
        }
        if (ie(t)) {
            WH.ae(e, WH.WHIcon.createByEntity(t, a, null, {dataEnv: i, size: WH.WHIcon.MEDIUM}));
            e.style.visibility = "visible";
            z.showIcon = true
        } else {
            e.style.visibility = "hidden";
            z.showIcon = false
        }
    }

    function Je(e) {
        rt();
        z.element = e;
        z.triggeringElementVisibilityObserver ??= new ResizeObserver((() => {
            if (!(z.element.offsetWidth || z.element.offsetHeight || z.element.getClientRects().length)) {
                Ie()
            }
        }));
        z.triggeringElementVisibilityObserver.observe(e);
        z.triggeringElementRemovalObserver ??= new MutationObserver((() => {
            if (z.element && !document.body.contains(z.element)) {
                Ie()
            }
        }));
        z.triggeringElementRemovalObserver.observe(document.body, {childList: true, subtree: true})
    }

    function Xe(e, t) {
        if (t) {
            e.setAttribute("data-visible", "yes");
            e.style.visibility = "visible"
        } else {
            e.setAttribute("data-visible", "no");
            e.style.visibility = "hidden"
        }
    }

    function Ze(e, t, a, i) {
        if (t == null || !z.enabled) {
            return
        }
        i = i || {};
        if (!i.padX || i.padX < 1) i.padX = 1;
        if (!i.padY || i.padY < 1) i.padY = 1;
        if (a) {
            t = ot(t, a)
        }
        let n = e.getBoundingClientRect();
        Ge(i.dataEnv, i.type, WH.isElementPositionFixedOrSticky(e), undefined, i.status);
        je(i);
        Ve(t, i.finalContent2, i.image, i.imageClass);
        Le(n.left + window.scrollX, n.top + window.scrollY, n.width, n.height, i.padX, i.padY)
    }

    function et(e, t, a, i) {
        if (t == null || !z.enabled) {
            return
        }
        i = i || {};
        if (!i.padX || i.padX < 10) i.padX = 10;
        if (!i.padY || i.padY < 10) i.padY = 10;
        if (a) {
            t = ot(t, a);
            if (i.finalContent2) {
                i.finalContent2 = ot(i.finalContent2, a)
            }
        }
        Ge(i.dataEnv, i.type, e.target && WH.isElementPositionFixedOrSticky(e.target), undefined, i.status);
        je(i);
        Ve(t, i.finalContent2, i.image, i.imageClass);
        Le(e.pageX, e.pageY, 0, 0, i.padX || 0, i.padY || 0)
    }

    function tt(e, t, a, i) {
        if (e == null || !z.enabled) {
            return
        }
        i = i || {};
        Ge(i.dataEnv, i.type, i.fixedPosition, undefined, i.status);
        je(i);
        Ve(e, i.finalContent2, i.image, i.imageClass);
        Le(t, a, 0, 0, i.padX || 0, i.padY || 0)
    }

    function at(e, t, a) {
        WH.Tooltips.clearTouchTooltip(true);
        if (t == null || !z.enabled) {
            return
        }
        a = a || {};
        qe();
        WH.ee(z.elements.screenCaption);
        let i = WH.ce("a", {
            innerHTML: WH.isRemote() ? "Tap Link" : WH.TERMS.taplink, onclick: function (e, t) {
                e.setAttribute("data-disable-wowhead-tooltip", "true");
                if (e.fireEvent) {
                    e.fireEvent("on" + t)
                } else if (typeof MouseEvent == "function") {
                    e.dispatchEvent(new MouseEvent(t, {bubbles: true, cancelable: true}))
                } else {
                    let a = document.createEvent("Events");
                    a.initEvent(t, true, true);
                    e.dispatchEvent(a)
                }
                if (e) {
                    e.removeAttribute("data-disable-wowhead-tooltip")
                }
                WH.Tooltips.clearTouchTooltip()
            }.bind(null, e, "click")
        });
        let n = WH.ce("i", {className: "fa fa-hand-point-up"});
        WH.aef(i, n);
        WH.ae(z.elements.screenCaption, i);
        Ge(a.dataEnv, a.type, false, z.elements.screenInnerBox, a.status);
        je(a);
        Ve(t, a.finalContent2, a.image, a.imageClass);
        Le()
    }

    function it(t, a, i, n) {
        if (z.show.type === t && z.show.fullId === a && z.show.dataEnv === i && z.show.locale === n) {
            nt(P, n, me(n, "loading"));
            let s = e.getEntity(t, a, i, n);
            s.timer = setTimeout(Re.bind(this, t, a, i, n), 3850)
        }
    }

    function nt(t, n, s, r, d, f, u, p, h, g) {
        ye();
        if (!z.initiatedByUser) {
            return
        }
        if (z.element) {
            if (z.element._fixTooltip) {
                s = z.element._fixTooltip(s, z.show.type, z.show.fullId, z.element)
            }
            if (z.element._fixTooltip2) {
                p = z.element._fixTooltip2(p, z.show.type, z.show.fullId, z.element)
            }
        }
        if (!s) {
            s = me(n, "notFound").replace("%s", de(z.show.type));
            t = k;
            d = a.UNKNOWN
        } else if (z.show.params) {
            let e = z.show.params;
            if (e.forg) {
                s = K(s, e.forg)
            }
            let t = [];
            if (e.spec) {
                s = WH.Tooltips.parseItemEffectTooltipForSpec(s, e.spec);
                let a = WH.specToSpells[parseInt(e.spec)];
                if (a) {
                    t = t.concat(...a)
                }
            }
            if (e.hero) {
                s = WH.Tooltips.parseItemEffectTooltipForHero(s, e.hero)
            }
            if (e.pcs && e.pcs.length) {
                var W = 0;
                for (var _ = 0, b = e.pcs.length; _ < b; ++_) {
                    var w;
                    var y = new RegExp("<span>\x3c!--si([0-9]+:)*" + e.pcs[_] + "(:[0-9]+)*--\x3e" + '<a href="[^"]*?/item=(\\d+)[^"]*">(.+?)</a></span>');
                    if (w = s.match(y)) {
                        let t = !isNaN(parseInt(z.show.locale)) ? m[z.show.locale] : "enus";
                        var A = WH.isSet("g_items") && g_items[e.pcs[_]] ? g_items[e.pcs[_]]["name_" + t] : w[4];
                        let a = WH.REMOTE ? "javascript:" : WH.Entity.getUrl(WH.Types.ITEM, w[3]);
                        var C = '<a href="' + a + '">' + A + "</a>";
                        var R = '<span class="q13">\x3c!--si' + e.pcs[_] + "--\x3e" + C + "</span>";
                        s = s.replace(w[0], R);
                        ++W
                    }
                }
                if (W > 0) {
                    s = s.replace("(0/", "(" + W + "/");
                    s = s.replace(new RegExp("<span>\\(([0-" + W + "])\\)", "g"), '<span class="q2">($1)')
                }
            }
            if (e.lvl && !e.ilvl) {
                s = WH.setTooltipLevel(s, e.lvl ? e.lvl : WH.getWowMaxLevel(), e.buff)
            }
            if (e.know) {
                t = t.concat(e.know)
            }
            if (e.spellModifier) {
                t = t.concat(e.spellModifier)
            }
            if (e.covenant) {
                t.push(o[e.covenant])
            }
            s = WH.setTooltipSpells(s, t, u);
            if (e.who && e.when) {
                s = s.replace("<table><tr><td><br />", '<table><tr><td><br /><span class="q2">' + WH.sprintf(me(n, "achievementComplete"), e.who, e.when.getMonth() + 1, e.when.getDate(), e.when.getFullYear()) + "</span><br /><br />");
                s = s.replace(/class="q0"/g, 'class="r3"')
            }
            if (e.notip && h) {
                s = "";
                d = undefined
            }
            if (z.show.type === i.BATTLE_PET_ABILITY && e.pwr) {
                s = s.replace(/<!--sca-->(\d+)<!--sca-->/g, (function (t, a) {
                    return Math.floor(parseInt(a) * (1 + .05 * e.pwr))
                }))
            }
            if (z.show.type === i.ACHIEVEMENT && e.cri) {
                for (var _ = 0; _ < e.cri.length; _++) {
                    s = s.replace(new RegExp("\x3c!--cr" + parseInt(e.cri[_]) + ":[^<]+", "g"), '<span class="q2">$&</span>')
                }
            }
        }
        if (z.showCharacterCompletion && window.g_user && (WH.isRetailTree(z.show.dataEnv) && g_user.lists || !WH.isRetailTree(z.show.dataEnv) && g_user.characterProfiles && g_user.characterProfiles.length)) {
            var L = "";
            let a = WH.isRetailTree(z.show.dataEnv) ? WH.User.Completion.getByType(z.show.type) : false;
            let n = e.getEntity(z.show.type, z.show.fullId, z.show.dataEnv, z.show.locale);
            if (a && z.show.type === i.QUEST) {
                if (t !== B || n.worldquesttype || n.daily || n.weekly) {
                    a = false
                }
            }
            let r = !(a && z.show.type in g_completion_categories && WH.inArray(g_completion_categories[z.show.type], n.completion_category) === -1);
            let o = /^-?\d+(?:\.\d+)?/.exec(z.show.fullId);
            o = o && o.length ? o[0] : z.show.fullId;
            if (a) {
                for (var M in g_user.lists) {
                    var O = g_user.lists[M];
                    if (!(O.id in a)) {
                        continue
                    }
                    let e = WH.inArray(a[O.id], o) !== -1;
                    if (!e && !r) {
                        continue
                    }
                    L += '<br><span class="progress-icon ' + (e ? "progress-8" : "progress-0") + '"></span> ';
                    L += O.character + " - " + O.realm + " " + O.region
                }
            }
            if (z.show.type === i.ACHIEVEMENT) {
                s = _e(s, z.show)
            }
            if (!WH.isRetailTree(z.show.dataEnv) && z.show.type === i.QUEST) {
                for (var D, _ = 0; D = g_user.characterProfiles[_]; _++) {
                    let e = WH.inArray(D.quests, o) !== -1;
                    if (!e && !r) {
                        continue
                    }
                    L += '<br><span class="progress-icon ' + (e ? "progress-8" : "progress-0");
                    L += '"></span> ' + D.name + " - " + D.realm
                }
            }
            if (WH.isRetailTree(z.show.dataEnv) && z.show.type === i.TRANSMOG_SET) {
                (g_user.lists || []).forEach((function (e) {
                    let t = WH.Wow.TransmogSet.getCompletionAmount(n.data.completionData || {}, e.id);
                    if (t > 0) {
                        L += '<br><span class="progress-icon progress-' + Math.max(1, Math.floor(t * 8)) + '"></span> ';
                        L += e.character + " - " + e.realm + " " + e.region
                    }
                }))
            }
            if (L !== "") {
                s += '<br><span class="q">' + WH.TERMS.completion + ":</span>" + L
            }
        }
        if (!WH.REMOTE && [i.TRANSMOG_SET, i.ITEM_SET].includes(z.show.type) && typeof WH.getPreferredTransmogRace !== "undefined") {
            let e = WH.getPreferredTransmogRace();
            let t = e.race;
            let a = e.gender - 1;
            let n = WH.ce("div", {innerHTML: s});
            let r = WH.qs("picture", n);
            if (r) {
                if (r.dataset.requiredRace && !z.element.dataset.tooltipIgnoreRequiredRace) {
                    t = r.dataset.requiredRace
                }
                let e = z.show.type === i.ITEM_SET ? WH.Wow.ItemSet : WH.Wow.TransmogSet;
                r.parentNode.replaceChild(WH.ce("img", {
                    src: e.getThumbUrl(z.show.fullId, t, a, z.show.dataEnv),
                    width: 260,
                    height: 440,
                    style: {display: "block", margin: "0 auto"}
                }), r);
                s = n.innerHTML
            }
        }
        if (!WH.REMOTE && s && (z.show.params.diff || z.show.params.diffnew || z.show.params.noimage)) {
            h = "";
            g = ""
        }
        s = s.replace("http://", "https://");
        if (z.show.params.map && f && f.getMap) {
            p = f.getMap()
        }
        let N = function (e, t, a) {
            if (z.show.type !== t.type || z.show.fullId !== t.fullId || z.show.dataEnv !== t.dataEnv || z.show.locale !== t.locale || z.show.params !== t.params) {
                return
            }
            switch (z.show.mode) {
                case I:
                    at(z.element, a, {
                        dataEnv: t.dataEnv,
                        entity: r,
                        iconName: d,
                        image: h,
                        imageClass: g,
                        showIcon: true,
                        status: e,
                        finalContent2: p,
                        type: t.type
                    });
                    break;
                case S:
                    Ze(z.element, a, undefined, {
                        dataEnv: t.dataEnv,
                        entity: r,
                        image: h,
                        imageClass: g,
                        showIcon: false,
                        status: e,
                        finalContent2: p,
                        type: t.type
                    });
                    break;
                case T:
                    Ze(z.element, a, undefined, {
                        dataEnv: t.dataEnv,
                        entity: r,
                        iconName: d,
                        image: h,
                        imageClass: g,
                        showIcon: true,
                        status: e,
                        finalContent2: p,
                        type: t.type
                    });
                    break;
                case v:
                    Ze(z.element, a, undefined, {
                        dataEnv: t.dataEnv,
                        entity: r,
                        iconName: d,
                        showIcon: true,
                        finalContent2: p,
                        type: t.type
                    });
                    break;
                case E:
                    tt(a, z.cursorX, z.cursorY, {
                        dataEnv: t.dataEnv,
                        entity: r,
                        fixedPosition: WH.isElementPositionFixedOrSticky(z.element),
                        image: h,
                        imageClass: g,
                        padX: l,
                        padY: c,
                        showIcon: false,
                        status: e,
                        finalContent2: p,
                        type: t.type
                    });
                    break;
                case H:
                default:
                    tt(a, z.cursorX, z.cursorY, {
                        dataEnv: t.dataEnv,
                        entity: r,
                        fixedPosition: WH.isElementPositionFixedOrSticky(z.element),
                        iconName: d,
                        image: h,
                        imageClass: g,
                        padX: l,
                        padY: c,
                        showIcon: true,
                        status: e,
                        finalContent2: p,
                        type: t.type
                    })
            }
            if (WH.REMOTE && z.elements.logo) {
                z.elements.logo.style.display = z.show.hasLogo ? "block" : "none"
            }
        };
        let P = {
            type: z.show.type,
            fullId: z.show.fullId,
            dataEnv: z.show.dataEnv,
            locale: z.show.locale,
            params: z.show.params
        };
        Ue(s, u, N.bind(this, t, P), P)
    }

    function st() {
        let t = e.getEntity(z.show.type, z.show.fullId, z.show.dataEnv, z.show.locale);
        if (G.includes(z.show.type) && !t.data[ve()]) {
            Pe();
            return
        }
        let a = t.data[he(z.show.params["image"])];
        let i = t.data["image" + z.show.params["image"] + "_class"];
        let n = ge(z.show.type, z.show.fullId, z.show.dataEnv);
        if (n) {
            a = n[0];
            i = n[1]
        }
        let s = t.data[ve(2)];
        let r = z.element?.dataset?.tooltip2Template;
        let o = r ? WH.ge(r) : null;
        if (o?.tagName === "TEMPLATE") {
            s = o.content.cloneNode(true)
        }
        nt(t.status, z.show.locale, t.data[ve()], t.data, t.data[pe()], t.data.map, t.data[Te()], s, a, i)
    }

    function rt() {
        z.triggeringElementRemovalObserver?.disconnect();
        z.triggeringElementVisibilityObserver?.disconnect();
        z.element = undefined
    }

    function ot(e, t) {
        let a = WH.ce("div", {className: t});
        if (typeof e === "string") {
            a.innerHTML = e
        } else {
            WH.ae(a, e)
        }
        return a
    }

    function lt(e) {
        z.cursorX = e.pageX;
        z.cursorY = e.pageY
    }

    window.Locale = window.Locale || {
        getId: function () {
            return 0
        }, getName: function () {
            return "enus"
        }
    };
    let ct = new function () {
        const e = this;
        let t = {loadedData: {}};
        var a = {};
        var s = {};
        var r = {};
        var o = {};
        this.fetch = function (e, t) {
            if (!o.hasOwnProperty(e) || o[e].hasOwnProperty(t)) {
                return
            }
            o[e][t] = P;
            a[e][t] = [];
            let i;
            if (WH.REMOTE) {
                i = Se(n) + s[e] + `&dataEnv=${t}`
            } else {
                i = WH.Url.getDataPageUrl(s[e].replace("/data/", ""), t)
            }
            i += "&json";
            WH.xhrJsonRequest(i, function (e, t, a) {
                if (!a) {
                    WH.error("Wowhead tooltips failed to load entity scaling data.", de(e));
                    return
                }
                ct.setData(e, t, a)
            }.bind(null, e, t))
        };
        this.getDataByKey = (e, a) => (t.loadedData[e] || {})[a];
        this.getSpellsBySpec = function (e, t) {
            let a = z.show.dataEnv || WH.getDataEnv();
            this.registerCallback(i.PLAYER_CLASS, a, (function () {
                var n = r[i.PLAYER_CLASS][a];
                var s = [];
                if (n.specMap.hasOwnProperty(e)) {
                    s = n["class"][n.specMap[e]].concat(n.spec[e] || [])
                }
                t(s)
            }))
        };
        this.isLoaded = function (e, t) {
            if (!o.hasOwnProperty(e)) {
                return true
            }
            if (o[e][t] === B) {
                c();
                return true
            }
            return false
        };
        this.registerCallback = function (t, i, n) {
            if (e.isLoaded(t, i)) {
                window.requestAnimationFrame(n);
                return
            }
            if (!a[t].hasOwnProperty(i)) {
                e.fetch(t, i)
            }
            a[t][i].push(n)
        };
        this.setData = function (e, t, i) {
            o[e][t] = B;
            a[e][t] = a[e][t] || [];
            r[e][t] = i;
            c();
            let n = a[e][t];
            while (n.length) {
                n.shift()()
            }
        };

        function l() {
            s[i.ITEM] = "/data/item-scaling";
            s[i.SPELL] = "/data/spell-scaling";
            s[i.PLAYER_CLASS] = "/data/spec-spells";
            for (var e in s) {
                if (!s.hasOwnProperty(e)) {
                    continue
                }
                o[e] = {};
                a[e] = {};
                r[e] = {}
            }
        }

        function c() {
            let e = z.show.dataEnv || WH.getDataEnv();
            let a = r[i.ITEM][e];
            if (a) {
                t.loadedData[i.ITEM] = a;
                WH.staminaFactor = a.staminaByIlvl;
                WH.convertRatingToPercent.RM = a.ratingsToPercentRM;
                WH.convertRatingToPercent.LT = a.ratingsToPercentLT;
                WH.convertScalingFactor.SV = a.itemScalingValue;
                WH.convertScalingFactor.SD = a.scalingFactors;
                WH.curvePoints = a.curvePoints;
                WH.applyStatModifications.ScalingData = a.scalingData;
                WH.contentTuningLevels = a.contentTuningLevels;
                WH.reforgeStats = a.reforgeStats ?? {};
                Object.values(WH.reforgeStats).forEach((e => {
                    e.s1 = WH.statToJson[e.i1];
                    e.s2 = WH.statToJson[e.i2]
                }))
            }
            let n = r[i.SPELL][e];
            if (n) {
                t.loadedData[i.SPELL] = n;
                WH.convertScalingSpell.SV = n.scalingValue;
                WH.convertScalingSpell.SpellInformation = n.spellInformation;
                WH.convertScalingSpell.RandPropPoints = n.randPropPoints
            }
        }

        l()
    };
    we()
};
window.$WowheadPower = new function () {
    this.refreshLinks = WH.Tooltips.refreshLinks;
    this.setScales = WH.Tooltips.setScales
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
            s(t)
        }))
    }

    function s(e) {
        if (!document.body) {
            if (i.bodyFrameWaitCount > t) {
                window.addEventListener("DOMContentLoaded", s.bind(this, e));
                return
            }
            i.bodyFrameWaitCount++;
            requestAnimationFrame(s.bind(this, e));
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
    this.getUrl = e => {
        let n = [i, t.getSubDirectory(), t.getBaseName(), a.getImageExtension()].join("");
        return e ? WH.Url.generatePrivate(n) : `${WH.STATIC_URL}${n}`
    };

    function s(e) {
        n.baseName = e
    }

    s.apply(this, arguments)
};
