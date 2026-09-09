<?php

namespace App\Console\Commands\Test;

use App\Logic\Testing\AffectedTestGroupsResolver;
use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class AffectedTestGroupsCommand extends Command
{
    public const VERDICT_GROUPS = 'groups';

    public const VERDICT_FULL = 'full';

    public const VERDICT_NONE = 'none';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:affected-groups
                            {paths?* : Changed paths, relative to the repository root}
                            {--stdin : Read the changed paths from stdin, one per line}
                            {--range=origin/master...HEAD : git diff range to take the changed paths from when none are given}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prints the PHPUnit groups that can observe the changed paths, as "groups --group=A --group=B", ' .
        '"full" (a shared or unmapped path changed: run everything) or "none" (no PHP test can observe the change). ' .
        'The reasoning goes to stderr.';

    public function handle(): int
    {
        $changedPaths = $this->getChangedPaths();
        if ($changedPaths === null) {
            return self::FAILURE;
        }

        $result = (new AffectedTestGroupsResolver(base_path()))->resolve($changedPaths);
        $stderr = $this->output->getErrorStyle();

        foreach ($result->fullSuitePaths as $path) {
            $stderr->writeln(sprintf('full suite: %s', $path));
        }
        foreach ($result->ignoredPaths as $path) {
            $stderr->writeln(sprintf('no tests: %s', $path));
        }

        if ($result->requiresFullSuite()) {
            $this->line(self::VERDICT_FULL);
        } elseif ($result->hasNothingToRun()) {
            $this->line(self::VERDICT_NONE);
        } else {
            $this->line(sprintf('%s %s', self::VERDICT_GROUPS, $result->toPhpUnitArguments()));
        }

        return self::SUCCESS;
    }

    /**
     * @return string[]|null
     */
    private function getChangedPaths(): ?array
    {
        /** @var string[] $paths */
        $paths = $this->argument('paths');
        if ($paths !== []) {
            return $paths;
        }

        if ($this->option('stdin')) {
            return explode(PHP_EOL, (string)stream_get_contents(STDIN));
        }

        $process = new Process(['git', 'diff', '--name-only', (string)$this->option('range')], base_path());
        $process->run();

        if (!$process->isSuccessful()) {
            $this->output->getErrorStyle()->writeln(trim($process->getErrorOutput()));

            return null;
        }

        return explode(PHP_EOL, $process->getOutput());
    }
}
