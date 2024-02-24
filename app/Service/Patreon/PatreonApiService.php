<?php

namespace App\Service\Patreon;

use App\Service\Patreon\Logging\PatreonApiServiceLoggingInterface;
use Patreon\API;
use Patreon\OAuth;

class PatreonApiService implements PatreonApiServiceInterface
{
    public function __construct(private PatreonApiServiceLoggingInterface $log)
    {
    }


    /**
     * @param string $accessToken
     * @return array{errors: array|null, included: array|null}|null
     */
    public function getIdentity(string $accessToken): ?array
    {
        $this->log->getIdentityStart();
        $identityResponse = null;

        try {
            $identityResponse = $this->getApiClient($accessToken)->get_data(
                sprintf('identity?include=memberships,memberships.currently_entitled_tiers' .
                    '&%s=email,first_name,full_name,image_url,last_name,thumb_url,url,vanity,is_email_verified' .
                    '&%s=email,currently_entitled_amount_cents,lifetime_support_cents,last_charge_status,patron_status,last_charge_date,pledge_relationship_start',
                    urlencode('fields[user]'),
                    urlencode('fields[member]')
                )
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
     * @param string $accessToken
     * @return array|null
     * @example {"data":{"attributes":{},"id":"2102279","relationships":{"tiers":{"data":[{"id":"2971575","type":"tier"},{"id":"9068557","type":"tier"}]}},"type":"campaign"},"included":[{"attributes":{"title":"Supporter of Keystone.guru"},"id":"2971575","relationships":{"benefits":{"data":[{"id":"367345","type":"benefit"},{"id":"3348264","type":"benefit"},{"id":"367914","type":"benefit"}]}},"type":"tier"},{"attributes":{"title":"Advanced Simulation Features"},"id":"9068557","relationships":{"benefits":{"data":[{"id":"367345","type":"benefit"},{"id":"3348264","type":"benefit"},{"id":"367914","type":"benefit"},{"id":"11542092","type":"benefit"}]}},"type":"tier"},{"attributes":{"title":"ad-free"},"id":"367345","type":"benefit"},{"attributes":{"title":"animated-polylines"},"id":"3348264","type":"benefit"},{"attributes":{"title":"unlisted-routes"},"id":"367914","type":"benefit"},{"attributes":{"title":"advanced-simulation"},"id":"11542092","type":"benefit"}],"links":{"self":"https://www.patreon.com/api/oauth2/v2/campaigns/2102279"}}
     */
    public function getCampaignTiersAndBenefits(string $accessToken): ?array
    {
        $result = null;

        try {
            $this->log->getCampaignTiersAndBenefitsStart();

            $result = $this->getAllPages(
                $this->getApiClient($accessToken),
                sprintf('campaigns/%d?include=tiers,tiers.benefits&%s=title',
                    config('keystoneguru.patreon.campaign_id'),
                    urlencode('fields[benefit]')
                )
            );
        } finally {
            $this->log->getCampaignTiersAndBenefitsEnd($result);
        }

        return $result;
    }


    /**
     * @param string $accessToken
     * @return array|null Null whenever we couldn't authenticate with the refreshToken provided
     */
    public function getCampaignMembers(string $accessToken): ?array
    {
        $result = null;

        try {
            $this->log->getCampaignMembersStart();
            $result = $this->getAllPages(
                $this->getApiClient($accessToken),
                sprintf('campaigns/%d/members?include=currently_entitled_tiers&%s=email',
                    config('keystoneguru.patreon.campaign_id'),
                    urlencode('fields[member]')
                )
            );
        } finally {
            $this->log->getCampaignMembersEnd($result);
        }

        return $result;
    }


    /**
     * @param string $refreshToken
     * @return array{errors: ?array, access_token: string, refresh_token: string, expires_in: int, scope: string, token_type: string, version: string}
     */
    public function getAccessTokenFromRefreshToken(string $refreshToken): array
    {
        return $this->getOAuthClient()->refresh_token($refreshToken, '');
    }

    /**
     * @param string $code
     * @param string $redirectUrl
     * @return array{errors: ?array, access_token: string, refresh_token: string, expires_in: int, scope: string, token_type: string, version: string}
     */
    public function getAccessTokenFromCode(string $code, string $redirectUrl): array
    {
        return $this->getOAuthClient()->get_tokens($code, $redirectUrl);
    }

    /**
     * @return array
     */
    private function getAllPages(API $apiClient, string $suffix): array
    {
        $resultData = [];

        $next  = $suffix;
        $count = 0;
        do {
            $this->log->getAllPagesPageNr($count);
            $requestResult = $originalResponse = $apiClient->get_data($next);
            // Insane workaround if you get a 4xx error it won't do json_decode
            if (is_string($requestResult)) {
                $requestResult = json_decode($requestResult, true);
            }

            if ($requestResult === null) {
                $next = null;
                $this->log->getAllPagesUnknownResponse($originalResponse);
            } else if (!isset($requestResult['errors'])) {
                // No errors - continue fetching pages
                $resultData = array_merge($resultData, $requestResult['data']);

                $next = isset($requestResult['links']['next']) ?
                    // Build the URL ourselves because obviously somehow using the 'links'.'next' does not work since it contains the full API url
                    sprintf('%s&%s%s', $suffix, 'page%5Bcursor%5D=', urlencode($requestResult['meta']['pagination']['cursors']['next'])) :
                    null;
            } else {
                // Found an error - just stop it now
                $next = null;
                $this->log->getAllPagesError($requestResult['errors']);
            }
            $count++;
        } while ($next !== null);

        // Assign the data back to the last request and pretend that THAT's all the data there is
        if (!empty($resultData)) {
            $requestResult['data'] = $resultData;
        }

        return $requestResult ?? [];
    }

    /**
     * @return OAuth
     */
    private function getOAuthClient(): OAuth
    {
        $client_id     = config('keystoneguru.patreon.oauth.client_id');
        $client_secret = config('keystoneguru.patreon.oauth.secret');

        return new OAuth($client_id, $client_secret);
    }

    /**
     * @return API
     */
    private function getApiClient(string $accessToken): API
    {
        return new API($accessToken);
    }
}
