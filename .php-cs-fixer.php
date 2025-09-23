<?php

use ErickSkrauch\PhpCsFixer\Fixers as ErickSkrauchFixers;

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
    // It legit breaks views if you let it try to fix them - it removes needed imports
    ->exclude(['vendor', 'node_modules', 'storage', 'bootstrap/cache', 'resources/views'])
    ->name('*.php')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true);

return (new PhpCsFixer\Config())
    ->registerCustomFixers(new ErickSkrauchFixers())
    ->setRiskyAllowed(true)
    ->setIndent("    ")
    ->setLineEnding("\n")
    ->setFinder($finder)
    ->setRules([
        // Arrays & commas
        'array_syntax'                => ['syntax' => 'short'],
        'trailing_comma_in_multiline' => [
            'after_heredoc' => true,
            'elements'      => ['arrays', 'arguments', 'parameters', 'match'],
        ],

        // Operators & alignment
        'binary_operator_spaces' => [
            'default'   => 'single_space',
            'operators' => [
                '=>' => 'align_single_space_minimal',
                '='  => 'align_single_space_minimal',
            ],
        ],
        'concat_space' => ['spacing' => 'one'],

        // Parentheses & function/call spacing
        'no_spaces_after_function_name' => true,
        'spaces_inside_parentheses'     => ['space' => 'none'],
        'function_declaration'          => [
            'closure_function_spacing'   => 'one',  // "function (...)"
            'closure_fn_spacing'         => 'none', // "fn(...)"
            'trailing_comma_single_line' => false,
        ],

        // Types & casts
        'type_declaration_spaces'        => ['elements' => ['property']],
        'cast_spaces'                    => ['space' => 'none'],
        'native_type_declaration_casing' => true,
        'lowercase_keywords'             => true,
        'constant_case'                  => ['case' => 'lower'],
        'lowercase_static_reference'     => true,

        // Braces placement
        'braces' => [
            'position_after_functions_and_oop_constructs' => 'next', // methods on next line
            'position_after_control_structures'           => 'same', // if (...) {
            'position_after_anonymous_constructs'         => 'same', // anonymous blocks same line
        ],

        // Imports
        'ordered_imports'             => ['sort_algorithm' => 'alpha'],
        'single_import_per_statement' => true,
        'single_line_after_imports'   => true,
        'no_unused_imports'           => true,

        // Indentation niceties (no wrapping)
        'method_chaining_indentation' => true,
        'method_argument_space'       => ['on_multiline' => 'ensure_fully_multiline'],
        'array_indentation'           => true,

        // Blank lines
        'blank_line_before_statement' => ['statements' => ['return', 'throw', 'try']],
        'no_extra_blank_lines'        => ['tokens' => ['use', 'curly_brace_block', 'extra', 'throw']],
        'no_whitespace_in_blank_line' => true,

        // PHPDoc
        'phpdoc_align'    => ['align' => 'vertical'],
        'phpdoc_scalar'   => true,
        'phpdoc_indent'   => true,
        'no_empty_phpdoc' => true,

        // Whitespace miscellany
        'trim_array_spaces'                   => true,
        'whitespace_after_comma_in_array'     => true,
        'no_whitespace_before_comma_in_array' => true,
        'no_trailing_whitespace'              => true,
        'single_blank_line_at_eof'            => true,

        // Misc readability
        'elseif' => true,

        // Custom fixers
        'ErickSkrauch/align_multiline_parameters' => [
            'variables' => true,   // align variable names
            'defaults'  => false,  // (optional) also align default values if you want
        ],
    ]);
