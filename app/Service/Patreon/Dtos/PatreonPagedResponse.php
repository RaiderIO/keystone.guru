<?php

namespace App\Service\Patreon\Dtos;

/**
 * The result of walking every page of a paginated Patreon API response, together with what it took to
 * get there.
 *
 * The page counts exist because a partially fetched response used to be indistinguishable from a
 * complete one (#4373): pagination that gave up halfway returned the pages it did get with no error
 * attached, and the hourly sync then treated a truncated member list as the full campaign. `$truncated`
 * is that missing signal, and `$response` carries an `errors` key whenever it is true so the existing
 * error checks in PatreonService catch it too.
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
