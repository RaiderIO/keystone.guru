<?php

namespace App\Service\Spell\Description;

use App\Repositories\Interfaces\Spell\SpellDescriptionImportStateRepositoryInterface;
use App\Service\Spell\Description\Logging\SpellDescriptionPatchCheckServiceLoggingInterface;
use App\Service\WagoTools\WagoToolsServiceInterface;
use Github\Api\Issue;
use GrahamCampbell\GitHub\Facades\GitHub;
use Throwable;

/**
 * Reminds a human to re-run the spell description import when wago.tools has moved on to a newer game
 * build than the one we last actually imported. The trigger is a game patch, not a release, so this
 * cannot be fully automated - the output is a seeder diff a human has to read and commit - what can be
 * automated is filing the reminder itself, as a GitHub issue (#4021).
 */
class SpellDescriptionPatchCheckService implements SpellDescriptionPatchCheckServiceInterface
{
    public function __construct(
        private readonly WagoToolsServiceInterface                         $wagoToolsService,
        private readonly SpellDescriptionImportStateRepositoryInterface    $spellDescriptionImportStateRepository,
        private readonly SpellDescriptionPatchCheckServiceLoggingInterface $log,
    ) {
    }

    public function checkForPatch(string $product, string $gameVersionKey, int $gameVersionId): void
    {
        $this->log->checkForPatchStart($product, $gameVersionKey, $gameVersionId);

        try {
            $latestBuild = $this->wagoToolsService->getLatestBuild($product);

            // wago.tools was unreachable, or its response could not be parsed - WagoToolsService already
            // logged the specifics. A missed reminder is recoverable; filing on a bad read is not.
            if ($latestBuild === null) {
                $this->log->checkForPatchUnknownBuild($product);

                return;
            }

            $lastImportedBuild = $this->spellDescriptionImportStateRepository->findLastImportedBuild($gameVersionId);

            if ($latestBuild === $lastImportedBuild) {
                $this->log->checkForPatchUpToDate($latestBuild);

                return;
            }

            $this->fileReminderIssue($latestBuild, $lastImportedBuild, $gameVersionKey);
        } finally {
            $this->log->checkForPatchEnd();
        }
    }

    private function fileReminderIssue(string $build, ?string $previousBuild, string $gameVersionKey): void
    {
        $owner      = (string)config('keystoneguru.github_repository_owner');
        $repository = (string)config('keystoneguru.github_repository');
        $title      = $this->getIssueTitle($build);

        try {
            $existingIssueNumber = $this->findOpenIssueNumber($owner, $repository, $build);

            if ($existingIssueNumber !== null) {
                $this->log->checkForPatchIssueAlreadyOpen($build, $existingIssueNumber);

                return;
            }

            /** @var Issue $issueApi */
            // @phpstan-ignore staticMethod.notFound
            $issueApi = GitHub::issue();

            $createdIssue = $issueApi->create($owner, $repository, [
                'title' => $title,
                'body'  => $this->getIssueBody($build, $previousBuild, $gameVersionKey),
            ]);

            $this->log->checkForPatchIssueFiled($build, $previousBuild, (int)($createdIssue['number'] ?? 0));
        } catch (Throwable $exception) {
            // Covers both GitHub API errors (Github\Exception\ExceptionInterface) and transport failures
            // (DNS, connect timeout - PSR-18 exceptions that don't implement it). Either way, the same
            // "a missed reminder is recoverable" rule applies as it does to a wago.tools failure above.
            $this->log->checkForPatchGithubRequestFailed($exception->getMessage());
        }
    }

    /**
     * The number of an open issue whose title already names this build, or null when none exists yet -
     * so a run that finds nothing new never files a second reminder for the same patch.
     *
     * Compares the full open-issue list rather than the GitHub Search API: search results lag real time
     * by an indexing delay and its title tokenizer is not guaranteed to keep a dotted build string
     * intact, either of which would let a duplicate through. This repository's open-issue count is small
     * enough that listing and matching client-side is cheap.
     */
    private function findOpenIssueNumber(string $owner, string $repository, string $build): ?int
    {
        /** @var Issue $issueApi */
        // @phpstan-ignore staticMethod.notFound
        $issueApi = GitHub::issue();

        $title = $this->getIssueTitle($build);

        foreach ($issueApi->all($owner, $repository, ['state' => 'open', 'per_page' => 100]) as $issue) {
            if (($issue['title'] ?? null) === $title) {
                return (int)$issue['number'];
            }
        }

        return null;
    }

    private function getIssueTitle(string $build): string
    {
        return sprintf('Patch %s is out - re-run the spell description import', $build);
    }

    private function getIssueBody(string $build, ?string $previousBuild, string $gameVersionKey): string
    {
        $previousBuildText = $previousBuild === null ? 'no build recorded yet' : sprintf('`%s`', $previousBuild);

        return implode("\n", [
            sprintf(':robot: wago.tools now offers build `%s` for the %s client (previously imported: %s).', $build, $gameVersionKey, $previousBuildText),
            '',
            'Re-run the spell description import and commit the result - see the `spell-descriptions` skill for details:',
            '',
            '```sh',
            'docker compose exec -T app php artisan wagotools:importspelldescriptions   # DB2 -> templates + coefficients',
            'docker compose exec -T app php artisan wowhead:calibratespelldamage        # -> spells.damage_multiplier',
            'docker compose exec -T app php artisan wagotools:importspelldescriptions   # re-render with the multipliers',
            'docker compose exec -T app php artisan mapping:save                        # -> database/seeders/dungeondata/spells.json',
            'git add database/seeders/dungeondata/spells.json database/data/spell_description/import_state.json',
            '```',
        ]);
    }
}
