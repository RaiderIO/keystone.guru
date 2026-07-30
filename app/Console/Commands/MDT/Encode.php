<?php

namespace App\Console\Commands\MDT;

use App\Console\Commands\Traits\ConvertsMDTStrings;
use App\Console\Commands\Traits\ExecutesShellCommands;
use App\Logic\MDT\IO\MDTStringFormat;
use Illuminate\Console\Command;

class Encode extends Command
{
    use ConvertsMDTStrings;
    use ExecutesShellCommands;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mdt:encode {string} {--mdt2 : Encode into the MDT 6.2+ !~MDT2~ format instead of the legacy format}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Encodes an MDT string from a json string';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $string  = $this->argument('string');
        $decoded = json_decode($string, true);

        if (!is_array($decoded)) {
            $this->error('The string argument must be valid JSON describing a preset');

            return;
        }

        $format  = $this->option('mdt2') ? MDTStringFormat::MDT2 : MDTStringFormat::Legacy;
        $encoded = $this->encode($decoded, $format);

        if ($encoded !== null) {
            $this->info($encoded);
        }
    }
}
