<?php

namespace App\Models\Interfaces;

interface CombatLogCriterionModelInterface
{
    /**
     * Returns the display name for this model in the context of combat log parsing criteria.
     * By default, implementations translate $this->name as a lang key.
     */
    public function getName(): string;

    /**
     * Returns a URL to an image representing this model, or null if none is available.
     */
    public function getImageLink(): ?string;
}
