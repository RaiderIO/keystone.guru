<?php

namespace App\Logic\MDT\Entity;

enum MDTMapPOIType: string
{
    case MapLink              = 'mapLink';
    case DungeonEntrance      = 'dungeonEntrance';
    case Graveyard            = 'graveyard';
    case GeneralNote          = 'generalNote';
    case Zoom                 = 'zoom';
    case IronDocksIronStar    = 'ironDocksIronStar';
    case NyalothaSpire        = 'nyalothaSpire';
    case TheUnderrotSkip      = 'tuSkip';
    case BrackenhideCage      = 'brackenhideCage';
    case BrackenhideCauldron  = 'brackenhideCauldron';
    case NeltharusChain       = 'neltharusChain';
    case NeltharusFood        = 'neltharusFood';
    case NeltharusShield      = 'neltharusShield';
    case NwItem               = 'nwItem';
    case AraKaraItem          = 'araKaraItem';
    case MistsItem            = 'mistsItem';
    case StonevaultItem       = 'stonevaultItem';
    case CotItem              = 'cityOfThreadsItem';
    case CinderbrewItemA      = 'brewItemA';
    case CinderbrewItemB      = 'brewItemB';
    case WorkshopItem         = 'workshopItem';
    case PrioryItem           = 'prioryItem';
    case MotherlodeItem       = 'motherlodeItem';
    case FloodgateItem        = 'floodgateItem';
    case EcoDomeAlDaniItem1   = 'EDAItem1';
    case EcoDomeAlDaniItem2   = 'EDAItem2';
    case EcoDomeAlDaniItem3   = 'EDAItem3';
    case TextFrame            = 'textFrame';
    case GenericItem          = 'genericItem';
    case GenericAssignablePOI = 'genericAssignablePOI';
}
