<?php

namespace App\Service\RaiderIO\Exceptions;

use Illuminate\Contracts\Support\Arrayable;

class InvalidApiResponseException extends \Exception implements Arrayable
{
    public function __construct(
        string                  $message = '',
        private readonly string $url = '',
        private readonly string $response = '',
    ) {
        parent::__construct($message);
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getResponse(): string
    {
        return $this->response;
    }

    public function toArray(): array
    {
        return array_filter([
            'message'  => $this->getMessage(),
            'url'      => config('app.debug') ? $this->getUrl() : null,
            'response' => config('app.debug') ? $this->getResponse() : null,
        ]);
    }
}
