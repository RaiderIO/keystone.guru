<?php

namespace App\Logic\MDT\Entity;

enum MDTMapPOITemplate: string
{
    case LinkPin         = 'MapLinkPinTemplate';
    case DeathReleasePin = 'DeathReleasePinTemplate';
    // Ny'alotha spires
    case VignettePin  = 'VignettePinTemplate';
    case AnimatedLine = 'MDTAnimatedLineTemplate';
}
