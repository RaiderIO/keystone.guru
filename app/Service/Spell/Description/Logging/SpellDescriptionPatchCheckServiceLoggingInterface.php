<?php

namespace App\Service\Spell\Description\Logging;

interface SpellDescriptionPatchCheckServiceLoggingInterface
{
    public function checkForPatchStart(string $product, string $gameVersionKey, int $gameVersionId): void;

    public function checkForPatchUnknownBuild(string $product): void;

    public function checkForPatchUpToDate(string $build): void;

    public function checkForPatchCommittedStateUpToDate(string $build, ?string $lastImportedBuild): void;

    public function checkForPatchCommittedStateUnreadable(string $error): void;

    public function checkForPatchIssueAlreadyOpen(string $build, int $issueNumber): void;

    public function checkForPatchIssueFiled(string $build, ?string $previousBuild, int $issueNumber): void;

    public function checkForPatchGithubRequestFailed(string $error): void;

    public function checkForPatchEnd(): void;
}
