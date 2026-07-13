<?php

namespace App\Logic\Utils;

use DOMDocument;
use DOMElement;
use DOMNode;
use Exception;

class HtmlSanitizer
{
    /**
     * @param  string|null $input
     * @param  bool        $convertLineEnding
     * @return string
     */
    public function sanitize(?string $input, bool $convertLineEnding = true): string
    {
        if (is_null($input) || empty($input)) {
            return $input ?? '';
        }

        $allowedTags    = config('keystoneguru.sanitize_text.allowed_tags');
        $allowedDomains = config('keystoneguru.sanitize_text.allowed_domains');

        if ($convertLineEnding) {
            $input = str_replace("\n", '<br>', $input);
            if (!in_array('br', $allowedTags)) {
                $allowedTags[] = 'br';
            }
        }

        // Use DOMDocument to parse the HTML
        $dom = new DOMDocument();
        // Use a wrapper to handle multiple root elements and avoid issues with HTML snippets
        // Use MB_CONVERT_ENCODING to handle UTF-8 correctly
        $libxml_previous_state = libxml_use_internal_errors(true);
        // We wrap it in a div to ensure we have a single root element to work with
        $dom->loadHTML('<div>' . mb_convert_encoding($input, 'HTML-ENTITIES', 'UTF-8') . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($libxml_previous_state);

        $wrapper = $dom->getElementsByTagName('div')->item(0);
        if ($wrapper instanceof DOMElement) {
            $this->sanitizeNode($wrapper, $allowedTags, $allowedDomains);

            // Extract the content from the wrapper
            $output = '';
            foreach ($wrapper->childNodes as $child) {
                $output .= $dom->saveHTML($child);
            }

            return $output;
        }

        return '';
    }

    /**
     * @param DOMNode            $element
     * @param array<int, string> $allowedTags
     * @param array<int, string> $allowedDomains
     */
    private function sanitizeNode(DOMNode $element, array $allowedTags, array $allowedDomains): void
    {
        $childNodes = iterator_to_array($element->childNodes);
        /** @var DOMNode $child */
        foreach ($childNodes as $child) {
            if ($child instanceof DOMElement) {
                $tagName = strtolower($child->tagName);

                if (!in_array($tagName, $allowedTags)) {
                    // Replace element with its text content
                    $textNode = $element->ownerDocument->createTextNode($child->textContent);
                    $element->replaceChild($textNode, $child);
                } else {
                    // Allowed tag, now check attributes
                    if ($tagName === 'a') {
                        $href = $child->getAttribute('href');
                        if (!empty($href)) {
                            try {
                                $url  = parse_url($href);
                                $host = $url['host'] ?? '';
                                if ($host !== '' && !in_array($host, $allowedDomains)) {
                                    // Invalid domain, replace with text
                                    $textNode = $element->ownerDocument->createTextNode($child->textContent);
                                    $element->replaceChild($textNode, $child);
                                    continue;
                                }
                            } catch (Exception) {
                                // Invalid URL, replace with text
                                $textNode = $element->ownerDocument->createTextNode($child->textContent);
                                $element->replaceChild($textNode, $child);
                                continue;
                            }
                        }
                    }

                    // Remove all attributes except href for <a>
                    $attributes = iterator_to_array($child->attributes);
                    foreach ($attributes as $attr) {
                        if (!($tagName === 'a' && $attr->name === 'href')) {
                            $child->removeAttribute($attr->name);
                        }
                    }

                    // Recursively sanitize children
                    $this->sanitizeNode($child, $allowedTags, $allowedDomains);
                }
            }
        }
    }
}
