<?php

return [
    'host'             => env('OPENSEARCH_HOST', 'http://localhost:9200'), // explode(',', env('OPENSEARCH_HOST', 'http://localhost:9200')),
    'username'         => env('OPENSEARCH_USERNAME', 'admin'),
    'password'         => env('OPENSEARCH_PASSWORD', 'admin'),
    'ssl_verification' => env('OPENSEARCH_SSL_VERIFICATION', false),
];
