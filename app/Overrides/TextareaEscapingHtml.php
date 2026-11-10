<?php

namespace App\Overrides;

use Override;
use Spatie\Html\Elements\Textarea;
use Spatie\Html\Html;

/**
 * Spatie's textarea() writes its value (or the old input) as raw inner HTML - this one escapes it.
 */
class TextareaEscapingHtml extends Html
{
    /**
     * @param string|null $name
     * @param string|null $value
     */
    #[Override]
    public function textarea($name = null, $value = null): Textarea
    {
        $value = $this->old($name, $value);

        return parent::textarea($name)->value(e($value ?? ''));
    }
}
