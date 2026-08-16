<?php

namespace App\Service\Spell\Description;

use App\Repositories\Interfaces\Spell\SpellDescriptionImportStateRepositoryInterface;
use App\Service\Spell\Description\Logging\SpellDescriptionPatchCheckServiceLoggingInterface;
use App\Service\WagoTools\WagoToolsServiceInterface;
use Github\Api\Issue;
use Github\Api\Search;
use Github\Exception\ExceptionInterface as GithubExceptionInterface;
use GrahamCampbell\GitHub\Facades\GitHub;

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
        } catch (GithubExceptionInterface $exception) {
            $this->log->checkForPatchGithubRequestFailed($exception->getMessage());
        }
    }

    /**
     * The number of an open issue whose title already names this build, or null when none exists yet -
     * so a run that finds nothing new never files a second reminder for the same patch.
     */
    private function findOpenIssueNumber(string $owner, string $repository, string $build): ?int
    {
        /** @var Search $searchApi */
        // @phpstan-ignore staticMethod.notFound
        $searchApi = GitHub::search();

        $query  = sprintf('repo:%s/%s is:issue is:open in:title "%s"', $owner, $repository, $build);
        $result = $searchApi->issues($query);

        $issueNumber = $result['items'][0]['number'] ?? null;

        return is_int($issueNumber) ? $issueNumber : null;
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
            'git add database/seeders/dungeondata/spells.json',
            '```',
        ]);
    }
}
