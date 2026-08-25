<?php

namespace App\Overrides;

use Illuminate\Translation\MessageSelector as BaseMessageSelector;
use Override;

class AiLocaleMessageSelector extends BaseMessageSelector
{
    /**
     * The `*_ai` locales (e.g. `ru_RU_ai`) are machine translations of a base locale
     * (`ru_RU`) that Illuminate\Translation\MessageSelector::getPluralIndex() does not
     * recognize, so it falls through to `default: return 0` and only the singular
     * segment is ever selected. Stripping the `_ai` suffix hands the parent the base
     * locale whose plural rules the translations actually follow.
     */
    #[Override]
    public function getPluralIndex($locale, $number): int
    {
        return parent::getPluralIndex(preg_replace('/_ai$/', '', (string)$locale), $number);
    }
}
