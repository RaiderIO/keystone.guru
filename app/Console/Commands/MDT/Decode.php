<?php

namespace App\Console\Commands\MDT;

use App\Console\Commands\Traits\ConvertsMDTStrings;
use App\Console\Commands\Traits\ExecutesShellCommands;
use Illuminate\Console\Command;

class Decode extends Command
{
    use ConvertsMDTStrings;
    use ExecutesShellCommands;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mdt:decode {string}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Decodes an MDT string';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $string  = $this->argument('string');
        $decoded = $this->decode($string);

        if ($decoded === null) {
            // The trait already reported the failure via $this->error()/logger()
            return;
        }

        // CBOR byte strings may contain non-UTF-8 binary - substitute rather than have json_encode fail
        $json = json_encode($decoded, JSON_INVALID_UTF8_SUBSTITUTE | JSON_PARTIAL_OUTPUT_ON_ERROR);

        if ($json === false) {
            $this->error('Unable to encode the decoded preset as JSON');
        } else {
            $this->info($json);
        }
    }
}
