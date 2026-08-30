<?php

namespace App\Service\Patreon;

use App\Service\Patreon\Dtos\PatreonPagedResponse;
use App\Service\Patreon\Logging\PatreonApiServiceLoggingInterface;
use Patreon\API;
use Patreon\OAuth;

class PatreonApiService implements PatreonApiServiceInterface
{
    public function __construct(private readonly PatreonApiServiceLoggingInterface $log)
    {
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getIdentity(string $accessToken): ?array
    {
        $this->log->getIdentityStart();
        $identityResponse = null;

        try {
            $identityResponse = $this->getApiClient($accessToken)->get_data(
                sprintf(
                    'identity?include=memberships,memberships.currently_entitled_tiers' .
                    '&%s=email,first_name,full_name,image_url,last_name,thumb_url,url,vanity,is_email_verified' .
                    '&%s=email,currently_entitled_amount_cents,lifetime_support_cents,last_charge_status,patron_status,last_charge_date,pledge_relationship_start',
                    urlencode('fields[user]'),
                    urlencode('fields[member]'),
                ),
            );

            if (!isset($identityResponse['errors'])) {
                if (!isset($identityResponse['included'])) {
                    $this->log->getIdentityIncludedNotFound();
                } else {
                    // Bit ugly but otherwise I'd need the broad 'campaigns.members[email]' permission which I don't need/want
                    foreach ($identityResponse['included'] as &$included) {
                        if ($included['type'] === 'member') {
                            $included['attributes']['email'] = $identityResponse['data']['attributes']['email'];
                            $this->log->getIdentityUpdatedEmailAddress($included['attributes']['email']);
                            break;
                        }
                    }
                }
            }
        } finally {
            $this->log->getIdentityEnd($identityResponse);
        }

        return $identityResponse;
    }

    /**
     * @example {"data":{"attributes":{},"id":"2102279","relationships":{"tiers":{"data":[{"id":"2971575","type":"tier"},{"id":"9068557","type":"tier"}]}},"type":"campaign"},"included":[{"attributes":{"title":"Supporter of Keystone.guru"},"id":"2971575","relationships":{"benefits":{"data":[{"id":"367345","type":"benefit"},{"id":"3348264","type":"benefit"},{"id":"367914","type":"benefit"}]}},"type":"tier"},{"attributes":{"title":"Advanced Simulation Features"},"id":"9068557","relationships":{"benefits":{"data":[{"id":"367345","type":"benefit"},{"id":"3348264","type":"benefit"},{"id":"367914","type":"benefit"},{"id":"11542092","type":"benefit"}]}},"type":"tier"},{"attributes":{"title":"ad-free"},"id":"367345","type":"benefit"},{"attributes":{"title":"animated-polylines"},"id":"3348264","type":"benefit"},{"attributes":{"title":"unlisted-routes"},"id":"367914","type":"benefit"},{"attributes":{"title":"advanced-simulation"},"id":"11542092","type":"benefit"}],"links":{"self":"https://www.patreon.com/api/oauth2/v2/campaigns/2102279"}}
     */
    public function getCampaignTiersAndBenefits(string $accessToken): ?PatreonPagedResponse
    {
        $result = null;

        try {
            $this->log->getCampaignTiersAndBenefitsStart();

            $result = $this->getAllPages(
                $this->getApiClient($accessToken),
                sprintf(
                    'campaigns/%d?include=tiers,tiers.benefits&%s=title',
                    config('keystoneguru.patreon.campaign_id'),
                    urlencode('fields[benefit]'),
                ),
            );
        } finally {
            $this->log->getCampaignTiersAndBenefitsEnd($result);
        }

        return $result;
    }

    /**
     * @return PatreonPagedResponse|null Null whenever we couldn't authenticate with the accessToken provided
     */
    public function getCampaignMembers(string $accessToken): ?PatreonPagedResponse
    {
        $result = null;

        try {
            $this->log->getCampaignMembersStart();
            $result = $this->getAllPages(
                $this->getApiClient($accessToken),
                sprintf(
                    'campaigns/%d/members?include=currently_entitled_tiers&%s=email',
                    config('keystoneguru.patreon.campaign_id'),
                    urlencode('fields[member]'),
                ),
            );
        } finally {
            $this->log->getCampaignMembersEnd($result);
        }

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    public function getAccessTokenFromRefreshToken(string $refreshToken): array
    {
        return $this->getOAuthClient()->refresh_token($refreshToken, '');
    }

    /**
     * @return array<string, mixed>
     */
    public function getAccessTokenFromCode(string $code, string $redirectUrl): array
    {
        return $this->getOAuthClient()->get_tokens($code, $redirectUrl);
    }

    /**
     * Walks every page of a paginated endpoint and merges the pages into one response.
     *
     * A page that fails - an undecodable body (Patreon serving an HTML 502 rather than JSON) or a page
     * carrying `errors` - stops the walk, and the result is marked truncated *and* given an `errors` key.
     * That last part is the #4373 fix: this used to return the pages it had already collected with no
     * error attached whenever the failure happened after page 1, so `loadCampaignMembers()` accepted a
     * partial member list as the complete campaign and the hourly sync silently skipped everyone on the
     * pages that never arrived. A partial result must never be indistinguishable from a complete one.
     */
    private function getAllPages(API $apiClient, string $suffix): PatreonPagedResponse
    {
        $resultData     = [];
        $resultIncluded = [];
        $truncated      = false;
        /** @var array<string, mixed>|null $requestResult */
        $requestResult = null;
        /** @var array<int, mixed> $errors */
        $errors = [];

        $next  = $suffix;
        $count = 0;
        do {
            $this->log->getAllPagesPageNr($count);
            $pageResult       = $apiClient->get_data($next);
            $originalResponse = $pageResult;
            // Insane workaround if you get a 4xx error it won't do json_decode
            if (is_string($pageResult)) {
                $pageResult = json_decode($pageResult, true);
            }

            $count++;

            if (!is_array($pageResult)) {
                $next      = null;
                $truncated = true;
                $errors[]  = ['detail' => sprintf('Page %d of "%s" could not be decoded', $count, $suffix)];
                $this->log->getAllPagesUnknownResponse($originalResponse);
            } elseif (!isset($pageResult['errors'])) {
                $requestResult = $pageResult;

                // `data` is a list for collection endpoints and a single object for a resource endpoint
                // (campaigns/{id}) - array_merge handles both, string keys of the latter simply overwrite
                $resultData = array_merge($resultData, $pageResult['data'] ?? []);
                // `included` must be merged too: a campaign's tiers and benefits live there, so keeping
                // only the last page's would silently drop tiers and make entitled tier ids unresolvable
                $resultIncluded = array_merge($resultIncluded, $pageResult['included'] ?? []);

                $next = isset($pageResult['links']['next']) ?
                    // Build the URL ourselves because obviously somehow using the 'links'.'next' does not work since it contains the full API url
                    sprintf('%s&%s%s', $suffix, 'page%5Bcursor%5D=', urlencode((string)$pageResult['meta']['pagination']['cursors']['next'])) :
                    null;

                // A `next` link we cannot follow is truncation just the same
                if ($next !== null && !isset($pageResult['meta']['pagination']['cursors']['next'])) {
                    $next      = null;
                    $truncated = true;
                    $errors[]  = ['detail' => sprintf('Page %d of "%s" advertised a next page without a cursor', $count, $suffix)];
                }
            } else {
                // Found an error - just stop it now
                $next      = null;
                $truncated = true;
                /** @var array<int, mixed> $pageErrors */
                $pageErrors = is_array($pageResult['errors']) ? $pageResult['errors'] : [$pageResult['errors']];
                $errors     = array_merge($errors, $pageErrors);
                $this->log->getAllPagesError($pageErrors);
            }
        } while ($next !== null);

        // Assign the data back to the last successful request and pretend that THAT's all the data there is
        $response = $requestResult ?? [];
        if ($resultData !== []) {
            $response['data'] = $resultData;
        }
        if ($resultIncluded !== []) {
            $response['included'] = $resultIncluded;
        }
        if ($errors !== []) {
            $response['errors'] = $errors;
        }

        return new PatreonPagedResponse(
            response: $response,
            pageCount: $count,
            rowCount: count($resultData),
            truncated: $truncated,
        );
    }

    private function getOAuthClient(): OAuth
    {
        $client_id     = config('keystoneguru.patreon.oauth.client_id');
        $client_secret = config('keystoneguru.patreon.oauth.secret');

        return new OAuth($client_id, $client_secret);
    }

    private function getApiClient(string $accessToken): API
    {
        return new API($accessToken);
    }
}
