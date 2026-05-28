<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Service\Discord\DiscordApiServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\UnauthorizedException;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class GithubWebhookController extends Controller
{
    /**
     * @throws BadRequestException|UnauthorizedException
     *
     * @see https://dev.to/ryan1/how-to-validate-github-webhooks-with-laravel-and-php-2he1
     */
    protected function validateGithubWebhook(Request $request): void
    {
        if (($signature = $request->headers->get('X-Hub-Signature')) == null) {
            throw new BadRequestException('Header not set');
        }

        $signatureParts = explode('=', $signature);

        if (count($signatureParts) != 2) {
            throw new BadRequestException('signature has invalid format');
        }

        $knownSignature = hash_hmac('sha1', $request->getContent(), (string)config('keystoneguru.webhook.github.secret'));

        if (!hash_equals($knownSignature, $signatureParts[1])) {
            throw new UnauthorizedException('Could not verify request signature ' . $signatureParts[1]);
        }
    }

    public function github(Request $request, DiscordApiServiceInterface $discordApiService): Response
    {
        $this->validateGithubWebhook($request);

        $commits = $request->get('commits');
        $ref     = $request->get('ref');
        $branch  = str_replace('refs/heads/', '', (string)$ref);

        // We don't need duplicate messages in Discord since mapping is automatically managed
        if ($branch !== 'mapping') {
            $embeds = [];

            // https://discord.com/developers/docs/resources/channel#embed-object-embed-limits
            $totalCharacterCount      = 0;
            $totalEmbedCharacterLimit = 5950;

            // https://discord.com/developers/docs/resources/message#embed-object
            $maxEmbedsPerMessage      = 10;
            $maxDescriptionCharacters = 4096;

            foreach ($commits as $commit) {
                // Skip system commits (such as merge branch X into Y)
                if (($commit['committer']['name'] === 'Github' && $commit['committer']['email'] === 'noreply@github.com') ||
                    // Skip commits that have originally be done on another branch
                    !$commit['distinct'] ||
                    // Skip merge commits
                    str_starts_with((string)$commit['message'], 'Merge remote-tracking branch')
                ) {
                    continue;
                }

                $remainingBudget = $totalEmbedCharacterLimit - $totalCharacterCount;

                if ($remainingBudget <= 0 || count($embeds) >= $maxEmbedsPerMessage) {
                    break;
                }

                $lines = explode('\\n', (string)$commit['message']);

                $commitDescription = substr(trim(view('app.commit.commit', [
                    'commit' => $commit,
                    'lines'  => $lines,
                ])->render()), 0, min($maxDescriptionCharacters, $remainingBudget));

                $totalCharacterCount += strlen($commitDescription);

                $embeds[] = [
                    'title' => sprintf(
                        '%s: %s',
                        $branch,
                        substr(array_shift($lines), 0, 256),
                    ),
                    'description' => $commitDescription,
                    'url'         => $commit['url'],
                ];

                $remainingBudget = $totalEmbedCharacterLimit - $totalCharacterCount;

                if (!empty($commit['added']) && $remainingBudget > 0 && count($embeds) < $maxEmbedsPerMessage) {
                    $addedDescription = substr(trim(view('app.commit.added', [
                        'added' => $commit['added'],
                    ])->render()), 0, min($maxDescriptionCharacters, $remainingBudget));
                    $totalCharacterCount += strlen($addedDescription);

                    $embeds[] = [
                        'color' => 2328118,
                        // #238636
                        'description' => $addedDescription,
                    ];

                    $remainingBudget = $totalEmbedCharacterLimit - $totalCharacterCount;
                }

                if (!empty($commit['modified']) && $remainingBudget > 0 && count($embeds) < $maxEmbedsPerMessage) {
                    $modifiedDescription = substr(trim(view('app.commit.modified', [
                        'modified' => $commit['modified'],
                    ])->render()), 0, min($maxDescriptionCharacters, $remainingBudget));
                    $totalCharacterCount += strlen($modifiedDescription);

                    $embeds[] = [
                        'color' => 25284,
                        // #0062C4
                        'description' => $modifiedDescription,
                    ];

                    $remainingBudget = $totalEmbedCharacterLimit - $totalCharacterCount;
                }

                if (!empty($commit['removed']) && $remainingBudget > 0 && count($embeds) < $maxEmbedsPerMessage) {
                    $removedDescription = substr(trim(view('app.commit.removed', [
                        'removed' => $commit['removed'],
                    ])->render()), 0, min($maxDescriptionCharacters, $remainingBudget));
                    $totalCharacterCount += strlen($removedDescription);

                    $embeds[] = [
                        'color' => 14300723,
                        // #DA3633
                        'description' => $removedDescription,
                    ];
                }

                $lastKey                       = array_key_last($embeds);
                $embeds[$lastKey]['timestamp'] = $commit['timestamp'];
            }

            // Only send a message if we found a commit that was worthy of mentioning
            if (!empty($embeds)) {
                // Add footer to the last embed
                $lastKey                    = array_key_last($embeds);
                $embeds[$lastKey]['footer'] = [
                    'icon_url' => ksgAssetImage('external/discord/footer_image.png'),
                    'text'     => 'Keystone.guru Discord Bot',
                ];

                $discordApiService->sendEmbeds(config('keystoneguru.webhook.github.url'), $embeds);
            }
        }

        return response()->noContent();
    }
}
