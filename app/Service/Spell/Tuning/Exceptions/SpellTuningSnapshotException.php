<?php

namespace App\Service\Spell\Tuning\Exceptions;

use RuntimeException;

/**
 * A snapshot source could not be turned into a {@see \App\Service\Spell\Tuning\Dtos\SpellTuningSnapshot} -
 * the message says what was missing (the file, the git ref, or the build it belongs to).
 */
class SpellTuningSnapshotException extends RuntimeException
{
}
