<?php

namespace App\Console\Commands\MDT;

use App\Console\Commands\Traits\ConvertsMDTStrings;
use App\Console\Commands\Traits\ExecutesShellCommands;
use App\Logic\MDT\Exception\MDT2DecodeException;
use App\Logic\MDT\IO\MDT2Codec;
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
        $string = $this->argument('string');

        // MDT 6.2+ strings are decoded natively in PHP - cli_weakauras_parser only handles the legacy format
        if (MDT2Codec::appliesTo($string)) {
            try {
                $this->info(json_encode(MDT2Codec::decode($string)));
            } catch (MDT2DecodeException $mdt2DecodeException) {
                $this->error($mdt2DecodeException->getMessage());

                logger()->error($mdt2DecodeException->getMessage(), [
                    'string' => $string,
                ]);
            }

            return;
        }

        $this->info($this->decode($string) ?? ''); // @phpstan-ignore nullCoalesce.expr
    }
}
