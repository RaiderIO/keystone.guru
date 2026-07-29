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
                // CBOR byte strings may contain non-UTF-8 binary - substitute rather than have json_encode fail
                $json = json_encode(MDT2Codec::decode($string), JSON_INVALID_UTF8_SUBSTITUTE | JSON_PARTIAL_OUTPUT_ON_ERROR);

                if ($json === false) {
                    $this->error('Unable to encode the decoded preset as JSON');
                } else {
                    $this->info($json);
                }
            } catch (MDT2DecodeException $mdt2DecodeException) {
                $this->error($mdt2DecodeException->getMessage());

                logger()->error($mdt2DecodeException->getMessage(), [
                    'string' => substr($string, 0, 2048),
                ]);
            }

            return;
        }

        $this->info($this->decode($string) ?? ''); // @phpstan-ignore nullCoalesce.expr
    }
}
