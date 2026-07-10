// @vitest-environment node
import fs from 'node:fs';
import {describe, expect, it} from 'vitest';
import {parsePhpTranslationFile} from './lang.mjs';

describe('parsePhpTranslationFile', () => {
    it('parsePhpTranslationFile_givenSimpleArray_returnsObject', () => {
        const source = `<?php

return [
    'title' => 'Keystone.guru',
    'count' => 5,
];
`;

        expect(parsePhpTranslationFile(source)).toEqual({title: 'Keystone.guru', count: 5});
    });

    it('parsePhpTranslationFile_givenNestedArraysAndEscapes_returnsNestedObject', () => {
        const source = `<?php
// Translations used by the menu
return array(
    'menu' => [
        'home' => "It's \\"here\\"",
        'items' => ['a', 'b'],
    ],
);
`;

        expect(parsePhpTranslationFile(source)).toEqual({
            menu: {
                home: 'It\'s "here"',
                items: ['a', 'b'],
            },
        });
    });

    it('parsePhpTranslationFile_givenRealTranslationFile_returnsNonEmptyObject', () => {
        // A real file from the repo, so the parser is exercised against production input
        const parsed = parsePhpTranslationFile(fs.readFileSync('lang/en_US/auth.php', 'utf8'));

        expect(typeof parsed).toBe('object');
        expect(Object.keys(parsed).length).toBeGreaterThan(0);
    });
});
