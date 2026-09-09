<?php

namespace App\Logic\Testing;

/**
 * The outcome of mapping a set of changed paths onto PHPUnit groups.
 *
 * @see AffectedTestGroupsResolver
 */
final readonly class AffectedTestGroups
{
    /**
     * @param string[] $groups         PHPUnit group names to run, sorted and unique. Empty when nothing needs running or
     *                                 when the full suite is required.
     * @param string[] $fullSuitePaths Changed paths that require the full suite (shared files, or paths the mapping table
     *                                 does not know). Non-empty means $groups must be ignored and everything runs.
     * @param string[] $ignoredPaths   Changed paths that no PHP test can observe.
     */
    public function __construct(
        public array $groups,
        public array $fullSuitePaths,
        public array $ignoredPaths,
    ) {
    }

    public function requiresFullSuite(): bool
    {
        return $this->fullSuitePaths !== [];
    }

    public function hasNothingToRun(): bool
    {
        return !$this->requiresFullSuite() && $this->groups === [];
    }

    /**
     * The `--group=X` arguments for PHPUnit, or an empty string when every test should run.
     */
    public function toPhpUnitArguments(): string
    {
        if ($this->requiresFullSuite()) {
            return '';
        }

        return implode(' ', array_map(static fn(string $group) => sprintf('--group=%s', $group), $this->groups));
    }
}
