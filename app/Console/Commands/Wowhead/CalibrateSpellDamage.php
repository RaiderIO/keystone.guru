<?php

namespace App\Console\Commands\Wowhead;

use App\Service\Spell\Description\SpellDamageCalibrationServiceInterface;
use Illuminate\Console\Command;

class CalibrateSpellDamage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wowhead:calibratespelldamage
                            {--force : Remeasure spells that already have a multiplier}
                            {--spellId= : Measure a single spell}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Measures what each spell\'s damage coefficients are multiplied by, against the numbers the game shows.';

    public function handle(SpellDamageCalibrationServiceInterface $spellDamageCalibrationService): int
    {
        $spellId = $this->option('spellId') === null ? null : (int)$this->option('spellId');

        $this->info('Measuring spell damage multipliers.');
        $this->comment('This reads one page per spell, so it takes a while - run it by hand after a patch, never on a schedule.');

        $progressBar = null;

        $result = $spellDamageCalibrationService->calibrate(
            (bool)$this->option('force'),
            $spellId,
            function (int $handled, int $total) use (&$progressBar): void {
                $progressBar ??= $this->output->createProgressBar($total);
                $progressBar->setProgress($handled);
            },
        );

        $progressBar?->finish();
        $this->newLine();

        $this->info(sprintf(
            '%d measured, %d unchanged, %d disagreed with themselves, %d unreadable.',
            $result['measured'],
            $result['unchanged'],
            $result['disagreed'],
            $result['unreadable'],
        ));

        if ($result['measured'] > 0) {
            $this->comment('Run mapping:save to export the new multipliers into the seeders.');
        }

        return 0;
    }
}
