<?php

namespace App\Service\MDT\Lua;

use App\Service\MDT\Exceptions\LuaParseException;

/**
 * A deliberately small parser for the subset of Lua that MDT's generated dungeon files use.
 *
 * It understands table constructors, `["key"] = value` / `key = value` / positional entries, and
 * treats every non-table value as an opaque {@see LuaLiteral} so numbers keep their exact source
 * representation. It is not a general purpose Lua parser and throws rather than guessing whenever
 * it meets something it does not recognise.
 */
class LuaTableParser
{
    private int $position = 0;

    private readonly int $length;

    public function __construct(private readonly string $source)
    {
        $this->length = strlen($source);
    }

    /**
     * Parses every `MDT.<name>[dungeonIndex] = <value>` assignment in the source.
     *
     * @return array<string, mixed> Keyed by the assignment name, e.g. `mapInfo` or `dungeonEnemies`.
     *
     * @throws LuaParseException
     */
    public function parseDungeonIndexAssignments(): array
    {
        $result = [];
        $offset = 0;

        while (preg_match(
            '/MDT\.([a-zA-Z]+)\[dungeonIndex]\s*=\s*/',
            $this->source,
            $matches,
            PREG_OFFSET_CAPTURE,
            $offset,
        ) === 1) {
            $this->position = $matches[0][1] + strlen($matches[0][0]);

            $result[$matches[1][0]] = $this->parseValue();

            $offset = $this->position;
        }

        return $result;
    }

    /**
     * @return LuaLiteral|array<int|string, mixed>
     *
     * @throws LuaParseException
     */
    private function parseValue(): LuaLiteral|array
    {
        $this->skipWhitespaceAndComments();

        if ($this->peek() === '{') {
            return $this->parseTable();
        }

        return new LuaLiteral($this->parseLiteral());
    }

    /**
     * @return array<int|string, mixed>
     *
     * @throws LuaParseException
     */
    private function parseTable(): array
    {
        $this->consume('{');

        $result            = [];
        $nextPositionalKey = 1;

        while (true) {
            $this->skipWhitespaceAndComments();

            $character = $this->peek();
            if ($character === null) {
                throw new LuaParseException('Unexpected end of source while parsing a table');
            }

            if ($character === '}') {
                $this->position++;

                break;
            }

            if ($character === ',' || $character === ';') {
                $this->position++;

                continue;
            }

            $key = $this->parseKey();

            $value = $this->parseValue();

            if ($key === null) {
                $result[$nextPositionalKey++] = $value;
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * @return int|string|null The key of the entry about to be parsed, null if it is positional.
     *
     * @throws LuaParseException
     */
    private function parseKey(): int|string|null
    {
        if ($this->peek() === '[') {
            $this->position++;

            $key = new LuaLiteral(trim($this->parseLiteral(']')));

            $this->consume(']');
            $this->skipWhitespaceAndComments();
            $this->consume('=');

            $unquoted = $key->toUnquotedString();

            return preg_match('/^-?\d+$/', $unquoted) === 1 ? (int)$unquoted : $unquoted;
        }

        if (preg_match('/\G([a-zA-Z_][a-zA-Z0-9_]*)\s*=(?!=)/', $this->source, $matches, 0, $this->position) === 1) {
            $this->position += strlen($matches[0]);

            return $matches[1];
        }

        return null;
    }

    /**
     * Reads a raw value up to the next separator that is not nested inside brackets or quotes.
     *
     * @param string $terminator An additional character that terminates the literal.
     *
     * @throws LuaParseException
     */
    private function parseLiteral(string $terminator = ''): string
    {
        $start        = $this->position;
        $bracketDepth = 0;

        while ($this->position < $this->length) {
            $character = $this->source[$this->position];

            if ($character === '"' || $character === '\'') {
                $this->skipQuotedString($character);

                continue;
            }

            if ($character === '[' || $character === '(') {
                $bracketDepth++;
            } elseif ($character === ')') {
                $bracketDepth--;
            } elseif ($character === ']') {
                if ($bracketDepth === 0 && $terminator === ']') {
                    break;
                }

                $bracketDepth--;
            } elseif ($bracketDepth === 0 &&
                ($character === ',' || $character === '}' || $character === ';' || $character === "\n")) {
                break;
            }

            $this->position++;
        }

        $literal = trim(substr($this->source, $start, $this->position - $start));
        if ($literal === '') {
            throw new LuaParseException(sprintf('Empty value at offset %d', $start));
        }

        return $literal;
    }

    /**
     * @throws LuaParseException
     */
    private function skipQuotedString(string $quote): void
    {
        // Skip the opening quote
        $this->position++;

        while ($this->position < $this->length) {
            $character = $this->source[$this->position];

            if ($character === '\\') {
                $this->position += 2;

                continue;
            }

            $this->position++;

            if ($character === $quote) {
                return;
            }
        }

        throw new LuaParseException('Unexpected end of source while parsing a string');
    }

    private function skipWhitespaceAndComments(): void
    {
        while ($this->position < $this->length) {
            if (preg_match('/\G\s+/', $this->source, $matches, 0, $this->position) === 1) {
                $this->position += strlen($matches[0]);

                continue;
            }

            if (substr($this->source, $this->position, 2) === '--') {
                $newLinePosition = strpos($this->source, "\n", $this->position);
                $this->position  = $newLinePosition === false ? $this->length : $newLinePosition;

                continue;
            }

            break;
        }
    }

    private function peek(): ?string
    {
        return $this->position < $this->length ? $this->source[$this->position] : null;
    }

    /**
     * @throws LuaParseException
     */
    private function consume(string $character): void
    {
        $this->skipWhitespaceAndComments();

        if ($this->peek() !== $character) {
            throw new LuaParseException(sprintf(
                'Expected %s but found %s at offset %d',
                $character,
                $this->peek() ?? 'end of source',
                $this->position,
            ));
        }

        $this->position++;
    }
}
