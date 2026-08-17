<?php

namespace App\Service\Spell\Description\Logging;

use App\Logging\Concerns\InteractsWithRollbar;
use App\Logging\StructuredLogging;

class SpellDescriptionPatchCheckServiceLogging extends StructuredLogging implements SpellDescriptionPatchCheckServiceLoggingInterface
{
    use InteractsWithRollbar;

    public function checkForPatchStart(string $product, string $gameVersionKey, int $gameVersionId): void
    {
        $this->start(__METHOD__, get_defined_vars());
    }

    public function checkForPatchUnknownBuild(string $product): void
    {
        $this->warning(__METHOD__, get_defined_vars());
    }

    public function checkForPatchUpToDate(string $build): void
    {
        $this->info(__METHOD__, get_defined_vars());
    }

    public function checkForPatchIssueAlreadyOpen(string $build, int $issueNumber): void
    {
        $this->info(__METHOD__, get_defined_vars());
    }

    public function checkForPatchIssueFiled(string $build, ?string $previousBuild, int $issueNumber): void
    {
        $this->info(__METHOD__, get_defined_vars());
    }

    public function checkForPatchGithubRequestFailed(string $error): void
    {
        $this->error(__METHOD__, get_defined_vars());
    }

    public function checkForPatchEnd(): void
    {
        $this->end(__METHOD__);
    }
}
