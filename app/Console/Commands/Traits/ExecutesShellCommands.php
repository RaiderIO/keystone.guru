<?php

namespace App\Console\Commands\Traits;

use Illuminate\Console\Command;

/**
 * @mixin Command
 */
trait ExecutesShellCommands
{

    /**
     * Executes a shell command and echos its response to the info console.
     *
     * @param string|array $cmds
     * @param boolean $echo
     * @return array
     */
    protected function shell($cmds, bool $echo = true): array
    {
        $result = [];

        if (is_string($cmds)) {
            $cmds = [$cmds];
        }

        foreach ($cmds as $cmd) {
            if (!empty($cmd)) {
                $cmdResult = trim(shell_exec($cmd));

                if ($echo) {
                    $this->info($cmdResult);
                }

                $result[$cmd] = $cmdResult;
            }
        }

        return $result;
    }
}