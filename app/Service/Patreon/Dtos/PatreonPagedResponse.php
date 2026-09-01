<?php

namespace App\Service\Patreon\Dtos;

/**
 * The result of walking every page of a paginated Patreon API response, together with what it took to
 * get there.
 */
class PatreonPagedResponse
{
    /**
     * @param array<string, mixed> $response  The merged JSON:API response - `data` and `included` hold every page's rows
     * @param int                  $pageCount How many pages were requested, including the one that failed
     * @param int                  $rowCount  How many `data` rows were collected in total
     * @param bool                 $truncated Whether pagination stopped before the last page
     */
    public function __construct(
        public readonly array $response,
        public readonly int   $pageCount,
        public readonly int   $rowCount,
        public readonly bool  $truncated,
    ) {
    }

    public function hasErrors(): bool
    {
        return isset($this->response['errors']);
    }
}
