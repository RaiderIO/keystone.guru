<?php

namespace App\Console\Commands\Patreon;

use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * A client for the read-only `/api/v1/patreon` diagnostics endpoints on a remote deployment.
 *
 * The campaign only exists on the deployment holding the admin account's tokens, so there is nothing to
 * run against local data. HTTP Basic credentials are read from stdin: the container cannot see
 * `~/.config`, so a host path passed as a file would not resolve.
 */
class DiagnosePatreonSync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'patreon:diagnose
        {--user= : Diagnose one account by id or username on the remote deployment}
        {--email= : Diagnose one account by the email it registered with}
        {--runs : Show the recorded sync run history (the cheapest check - no Patreon API calls)}
        {--campaign : Show every campaign tier and the benefits it resolves to}
        {--dry-run : Run the whole sync in plan-only mode and report what it would change}
        {--limit=30 : How many sync runs to show with --runs}
        {--host=production : Which deployment to ask - a key of config keystoneguru.remote_hosts}
        {--credentials-file= : File holding one "user:password" line for HTTP Basic auth; read from stdin when omitted}
        {--json : Print the raw API responses instead of a readable summary}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Diagnoses why the Patreon sync did or did not apply a member\'s benefits, against a remote deployment';

    public function handle(): int
    {
        $host    = (string)$this->option('host');
        $baseUrl = config(sprintf('keystoneguru.remote_hosts.%s.base_url', $host));
        if (!is_string($baseUrl) || $baseUrl === '') {
            $this->error(sprintf('Unknown host "%s" - add it to config keystoneguru.remote_hosts', $host));

            return self::FAILURE;
        }
        $baseUrl = rtrim($baseUrl, '/');

        $user     = $this->option('user');
        $email    = $this->option('email');
        $showRuns = (bool)$this->option('runs');
        $campaign = (bool)$this->option('campaign');
        $dryRun   = (bool)$this->option('dry-run');

        if ($user === null && $email === null && !$showRuns && !$campaign && !$dryRun) {
            $this->error('Nothing to do - pass --user, --email, --runs, --campaign or --dry-run.');
            $this->line('Start with --runs: it costs nothing and a members_fetched that drops between runs is already the answer.');

            return self::FAILURE;
        }

        $credentials = $this->readCredentials();
        if ($credentials === null) {
            return self::FAILURE;
        }
        [$authUser, $authPassword] = $credentials;

        // Retry only what a retry can fix - a connection failure or a 5xx - never a 4xx. The campaign
        // endpoints walk every page of the Patreon API, hence the generous timeout
        $http = Http::withBasicAuth($authUser, $authPassword)
            ->acceptJson()
            ->timeout(180)
            ->retry(
                2,
                500,
                static fn(Throwable $exception): bool => $exception instanceof ConnectionException ||
                    ($exception instanceof RequestException && $exception->response->serverError()),
                throw: false,
            );

        // Each section runs even when an earlier one failed: an unreachable campaign endpoint should not
        // withhold the run history
        $failed = false;

        if ($showRuns && !$this->showSyncRuns($http, $baseUrl)) {
            $failed = true;
        }

        if ($campaign && !$this->showCampaign($http, $baseUrl)) {
            $failed = true;
        }

        if ($dryRun && !$this->showDryRun($http, $baseUrl)) {
            $failed = true;
        }

        if (($user !== null || $email !== null) &&
            !$this->showUser($http, $baseUrl, is_string($user) ? $user : null, is_string($email) ? $email : null)) {
            $failed = true;
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }

    private function showSyncRuns(PendingRequest $http, string $baseUrl): bool
    {
        $body = $this->fetch($http, sprintf('%s/api/v1/patreon/sync-runs', $baseUrl), ['limit' => (int)$this->option('limit')]);
        if ($body === null) {
            return false;
        }

        /** @var array<int, array<string, mixed>> $runs */
        $runs = $body['data'] ?? [];

        if ($this->outputJson($body)) {
            return true;
        }

        if ($runs === []) {
            $this->warn('No sync runs recorded yet - the deployment has not run patreon:refreshmembers since this was deployed.');

            return true;
        }

        $this->info('Recorded sync runs (newest first):');
        $this->table(
            ['Started', 'Pages', 'Members', 'Trunc', 'Applied', 'Not linked', 'Unk. tiers', 'Unk. benefits', 'Failed', 'OK'],
            array_map(static fn(array $run): array => [
                (string)($run['started_at'] ?? ''),
                (string)($run['pages_fetched'] ?? ''),
                (string)($run['members_fetched'] ?? ''),
                ($run['truncated'] ?? false) ? 'YES' : '',
                (string)($run['members_applied'] ?? ''),
                (string)($run['members_not_linked'] ?? ''),
                (string)($run['members_unknown_tiers'] ?? ''),
                (string)($run['members_unknown_benefits'] ?? ''),
                (string)($run['members_failed'] ?? ''),
                ($run['successful'] ?? false) ? 'yes' : 'NO',
            ], $runs),
        );

        // A run that saw markedly fewer members than its predecessor never reached the rest of the campaign
        $memberCounts = array_map(static fn(array $run): int => (int)($run['members_fetched'] ?? 0), $runs);
        $healthy      = array_filter($memberCounts);
        if ($healthy !== []) {
            $highest = max($healthy);
            foreach ($runs as $run) {
                $fetched = (int)($run['members_fetched'] ?? 0);
                if ($fetched > 0 && $fetched < $highest * 0.9) {
                    $this->warn(sprintf(
                        'Run at %s fetched %d members against a high of %d - that run did not see the whole campaign.',
                        (string)($run['started_at'] ?? '?'),
                        $fetched,
                        $highest,
                    ));
                }
            }
        }

        return true;
    }

    private function showCampaign(PendingRequest $http, string $baseUrl): bool
    {
        $body = $this->fetch($http, sprintf('%s/api/v1/patreon/campaign', $baseUrl));
        if ($body === null) {
            return false;
        }

        if ($this->outputJson($body)) {
            return true;
        }

        /** @var array<string, mixed> $data */
        $data = $body['data'] ?? [];
        /** @var array<int, array<string, mixed>> $tiers */
        $tiers = $data['tiers'] ?? [];

        $this->info('Campaign tiers:');
        $this->table(
            ['Tier id', 'Title', 'Benefits', 'Unknown benefits'],
            array_map(static fn(array $tier): array => [
                (string)($tier['tier_id'] ?? ''),
                (string)($tier['title'] ?? ''),
                implode(', ', (array)($tier['benefit_titles'] ?? [])) ?: '(none)',
                implode(', ', (array)($tier['unknown_benefit_titles'] ?? [])),
            ], $tiers),
        );

        /** @var array<int, string> $unknown */
        $unknown = $data['unknown_benefit_titles'] ?? [];
        if ($unknown !== []) {
            $this->error(sprintf(
                'These benefit titles are missing from PatreonBenefit::ALL, and every member entitled to one is skipped entirely: %s',
                implode(', ', $unknown),
            ));
        }

        /** @var array<int, string> $grantsNothing */
        $grantsNothing = $data['tier_ids_granting_nothing'] ?? [];
        if ($grantsNothing !== []) {
            $this->warn(sprintf(
                'Tiers resolving to no benefits at all: %s. A paying member of such a tier computes to an empty benefit set, which reads as "unsubscribed".',
                implode(', ', $grantsNothing),
            ));
        }

        return true;
    }

    private function showDryRun(PendingRequest $http, string $baseUrl): bool
    {
        $body = $this->fetch($http, sprintf('%s/api/v1/patreon/sync-dry-run', $baseUrl));
        if ($body === null) {
            return false;
        }

        if ($this->outputJson($body)) {
            return true;
        }

        /** @var array<string, mixed> $data */
        $data = $body['data'] ?? [];

        $this->info(sprintf(
            'Dry run fetched %s members over %s pages.',
            (string)($data['members_fetched'] ?? '?'),
            (string)($data['pages_fetched'] ?? '?'),
        ));

        /** @var array<string, int> $resultCounts */
        $resultCounts = $data['result_counts'] ?? [];
        foreach ($resultCounts as $result => $count) {
            $this->line(sprintf('  %-20s %d', $result, $count));
        }

        foreach ([
            'members_blocked'          => 'Members skipped over unknown tiers or benefits',
            'members_losing_benefits'  => 'Members a sync would revoke benefits from',
            'members_gaining_benefits' => 'Members a sync would grant benefits to',
        ] as $key => $label) {
            /** @var array<int, array<string, mixed>> $members */
            $members = $data[$key] ?? [];
            if ($members === []) {
                continue;
            }

            $this->info(sprintf('%s (%d):', $label, count($members)));
            $this->table(
                ['Member id', 'Email', 'Result', 'User', 'Unresolved tiers', 'Add', 'Revoke'],
                array_map(static fn(array $member): array => [
                    (string)($member['member_id'] ?? ''),
                    (string)($member['email'] ?? ''),
                    (string)($member['result'] ?? ''),
                    (string)($member['user_id'] ?? ''),
                    implode(', ', (array)($member['unresolved_tier_ids'] ?? [])),
                    implode(', ', (array)($member['benefits_to_add'] ?? [])),
                    implode(', ', (array)($member['benefits_to_revoke'] ?? [])),
                ], $members),
            );
        }

        return true;
    }

    private function showUser(PendingRequest $http, string $baseUrl, ?string $user, ?string $email): bool
    {
        $query = [];
        if ($user !== null) {
            // An all-digits value is an account id, anything else a username
            $query[ctype_digit($user) ? 'user_id' : 'username'] = $user;
        }
        if ($email !== null) {
            $query['email'] = $email;
        }

        $body = $this->fetch($http, sprintf('%s/api/v1/patreon/user', $baseUrl), $query);
        if ($body === null) {
            return false;
        }

        if ($this->outputJson($body)) {
            return true;
        }

        /** @var array<string, mixed> $data */
        $data = $body['data'] ?? [];
        /** @var array<string, mixed>|null $member */
        $member = $data['member'] ?? null;

        $this->info(sprintf('Account %s (%s)', (string)($data['user_id'] ?? '?'), (string)($data['username'] ?? '?')));
        $this->table(['', ''], [
            ['Patreon link', (string)($data['patreon_user_link_id'] ?? '(none)')],
            ['Link email', (string)($data['link_email'] ?? '(none)')],
            ['Account email', (string)($data['account_email'] ?? '')],
            ['Manually granted', ($data['manually_granted'] ?? false) ? 'yes' : 'no'],
            ['Stored benefits', implode(', ', (array)($data['stored_benefits'] ?? [])) ?: '(none)'],
            ['Last seen by a sync', (string)($data['last_seen_at'] ?? '(never)')],
            ['Last sync result', (string)($data['last_sync_result'] ?? '(none)')],
            ['Duplicate link rows', implode(', ', (array)($data['duplicate_link_ids'] ?? [])) ?: '(none)'],
            ['Campaign member', $member === null ? '(the campaign lists no member for the link email)' : (string)($member['member_id'] ?? '')],
            ['Patron status', $member === null ? '' : (string)($member['patron_status'] ?? '')],
            ['Last charge status', $member === null ? '' : (string)($member['last_charge_status'] ?? '')],
            ['Entitled tiers', $member === null ? '' : implode(', ', (array)($member['entitled_tier_ids'] ?? []))],
            ['Would add', $member === null ? '' : implode(', ', (array)($member['benefits_to_add'] ?? []))],
            ['Would revoke', $member === null ? '' : implode(', ', (array)($member['benefits_to_revoke'] ?? []))],
        ]);

        $this->printVerdict($data, $member);

        return true;
    }

    /**
     * Says which of the failure modes this account's state matches.
     *
     * @param array<string, mixed>      $data
     * @param array<string, mixed>|null $member
     */
    private function printVerdict(array $data, ?array $member): void
    {
        if (($data['patreon_user_link_id'] ?? null) === null) {
            $this->warn('No Patreon link at all - this account never completed the Patreon linking flow, so no sync could ever have applied benefits to it.');

            return;
        }

        if (($data['email_drift_candidate'] ?? null) !== null) {
            $this->error(sprintf(
                'Email drift: the campaign lists a member at %s (this account\'s own email) while the link stores %s. ' .
                'Members are matched on the link email only, so this patron has been silently unmatched since they changed their Patreon email.',
                (string)$data['email_drift_candidate'],
                (string)($data['link_email'] ?? '?'),
            ));

            return;
        }

        if ($data['missed_by_latest_run'] ?? false) {
            $this->error(
                'The most recent recorded sync run started after this link was last seen, so that run never reached this patron - ' .
                'the signature of a member fetch that stopped before their page.',
            );

            return;
        }

        if ($member === null) {
            $this->warn('The campaign lists no member for the link email. Either the pledge ended, or the patron\'s Patreon email no longer matches the stored one.');

            return;
        }

        if (($member['unresolved_tier_ids'] ?? []) !== []) {
            $this->error(sprintf(
                'Entitled to tier(s) %s which the campaign response does not describe - the member is skipped rather than having their benefits revoked.',
                implode(', ', (array)$member['unresolved_tier_ids']),
            ));

            return;
        }

        if (($member['unknown_benefits'] ?? []) !== []) {
            $this->error(sprintf(
                'Entitled to benefit(s) %s missing from PatreonBenefit::ALL - the member is skipped entirely until those are added.',
                implode(', ', (array)$member['unknown_benefits']),
            ));

            return;
        }

        if (($member['entitled_tier_ids'] ?? []) === []) {
            $this->warn(sprintf(
                'The campaign lists this member with no entitled tiers (patron status: %s, last charge: %s). An unsettled charge looks exactly like this and resolves itself.',
                (string)($member['patron_status'] ?? '?'),
                (string)($member['last_charge_status'] ?? '?'),
            ));

            return;
        }

        if (($member['benefits_to_add'] ?? []) === [] && ($member['benefits_to_revoke'] ?? []) === []) {
            $this->info('Benefits are in sync - the account already holds exactly what the campaign says it should. If the patron still sees no benefits, the problem is downstream of the sync.');

            return;
        }

        $this->warn('The next sync would change this account\'s benefits - see "Would add" / "Would revoke" above.');
    }

    /**
     * @param  array<string, mixed>      $query
     * @return array<string, mixed>|null Null (after printing why) when the request did not succeed
     */
    private function fetch(PendingRequest $http, string $url, array $query = []): ?array
    {
        try {
            $response = $http->get($url, $query);
        } catch (ConnectionException $exception) {
            $this->error(sprintf('%s: %s', $url, $exception->getMessage()));

            return null;
        }

        if (!$response->successful()) {
            $this->error(sprintf('%s failed: HTTP %d %s', $url, $response->status(), $this->describeErrorResponse($response)));

            if ($response->status() === 401 || $response->status() === 403) {
                $this->line('The account behind the credentials needs the admin or ai_agent role on that deployment.');
            }

            return null;
        }

        /** @var array<string, mixed> $body */
        $body = $response->json() ?? [];

        return $body;
    }

    /**
     * @param array<string, mixed> $body
     */
    private function outputJson(array $body): bool
    {
        if (!$this->option('json')) {
            return false;
        }

        $this->line((string)json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return true;
    }

    /**
     * @return array{0: string, 1: string}|null [user, password], or null (after printing why) when none could be read
     */
    private function readCredentials(): ?array
    {
        $credentialsFile = $this->option('credentials-file');

        if ($credentialsFile !== null) {
            if (!is_readable((string)$credentialsFile)) {
                $this->error(sprintf('Credentials file %s does not exist or is not readable', $credentialsFile));

                return null;
            }

            $line = (string)file_get_contents((string)$credentialsFile);
        } else {
            if (function_exists('posix_isatty') && posix_isatty(STDIN)) {
                $this->error('No --credentials-file given and nothing piped on stdin. Pipe a "user:password" line, e.g.: ' .
                    'docker compose exec -T app php artisan patreon:diagnose --runs < ~/.config/keystone-guru/combatlog-production-basic-auth');

                return null;
            }

            $line = (string)stream_get_contents(STDIN);
        }

        $line = trim($line);
        if ($line === '' || !str_contains($line, ':')) {
            $this->error('Credentials must be a single "user:password" line');

            return null;
        }

        [$user, $password] = explode(':', $line, 2);

        return [$user, $password];
    }

    private function describeErrorResponse(Response $response): string
    {
        $error = $response->json('error') ?? $response->json('message');

        return is_string($error) ? sprintf('(%s)', $error) : '';
    }
}
