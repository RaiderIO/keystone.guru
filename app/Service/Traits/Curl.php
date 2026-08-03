<?php

namespace App\Service\Traits;

trait Curl
{
    /**
     * @param array<string, mixed> $options
     */
    public function curlGet(string $url, array $options = []): string
    {
        return $this->curlExec($url, $options)['body'];
    }

    /**
     * Downloads $url to $filePath, refusing to write the response body unless the request actually
     * succeeded (transport-level success and a 2xx status). Without this, an S3/HTTP error body (e.g.
     * an XML error document from an expired presigned URL) gets written to disk and silently treated
     * as a valid combat log segment by the caller.
     *
     * @param  string $url
     * @param  string $filePath
     * @return bool
     */
    public function curlSaveToFile(string $url, string $filePath): bool
    {
        $result = $this->curlExec($url);

        if ($result['errno'] !== 0 || $result['httpCode'] < 200 || $result['httpCode'] >= 300) {
            return false;
        }

        return file_put_contents($filePath, $result['body']) !== false;
    }

    /**
     * @param  array<string, mixed>                           $options
     * @return array{body: string, httpCode: int, errno: int}
     */
    private function curlExec(string $url, array $options = []): array
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
            $response = curl_exec($ch);
            $errno    = curl_errno($ch);
            $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        } finally {
            curl_close($ch);
        }

        return [
            'body'     => $response !== false ? $response : '',
            'httpCode' => $httpCode,
            'errno'    => $errno,
        ];
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
}
