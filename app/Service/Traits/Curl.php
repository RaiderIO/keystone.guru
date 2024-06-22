<?php

namespace App\Service\Traits;

trait Curl
{
    public function curlGet(string $url, array $options = []): string
    {
        $ch = curl_init();

        curl_setopt_array($ch, $options + [
            CURLOPT_RETURNTRANSFER => true,   // return web page
            CURLOPT_HEADER         => false,  // don't return headers
            CURLOPT_FOLLOWLOCATION => true,   // follow redirects
            CURLOPT_MAXREDIRS      => 10,     // stop after 10 redirects
            CURLOPT_ENCODING       => '',     // handle compressed
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/115.0.0.0 Safari/537.36', // name of client
            CURLOPT_AUTOREFERER    => true,   // set referrer on redirect
            CURLOPT_CONNECTTIMEOUT => 120,    // time-out on connect
            CURLOPT_TIMEOUT        => 120,    // time-out on response
            CURLOPT_URL            => $url,
        ]);

        try {
            $response = curl_exec($ch);
        } finally {
            curl_close($ch);
        }

        return $response;
    }

    /**
     * @param string $url
     * @param string $filePath
     * @return bool
     */
    public function curlSaveToFile(string $url, string $filePath): bool
    {
        $rawImage = $this->curlGet($url);

        return file_put_contents($filePath, $rawImage) !== false;
    }

    public function curlPost(string $url, array $postBody = [], array $headers = []): string
    {
        // https://stackoverflow.com/questions/51747829/how-to-send-a-embedded-webhook-using-php-discord
        $ch = curl_init();

        $combinedHeaders = [];
        foreach ($headers as $key => $value) {
            $combinedHeaders[] = sprintf('%s: %s', $key, $value);
        }

        curl_setopt_array($ch, [
            CURLOPT_URL        => $url,
            CURLOPT_POST       => true,
            // Found no way to disable this behaviour from json_encode
            CURLOPT_POSTFIELDS => str_replace('\\\\n', '\\n', json_encode($postBody)),
            CURLOPT_HTTPHEADER => array_merge([
                'Content-Type: application/json',
            ], $combinedHeaders),
        ]);

        try {
            $response = curl_exec($ch);
        } finally {
            curl_close($ch);
        }

        return $response;
    }
}
