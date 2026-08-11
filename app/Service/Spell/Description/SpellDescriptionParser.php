<?php

namespace App\Service\Spell\Description;

use App\Service\Spell\Description\Dtos\RenderedSpellDescription;
use App\Service\Spell\Description\Dtos\SpellDescriptionValue;
use App\Service\Spell\Description\Dtos\SpellDescriptionValueKind;

/**
 * Turns a game client spell description template into readable text.
 *
 * The templates are a small language of their own, e.g.
 *
 *     Stab the target, causing ${$s2*$<mult>} Physical damage. Damage increased by $s4% when you are
 *     behind your target$?s319949[, and critical strikes apply Find Weakness for $319949s1 sec][].
 *
 * Supported: effect values (`$s1`, `$w1`, `$m1`, `$M1`, `$o1`), periods (`$t1`), radii (`$a1`, `$A1`),
 * chain targets (`$x1`), durations (`$d`, `$D`), the same with a spell id prefix (`$319949s1`), inline
 * arithmetic (`${...}`), named variables (`$<mult>`), cross-spell name and description inserts
 * (`$@spellname123`, `$@spelldesc123`), conditionals (`$?s123[...][...]`), plural and gender macros
 * (`$lpoint:points;`, `|4ally:allies;`, `$ghe:she;`) and the UI escape codes (`|cFFFFFFFF...|r`).
 *
 * The result is not one finished sentence but a format string plus the numbers that go in it, each
 * tagged with what it means - so damage and healing, which are coefficients of the content the caster
 * belongs to, can be recalculated per key level without re-parsing anything (#3951).
 *
 * Conditionals are player state - whether the viewer knows a talent, has an aura, plays a class, is in
 * a given difficulty - which we have no answer for while rendering up front. They therefore render
 * their false branch, matching what Wowhead shows a logged out visitor.
 */
class SpellDescriptionParser implements SpellDescriptionParserInterface
{
    /** Guards `$@spelldescN` and `$<var>` chains from referring to each other in a circle. */
    private const int MAX_RECURSION_DEPTH = 5;

    /** Description tokens whose letter is two characters long, mapped onto the token they behave as. */
    private const array ALIAS_TOKENS = ['sw' => 's'];

    /**
     * Tokens that describe the spell as a whole rather than one of its effects, and so never carry an
     * effect index - the `1` in "$d1" is text that follows the token, not part of it.
     */
    private const array SPELL_WIDE_TOKENS = ['d', 'D'];

    /**
     * The game stores creature damage and healing in tenths of the content's expected damage, so a
     * coefficient is divided by this before the content's multiplier is applied.
     */
    private const float COEFFICIENT_SCALE = 10.0;

    public function parse(
        SpellDescriptionContextInterface $context,
        int                              $spellId,
        string                           $template,
        float                            $damageMultiplier = 0.0,
    ): RenderedSpellDescription {
        $builder    = new SpellDescriptionBuilder();
        $lastNumber = null;

        $this->render($context, $spellId, $this->normalizeNewlines($template), 0, $lastNumber, $builder, $damageMultiplier);

        return $builder->build();
    }

    /**
     * @param float|null $lastNumber the most recently rendered number, which the plural macros that
     *                               follow it refer to
     */
    private function render(
        SpellDescriptionContextInterface $context,
        int                              $spellId,
        string                           $template,
        int                              $depth,
        ?float                           &                           $lastNumber,
        SpellDescriptionBuilder          $builder,
        float                            $damageMultiplier,
    ): void {
        $offset = 0;
        $length = strlen($template);

        while ($offset < $length) {
            $character = $template[$offset];

            if ($character === '$') {
                $this->renderDollarToken($context, $spellId, $template, $offset, $depth, $lastNumber, $builder, $damageMultiplier);
            } elseif ($character === '|') {
                $this->renderEscapeCode($template, $offset, $lastNumber, $builder);
            } else {
                $builder->appendText($character);
                $offset++;
            }
        }
    }

    /**
     * Render whatever `$` introduces at `$offset`, advancing `$offset` past it.
     */
    private function renderDollarToken(
        SpellDescriptionContextInterface $context,
        int                              $spellId,
        string                           $template,
        int                              &                              $offset,
        int                              $depth,
        ?float                           &                           $lastNumber,
        SpellDescriptionBuilder          $builder,
        float                            $damageMultiplier,
    ): void {
        $next = $template[$offset + 1] ?? '';

        // An escaped dollar sign
        if ($next === '$') {
            $offset += 2;
            $builder->appendText('$');

            return;
        }

        if ($next === '{') {
            $offset += 2;
            $expression = $this->readUntilClosingBrace($template, $offset);
            $kind       = SpellDescriptionValueKind::Value;

            $value = $expression === null
                ? null
                : $this->evaluateExpression($context, $spellId, $expression, $depth, $kind);

            if ($value !== null) {
                $lastNumber = abs($value);
                $this->appendNumber($builder, $lastNumber, $kind, $damageMultiplier);
            }

            return;
        }

        if ($next === '<') {
            $offset += 2;
            $variableName = $this->readUntil($template, $offset, '>');
            $kind         = SpellDescriptionValueKind::Value;

            $value = $variableName === null
                ? null
                : $this->resolveVariable($context, $spellId, $variableName, $depth, $kind);

            if ($value !== null) {
                $lastNumber = abs($value);
                $this->appendNumber($builder, $lastNumber, $kind, $damageMultiplier);
            }

            return;
        }

        if ($next === '?') {
            $offset += 2;
            $branch = $this->readConditionalBranch($context, $spellId, $template, $offset, $depth);

            if ($branch !== null) {
                $this->render($context, $spellId, $branch, $depth, $lastNumber, $builder, $damageMultiplier);
            }

            return;
        }

        if ($next === '@') {
            $offset += 2;
            $this->renderFunction($context, $template, $offset, $depth, $builder, $damageMultiplier);

            return;
        }

        // $lsingular:plural; and $ghe:she; - not a value but a choice between two words
        if ($next === 'l' || $next === 'L' || $next === 'g' || $next === 'G') {
            $macroOffset = $offset + 2;
            $macro       = $this->readUntil($template, $macroOffset, ';');

            if ($macro !== null && str_contains($macro, ':')) {
                $offset = $macroOffset;
                $builder->appendText($this->chooseMacroOption($macro, $next === 'l' || $next === 'L' ? $lastNumber : null));

                return;
            }
        }

        $this->renderValueToken($context, $spellId, $template, $offset, $lastNumber, $builder, $damageMultiplier);
    }

    /**
     * Render `$s1`, `$319949s1`, `$d`, `$t2`, ... - an optional spell id, a one or two letter token, and
     * an optional effect index.
     */
    private function renderValueToken(
        SpellDescriptionContextInterface $context,
        int                              $spellId,
        string                           $template,
        int                              &                              $offset,
        ?float                           &                           $lastNumber,
        SpellDescriptionBuilder          $builder,
        float                            $damageMultiplier,
    ): void {
        $pattern = sprintf('/\G\$(\d*)(%s|[a-zA-Z])(\d{0,2})/', implode('|', array_keys(self::ALIAS_TOKENS)));

        if (preg_match($pattern, $template, $matches, 0, $offset) !== 1) {
            // Not a token at all - drop the dollar sign and carry on
            $offset++;

            return;
        }

        $offset += strlen($matches[0]);

        $token = self::ALIAS_TOKENS[$matches[2]] ?? $matches[2];

        // "for $d1 sec" is a duration followed by a 1, not effect 1's duration
        if ($matches[3] !== '' && in_array($token, self::SPELL_WIDE_TOKENS, true)) {
            $offset -= strlen($matches[3]);
            $matches[3] = '';
        }

        $referencedSpellId = $matches[1] === '' ? $spellId : (int)$matches[1];
        $effectIndex       = $matches[3] === '' ? 0 : max(0, (int)$matches[3] - 1);
        $kind              = SpellDescriptionValueKind::Value;

        $value = $this->resolveValueToken($context, $referencedSpellId, $token, $effectIndex, $kind);

        if ($value === null) {
            return;
        }

        $lastNumber = $value;

        if ($kind === SpellDescriptionValueKind::Duration) {
            $builder->appendValue(new SpellDescriptionValue($kind, $this->formatDuration($value)));

            return;
        }

        $this->appendNumber($builder, $value, $kind, $damageMultiplier, $referencedSpellId, $effectIndex);
    }

    /**
     * Record a number, keeping the coefficient behind it when it is one so the amount can be worked out
     * again later for a different key level.
     */
    private function appendNumber(
        SpellDescriptionBuilder   $builder,
        float                     $value,
        SpellDescriptionValueKind $kind,
        float                     $damageMultiplier,
        ?int                      $spellId = null,
        ?int                      $effectIndex = null,
    ): void {
        if (!$kind->isScalable()) {
            $builder->appendValue(new SpellDescriptionValue($kind, $this->formatNumber($value)));

            return;
        }

        // A coefficient without its multiplier is not a number anyone can read - 10 where the game shows
        // 50,845 - so nothing is shown for it. The value itself is still recorded: it carries the
        // coefficient, which is what the calibration measures the multiplier from in the first place.
        // Dropping it here would leave calibration with nothing to work with (#3972 review).
        $amount = $damageMultiplier > 0.0
            // An amount of damage is a whole number; the fraction is an artefact of the coefficient
            ? $this->formatNumber(round($value / self::COEFFICIENT_SCALE * $damageMultiplier))
            : '';

        $builder->appendValue(new SpellDescriptionValue(
            kind: $kind,
            text: $amount,
            coefficient: $value,
            spellId: $spellId,
            effectIndex: $effectIndex,
        ));
    }

    /**
     * The numeric value of a single description token, or null when we cannot know it. `$kind` comes back
     * saying what that number is, which decides whether it scales.
     */
    private function resolveValueToken(
        SpellDescriptionContextInterface $context,
        int                              $spellId,
        string                           $token,
        int                              $effectIndex,
        SpellDescriptionValueKind        &        $kind,
    ): ?float {
        if ($token === 'd' || $token === 'D') {
            $durationMs = $context->getDurationMs($spellId);
            $kind       = SpellDescriptionValueKind::Duration;

            return $durationMs === null ? null : $durationMs / 1000;
        }

        $effect = $context->getEffect($spellId, $effectIndex);

        if ($effect === null) {
            return null;
        }

        $points = match ($token) {
            's', 'w' => $effect->basePoints,
            'm'      => $effect->getMinPoints(),
            'M'      => $effect->getMaxPoints(),
            'o'      => $this->getTotalOverTime($context, $spellId, $effect->basePoints, $effect->periodMs),
            default  => null,
        };

        if ($points !== null) {
            $kind = $effect->getPointsKind();

            return $effect->hasKnownPoints() ? abs($points) : null;
        }

        $kind = match ($token) {
            't', 'T' => SpellDescriptionValueKind::Period,
            'a', 'A' => SpellDescriptionValueKind::Radius,
            'x'      => SpellDescriptionValueKind::Count,
            default  => SpellDescriptionValueKind::Value,
        };

        return match ($token) {
            't', 'T' => $effect->periodMs > 0 ? $effect->periodMs / 1000 : null,
            'a'      => $effect->radius,
            'A'      => $effect->maxRadius ?? $effect->radius,
            'x'      => $effect->chainTargets > 0 ? (float)$effect->chainTargets : null,
            default  => null,
        };
    }

    /**
     * `$o1` is the damage or healing an effect does over the spell's full duration, i.e. one tick for
     * every period that fits in it.
     */
    private function getTotalOverTime(
        SpellDescriptionContextInterface $context,
        int                              $spellId,
        float                            $basePoints,
        int                              $periodMs,
    ): ?float {
        $durationMs = $context->getDurationMs($spellId);

        if ($durationMs === null || $durationMs <= 0 || $periodMs <= 0) {
            return null;
        }

        return $basePoints * floor($durationMs / $periodMs);
    }

    /**
     * Read a conditional and hand back the branch that applies.
     *
     * Conditionals chain: `$?a[x]?b[y][z]` is the template language's if / else if / else, with the
     * final bracket group the else and every branch after the first introduced by its own `?`.
     */
    private function readConditionalBranch(
        SpellDescriptionContextInterface $context,
        int                              $spellId,
        string                           $template,
        int                              &                              $offset,
        int                              $depth,
    ): ?string {
        $matchedBranch = null;

        while (true) {
            $condition = $this->readUntil($template, $offset, '[', consumeTerminator: false);
            $branch    = $condition === null ? null : $this->readBranch($template, $offset);

            if ($branch === null) {
                return $matchedBranch;
            }

            if ($matchedBranch === null && $this->evaluateCondition($context, $spellId, $condition ?? '', $depth)) {
                $matchedBranch = $branch;
            }

            if (($template[$offset] ?? '') !== '?') {
                break;
            }

            $offset++;
        }

        $elseBranch = $this->readOptionalBranch($template, $offset);

        return $matchedBranch ?? $elseBranch;
    }

    /**
     * Conditions are combinations of player state (`s123` knows a spell, `a123` has an aura, `c2` plays a
     * class, `diff8` is in a difficulty) which is unknowable while rendering up front, so every such
     * check is false - and its negation true. Conditions that are a plain comparison of spell values
     * (`$s4>0`) are evaluated for real.
     */
    private function evaluateCondition(
        SpellDescriptionContextInterface $context,
        int                              $spellId,
        string                           $condition,
        int                              $depth,
    ): bool {
        if (str_contains($condition, '$')) {
            $kind = SpellDescriptionValueKind::Value;

            return ($this->evaluateExpression($context, $spellId, $condition, $depth, $kind) ?? 0.0) !== 0.0;
        }

        foreach (explode('|', $condition) as $conjunction) {
            $result = true;

            foreach (explode('&', $conjunction) as $atom) {
                $result = $result && str_starts_with(trim($atom), '!');
            }

            if ($result && trim($conjunction) !== '') {
                return true;
            }
        }

        return false;
    }

    /**
     * Render `$@spellname123` / `$@spelldesc123`. Every other function - `$@versadmg` and friends - is
     * character state we cannot know.
     */
    private function renderFunction(
        SpellDescriptionContextInterface $context,
        string                           $template,
        int                              &                              $offset,
        int                              $depth,
        SpellDescriptionBuilder          $builder,
        float                            $damageMultiplier,
    ): void {
        if (preg_match('/\G([a-zA-Z]+)(\d*)/', $template, $matches, 0, $offset) !== 1) {
            return;
        }

        $offset += strlen($matches[0]);

        if ($matches[2] === '') {
            return;
        }

        $referencedSpellId = (int)$matches[2];

        if (strtolower($matches[1]) === 'spellname') {
            $builder->appendText($context->getName($referencedSpellId) ?? '');

            return;
        }

        if (strtolower($matches[1]) === 'spelldesc' && $depth < self::MAX_RECURSION_DEPTH) {
            $referencedTemplate = $context->getDescriptionTemplate($referencedSpellId);

            if ($referencedTemplate === null) {
                return;
            }

            $nestedLastNumber = null;

            $this->render(
                $context,
                $referencedSpellId,
                $this->normalizeNewlines($referencedTemplate),
                $depth + 1,
                $nestedLastNumber,
                $builder,
                $damageMultiplier,
            );
        }
    }

    /**
     * Resolve a named description variable (`$<mult>`) to its numeric value.
     */
    private function resolveVariable(
        SpellDescriptionContextInterface $context,
        int                              $spellId,
        string                           $variableName,
        int                              $depth,
        SpellDescriptionValueKind        &        $kind,
    ): ?float {
        if ($depth >= self::MAX_RECURSION_DEPTH) {
            return null;
        }

        $expression = $context->getDescriptionVariables($spellId)[$variableName] ?? null;

        return $expression === null
            ? null
            : $this->evaluateExpression($context, $spellId, $expression, $depth + 1, $kind);
    }

    /**
     * Evaluate the arithmetic in `${...}`, a `$<var>` definition or a comparison condition. Every token
     * inside is resolved to a number first; if any of them cannot be, the whole expression is unknown.
     *
     * The arithmetic around a coefficient is linear in practice ("twice this effect's damage"), so an
     * expression built from damage tokens is itself a coefficient and `$kind` says so.
     */
    private function evaluateExpression(
        SpellDescriptionContextInterface $context,
        int                              $spellId,
        string                           $expression,
        int                              $depth,
        SpellDescriptionValueKind        &        $kind,
    ): ?float {
        $substituted = $this->substituteNumbers($context, $spellId, $expression, $depth, $kind);

        if ($substituted === null) {
            return null;
        }

        return new MathExpressionEvaluator()->evaluate($substituted);
    }

    /**
     * Replace every token in an arithmetic expression by its raw numeric value, or return null when one
     * of them is unknown. Values keep their sign here - only what is finally shown is a magnitude.
     */
    private function substituteNumbers(
        SpellDescriptionContextInterface $context,
        int                              $spellId,
        string                           $expression,
        int                              $depth,
        SpellDescriptionValueKind        &        $kind,
    ): ?string {
        $result = '';
        $offset = 0;
        $length = strlen($expression);

        while ($offset < $length) {
            if ($expression[$offset] !== '$') {
                $result .= $expression[$offset];
                $offset++;

                continue;
            }

            $next      = $expression[$offset + 1] ?? '';
            $tokenKind = SpellDescriptionValueKind::Value;

            if ($next === '{') {
                $offset += 2;
                $nested = $this->readUntilClosingBrace($expression, $offset);
                $value  = $nested === null ? null : $this->evaluateExpression($context, $spellId, $nested, $depth, $tokenKind);
            } elseif ($next === '<') {
                $offset += 2;
                $variableName = $this->readUntil($expression, $offset, '>');
                $value        = $variableName === null ? null : $this->resolveVariable($context, $spellId, $variableName, $depth, $tokenKind);
            } elseif ($next === '?') {
                $offset += 2;
                $branch = $this->readConditionalBranch($context, $spellId, $expression, $offset, $depth);

                if ($branch === null) {
                    return null;
                }

                $value = $this->evaluateExpression($context, $spellId, $branch, $depth, $tokenKind);
            } else {
                $value = $this->substituteValueToken($context, $spellId, $expression, $offset, $tokenKind);
            }

            if ($value === null) {
                return null;
            }

            // The first amount the expression is built from is what the whole expression is an amount of
            if ($tokenKind->isScalable() && !$kind->isScalable()) {
                $kind = $tokenKind;
            }

            // Wrapped, so that a negative value does not turn `2-$s1` into `2--5`, and written out in
            // full because the evaluator's grammar has no exponent notation
            $result .= sprintf('(%.6F)', $value);
        }

        return $result;
    }

    /**
     * The raw, signed value of the token at `$offset` inside an arithmetic expression.
     */
    private function substituteValueToken(
        SpellDescriptionContextInterface $context,
        int                              $spellId,
        string                           $expression,
        int                              &                              $offset,
        SpellDescriptionValueKind        &        $kind,
    ): ?float {
        $pattern = sprintf('/\G\$(\d*)(%s|[a-zA-Z])(\d{0,2})/', implode('|', array_keys(self::ALIAS_TOKENS)));

        if (preg_match($pattern, $expression, $matches, 0, $offset) !== 1) {
            return null;
        }

        $offset += strlen($matches[0]);

        $token             = self::ALIAS_TOKENS[$matches[2]] ?? $matches[2];
        $referencedSpellId = $matches[1] === '' ? $spellId : (int)$matches[1];
        $effectIndex       = $matches[3] === '' ? 0 : max(0, (int)$matches[3] - 1);

        return $this->resolveValueToken($context, $referencedSpellId, $token, $effectIndex, $kind);
    }

    /**
     * Render the client's UI escape codes: colours, textures and hyperlinks are dropped, keeping the text
     * they wrap; `|4singular:plural;` picks a word.
     */
    private function renderEscapeCode(
        string                  $template,
        int                     &                     $offset,
        ?float                  &                  $lastNumber,
        SpellDescriptionBuilder $builder,
    ): void {
        $code = $template[$offset + 1] ?? '';

        // |cFFFFFFFF opens a colour, |r closes it
        if (($code === 'c' || $code === 'C') && preg_match('/\G\|[cC][0-9a-fA-F]{8}/', $template, $matches, 0, $offset) === 1) {
            $offset += strlen($matches[0]);

            return;
        }

        if ($code === 'n') {
            $offset += 2;
            $builder->appendText("\n");

            return;
        }

        // |4singular:plural; and its declension variants
        if (ctype_digit($code)) {
            $macroOffset = $offset + 2;
            $macro       = $this->readUntil($template, $macroOffset, ';');

            if ($macro !== null && str_contains($macro, ':')) {
                $offset = $macroOffset;
                $builder->appendText($this->chooseMacroOption($macro, $lastNumber));

                return;
            }
        }

        // |Hspell:123|h, |T...|t and every other code: drop the code, keep what it wraps
        if (preg_match('/\G\|[a-zA-Z][^|]*/', $template, $matches, 0, $offset) === 1 && ($code === 'H' || $code === 'T')) {
            $offset += strlen($matches[0]);

            return;
        }

        $offset += 2;
    }

    /**
     * Pick from `singular:plural` or `male:female`; a null count reads as singular.
     */
    private function chooseMacroOption(string $macro, ?float $count): string
    {
        $options = explode(':', $macro);

        if (count($options) < 2) {
            return $macro;
        }

        return $count === null || $count === 1.0 ? $options[0] : $options[1];
    }

    /**
     * Read a conditional's false branch, which may be missing entirely - and which some templates put on
     * the next line rather than straight after the true branch. Whitespace in between belongs to neither
     * branch, so it is swallowed along with the brackets.
     */
    private function readOptionalBranch(string $template, int &$offset): ?string
    {
        $cursor = $offset;

        while (($template[$cursor] ?? '') !== '' && ctype_space($template[$cursor])) {
            $cursor++;
        }

        if (($template[$cursor] ?? '') !== '[') {
            return '';
        }

        $offset = $cursor;

        return $this->readBranch($template, $offset);
    }

    /**
     * Read a `[...]` branch, keeping nested brackets intact, and advance past it.
     */
    private function readBranch(string $template, int &$offset): ?string
    {
        if (($template[$offset] ?? '') !== '[') {
            return null;
        }

        $bracketDepth = 0;
        $length       = strlen($template);

        for ($cursor = $offset; $cursor < $length; $cursor++) {
            if ($template[$cursor] === '[') {
                $bracketDepth++;
            } elseif ($template[$cursor] === ']') {
                $bracketDepth--;

                if ($bracketDepth === 0) {
                    $branch = substr($template, $offset + 1, $cursor - $offset - 1);
                    $offset = $cursor + 1;

                    return $branch;
                }
            }
        }

        // Unbalanced brackets - consume the rest so the caller cannot loop forever
        $offset = $length;

        return null;
    }

    private function readUntilClosingBrace(string $template, int &$offset): ?string
    {
        $braceDepth = 1;
        $length     = strlen($template);

        for ($cursor = $offset; $cursor < $length; $cursor++) {
            if ($template[$cursor] === '{') {
                $braceDepth++;
            } elseif ($template[$cursor] === '}') {
                $braceDepth--;

                if ($braceDepth === 0) {
                    $expression = substr($template, $offset, $cursor - $offset);
                    $offset     = $cursor + 1;

                    return $expression;
                }
            }
        }

        $offset = $length;

        return null;
    }

    private function readUntil(string $template, int &$offset, string $terminator, bool $consumeTerminator = true): ?string
    {
        $position = strpos($template, $terminator, $offset);

        if ($position === false) {
            $offset = strlen($template);

            return null;
        }

        $value  = substr($template, $offset, $position - $offset);
        $offset = $consumeTerminator ? $position + strlen($terminator) : $position;

        return $value;
    }

    private function formatNumber(float $value): string
    {
        $rounded = round($value, 2);

        if ($rounded === floor($rounded)) {
            return number_format($rounded, 0, '.', ',');
        }

        return rtrim(rtrim(number_format($rounded, 2, '.', ','), '0'), '.');
    }

    /**
     * The units are deliberately not translated: DB2 only hands us English description templates, so the
     * text around the duration is English too and a localized unit would read worse than the game's own.
     *
     * @param float $seconds negative for a duration that lasts until it is cancelled
     */
    private function formatDuration(float $seconds): string
    {
        if ($seconds < 0) {
            return 'until cancelled';
        }

        if ($seconds < 60) {
            return sprintf('%s sec', $this->formatNumber($seconds));
        }

        if ($seconds < 3600) {
            return sprintf('%s min', $this->formatNumber($seconds / 60));
        }

        return sprintf('%s hr', $this->formatNumber($seconds / 3600));
    }

    private function normalizeNewlines(string $template): string
    {
        return str_replace(["\r\n", "\r"], "\n", $template);
    }
}
