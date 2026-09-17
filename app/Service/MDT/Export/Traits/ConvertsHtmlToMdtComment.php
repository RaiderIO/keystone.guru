<?php

namespace App\Service\MDT\Export\Traits;

trait ConvertsHtmlToMdtComment
{
    /**
     * Convert HTML to a format MDT understands:
     * - Replace <a href="...">...</a> with "(href)"
     * - Strip all remaining HTML tags.
     */
    private function convertHtmlToMdtComment(?string $html): string
    {
        $html ??= '';

        if ($html === '') {
            return '';
        }

        // Replace anchors with "(href)"
        $html = preg_replace_callback(
            '/<a\b[^>]*?href=(?:"([^"]+)"|\'([^\']+)\')[^>]*>.*?<\/a>/i',
            static function (array $matches): string {
                $href = $matches[1] !== '' ? $matches[1] : $matches[2];

                return sprintf('(%s)', $href);
            },
            $html,
        );

        // Strip any remaining HTML tags
        return trim(strip_tags((string)$html));
    }
}
