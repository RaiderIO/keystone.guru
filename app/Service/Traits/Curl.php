<?php

namespace App\Service\Traits;

trait Curl
{
    /**
     * @param array<int, mixed> $options CURLOPT_* constants as keys - they are ints, not strings
     */
    public function curlGet(string $url, array $options = []): string
    {
        return $this->curlGetResponse($url, $options)['body'];
    }

    /**
     * @param  string $url
     * @param  string $filePath
     * @return bool
     */
    public function curlSaveToFile(string $url, string $filePath): bool
    {
        $response = $this->curlGetResponse($url);

        // A failed HTTP request still has a body - S3, for example, answers an expired or denied presigned URL
        // with an XML `Error` document. Saving that body would hand a bogus file to whatever consumes it, so
        // treat any error response as a download failure instead (see #3789).
        if ($response['body'] === false || $response['errorNumber'] !== CURLE_OK
            || $response['httpCode'] < 200 || $response['httpCode'] >= 300) {
            return false;
        }

        return file_put_contents($filePath, $response['body']) !== false;
    }

    /**
     * @param array<string, mixed>  $postBody
     * @param array<string, string> $headers
     */
    public function curlPost(string $url, array $postBody = [], array $headers = []): string
    {
        // https://stackoverflow.com/questions/51747829/how-to-send-a-embedded-webhook-using-php-discord
        $ch = curl_init();

        $combinedHeaders = [];
        foreach ($headers as $key => $value) {
            $combinedHeaders[] = sprintf('%s: %s', $key, $value);
        }

        curl_setopt_array($ch, [
            CURLOPT_URL  => $url,
            CURLOPT_POST => true,
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

    /**
     * Perform the GET and report the transport outcome alongside the body.
     *
     * Note `httpCode` is 0 for non-HTTP protocols, and when the request never got a response at all, so callers
     * should judge failure on `errorNumber` first.
     *
     * @param  array<int, mixed>                                         $options CURLOPT_* constants as keys - they are ints, not strings
     * @return array{body: string|bool, httpCode: int, errorNumber: int}
     */
    protected function curlGetResponse(string $url, array $options = []): array
    {
        $ch = curl_init();

        curl_setopt_array($ch, $options + [
            CURLOPT_RETURNTRANSFER => true,
            // return web page
            CURLOPT_HEADER => false,
            // don't return headers
            CURLOPT_FOLLOWLOCATION => true,
            // follow redirects
            CURLOPT_MAXREDIRS => 10,
            // stop after 10 redirects
            CURLOPT_ENCODING => '',
            // handle compressed
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36',
            // name of client
            CURLOPT_AUTOREFERER => true,
            // set referrer on redirect
            CURLOPT_CONNECTTIMEOUT => 120,
            // time-out on connect
            CURLOPT_TIMEOUT => 120,
            // time-out on response
            CURLOPT_URL => $url,
        ]);

        try {
            $response    = curl_exec($ch);
            $httpCode    = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            $errorNumber = curl_errno($ch);
        } finally {
            curl_close($ch);
        }

        return ['body' => $response, 'httpCode' => $httpCode, 'errorNumber' => $errorNumber];
    }
}
