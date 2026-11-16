<?php

namespace App\Logging\Rollbar;

use App\Logging\SensitiveDataRedactor;
use Override;
use Rollbar\DataBuilder;
use Rollbar\ScrubberInterface;

/**
 * Rollbar scrubber backed by the shared {@see SensitiveDataRedactor} patterns. Rollbar's own scrubber only matches
 * exact key names, so it is replaced rather than configured through `scrub_fields`. Rollbar instantiates the class
 * itself and passes its whole config to the constructor, which is ignored.
 */
final class RollbarScrubbing implements ScrubberInterface
{
    /**
     * Keys of the Rollbar request payload that hold request parameters (query string and form body).
     */
    private const array REQUEST_INPUT_KEYS = [
        'GET',
        'POST',
    ];

    /**
     * @return array{scrubber: class-string<ScrubberInterface>, capture_ip: string, local_vars_dump: bool}
     */
    public static function options(): array
    {
        return [
            'scrubber'   => self::class,
            'capture_ip' => DataBuilder::ANONYMIZE_IP,
            // Frame arguments carry the whole Request (cookie and server bags) and plain positional strings, which no
            // key-based scrubber can recognise
            'local_vars_dump' => false,
        ];
    }

    /**
     * @param  array<array-key, mixed> $data
     * @return array<array-key, mixed>
     */
    #[Override]
    public function scrub(array &$data, string $replacement = SensitiveDataRedactor::FILTERED): array
    {
        $requestInput = [];
        if (isset($data['request']) && is_array($data['request'])) {
            foreach (self::REQUEST_INPUT_KEYS as $key) {
                if (isset($data['request'][$key]) && is_array($data['request'][$key])) {
                    $requestInput[$key] = SensitiveDataRedactor::scrubArray($data['request'][$key], true);
                    unset($data['request'][$key]);
                }
            }
        }

        $data = SensitiveDataRedactor::scrubArray($data);

        foreach ($requestInput as $key => $values) {
            $data['request'][$key] = $values;
        }

        return $data;
    }
}
