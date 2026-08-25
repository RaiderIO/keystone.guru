<?php

namespace App\Models\Spell;

/**
 * What kind of build-over-build change a {@see SpellTuningChange} row records.
 */
enum SpellTuningChangeType: string
{
    /** One number in the description moved; `value_index`/`kind` say which, old/new say how. */
    case ValueChanged = 'value_changed';

    /**
     * The description's numbers no longer line up with the previous build's (a value was added, removed
     * or the sentence was restructured), so the whole old and new text is recorded instead of pairs.
     */
    case DescriptionRewritten = 'description_rewritten';
}
